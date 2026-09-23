<?php

namespace App\Services\Provisioning;

use App\Models\GitRepository;
use App\Models\Site;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Turns a wizard's chosen project_type into the actual scaffolding
 * commands run inside the site's own directory. Every command is run
 * through ProvisionerDriver::runCommand, which enforces the same
 * CommandSanitizer allowlist as the web terminal — a recipe has no more
 * power than a user typing into their own terminal would.
 *
 * Scaffolding failures are logged and swallowed rather than failing the
 * whole provisioning job: a site with an empty document root the user
 * can still reach via the File Manager or Git is a better outcome than
 * losing the account's website slot over, say, a slow npm registry.
 */
class Recipes
{
    public function install(Site $site, ProvisionerDriver $driver, array $wizardData): void
    {
        try {
            match ($site->project_type) {
                'laravel' => $this->installLaravel($site, $driver),
                'wordpress' => $this->installWordPress($site, $driver),
                'nodejs' => $this->installPlainNode($site, $driver),
                'react' => $this->scaffoldNode($site, $driver, 'npx --yes create-react-app .'),
                'vue' => $this->scaffoldNode($site, $driver, 'npx --yes create-vue@latest . --default'),
                'nextjs' => $this->scaffoldNode($site, $driver, 'npx --yes create-next-app@latest . --yes'),
                'nuxt' => $this->scaffoldNode($site, $driver, 'npx --yes nuxi@latest init . --force'),
                'vite' => $this->scaffoldNode($site, $driver, 'npm create vite@latest . -- --template vanilla'),
                'git' => $this->importFromGit($site, $driver, $wizardData),
                'upload' => null, // The user uploads files via the File Manager next.
                default => null,  // php_empty / static_html — already seeded.
            };
        } catch (Throwable $e) {
            Log::channel('hosting')->warning("[recipes] {$site->slug} ({$site->project_type}) scaffolding failed: {$e->getMessage()}");
        }
    }

    private function installLaravel(Site $site, ProvisionerDriver $driver): void
    {
        $driver->runCommand($site, 'composer create-project laravel/laravel . --prefer-dist', timeoutSeconds: 300);
        $site->update(['document_root' => 'public']);
    }

    private function installWordPress(Site $site, ProvisionerDriver $driver): void
    {
        $driver->runCommand($site, 'wp core download --force', timeoutSeconds: 180);
    }

    private function installPlainNode(Site $site, ProvisionerDriver $driver): void
    {
        $driver->runCommand($site, 'npm init -y', timeoutSeconds: 60);
        $site->update([
            'node_install_command' => 'npm install',
            'node_start_command' => 'node index.js',
        ]);
    }

    private function scaffoldNode(Site $site, ProvisionerDriver $driver, string $scaffoldCommand): void
    {
        $driver->runCommand($site, $scaffoldCommand, timeoutSeconds: 300);
        $driver->runCommand($site, 'npm install', timeoutSeconds: 300);

        $site->update([
            'node_install_command' => 'npm install',
            'node_build_command' => 'npm run build',
            'node_start_command' => 'npm run start',
        ]);
    }

    private function importFromGit(Site $site, ProvisionerDriver $driver, array $wizardData): void
    {
        $url = $wizardData['git_url'] ?? null;
        $branch = $wizardData['git_branch'] ?? 'main';

        if (! $url) {
            return;
        }

        $driver->runCommand($site, sprintf(
            'git clone --branch %s --single-branch %s .',
            escapeshellarg($branch),
            escapeshellarg($url)
        ), timeoutSeconds: 180);

        GitRepository::create([
            'site_id' => $site->id,
            'provider' => $this->guessProvider($url),
            'url' => $url,
            'branch' => $branch,
        ]);
    }

    private function guessProvider(string $url): string
    {
        return match (true) {
            str_contains($url, 'github.com') => 'github',
            str_contains($url, 'gitlab.com') => 'gitlab',
            str_contains($url, 'bitbucket.org') => 'bitbucket',
            default => 'generic',
        };
    }
}
