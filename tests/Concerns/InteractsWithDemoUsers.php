<?php

namespace Tests\Concerns;

use App\Models\User;

trait InteractsWithDemoUsers
{
    protected function loginAsAdmin(): User
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($user);

        return $user;
    }

    protected function loginAsStudentUmar(): User
    {
        $user = User::where('email', 'umar@example.com')->firstOrFail();

        $this->actingAs($user);

        return $user;
    }

    protected function loginAsStudentFatima(): User
    {
        $user = User::where('email', 'fatima@example.com')->firstOrFail();

        $this->actingAs($user);

        return $user;
    }
}

