<?php

namespace App\Services\Files;

use App\Exceptions\QuotaExceededException;
use App\Models\Site;
use App\Services\Storage\StorageUsageCalculator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * The backing service for the browser-based File Manager. Every method
 * takes user-supplied relative paths, resolves them through
 * PathResolver (which throws on any attempt to escape the site's
 * directory), and operates only on the "hosting" disk.
 */
class FileManagerService
{
    private const DISK = 'hosting';

    /** Extensions considered "safe" text for the in-browser Monaco editor. */
    public const EDITABLE_EXTENSIONS = [
        'php', 'html', 'htm', 'css', 'js', 'mjs', 'json', 'env', 'txt', 'md',
        'markdown', 'xml', 'yaml', 'yml', 'sql', 'sh', 'gitignore', 'htaccess',
        'blade.php', 'vue', 'jsx', 'tsx', 'ts', 'conf',
    ];

    private PathResolver $paths;

    public function __construct(private Site $site, private StorageUsageCalculator $quota)
    {
        $this->paths = new PathResolver($site);
    }

    private function disk()
    {
        return Storage::disk(self::DISK);
    }

    public function listDirectory(string $relativePath = '', ?string $search = null, string $sortBy = 'name', string $sortDir = 'asc'): array
    {
        $absolute = $this->paths->resolve($relativePath);
        $disk = $this->disk();

        $directories = $disk->directories($absolute);
        $files = $disk->files($absolute);

        $entries = [];

        foreach ($directories as $dir) {
            $name = basename($dir);
            if ($search && ! str_contains(strtolower($name), strtolower($search))) {
                continue;
            }
            $entries[] = [
                'name' => $name,
                'type' => 'directory',
                'path' => $this->paths->toRelative($dir),
                'size' => null,
                'modified_at' => $disk->lastModified($dir),
            ];
        }

        foreach ($files as $file) {
            $name = basename($file);
            if ($search && ! str_contains(strtolower($name), strtolower($search))) {
                continue;
            }
            $entries[] = [
                'name' => $name,
                'type' => 'file',
                'path' => $this->paths->toRelative($file),
                'size' => $disk->size($file),
                'modified_at' => $disk->lastModified($file),
                'extension' => strtolower(pathinfo($name, PATHINFO_EXTENSION)),
                'editable' => $this->isEditable($name),
            ];
        }

        usort($entries, function ($a, $b) use ($sortBy, $sortDir) {
            // Directories always float to the top, like every familiar file manager.
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'directory' ? -1 : 1;
            }

            $result = match ($sortBy) {
                'size' => ($a['size'] ?? 0) <=> ($b['size'] ?? 0),
                'modified_at' => $a['modified_at'] <=> $b['modified_at'],
                default => strcasecmp($a['name'], $b['name']),
            };

            return $sortDir === 'desc' ? -$result : $result;
        });

        return $entries;
    }

    public function isEditable(string $filename): bool
    {
        $lower = strtolower($filename);

        foreach (self::EDITABLE_EXTENSIONS as $ext) {
            if (str_ends_with($lower, '.'.$ext) || $lower === $ext) {
                return true;
            }
        }

        return false;
    }

    public function read(string $relativePath): string
    {
        return $this->disk()->get($this->paths->resolve($relativePath));
    }

    public function write(string $relativePath, string $contents): void
    {
        $this->assertQuota(strlen($contents));
        $this->disk()->put($this->paths->resolve($relativePath), $contents);
    }

    public function createDirectory(string $relativePath): void
    {
        $this->disk()->makeDirectory($this->paths->resolve($relativePath));
    }

    public function createFile(string $relativePath): void
    {
        $this->disk()->put($this->paths->resolve($relativePath), '');
    }

    public function upload(string $directory, UploadedFile $file): void
    {
        $this->assertQuota($file->getSize());

        $destination = $this->paths->resolve(rtrim($directory, '/').'/'.$file->getClientOriginalName());
        $this->disk()->put($destination, fopen($file->getRealPath(), 'r'));
    }

    public function delete(string $relativePath): void
    {
        $absolute = $this->paths->resolve($relativePath);
        $disk = $this->disk();

        if (is_dir($disk->path($absolute))) {
            $disk->deleteDirectory($absolute);
        } else {
            $disk->delete($absolute);
        }
    }

    public function rename(string $relativePath, string $newName): void
    {
        $absolute = $this->paths->resolve($relativePath);
        $destination = dirname($absolute).'/'.$this->sanitizeName($newName);
        $this->disk()->move($absolute, $destination);
    }

    public function move(string $relativePath, string $destinationDirectory): void
    {
        $absolute = $this->paths->resolve($relativePath);
        $destination = $this->paths->resolve(rtrim($destinationDirectory, '/').'/'.basename($absolute));
        $this->disk()->move($absolute, $destination);
    }

    public function copy(string $relativePath, string $destinationDirectory): void
    {
        $absolute = $this->paths->resolve($relativePath);
        $destination = $this->paths->resolve(rtrim($destinationDirectory, '/').'/'.basename($absolute));
        $this->disk()->copy($absolute, $destination);
    }

    public function setPermissions(string $relativePath, string $octal): void
    {
        if (! preg_match('/^[0-7]{3,4}$/', $octal)) {
            throw new RuntimeException('Permissions must be an octal mode like 0644 or 0755.');
        }

        $path = $this->disk()->path($this->paths->resolve($relativePath));
        chmod($path, intval($octal, 8));
    }

    public function zip(array $relativePaths, string $zipRelativePath): void
    {
        $disk = $this->disk();
        $zip = new ZipArchive;
        $absoluteZipPath = $disk->path($this->paths->resolve($zipRelativePath));

        if ($zip->open($absoluteZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create zip archive.');
        }

        foreach ($relativePaths as $relativePath) {
            $absolute = $this->paths->resolve($relativePath);
            $fullPath = $disk->path($absolute);

            if (is_dir($fullPath)) {
                foreach ($disk->allFiles($absolute) as $file) {
                    $zip->addFile($disk->path($file), basename($fullPath).'/'.ltrim(str_replace($fullPath, '', $disk->path($file)), '/'));
                }
            } else {
                $zip->addFile($fullPath, basename($fullPath));
            }
        }

        $zip->close();
    }

    public function extract(string $zipRelativePath, string $destinationDirectory): void
    {
        $disk = $this->disk();
        $absoluteZipPath = $disk->path($this->paths->resolve($zipRelativePath));
        $destinationDirectory = rtrim($destinationDirectory, '/');
        $destination = $disk->path($this->paths->resolve($destinationDirectory));

        $zip = new ZipArchive;
        if ($zip->open($absoluteZipPath) !== true) {
            throw new RuntimeException('Could not open zip archive.');
        }

        $uncompressedTotal = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $this->assertSafeZipEntry($stat, $destinationDirectory);
            $uncompressedTotal += $stat['size'] ?? 0;
        }

        $this->assertQuota($uncompressedTotal);

        $zip->extractTo($destination);
        $zip->close();
    }

    /**
     * ZipArchive::extractTo() writes every entry's own path verbatim —
     * it does not go through PathResolver. Left unchecked, a crafted
     * archive could "zip-slip" out of the destination with a "../"
     * entry name, or plant a symlink whose target later lets a
     * seemingly ordinary read()/write() call reach outside the site
     * root. Every entry is validated against both before extraction is
     * allowed to proceed.
     */
    private function assertSafeZipEntry(array $stat, string $destinationDirectory): void
    {
        $name = $stat['name'] ?? '';

        if ($name === '' || str_contains($name, "\0")) {
            throw new RuntimeException('Zip archive contains an unsafe entry name.');
        }

        // Resolving the entry underneath the destination reuses
        // PathResolver's own traversal/control-character checks — it
        // throws on a ".." segment or a null byte anywhere in the path,
        // including an entry name that starts with a leading "/".
        $this->paths->resolve(trim($destinationDirectory.'/'.$name, '/'));

        // Unix external file attributes pack st_mode into the high 16
        // bits; S_IFLNK (0xA000) identifies a symlink entry. Reject it
        // outright rather than letting extractTo() materialize a link
        // that could point anywhere on disk.
        $mode = ($stat['external_attributes'] ?? 0) >> 16;

        if (($mode & 0xF000) === 0xA000) {
            throw new RuntimeException('Zip archive contains a symlink entry, which is not allowed.');
        }
    }

    private function sanitizeName(string $name): string
    {
        $name = basename($name);

        if ($name === '' || $name === '.' || $name === '..') {
            throw new RuntimeException('Invalid file name.');
        }

        return $name;
    }

    private function assertQuota(int $additionalBytes): void
    {
        if (! $this->quota->hasHeadroomFor($this->site->user, $additionalBytes)) {
            throw QuotaExceededException::storage();
        }
    }
}
