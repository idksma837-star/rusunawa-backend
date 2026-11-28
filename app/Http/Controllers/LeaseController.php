<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaseRequest;
use App\Http\Requests\UpdateLeaseRequest;
use App\Http\Resources\LeaseResource;
use App\Models\Lease;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return LeaseResource::collection(
            $request->user()->leases()->with('room.building')->latest()->get()
        );
    }

    public function store(StoreLeaseRequest $request): JsonResponse
    {
        $data = $request->validated();

        $lease = DB::transaction(function () use ($data, $request): Lease {
            $room = Room::query()->lockForUpdate()->findOrFail($data['room_id']);

            if (
                $room->status !== 'available'
                || $room->leases()->whereIn('status', ['pending', 'active'])->exists()
            ) {
                throw ValidationException::withMessages([
                    'room_id' => ['Kamar ini sudah terisi.'],
                ]);
            }

            return $request->user()->leases()->create([
                'room_id' => $room->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => 'pending',
            ])->load('room.building');
        });

        return (new LeaseResource($lease))->response()->setStatusCode(201);
    }

    public function show(Request $request, Lease $lease): LeaseResource
    {
        $this->ensureOwnedBy($request, $lease);

        return new LeaseResource($lease->load('room.building'));
    }

    public function update(UpdateLeaseRequest $request, Lease $lease): LeaseResource
    {
        $lease->update($request->validated());

        return new LeaseResource($lease->refresh()->load('room.building'));
    }

    public function destroy(Request $request, Lease $lease): JsonResponse
    {
        $this->ensureOwnedBy($request, $lease);
        abort_if($lease->payments()->exists(), 409, 'Sewa memiliki riwayat pembayaran dan tidak dapat dihapus.');

        $lease->delete();

        return response()->json(['message' => 'Sewa dihapus']);
    }

    private function ensureOwnedBy(Request $request, Lease $lease): void
    {
        abort_unless($lease->user_id === $request->user()->getAuthIdentifier(), 404);
    }
}
