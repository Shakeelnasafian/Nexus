<?php

namespace App\Domain\Vendor\States\Contract;

class Expired extends ContractState
{
    public static $name = 'expired';

    public function label(): string
    {
        return 'Expired';
    }
}
