<?php

namespace App\Http\Requests\Meme;

use App\Enums\MemeAge;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // già protetto da auth:sanctum nella route
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'image' => 'required|image|max:2048',
            'age'   => 'required|in:' . implode(',', array_column(MemeAge::cases(), 'value')),
            'tags'  => 'array|exists:tags,id',
        ];
    }
}