<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
        {
            return [
                'name' => ['required', 'string', 'max:255'],
                'age' => ['required', 'integer', 'min:1', 'max:150'],
                'sex' => ['required', 'string', 'in:Male,Female,Other'],
                'participant_type' => ['required', 'string', 'max:255'],
                'organization' => ['required', 'string', 'max:255'],
                'agency' => ['required', 'string', 'max:255'],
                'mobile_number' => ['required', 'digits:11'],
                'landline_number' => ['nullable', 'digits:10'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)],
                'picture' => ['nullable', 'image', 'max:4096'],
                ];
        }
    }

