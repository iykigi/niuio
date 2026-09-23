<?php

namespace App\Http\Controllers\Releases;

use App\Enums\ReleasePlatform;
use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The public face of the release system. It only ever renders live builds:
 * a platform with nothing published is not rendered at all — no greyed-out
 * button, no "coming soon" — and an application with no live build on any
 * platform 404s rather than showing an empty page.
 */
class PublicAppController extends Controller
{
    /** The directory of everything currently downloadable on this install. */
    public function index(): View
    {
        abort_unless(config('portway.features.app_distribution', true), 404);

        $applications = Application::query()
            ->publiclyVisible()
            ->with(['activeReleases'])
            ->orderByDesc('downloads_count')
            ->orderBy('name')
            ->paginate(24);

        return view('marketing.apps-index', [
            'applications' => $applications,
        ]);
    }

    public function show(Request $request, Application $application): View
    {
        abort_unless(config('portway.features.app_distribution', true), 404);

        // is_listed off, or nothing live anywhere: the page does not exist
        // for the public. The owner and staff still get to preview it.
        if (! $application->isVisibleToPublic() && ! $this->mayPreview($request, $application)) {
            abort(404);
        }

        $downloads = $application->liveDownloads();

        return view('marketing.app-show', [
            'application' => $application,
            'downloads' => $downloads,
            'suggested' => ReleasePlatform::detectFromUserAgent($request->userAgent()),
            'isPreview' => ! $application->isVisibleToPublic(),
        ]);
    }

    private function mayPreview(Request $request, Application $application): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        return $user->id === $application->user_id || $user->can('releases.view');
    }
}
