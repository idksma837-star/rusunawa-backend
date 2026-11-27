<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Lease;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\RoomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoomAndLeaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_and_lease_routes_require_authentication(): void
    {
        $this->getJson('/api/rooms')->assertUnauthorized();
        $this->getJson('/api/leases')->assertUnauthorized();
        $this->postJson('/api/leases')->assertUnauthorized();
    }

    public function test_room_seeder_creates_demo_user_buildings_and_rooms(): void
    {
        $this->seed(RoomSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('buildings', 2);
        $this->assertDatabaseCount('rooms', 8);

        $user = User::query()->where('email', 'demo@rusunawa.test')->firstOrFail();
        $this->assertSame('tenant', $user->role);
        $this->assertTrue(Hash::check('password123', $user->password));

        $this->assertDatabaseHas('rooms', [
            'room_number' => 'A-104',
            'floor' => 2,
            'price' => 400000,
            'status' => 'occupied',
        ]);
    }

    public function test_authenticated_user_can_list_and_view_rooms(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $room = $this->createRoom();

        $this->getJson('/api/rooms')
            ->assertOk()
            ->assertJsonPath('data.0.id', $room->id)
            ->assertJsonPath('data.0.building.id', $room->building_id);

        $this->getJson('/api/rooms/'.$room->id)
            ->assertOk()
            ->assertJsonPath('data.id', $room->id)
            ->assertJsonPath('data.building.id', $room->building_id);
    }

    public function test_room_list_can_be_filtered_by_status_and_building(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $availableRoom = $this->createRoom();
        $occupiedRoom = $this->createRoom([
            'room_number' => 'B-201',
            'status' => 'occupied',
        ]);

        $this->getJson('/api/rooms?status=available')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $availableRoom->id);

        $this->getJson('/api/rooms?building_id='.$occupiedRoom->building_id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $occupiedRoom->id);
    }

    public function test_authenticated_user_can_create_a_pending_lease_for_an_available_room(): void
    {
        $user = User::factory()->create();
        $room = $this->createRoom(['price' => 125]);
        $startDate = now()->addDay()->toDateString();
        $endDate = now()->addYear()->toDateString();
        Sanctum::actingAs($user);

        $this->postJson('/api/leases', [
            'room_id' => $room->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ])
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'start_date',
                    'end_date',
                    'status',
                    'room' => [
                        'id',
                        'room_number',
                        'floor',
                        'price',
                        'status',
                        'building' => ['id', 'name', 'address'],
                    ],
                ],
            ])
            ->assertJsonPath('data.room.price', 125)
            ->assertJsonPath('data.room.building.name', 'Building A')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('leases', [
            'user_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'pending',
        ]);

        $lease = Lease::query()->firstOrFail();
        $this->assertSame($startDate, $lease->start_date->toDateString());
        $this->assertSame($endDate, $lease->end_date->toDateString());
    }

    public function test_user_can_list_only_their_leases_with_room_and_building_data(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $room = $this->createRoom();
        $ownLease = $user->leases()->create([
            'room_id' => $room->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'pending',
        ]);
        $otherUser->leases()->create([
            'room_id' => $this->createRoom(['room_number' => 'B-101'])->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'pending',
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/leases')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownLease->id)
            ->assertJsonPath('data.0.room.id', $room->id)
            ->assertJsonPath('data.0.room.building.name', 'Building A');
    }

    public function test_room_with_a_pending_or_active_lease_cannot_be_leased_again(): void
    {
        $user = User::factory()->create();
        $room = $this->createRoom();
        $room->leases()->create([
            'user_id' => $user->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'pending',
        ]);
        Sanctum::actingAs(User::factory()->create());

        $payload = [
            'room_id' => $room->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ];

        $this->postJson('/api/leases', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('room_id');

        $room->leases()->update(['status' => 'active']);

        $this->postJson('/api/leases', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('room_id');
        $this->assertDatabaseCount('leases', 1);
    }

    private function createRoom(array $attributes = []): Room
    {
        $building = Building::query()->create(['name' => 'Building A']);

        return Room::query()->create(array_merge([
            'building_id' => $building->id,
            'room_number' => 'A-101',
            'floor' => 1,
            'price' => 100,
            'status' => 'available',
        ], $attributes));
    }
}
