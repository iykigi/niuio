<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\Files\PathResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function __invoke(Request $request, Site $site)
    {
        $this->authorize('view', $site);

        $relativePath = $request->validate(['path' => ['required', 'string']])['path'];
        $resolver = new PathResolver($site);

        try {
            $absolute = $resolver->resolve($relativePath);
        } catch (\InvalidArgumentException) {
            abort(404);
        }

        // fileExists(), not exists(): the latter is also true for folders,
        // which can't be streamed as a download.
        abort_unless(Storage::disk('hosting')->fileExists($absolute), 404);

        return Storage::disk('hosting')->download($absolute);
    }
}
