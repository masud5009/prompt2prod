<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GenerationPackage;
use App\Models\User;
use App\Models\UserPackagePurchase;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $totalQuota = (int) UserPackagePurchase::sum('purchased_images');
        $totalUsed = (int) UserPackagePurchase::sum('used_images');

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::count(),
                'admins' => User::where('is_admin', true)->count(),
                'packages' => GenerationPackage::count(),
                'activePackages' => GenerationPackage::where('is_active', true)->count(),
                'purchases' => UserPackagePurchase::count(),
                'activePurchases' => UserPackagePurchase::where('status', 'active')->count(),
                'totalQuota' => $totalQuota,
                'totalUsed' => $totalUsed,
                'remainingQuota' => max(0, $totalQuota - $totalUsed),
            ],
            'recentPurchases' => UserPackagePurchase::query()
                ->with(['user:id,name,email', 'package:id,name'])
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }
}
