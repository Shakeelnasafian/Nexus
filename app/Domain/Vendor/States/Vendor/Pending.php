<?php

namespace App\Domain\Vendor\States\Vendor;

class Pending extends VendorState
{
    public static $name = 'pending';

    public function label(): string
    {
        return 'Pending';
    }
}
