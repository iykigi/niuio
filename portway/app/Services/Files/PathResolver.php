<?php

namespace App\Services\Files;

use App\Models\Site;
use InvalidArgumentException;

/**
 * Turns a user-supplied relative path into a path guaranteed to stay
 * inside the site's own root directory on the "hosting" disk. Every
 * File Manager operation — and nothing else — is allowed to touch that
 * disk, and every one of them resolves its path through here first.
 * This is the single choke point that makes "never expose files
 * belonging to another user" and "prevent path traversal" true.
 */
class PathResolver
{
    public function __construct(private Site $site)
    {
    }

    /**
     * @throws InvalidArgumentException if the path escapes the site root.
     */
    public function resolve(string $relativePath): string
    {
        $relativePath = str_replace('\\', '/', $relativePath);
        $segments = [];

        foreach (explode('/', $relativePath) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                throw new InvalidArgumentException('Path traversal is not allowed.');
            }

            if (preg_match('/[\x00-\x1F]/', $segment)) {
                throw new InvalidArgumentException('Invalid characters in path.');
            }

            $segments[] = $segment;
        }

        $clean = implode('/', $segments);

        return $clean === '' ? $this->site->rootPath() : $this->site->rootPath().'/'.$clean;
    }

    public function siteRoot(): string
    {
        return $this->site->rootPath();
    }

    /**
     * Strips the site's root prefix back off a disk path, for display.
     */
    public function toRelative(string $diskPath): string
    {
        return ltrim(substr($diskPath, strlen($this->site->rootPath())), '/');
    }
}
