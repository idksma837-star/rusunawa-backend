<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoomController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $rooms = Room::query()
            ->with('building')
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->building_id, fn ($query, $buildingId) => $query->where('building_id', $buildingId))
            ->orderBy('room_number')
            ->get();

        return RoomResource::collection($rooms);
    }

    public function show(Room $room): RoomResource
    {
        return new RoomResource($room->load('building'));
    }
}
