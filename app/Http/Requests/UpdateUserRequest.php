<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validate user update (password optional, but min 6 when filled). */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->route('user') instanceof User
            ? $this->route('user')->id
            : $this->route('user');

        return [
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'active' => ['sometimes', 'boolean'],
            'password' => ['nullable', 'string', 'min:6'],
        ];
    }
}
