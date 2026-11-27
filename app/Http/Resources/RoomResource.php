<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'room_number' => $this->room_number,
            'floor' => $this->floor,
            'price' => $this->price,
            'status' => $this->status,
            'building' => $this->whenLoaded('building', function () {
                return [
                    'id' => $this->building->id,
                    'name' => $this->building->name,
                    'address' => $this->building->address,
                ];
            }),
        ];
    }
}
