<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Databases\StoreDatabaseRequest;
use App\Http\Resources\DatabaseResource;
use App\Models\Database;
use App\Services\Databases\DatabaseProvisioningService;
use Illuminate\Http\Request;

class DatabaseApiController extends Controller
{
    public function index(Request $request)
    {
        $request->user()->tokenCan('databases:read') || abort(403);

        return DatabaseResource::collection($request->user()->databases()->paginate(20));
    }

    public function store(StoreDatabaseRequest $request, DatabaseProvisioningService $service)
    {
        $request->user()->tokenCan('databases:write') || abort(403);

        $result = $service->create($request->user(), $request->validated('label'));

        return DatabaseResource::make($result['database']->fresh())->additional([
            'credentials' => [
                'username' => $result['user']->username,
                'password' => $result['plain_password'],
            ],
        ])->response()->setStatusCode(201);
    }

    public function show(Request $request, Database $database)
    {
        $this->authorize('view', $database);

        return DatabaseResource::make($database);
    }

    public function destroy(Request $request, Database $database, DatabaseProvisioningService $service)
    {
        $this->authorize('delete', $database);
        $request->user()->tokenCan('databases:write') || abort(403);

        $service->delete($database);

        return response()->noContent();
    }
}
