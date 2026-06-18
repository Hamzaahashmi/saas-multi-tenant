<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => 'required|string|max:255',
            'subdomain'    => [
                'required',
                'string',
                'min:3',
                'max:63',
                'regex:/^[a-z0-9-]+$/',
                'unique:domains,domain,' . request('subdomain') . '.' . config('tenancy.central_domains')[0],
            ],
            'owner_name'   => 'required|string|max:255',
            'email'        => 'required|email|unique:tenants,email',
            'password'     => 'required|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'subdomain.regex'  => 'Subdomain can only contain lowercase letters, numbers, and hyphens.',
            'subdomain.unique' => 'This subdomain is already taken.',
            'email.unique'     => 'An account with this email already exists.',
        ];
    }
}
