<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $payment = $this->route('payment');
        $user = $this->user();

        return $payment instanceof Payment
            && $user !== null
            && $payment->status === 'pending'
            && $payment->lease()->where('user_id', $user->getAuthIdentifier())->exists();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['sometimes', 'integer', 'min:1'],
            'paid_at' => ['sometimes', 'date'],
            'status' => ['sometimes', Rule::in(['pending', 'paid'])],
            'lease_id' => ['prohibited'],
        ];
    }
}
