<?php

namespace App\Http\Requests;

use App\Tenancy\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:100', 'alpha_dash',
                Rule::unique('vendors', 'code')->where('tenant_id', app(CurrentTenant::class)->id()),
            ],
        ];
    }
}
