<?php

namespace App\Http\Requests\Meme;

use App\Enums\MemeAge;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'age'   => 'sometimes|in:' . implode(',', array_column(MemeAge::cases(), 'value')),
        ];
    }
}