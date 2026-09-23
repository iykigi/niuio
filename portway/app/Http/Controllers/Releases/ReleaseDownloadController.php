<?php

namespace App\Http\Controllers\Releases;

use App\Enums\ReleasePlatform;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Release;
use App\Services\Releases\ReleaseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The only way bytes ever leave the private releases disk.
 *
 * Every download is resolved through the release rules rather than from a
 * path in the URL: a visitor can only ever reach the live build for a
 * platform, and only when the application is listed. Drafts, archived
 * builds and builds sitting in the admin archive are invisible here —
 * except to the owner or to staff, who may pull their own.
 */
class ReleaseDownloadController extends Controller
{
    public function __construct(private readonly ReleaseService $releases) {}

    /** Public entry point: /download/{application}/{platform}. */
    public function __invoke(Request $request, Application $application, string $platform): StreamedResponse
    {
        // The public directory pages 404 when app distribution is switched
        // off; the download URL has to go with them, or old links keep
        // serving builds from a feature the operator has turned off.
        abort_unless(config('portway.features.app_distribution', true), 404);

        $platformEnum = ReleasePlatform::tryFrom(strtolower($platform));

        abort_if($platformEnum === null, 404);

        try {
            $release = $this->releases->findDownloadable($application, $platformEnum, $request->user());
        } catch (ModelNotFoundException) {
            abort(404);
        }

        return $this->stream($release);
    }

    /** Owner/staff entry point for a specific build, live or not. */
    public function build(Request $request, Release $release): StreamedResponse
    {
        $this->authorize('download', $release);

        return $this->stream($release);
    }

    private function stream(Release $release): StreamedResponse
    {
        abort_unless($this->releases->fileExists($release), 404);

        $this->releases->recordDownload($release);

        $application = $release->application;
        $slug = $application?->slug ?? 'application';
        $extension = pathinfo((string) $release->original_filename, PATHINFO_EXTENSION);

        // Build the download name ourselves rather than echoing back the
        // uploader's filename, so nothing user-supplied reaches the
        // Content-Disposition header unsanitised.
        $filename = $this->releases->sanitiseFilename(
            $slug.'-'.$release->version.'-'.$release->platform->value.($extension !== '' ? '.'.$extension : '')
        );

        return Storage::disk($release->disk)->download($release->path, $filename, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
