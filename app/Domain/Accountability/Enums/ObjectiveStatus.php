<?php

namespace App\Domain\Accountability\Enums;

enum ObjectiveStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Missed = 'missed';
}
