<?php

namespace App\Domain\Vendor\States\Vendor;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class VendorState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Pending::class)
            ->allowTransition(Pending::class, Active::class)
            ->allowTransition(Active::class, Suspended::class)
            ->allowTransition(Active::class, Terminated::class)
            ->allowTransition(Suspended::class, Active::class)
            ->allowTransition(Suspended::class, Terminated::class);
    }

    abstract public function label(): string;
}
