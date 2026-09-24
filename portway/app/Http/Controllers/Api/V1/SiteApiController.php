<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sites\StoreSiteRequest;
use App\Http\Resources\SiteResource;
use App\Models\Site;
use App\Services\Provisioning\SiteProvisioningService;
use Illuminate\Http\Request;

class SiteApiController extends Controller
{
    public function index(Request $request)
    {
        $request->user()->tokenCan('sites:read') || abort(403, 'Token missing the sites:read ability.');

        return SiteResource::collection($request->user()->sites()->with('domains')->paginate(20));
    }

    public function store(StoreSiteRequest $request, SiteProvisioningService $service)
    {
        $request->user()->tokenCan('sites:write') || abort(403, 'Token missing the sites:write ability.');

        $site = $service->create($request->user(), $request->validated());

        return SiteResource::make($site->fresh())->response()->setStatusCode(202);
    }

    public function show(Request $request, Site $site)
    {
        $this->authorize('view', $site);

        return SiteResource::make($site->load('domains'));
    }

    public function destroy(Request $request, Site $site, SiteProvisioningService $service)
    {
        $this->authorize('delete', $site);
        $request->user()->tokenCan('sites:write') || abort(403, 'Token missing the sites:write ability.');

        $service->delete($site);

        return response()->noContent();
    }
}
