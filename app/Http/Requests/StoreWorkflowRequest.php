<?php

namespace App\Http\Requests;

use App\Domain\Workflow\Enums\WorkflowTrigger;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreWorkflowRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'trigger_event' => ['required', 'string', new Enum(WorkflowTrigger::class)],
            'is_active'     => ['boolean'],
        ];
    }
}
