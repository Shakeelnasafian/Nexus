<?php

namespace App\Domain\Vendor\States\Vendor;

class Terminated extends VendorState
{
    public static $name = 'terminated';

    public function label(): string
    {
        return 'Terminated';
    }
}
