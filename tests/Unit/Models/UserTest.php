<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_accessors_and_plan_helpers_work(): void
    {
        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '5551112222',
            'password' => Hash::make('password'),
            'plan' => 'premium',
            'storage_used' => 53687091200,
            'meta' => ['studio_name' => 'Kwik Studio'],
        ]);

        $this->assertSame('Jane', $user->first_name);
        $this->assertSame('Doe', $user->last_name);
        $this->assertSame('Premium', $user->plan_label);
        $this->assertSame('Kwik Studio', $user->studio_name);
        $this->assertSame('50 GB', $user->storage_used_human);
        $this->assertSame(8, $user->storage_percent);
        $this->assertSame('600 GB', $user->plan_limits['storage_label']);
        $this->assertStringStartsWith('https://ui-avatars.com/api/', $user->profile_photo_url);
    }

    public function test_user_can_generate_and_verify_otp(): void
    {
        $user = User::create([
            'name' => 'OTP User',
            'email' => 'otp@example.com',
            'phone' => '5552223333',
            'password' => Hash::make('password'),
        ]);

        $otp = $user->generateOtp();
        $fresh = $user->fresh();

        $this->assertMatchesRegularExpression('/^\\d{6}$/', $otp);
        $this->assertNotSame($otp, $fresh->otp);
        $this->assertTrue($fresh->otp_expires_at->isFuture());
        $this->assertTrue($fresh->verifyOtp($otp));
        $this->assertFalse($fresh->verifyOtp('000000'));
    }
}
