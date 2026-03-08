<?php

namespace App\Domain\Vendor\States\Vendor;

class Suspended extends VendorState
{
    public static $name = 'suspended';

    public function label(): string
    {
        return 'Suspended';
    }
}
