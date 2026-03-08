<?php

namespace App\Domain\Vendor\States\Contract;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class ContractState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, Active::class)
            ->allowTransition(Active::class, Expired::class)
            ->allowTransition(Active::class, Terminated::class);
    }

    abstract public function label(): string;
}
