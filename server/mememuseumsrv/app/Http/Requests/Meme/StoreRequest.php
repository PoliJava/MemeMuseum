<?php

namespace App\Http\Requests\Meme;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\MemeAge;
use Illuminate\Validation\Rules\Enum;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'        => 'required|string|max:255',
            'image'        => 'required|image|max:2048',
            'age'          => ['required', new Enum(MemeAge::class)],
            'board_id'     => 'required|exists:boards,id',
            'is_anonymous' => 'boolean',
            'author_name'  => 'nullable|string|max:64|required_if:is_anonymous,true',
            'tags'         => 'array',
            'tags.*'       => 'string|max:50',
        ];
    }
}