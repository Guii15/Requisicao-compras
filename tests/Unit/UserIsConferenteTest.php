<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIsConferenteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_role_conferente_is_conferente(): void
    {
        $user = User::factory()->create(['role' => 'conferente']);

        $this->assertTrue($user->isConferente());
    }

    public function test_admin_is_conferente_even_without_role(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'role' => null]);

        $this->assertTrue($admin->isConferente());
    }

    public function test_regular_user_without_role_is_not_conferente(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'role' => null]);

        $this->assertFalse($user->isConferente());
    }
}
