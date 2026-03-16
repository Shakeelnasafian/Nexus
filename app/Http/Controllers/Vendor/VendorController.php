<?php

namespace App\Http\Controllers\Vendor;

use App\Domain\Vendor\Actions\CreateVendor;
use App\Domain\Vendor\Models\Vendor;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVendorRequest;
use Inertia\Inertia;
use Inertia\Response;

class VendorController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Vendors/Index', [
            'vendors' => Vendor::all(['id', 'name', 'code', 'state', 'created_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Vendors/Create');
    }

    public function store(StoreVendorRequest $request, CreateVendor $action): \Illuminate\Http\RedirectResponse
    {
        $vendor = $action->execute(
            name: $request->validated('name'),
            code: $request->validated('code'),
        );

        return redirect()->route('vendors.show', $vendor);
    }

    public function show(Vendor $vendor): Response
    {
        return Inertia::render('Vendors/Show', [
            'vendor' => $vendor->only('id', 'name', 'code', 'state', 'created_at'),
            'contracts' => $vendor->contracts()->get(['id', 'vendor_id', 'title', 'state', 'starts_at']),
        ]);
    }
}
