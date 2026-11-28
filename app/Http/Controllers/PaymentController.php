<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $payments = Payment::query()
            ->whereHas('lease', fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return $this->paginated(PaymentResource::collection($payments));
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $payment = Payment::create([
            ...$data,
            'status' => $data['status'] ?? 'pending',
        ]);

        return $this->ok(new PaymentResource($payment), 'Pembayaran dibuat', 201);
    }

    public function show(Request $request, Payment $payment): JsonResponse
    {
        $this->ensureOwnedBy($request, $payment);

        return $this->ok(new PaymentResource($payment));
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): JsonResponse
    {
        $payment->update($request->validated());

        return $this->ok(new PaymentResource($payment->refresh()), 'Pembayaran diperbarui');
    }

    public function destroy(Request $request, Payment $payment): JsonResponse
    {
        $this->ensureOwnedBy($request, $payment);
        abort_unless($payment->status === 'pending', 403);

        $payment->delete();

        return $this->ok(null, 'Pembayaran dihapus');
    }

    private function ensureOwnedBy(Request $request, Payment $payment): void
    {
        abort_unless(
            $payment->lease()->where('user_id', $request->user()->id)->exists(),
            404
        );
    }
}
