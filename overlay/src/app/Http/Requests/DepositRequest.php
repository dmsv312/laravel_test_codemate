<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user_id' => ['required','integer','exists:users,id'],
            'amount' => ['required','regex:/^\d+(?:[\.,]\d{1,2})?$/'],
            'comment' => ['nullable','string','max:255'],
        ];
    }
}
