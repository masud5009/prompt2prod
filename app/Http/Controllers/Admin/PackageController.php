<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GenerationPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PackageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Packages/Index', [
            'packages' => GenerationPackage::query()
                ->withCount('purchases')
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'image_quota' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug !== '' ? $baseSlug : 'package';
        $counter = 1;

        while (GenerationPackage::where('slug', $slug)->exists()) {
            $counter += 1;
            $slug = $baseSlug.'-'.$counter;
        }

        GenerationPackage::create([
            ...$validated,
            'currency' => strtoupper($validated['currency']),
            'slug' => $slug,
        ]);

        return back()->with('success', 'Package created successfully.');
    }

    public function update(Request $request, GenerationPackage $package): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'image_quota' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $package->update([
            ...$validated,
            'currency' => strtoupper($validated['currency']),
        ]);

        return back()->with('success', 'Package updated successfully.');
    }

    public function toggle(GenerationPackage $package): RedirectResponse
    {
        $package->update([
            'is_active' => !$package->is_active,
        ]);

        return back()->with('success', 'Package status updated.');
    }
}
