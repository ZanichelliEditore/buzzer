<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Tests\TestCaseWithoutMiddleware;

class BoardGateTest extends TestCaseWithoutMiddleware
{
    private function userWithPermissions(array $permissions): User
    {
        $user = new User();
        $user->id = 42;
        $user->permissions = $permissions;

        return $user;
    }

    public function testShouldDenyGuest(): void
    {
        $this->assertFalse(Gate::allows('board', 'VIEW_ALL'));
    }

    public function testShouldAllowUserWithPermission(): void
    {
        Log::spy();
        $this->actingAs($this->userWithPermissions(['VIEW_ALL']));

        $this->assertTrue(Gate::allows('board', 'VIEW_ALL'));
        Log::shouldNotHaveReceived('error');
    }

    public function testShouldDenyUserWithoutPermissionAndLog403(): void
    {
        Log::spy();
        $this->actingAs($this->userWithPermissions(['OTHER']));

        $this->assertFalse(Gate::allows('board', 'VIEW_ALL'));
        Log::shouldHaveReceived('error')->once()->withArgs(
            fn ($message) => str_starts_with($message, '403* ')
                && str_contains($message, 'User with id  42')
                && str_contains($message, 'permission required:VIEW_ALL')
        );
    }

    public function testShouldDenyUserWithEmptyPermissions(): void
    {
        $this->actingAs($this->userWithPermissions([]));

        $this->assertFalse(Gate::allows('board', 'VIEW_ALL'));
    }
}
