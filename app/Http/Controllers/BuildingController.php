<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBuildingRequest;
use App\Http\Requests\UpdateBuildingRequest;
use App\Http\Resources\BuildingResource;
use App\Models\Building;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $buildings = Building::query()
            ->orderBy('name')
            ->paginate($request->integer('per_page', 10));

        return $this->paginated(BuildingResource::collection($buildings));
    }

    public function store(StoreBuildingRequest $request): JsonResponse
    {
        $building = Building::create($request->validated())->refresh();

        return $this->ok(new BuildingResource($building), 'Gedung dibuat', 201);
    }

    public function show(Building $building): JsonResponse
    {
        return $this->ok(new BuildingResource($building));
    }

    public function update(UpdateBuildingRequest $request, Building $building): JsonResponse
    {
        $building->update($request->validated());

        return $this->ok(new BuildingResource($building->refresh()), 'Gedung diperbarui');
    }

    public function destroy(Building $building): JsonResponse
    {
        abort_if($building->rooms()->exists(), 409, 'Gedung masih memiliki kamar.');

        $building->delete();

        return $this->ok(null, 'Gedung dihapus');
    }
}
