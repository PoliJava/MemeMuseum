<?php

namespace App\Http\Requests\Meme;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\MemeAge;
use Illuminate\Validation\Rules\Enum;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === $this->meme->user_id;
    }

    public function rules(): array
    {
        return [
            'title'        => 'sometimes|string|max:255',
            'age'          => ['sometimes', new Enum(MemeAge::class)],
            'board_id'     => 'sometimes|exists:boards,id',
            'is_anonymous' => 'boolean',
            'author_name'  => 'nullable|string|max:64',
        ];
    }
}