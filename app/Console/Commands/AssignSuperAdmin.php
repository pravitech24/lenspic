<?php

namespace App\Console\Commands;

use App\Models\{AuditLog, User};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssignSuperAdmin extends Command
{
    protected $signature = 'lenspic:assign-super-admin {email : Exact email of an existing verified user} {--force : Skip the interactive confirmation}';
    protected $description = 'Securely assign the Super Admin platform role to an existing verified LensPic user';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $matches = User::whereRaw('LOWER(email) = ?', [$email])->get();
        if ($matches->count() !== 1) {
            $this->error($matches->isEmpty() ? 'No user matches that exact email.' : 'The email match is ambiguous; no role was changed.');
            return self::FAILURE;
        }
        $user = $matches->first();
        if (! $user->email_verified_at && ! $user->phone_verified_at) {
            $this->error('The existing user must verify an email address or phone number first.');
            return self::FAILURE;
        }
        if ($user->isSuperAdmin()) {
            $this->info("{$user->email} is already a Super Admin; no change was needed.");
            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm("Assign platform-wide Super Admin access to {$user->email}?")) {
            $this->warn('Cancelled; no role was changed.');
            return self::FAILURE;
        }
        DB::transaction(function () use ($user) {
            $before = ['role' => $user->role, 'account_type' => $user->account_type, 'is_admin' => $user->is_admin];
            $user->update(['role' => 'super_admin', 'is_admin' => true, 'account_type' => 'user', 'status' => 'active', 'role_assigned_at' => now(), 'remember_token' => null]);
            AuditLog::create(['actor_type' => 'system', 'action' => 'roles.initial_super_admin_assigned', 'subject_type' => User::class, 'subject_id' => $user->id, 'request_id' => (string) Str::uuid(), 'before' => $before, 'after' => ['role' => 'super_admin', 'account_type' => 'user', 'is_admin' => true], 'metadata' => ['source' => 'artisan_command']]);
        });
        $this->info("Super Admin access assigned to {$user->email}. No password was created or changed.");
        return self::SUCCESS;
    }
}
