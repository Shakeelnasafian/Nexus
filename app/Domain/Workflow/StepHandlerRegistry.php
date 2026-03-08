<?php

namespace App\Domain\Workflow;

use App\Domain\Workflow\Contracts\StepHandler;
use App\Domain\Workflow\Exceptions\UnknownStepActionType;

class StepHandlerRegistry
{
    /** @var array<string, class-string<StepHandler>> */
    private array $handlers = [];

    /** @param class-string<StepHandler> $handlerClass */
    public function register(string $actionType, string $handlerClass): void
    {
        $this->handlers[$actionType] = $handlerClass;
    }

    public function resolve(string $actionType): StepHandler
    {
        if (! isset($this->handlers[$actionType])) {
            throw UnknownStepActionType::for($actionType);
        }

        return app($this->handlers[$actionType]);
    }
}
