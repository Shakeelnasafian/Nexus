<?php

namespace App\Http\Controllers\Vendor;

use App\Domain\Vendor\Actions\ActivateVendor;
use App\Domain\Vendor\Actions\ReinstateVendor;
use App\Domain\Vendor\Actions\SuspendVendor;
use App\Domain\Vendor\Actions\TerminateVendor;
use App\Domain\Vendor\Models\Vendor;
use App\Http\Controllers\Controller;

class VendorActionController extends Controller
{
    public function activate(Vendor $vendor, ActivateVendor $action): \Illuminate\Http\RedirectResponse
    {
        $action->execute($vendor);
        return back();
    }

    public function suspend(Vendor $vendor, SuspendVendor $action): \Illuminate\Http\RedirectResponse
    {
        $action->execute($vendor);
        return back();
    }

    public function reinstate(Vendor $vendor, ReinstateVendor $action): \Illuminate\Http\RedirectResponse
    {
        $action->execute($vendor);
        return back();
    }

    public function terminate(Vendor $vendor, TerminateVendor $action): \Illuminate\Http\RedirectResponse
    {
        $action->execute($vendor);
        return back();
    }
}
