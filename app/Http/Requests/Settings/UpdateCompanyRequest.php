<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cmp_name' => ['required', 'string', 'max:150'],
            'cmp_rif' => ['required', 'string', 'max:20', 'regex:/^[JPVGGE]-\d{8}-\d$/'],
            'cmp_address' => ['required', 'string', 'max:500'],
            'cmp_phone' => ['required', 'string', 'max:30'],
            'cmp_email' => ['required', 'email', 'max:100'],
            'cmp_website' => ['nullable', 'url', 'max:150'],
            'cmp_logo' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cmp_rif.regex' => 'El RIF debe tener el formato J-12345678-9.',
        ];
    }
}
