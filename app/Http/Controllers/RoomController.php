<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $rooms = Room::query()
            ->with('building')
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->building_id, fn ($query, $buildingId) => $query->where('building_id', $buildingId))
            ->orderBy('room_number')
            ->paginate($request->integer('per_page', 10));

        return $this->paginated(RoomResource::collection($rooms));
    }

    public function store(StoreRoomRequest $request): JsonResponse
    {
        $room = Room::create($request->validated())->refresh()->load('building');

        return $this->ok(new RoomResource($room), 'Kamar dibuat', 201);
    }

    public function show(Room $room): JsonResponse
    {
        return $this->ok(new RoomResource($room->load('building')));
    }

    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        $room->update($request->validated());

        return $this->ok(new RoomResource($room->refresh()->load('building')), 'Kamar diperbarui');
    }

    public function destroy(Room $room): JsonResponse
    {
        $room->delete();

        return $this->ok(null, 'Kamar dihapus');
    }
}
