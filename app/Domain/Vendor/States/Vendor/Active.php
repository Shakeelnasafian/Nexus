<?php

namespace App\Domain\Vendor\States\Vendor;

class Active extends VendorState
{
    public static $name = 'active';

    public function label(): string
    {
        return 'Active';
    }
}
