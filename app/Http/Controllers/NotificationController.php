<?php

namespace App\Http\Controllers;

use App\Services\Notifications\{NotificationAccess, NotificationService};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationAccess $access)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['all','unread'])],
            'category' => ['nullable', Rule::in(NotificationService::CATEGORIES)],
            'page' => ['nullable','integer','min:1'],
        ]);
        $status = $filters['status'] ?? 'all';
        $category = $filters['category'] ?? null;
        $items = $access->query($request->user())
            ->when($status === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->when($category, fn ($q, $value) => $q->where('data->category', $value))
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(20)->withQueryString()
            ->through(fn ($notification) => $access->present($notification));
        return Inertia::render('Notifications', [
            'notifications' => $items,
            'filters' => ['status' => $status, 'category' => $category],
            'categories' => NotificationService::CATEGORIES,
        ]);
    }

    public function count(Request $request, NotificationAccess $access) { return response()->json(['count' => $access->query($request->user())->whereNull('read_at')->count()]); }
    public function read(Request $request, string $notification, NotificationAccess $access) { $access->find($request->user(), $notification)->markAsRead(); return back()->with('success', 'Notification marked as read.'); }
    public function unread(Request $request, string $notification, NotificationAccess $access) { $access->find($request->user(), $notification)->markAsUnread(); return back()->with('success', 'Notification marked as unread.'); }
    public function readAll(Request $request, NotificationAccess $access) { $access->query($request->user())->whereNull('read_at')->update(['read_at' => now()]); return back()->with('success', 'All notifications marked as read.'); }
    public function destroy(Request $request, string $notification, NotificationAccess $access) { $access->find($request->user(), $notification)->delete(); return back()->with('success', 'Notification dismissed.'); }
}
