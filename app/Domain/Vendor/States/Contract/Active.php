<?php

namespace App\Domain\Vendor\States\Contract;

class Active extends ContractState
{
    public static $name = 'active';

    public function label(): string
    {
        return 'Active';
    }
}
