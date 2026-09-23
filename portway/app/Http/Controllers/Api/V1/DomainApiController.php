<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Domains\StoreDomainRequest;
use App\Http\Resources\DomainResource;
use App\Models\Domain;
use App\Models\Site;
use App\Services\Dns\DomainConnectionService;
use Illuminate\Http\Request;

class DomainApiController extends Controller
{
    public function index(Request $request)
    {
        $request->user()->tokenCan('domains:read') || abort(403);

        return DomainResource::collection($request->user()->domains()->paginate(20));
    }

    public function store(StoreDomainRequest $request, DomainConnectionService $service)
    {
        $request->user()->tokenCan('domains:write') || abort(403);

        $site = Site::where('user_id', $request->user()->id)->findOrFail($request->validated('site_id'));
        $domain = $service->addDomain($request->user(), $site, $request->validated('hostname'), $request->validated('type', 'primary'));

        return DomainResource::make($domain)->response()->setStatusCode(202);
    }

    public function show(Request $request, Domain $domain)
    {
        $this->authorize('view', $domain);

        return DomainResource::make($domain);
    }

    public function destroy(Request $request, Domain $domain)
    {
        $this->authorize('delete', $domain);
        $request->user()->tokenCan('domains:write') || abort(403);

        $domain->delete();

        return response()->noContent();
    }
}
