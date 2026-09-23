<?php

use App\Models\Site;
use App\Services\Files\PathResolver;

function makeResolvableSite(): Site
{
    $site = new Site;
    $site->forceFill(['user_id' => 42, 'slug' => 'demo-site']);

    return $site;
}

it('resolves a relative path underneath the site root', function () {
    $resolver = new PathResolver(makeResolvableSite());

    expect($resolver->resolve('public/index.php'))->toBe('42/demo-site/public/index.php');
    expect($resolver->resolve(''))->toBe('42/demo-site');
});

it('normalizes redundant separators and current-directory segments', function () {
    $resolver = new PathResolver(makeResolvableSite());

    expect($resolver->resolve('./public//index.php'))->toBe('42/demo-site/public/index.php');
});

it('throws when a path attempts to traverse above the site root', function () {
    $resolver = new PathResolver(makeResolvableSite());

    expect(fn () => $resolver->resolve('../../etc/passwd'))
        ->toThrow(InvalidArgumentException::class);
});

it('throws when a path contains a traversal segment anywhere in the middle', function () {
    $resolver = new PathResolver(makeResolvableSite());

    expect(fn () => $resolver->resolve('public/../../secrets'))
        ->toThrow(InvalidArgumentException::class);
});

it('throws on control characters in a path segment', function () {
    $resolver = new PathResolver(makeResolvableSite());

    expect(fn () => $resolver->resolve("public/\0hidden"))
        ->toThrow(InvalidArgumentException::class);
});

it('converts an absolute disk path back to a path relative to the site root', function () {
    $resolver = new PathResolver(makeResolvableSite());

    expect($resolver->toRelative('42/demo-site/public/index.php'))->toBe('public/index.php');
});
