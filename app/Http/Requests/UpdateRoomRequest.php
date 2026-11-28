<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'building_id' => ['sometimes', 'required', 'integer', 'exists:buildings,id'],
            'room_number' => ['sometimes', 'required', 'string', 'max:255'],
            'floor' => ['sometimes', 'required', 'integer', 'min:0', 'max:255'],
            'price' => ['sometimes', 'required', 'integer', 'min:0'],
            'status' => ['sometimes', 'required', 'in:available,occupied'],
        ];
    }
}
