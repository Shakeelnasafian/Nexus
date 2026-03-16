<?php

namespace App\Domain\Accountability\Enums;

enum SlaStatus: string
{
    case Active = 'active';
    case Breached = 'breached';
    case Closed = 'closed';
}
