<?php

namespace App\Domain\Vendor\States\Contract;

class Draft extends ContractState
{
    public static $name = 'draft';

    public function label(): string
    {
        return 'Draft';
    }
}
