<?php

namespace App\Http\Controllers;

use App\Models\{Group, MediaAsset, Photo};
use App\Services\Analytics\AnalyticsAuthorization;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index(Request $request, AnalyticsAuthorization $authorization)
    {
        [$filters, $groups] = $this->scope($request, $authorization);
        $groupIds = (clone $groups)->pluck('id');
        $photoQuery = $this->dated(Photo::whereIn('group_id', $groupIds), $filters);
        $assetQuery = $this->dated(MediaAsset::whereIn('group_id', $groupIds), $filters);
        $rows = (clone $groups)->withCount([
            'photos' => fn ($query) => $this->dated($query, $filters),
            'members',
        ])->latest()->get();

        return Inertia::render('Analytics', [
            'summary' => [
                'groups' => $rows->count(),
                'photos' => (clone $photoQuery)->count(),
                'participants' => DB::table('group_members')->whereIn('group_id', $groupIds)->where('membership_status', 'active')->count(),
                'storage_bytes' => (int) (clone $assetQuery)->whereNull('deleted_at')->sum('size_bytes'),
                'views' => (int) (clone $photoQuery)->sum('views_count'),
                'downloads' => (int) (clone $photoQuery)->sum('downloads_count'),
                'favourites' => DB::table('photo_likes')->whereIn('photo_id', (clone $photoQuery)->select('photos.id'))->count(),
                'processing_failures' => (clone $assetQuery)->where('state', 'failed')->count(),
            ],
            'groups' => $rows->map(fn ($group) => ['id' => $group->id, 'name' => $group->name, 'photos_count' => $group->photos_count, 'members_count' => $group->members_count, 'updated_at' => $group->updated_at]),
            'filters' => $filters,
            'availableGroups' => (clone $groups)->orderBy('name')->get(['id', 'name']),
            'exportUrl' => route('analytics.export'),
        ]);
    }

    public function export(Request $request, AnalyticsAuthorization $authorization, AuditLogger $audit)
    {
        [$filters, $groups] = $this->scope($request, $authorization);
        $rows = (clone $groups)->withCount(['photos' => fn ($query) => $this->dated($query, $filters), 'members'])->orderBy('name')->get();
        $audit->log('analytics.exported', 'analytics', null, [], [], ['permission' => $request->attributes->get('analytics_context')['permission'], 'group_ids' => $rows->pluck('id')->all(), 'filters' => $filters]);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Group', 'Photos', 'Participants', 'Updated']);
            foreach ($rows as $row) fputcsv($out, [$this->csv($row->name), $row->photos_count, $row->members_count, $row->updated_at?->toIso8601String()]);
            fclose($out);
        }, 'lenspic-analytics-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    private function scope(Request $request, AnalyticsAuthorization $authorization): array
    {
        $context = $request->attributes->get('analytics_context');
        $allowedIds = $authorization->groupQuery($context)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $filters = $request->validate(['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'], 'group_id' => ['nullable', 'integer', Rule::in($allowedIds)]]);
        $groups = $authorization->groupQuery($context);
        if ($filters['group_id'] ?? null) $groups->whereKey($filters['group_id']);
        return [$filters, $groups];
    }

    private function dated($query, array $filters)
    {
        if ($from = $filters['from'] ?? null) $query->where('created_at', '>=', $from.' 00:00:00');
        if ($to = $filters['to'] ?? null) $query->where('created_at', '<=', $to.' 23:59:59');
        return $query;
    }

    private function csv(mixed $value): string
    {
        $value = (string) $value;
        return preg_match('/^[=+\-@\t\r]/u', $value) ? "'".$value : $value;
    }
}
