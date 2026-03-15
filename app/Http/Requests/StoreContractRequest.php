<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title'     => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at'   => ['nullable', 'date', 'after:starts_at'],
        ];
    }
}
