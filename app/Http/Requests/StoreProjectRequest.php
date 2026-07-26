<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'repository_url' => ['nullable', 'url', 'max:500'],
            'production_url' => ['nullable', 'url', 'max:500'],
            'staging_url' => ['nullable', 'url', 'max:500'],
            'server_ip' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,pipeline,archived'],
            'client_id' => ['nullable', 'integer'],
        ];
    }
}
