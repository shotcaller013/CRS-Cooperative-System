<?php

// app/Providers/AuthServiceProvider.php
// Add these entries to the $policies array

namespace App\Providers;

use App\Models\Loan;
use App\Models\Member;
use App\Policies\LoanPolicy;
use App\Policies\MemberPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Member::class => MemberPolicy::class,
        Loan::class   => LoanPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
