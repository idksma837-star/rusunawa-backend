<?php

namespace App\Http\Requests;

use App\Models\Lease;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lease = $this->route('lease');
        $user = $this->user();

        return $lease instanceof Lease
            && $user !== null
            && $lease->user_id === $user->getAuthIdentifier();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after:start_date'],
            'status' => ['sometimes', 'in:pending,active,finished'],
        ];
    }
}
