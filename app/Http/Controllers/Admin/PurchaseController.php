<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GenerationPackage;
use App\Models\User;
use App\Models\UserPackagePurchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Purchases/Index', [
            'purchases' => UserPackagePurchase::query()
                ->with(['user:id,name,email', 'package:id,name,image_quota'])
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'users' => User::query()->select('id', 'name', 'email')->orderBy('name')->get(),
            'packages' => GenerationPackage::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'generation_package_id' => ['required', 'exists:generation_packages,id'],
            'purchased_images' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:active,expired,cancelled'],
            'expires_at' => ['nullable', 'date'],
        ]);

        UserPackagePurchase::create([
            ...$validated,
            'used_images' => 0,
            'purchased_at' => now(),
        ]);

        return back()->with('success', 'Purchase assigned successfully.');
    }

    public function update(Request $request, UserPackagePurchase $purchase): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,expired,cancelled'],
            'used_images' => ['required', 'integer', 'min:0'],
            'expires_at' => ['nullable', 'date'],
        ]);

        if ($validated['used_images'] > $purchase->purchased_images) {
            return back()->withErrors([
                'used_images' => 'Used images cannot be greater than purchased images.',
            ]);
        }

        $purchase->update($validated);

        return back()->with('success', 'Purchase updated successfully.');
    }
}
