<?php

namespace App\Http\Requests\Databases;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDatabaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Database::class);
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'min:2', 'max:40', 'regex:/^[A-Za-z0-9 _-]+$/'],
            'site_id' => ['nullable', 'integer', Rule::exists('sites', 'id')->where('user_id', $this->user()->id)],
        ];
    }
}
