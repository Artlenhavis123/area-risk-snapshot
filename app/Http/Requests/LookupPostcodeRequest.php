<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LookupPostcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'postcode' => ['required', 'string', 'regex:/^[A-Z]{1,2}\d[A-Z\d]?\s?\d[A-Z]{2}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'postcode.regex' => 'Enter a valid UK postcode, e.g. SW1A 1AA.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'postcode' => strtoupper(trim((string) $this->input('postcode'))),
        ]);
    }
}
