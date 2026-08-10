<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $used = DB::table('group_access_invites')->pluck('access_code')->flip()->all();

        DB::table('group_access_invites')->orderBy('id')->each(function ($invite) use (&$used) {
            if (preg_match('/^(?=.*[A-Z])(?=.*\d)[A-Z0-9]{6}$/', $invite->access_code)) {
                return;
            }

            do {
                $code = $this->mixedCode();
            } while (isset($used[$code]));

            unset($used[$invite->access_code]);
            $used[$code] = true;
            DB::table('group_access_invites')->where('id', $invite->id)->update(['access_code' => $code]);
        });
    }

    public function down(): void
    {
        // Codes cannot be safely restored after they have been shared.
    }

    private function mixedCode(): string
    {
        $letters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $numbers = '23456789';
        $characters = $letters.$numbers;
        $parts = [
            $letters[random_int(0, strlen($letters) - 1)],
            $numbers[random_int(0, strlen($numbers) - 1)],
        ];

        while (count($parts) < 6) {
            $parts[] = $characters[random_int(0, strlen($characters) - 1)];
        }
        for ($i = count($parts) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$parts[$i], $parts[$j]] = [$parts[$j], $parts[$i]];
        }

        return implode('', $parts);
    }
};
