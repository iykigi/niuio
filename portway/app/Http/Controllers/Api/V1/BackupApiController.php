<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BackupResource;
use App\Models\Backup;
use App\Models\Site;
use App\Services\Backups\BackupService;
use Illuminate\Http\Request;

class BackupApiController extends Controller
{
    public function index(Request $request)
    {
        $request->user()->tokenCan('backups:read') || abort(403);

        return BackupResource::collection($request->user()->backups()->latest()->paginate(20));
    }

    public function store(Request $request, BackupService $service)
    {
        $request->user()->tokenCan('backups:write') || abort(403);

        $data = $request->validate([
            'site_id' => ['required', 'integer'],
            'type' => ['nullable', 'in:files,database,full'],
        ]);

        $site = Site::where('user_id', $request->user()->id)->findOrFail($data['site_id']);
        $backup = $service->create($request->user(), $site, $data['type'] ?? 'full', 'manual');

        return BackupResource::make($backup)->response()->setStatusCode(202);
    }

    public function show(Request $request, Backup $backup)
    {
        $this->authorize('view', $backup);

        return BackupResource::make($backup);
    }

    public function destroy(Request $request, Backup $backup, BackupService $service)
    {
        $this->authorize('delete', $backup);
        $request->user()->tokenCan('backups:write') || abort(403);

        $service->delete($backup);

        return response()->noContent();
    }
}
