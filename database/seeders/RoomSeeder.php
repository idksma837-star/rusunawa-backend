<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Penyewa Demo',
            'email' => 'demo@rusunawa.test',
            'password' => 'password123',
            'role' => 'tenant',
            'phone' => '081234567890',
        ]);

        foreach (['Rusunawa A', 'Rusunawa B'] as $b => $name) {
            $building = Building::create([
                'name' => $name,
                'address' => 'Banda Aceh',
            ]);

            foreach (range(1, 4) as $i) {
                Room::create([
                    'building_id' => $building->id,
                    'room_number' => chr(65 + $b).'-'.(100 + $i),
                    'floor' => $i <= 2 ? 1 : 2,
                    'price' => 300000 + ($i * 25000),
                    'status' => $i === 4 ? 'occupied' : 'available',
                ]);
            }
        }
    }
}
