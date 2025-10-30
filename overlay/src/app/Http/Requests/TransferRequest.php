<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'from_user_id' => ['required','integer','exists:users,id'],
            'to_user_id' => ['required','integer','different:from_user_id','exists:users,id'],
            'amount' => ['required','regex:/^\d+(?:[\.,]\d{1,2})?$/'],
            'comment' => ['nullable','string','max:255'],
        ];
    }
}
