<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // No asset build exists in CI or on a fresh checkout (public/build is
        // gitignored), and every layout calls @vite(...). Without this, any
        // test that renders a page dies on ViteManifestNotFoundException
        // rather than on anything the test is actually about.
        $this->withoutVite();
    }

    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }
}
