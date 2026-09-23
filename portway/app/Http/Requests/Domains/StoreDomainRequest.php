<?php

namespace App\Http\Requests\Domains;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Domain::class);
    }

    public function rules(): array
    {
        return [
            'site_id' => ['required', 'integer', Rule::exists('sites', 'id')->where('user_id', $this->user()->id)],
            'hostname' => [
                'required', 'string', 'max:255',
                'regex:/^(?!-)[A-Za-z0-9-]{1,63}(\.[A-Za-z0-9-]{1,63})+$/',
            ],
            'type' => ['nullable', Rule::in(['primary', 'alias', 'www', 'subdomain', 'wildcard'])],
        ];
    }

    public function messages(): array
    {
        return [
            'hostname.regex' => 'Enter a valid domain name, e.g. example.com or blog.example.com.',
        ];
    }
}
