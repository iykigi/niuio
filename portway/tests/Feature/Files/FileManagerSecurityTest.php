<?php

use App\Exceptions\QuotaExceededException;
use App\Models\Site;
use App\Models\User;
use App\Services\Files\FileManagerService;
use App\Services\Storage\StorageUsageCalculator;
use Illuminate\Support\Facades\Storage;

function fileManagerFor(Site $site): FileManagerService
{
    return new FileManagerService($site, app(StorageUsageCalculator::class));
}

beforeEach(function () {
    Storage::fake('hosting');
});

it('writes and reads a file scoped underneath the website\'s own directory', function () {
    $site = Site::factory()->create();
    $manager = fileManagerFor($site);

    $manager->write('public/index.php', '<?php echo "hello";');

    expect($manager->read('public/index.php'))->toBe('<?php echo "hello";');
    Storage::disk('hosting')->assertExists($site->rootPath().'/public/index.php');
});

it('never lets a relative path escape the website\'s own root directory', function () {
    $site = Site::factory()->create();
    $manager = fileManagerFor($site);

    expect(fn () => $manager->write('../../other-user/secrets.php', 'x'))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $manager->read('../../../etc/passwd'))
        ->toThrow(InvalidArgumentException::class);
});

it('keeps two websites\' files completely isolated from each other', function () {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();

    fileManagerFor($siteA)->write('public/secret.txt', 'only for A');

    Storage::disk('hosting')->assertExists($siteA->rootPath().'/public/secret.txt');
    Storage::disk('hosting')->assertMissing($siteB->rootPath().'/public/secret.txt');

    // B has no way to address A's file through its own resolver, even if
    // it tries to guess the relative path.
    expect(fileManagerFor($siteB)->listDirectory(''))->toBe([]);
});

it('refuses to write a file that would push the account over its storage quota', function () {
    $user = User::factory()->create(['storage_quota_mb' => 1]); // 1 MB quota
    $site = Site::factory()->create([
        'user_id' => $user->id,
        'disk_usage_bytes' => 1048000, // already almost at quota
    ]);

    $manager = fileManagerFor($site);

    $oversized = str_repeat('a', 2048); // pushes well past the remaining headroom

    expect(fn () => $manager->write('public/big.txt', $oversized))
        ->toThrow(QuotaExceededException::class);
});

it('deletes a file and a directory correctly', function () {
    $site = Site::factory()->create();
    $manager = fileManagerFor($site);

    $manager->write('public/temp.txt', 'bye');
    $manager->createDirectory('public/assets');

    $manager->delete('public/temp.txt');
    $manager->delete('public/assets');

    Storage::disk('hosting')->assertMissing($site->rootPath().'/public/temp.txt');
    Storage::disk('hosting')->assertDirectoryEmpty($site->rootPath().'/public');
});
