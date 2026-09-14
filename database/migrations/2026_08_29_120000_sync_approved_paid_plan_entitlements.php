<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        foreach ((array) config('plans.entitlements', []) as $plan => $limits) {
            DB::table('plan_entitlements')->updateOrInsert(['plan'=>$plan], [...$limits,'is_active'=>true,'updated_at'=>$now,'created_at'=>$now]);
        }
    }

    public function down(): void
    {
        // Never restore duplicated placeholder commercial limits.
    }
};
