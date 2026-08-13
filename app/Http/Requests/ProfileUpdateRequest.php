<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    /**
     * A file that exceeds PHP's upload_max_filesize never reaches the "image"/"max" rules above —
     * PHP drops it before Laravel sees it, so the request looks like "no file chosen" and the save
     * silently no-ops. Surface that case as a real validation error instead.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $file = $this->file('picture');

            if ($file && ! $file->isValid() && $file->getError() !== UPLOAD_ERR_NO_FILE) {
                $validator->errors()->add('picture', match ($file->getError()) {
                    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The picture is too large for the server to accept (max '.ini_get('upload_max_filesize').'). Please choose a smaller file.',
                    default => 'The picture failed to upload. Please try again.',
                });
            }
        });
    }
    }

