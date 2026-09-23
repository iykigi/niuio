<?php

namespace App\Http\Requests\Sites;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Site::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'project_type' => ['required', Rule::in(array_keys(config('portway.project_types')))],
            'php_version' => ['nullable', Rule::in(config('portway.php_versions'))],
            'node_version' => ['nullable', Rule::in(config('portway.node_versions'))],
            // Restricted to the transports a hosting site should ever clone
            // over: 'file://' (arbitrary local file disclosure) and git's
            // 'ext::'/'fd::' remote helpers (arbitrary command execution)
            // are otherwise valid-looking URLs. CommandSanitizer enforces
            // this too before the clone actually runs, but rejecting it
            // here gives the user an immediate, specific validation error.
            'git_url' => ['nullable', 'required_if:project_type,git', 'url', 'regex:/^(https?|git|ssh):\/\//i'],
            'git_branch' => ['nullable', 'string', 'max:100'],
            'create_database' => ['nullable', 'boolean'],
        ];
    }
}
