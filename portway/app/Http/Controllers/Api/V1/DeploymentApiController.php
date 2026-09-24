<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeploymentResource;
use App\Models\Deployment;
use App\Models\GitRepository;
use App\Services\Deployments\DeploymentService;
use Illuminate\Http\Request;

class DeploymentApiController extends Controller
{
    public function index(Request $request)
    {
        $request->user()->tokenCan('deployments:read') || abort(403);

        return DeploymentResource::collection(
            Deployment::whereIn('site_id', $request->user()->sites()->pluck('id'))->latest()->paginate(20)
        );
    }

    public function store(Request $request, DeploymentService $service)
    {
        $request->user()->tokenCan('deployments:write') || abort(403);

        $data = $request->validate(['site_id' => ['required', 'integer']]);
        $repository = GitRepository::where('site_id', $data['site_id'])->firstOrFail();
        $this->authorize('deploy', $repository);

        $deployment = $service->deploy($repository, 'manual', $request->user());

        return DeploymentResource::make($deployment->fresh())->response()->setStatusCode(202);
    }

    public function show(Request $request, Deployment $deployment)
    {
        abort_unless($deployment->site->user_id === $request->user()->id, 403);

        return DeploymentResource::make($deployment);
    }
}
