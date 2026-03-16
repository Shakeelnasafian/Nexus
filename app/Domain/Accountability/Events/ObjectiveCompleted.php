<?php

namespace App\Domain\Accountability\Events;

final readonly class ObjectiveCompleted
{
    public function __construct(
        public int $tenantId,
        public int $objectiveId,
        public string $title,
    ) {}
}
