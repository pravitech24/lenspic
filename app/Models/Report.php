<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Report extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'type', 'data', 'report_date'];

    protected $casts = [
        'data' => 'json',
        'report_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function generateUserActivityReport()
    {
        return [
            'total_users' => User::count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),
            'new_users_week' => User::whereBetween('created_at', [today()->subDays(7), today()])->count(),
            'active_users' => User::where('last_seen_at', '>=', now()->subDays(30))->count(),
        ];
    }

    public static function generateGroupActivityReport()
    {
        return [
            'total_groups' => Group::count(),
            'new_groups_today' => Group::whereDate('created_at', today())->count(),
            'active_groups' => Group::where('is_active', true)->count(),
            'archived_groups' => Group::where('is_active', false)->count(),
        ];
    }

    public static function generateStorageUsageReport()
    {
        $users = User::all();
        return [
            'total_users' => $users->count(),
            'total_storage_used' => $users->sum('storage_used'),
            'average_storage_per_user' => $users->avg('storage_used'),
            'max_storage_user' => $users->max('storage_used'),
        ];
    }

    public static function generateSubscriptionReport()
    {
        return [
            'total_active' => Subscription::where('status', 'active')->count(),
            'by_plan' => Subscription::where('status', 'active')->groupBy('plan')->selectRaw('plan, count(*) as count')->get(),
            'total_revenue' => Subscription::where('status', 'active')->sum('amount'),
        ];
    }
}
