<?php

namespace App\Domain\Vendor\States\Contract;

class Terminated extends ContractState
{
    public static $name = 'terminated';

    public function label(): string
    {
        return 'Terminated';
    }
}
