<?php

namespace App\Http\Requests\V1;

use App\Models\User;
use App\Rules\UniqueInvitationEmail;
use App\Rules\ValidRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserInviteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create invitations', User::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'unique_users', new UniqueInvitationEmail],
            'role' => ['required', new ValidRole]
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages()
    {
        return [
            'email.unique_users' => 'Alamat email ' . $this->input('email') . ' sudah terdaftar.',
            'email.unique_invitations' => 'Alamat email ' . $this->input('email') . ' sudah diundang.'
        ];
    }
}
