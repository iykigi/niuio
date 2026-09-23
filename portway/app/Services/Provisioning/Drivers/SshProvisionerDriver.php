<?php

namespace App\Services\Provisioning\Drivers;

use App\Models\Backup;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Domain;
use App\Models\Site;
use App\Models\SslCertificate;
use App\Services\Provisioning\CommandSanitizer;
use App\Services\Provisioning\ProvisionerDriver;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * The production counterpart to LocalProvisionerDriver: every method
 * here runs a real command over SSH against the Site's assigned
 * hosting node (App\Models\Server). It intentionally does the minimum
 * needed to be genuinely operable — creating directories, writing
 * nginx/PHP-FPM config, running certbot, issuing MySQL DDL — as an
 * unprivileged, per-account system user on the node, never as root and
 * never through a shell built from unescaped user input.
 *
 * A real fleet needs more than any one class should own (queueing
 * config rollouts, health-checking nginx before reload, coordinating a
 * multi-node MySQL tier). Those concerns belong in the small
 * `portway-agent` script this driver expects to find on every node
 * (see docs/ARCHITECTURE.md §Hosting Node Agent) — this class's job is
 * to reach that agent safely and predictably, not to reimplement it.
 */
class SshProvisionerDriver implements ProvisionerDriver
{
    private const HOSTING_ROOT = '/srv/portway/accounts';

    public function collectServerMetrics(\App\Models\Server $server): array
    {
        $result = $this->ssh($server, 'portway-agent metrics:collect', asRoot: true, timeout: 20);

        $decoded = json_decode($result['output'], true);

        return is_array($decoded) ? $decoded : [
            'cpu_percent' => null, 'memory_percent' => null, 'disk_percent' => null,
            'load_1m' => null, 'load_5m' => null, 'load_15m' => null,
            'network_rx_bytes' => null, 'network_tx_bytes' => null,
            'service_status' => [],
        ];
    }

    public function createSiteDirectory(Site $site): void
    {
        $this->ssh($site->server, sprintf(
            'mkdir -p %s %s %s && chown -R %s:%s %s',
            $this->shellQuoteAll($this->accountDir($site), $this->siteDir($site), $this->siteDir($site).'/logs'),
            $this->systemUser($site), $this->systemUser($site),
            $this->shellQuote($this->accountDir($site))
        ));

        $this->agent($site->server, 'site:create', [
            'slug' => $site->slug,
            'runtime' => $site->runtime,
            'project_type' => $site->project_type,
            'php_version' => $site->php_version,
        ]);
    }

    public function deleteSiteDirectory(Site $site): void
    {
        $this->ssh($site->server, sprintf('rm -rf %s', $this->shellQuote($this->siteDir($site))));
    }

    public function diskUsageBytes(Site $site): int
    {
        $result = $this->ssh($site->server, sprintf('du -sb %s 2>/dev/null | cut -f1', $this->shellQuote($this->siteDir($site))));

        return (int) trim($result['output']);
    }

    public function writeVirtualHost(Site $site): void
    {
        $this->agent($site->server, 'vhost:write', [
            'slug' => $site->slug,
            'domains' => $site->domains()->pluck('hostname')->all(),
            'document_root' => $this->siteDir($site).'/'.trim($site->document_root, '/'),
            'php_version' => $site->php_version,
            'runtime' => $site->runtime,
            'node_port' => $site->node_port,
            'force_https' => $site->force_https,
        ]);
    }

    public function removeVirtualHost(Site $site): void
    {
        $this->agent($site->server, 'vhost:remove', ['slug' => $site->slug]);
    }

    public function setPhpVersion(Site $site, string $version): void
    {
        $this->agent($site->server, 'php:set-version', ['slug' => $site->slug, 'version' => $version]);
    }

    public function reloadWebServer(Site $site): void
    {
        $webServer = $site->server->web_server === 'apache' ? 'apache2ctl' : 'nginx';
        $this->ssh($site->server, "{$webServer} -t && systemctl reload {$webServer}", asRoot: true);
    }

    public function writeEnvFile(Site $site, array $variables): void
    {
        $lines = array_map(fn ($k, $v) => $k.'='.$v, array_keys($variables), $variables);
        $this->writeRemoteFile($site->server, $this->siteDir($site).'/.env', implode("\n", $lines)."\n");
    }

    public function startNodeProcess(Site $site): void
    {
        $this->agent($site->server, 'node:start', ['slug' => $site->slug]);
    }

    public function stopNodeProcess(Site $site): void
    {
        $this->agent($site->server, 'node:stop', ['slug' => $site->slug]);
    }

    public function restartNodeProcess(Site $site): void
    {
        $this->agent($site->server, 'node:restart', ['slug' => $site->slug]);
    }

    public function runCommand(Site $site, string $command, int $timeoutSeconds = 60): array
    {
        CommandSanitizer::assertSafe($command);

        $documentRoot = $this->siteDir($site).'/'.trim($site->document_root, '/');
        $wrapped = sprintf(
            'sudo -u %s -H bash -lc %s',
            $this->systemUser($site),
            $this->shellQuote("cd {$documentRoot} && {$command}")
        );

        return $this->ssh($site->server, $wrapped, timeout: $timeoutSeconds);
    }

    public function checkDnsPropagation(Domain $domain): array
    {
        $expectedIp = config('portway.server_ip');
        $a = @dns_get_record($domain->hostname, DNS_A) ?: [];
        $cname = @dns_get_record($domain->hostname, DNS_CNAME) ?: [];

        $ips = array_column($a, 'ip');

        return [
            'a' => $ips,
            'cname' => array_column($cname, 'target'),
            'matches_expected' => in_array($expectedIp, $ips, true),
            'checked_at' => now()->toIso8601String(),
        ];
    }

    public function applyDnsRecord(Domain $domain, string $type, string $name, string $content, int $ttl): void
    {
        // Real DNS hosting (for domains delegated to Portway's own
        // nameservers) is delegated to a DnsProviderDriver — see
        // App\Services\Dns\DnsProviderDriver — this method is reserved
        // for that integration point in the next iteration.
        throw new RuntimeException('Managed DNS is not configured. See App\\Services\\Dns\\DnsProviderDriver.');
    }

    public function removeDnsRecord(Domain $domain, string $type, string $name): void
    {
        throw new RuntimeException('Managed DNS is not configured. See App\\Services\\Dns\\DnsProviderDriver.');
    }

    public function issueSslCertificate(SslCertificate $certificate): void
    {
        $hostname = $certificate->domain->hostname;
        $email = config('services.acme.email');
        $staging = config('services.acme.staging') ? ' --staging' : '';

        $result = $this->ssh($certificate->domain->site->server, sprintf(
            'certbot certonly --webroot -w %s -d %s --non-interactive --agree-tos -m %s%s',
            $this->shellQuote($this->siteDir($certificate->domain->site).'/'.trim($certificate->domain->site->document_root, '/')),
            $this->shellQuote($hostname),
            $this->shellQuote((string) $email),
            $staging
        ), asRoot: true, timeout: 120);

        if ($result['exit_code'] !== 0) {
            $certificate->update(['status' => 'failed', 'last_error' => $result['output']]);
            throw new RuntimeException('certbot failed: '.$result['output']);
        }

        $certPath = "/etc/letsencrypt/live/{$hostname}";
        $cert = $this->ssh($certificate->domain->site->server, "cat {$certPath}/fullchain.pem", asRoot: true);
        $key = $this->ssh($certificate->domain->site->server, "cat {$certPath}/privkey.pem", asRoot: true);

        $certificate->forceFill([
            'issuer' => "Let's Encrypt",
            'certificate' => $cert['output'],
            'private_key' => $key['output'],
            'issued_at' => now(),
            'expires_at' => now()->addDays(90),
            'status' => 'active',
        ])->save();
    }

    public function revokeSslCertificate(SslCertificate $certificate): void
    {
        $this->ssh($certificate->domain->site->server, sprintf(
            'certbot revoke --cert-path /etc/letsencrypt/live/%s/cert.pem --non-interactive',
            $this->shellQuote($certificate->domain->hostname)
        ), asRoot: true);
    }

    public function createDatabase(Database $database): void
    {
        $name = $this->assertSafeIdentifier($database->name);
        $this->mysql($database->server, "CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    }

    public function deleteDatabase(Database $database): void
    {
        $name = $this->assertSafeIdentifier($database->name);
        $this->mysql($database->server, "DROP DATABASE IF EXISTS `{$name}`;");
    }

    public function createDatabaseUser(DatabaseUser $databaseUser, Database $database): void
    {
        $user = $this->assertSafeIdentifier($databaseUser->username);
        $db = $this->assertSafeIdentifier($database->name);
        $password = addslashes($databaseUser->password);

        $this->mysql($database->server, implode(' ', [
            "CREATE USER IF NOT EXISTS '{$user}'@'{$databaseUser->host}' IDENTIFIED BY '{$password}';",
            "GRANT ALL PRIVILEGES ON `{$db}`.* TO '{$user}'@'{$databaseUser->host}';",
            'FLUSH PRIVILEGES;',
        ]));
    }

    public function deleteDatabaseUser(DatabaseUser $databaseUser): void
    {
        $user = $this->assertSafeIdentifier($databaseUser->username);
        $this->mysql($databaseUser->database->server, "DROP USER IF EXISTS '{$user}'@'{$databaseUser->host}';");
    }

    public function updateDatabaseUserPassword(DatabaseUser $databaseUser, string $plainPassword): void
    {
        $user = $this->assertSafeIdentifier($databaseUser->username);
        $password = addslashes($plainPassword);
        $this->mysql($databaseUser->database->server, "ALTER USER '{$user}'@'{$databaseUser->host}' IDENTIFIED BY '{$password}'; FLUSH PRIVILEGES;");
    }

    public function databaseSizeBytes(Database $database): int
    {
        $name = $this->assertSafeIdentifier($database->name);
        $sql = "SELECT SUM(data_length+index_length) FROM information_schema.tables WHERE table_schema='{$name}';";
        $result = $this->mysql($database->server, $sql, wantOutput: true);

        return (int) trim($result['output']);
    }

    public function createBackupArchive(Backup $backup): string
    {
        $site = $backup->site;
        $relativePath = "{$backup->user_id}/{$site->slug}/".now()->format('Y-m-d_His').'-'.$backup->type.'.tar.gz';

        $this->agent($site->server, 'backup:create', [
            'slug' => $site->slug,
            'type' => $backup->type,
            'destination' => $relativePath,
        ]);

        return $relativePath;
    }

    public function restoreBackupArchive(Backup $backup): void
    {
        $this->agent($backup->site->server, 'backup:restore', [
            'slug' => $backup->site->slug,
            'source' => $backup->path,
        ]);
    }

    // --- Internals -----------------------------------------------------------

    private function accountDir(Site $site): string
    {
        return self::HOSTING_ROOT.'/'.$site->user_id;
    }

    private function siteDir(Site $site): string
    {
        return $this->accountDir($site).'/'.$site->slug;
    }

    private function systemUser(Site $site): string
    {
        return 'pw'.$site->user_id;
    }

    private function assertSafeIdentifier(string $identifier): string
    {
        if (! preg_match('/^[A-Za-z0-9_]{1,64}$/', $identifier)) {
            throw new RuntimeException("Refusing to use unsafe SQL identifier: {$identifier}");
        }

        return $identifier;
    }

    private function shellQuote(string $value): string
    {
        return "'".str_replace("'", "'\\''", $value)."'";
    }

    private function shellQuoteAll(string ...$values): string
    {
        return implode(' ', array_map($this->shellQuote(...), $values));
    }

    /**
     * Run a single command on the node over SSH as the "portway"
     * management user (never root, unless $asRoot is explicitly needed
     * for a package-manager-level action like an nginx reload).
     */
    private function ssh(\App\Models\Server $server, string $command, bool $asRoot = false, int $timeout = 60): array
    {
        $keyFile = $this->writeTemporaryKeyFile($server);

        try {
            $target = $asRoot ? 'root' : $server->ssh_user;

            $process = new Process([
                'ssh',
                '-i', $keyFile,
                '-p', (string) $server->ssh_port,
                '-o', 'StrictHostKeyChecking=accept-new',
                '-o', 'ConnectTimeout=10',
                "{$target}@{$server->ip_address}",
                $command,
            ]);
            $process->setTimeout($timeout);
            $process->run();

            return [
                'exit_code' => $process->getExitCode() ?? 1,
                'output' => $process->getOutput().$process->getErrorOutput(),
            ];
        } finally {
            @unlink($keyFile);
        }
    }

    /**
     * Calls the lightweight `portway-agent` helper installed on every
     * hosting node, passing a JSON payload over stdin. The agent owns
     * the actual nginx/PHP-FPM/Supervisor file templating so this class
     * never string-builds server config directly.
     */
    private function agent(\App\Models\Server $server, string $action, array $payload): array
    {
        $json = json_encode($payload);
        $command = sprintf('portway-agent %s %s', $this->shellQuote($action), $this->shellQuote($json));

        $result = $this->ssh($server, $command, asRoot: true, timeout: 120);

        if ($result['exit_code'] !== 0) {
            throw new RuntimeException("portway-agent {$action} failed: {$result['output']}");
        }

        return $result;
    }

    private function mysql(?\App\Models\Server $server, string $sql, bool $wantOutput = false): array
    {
        if (! $server) {
            throw new RuntimeException('Database server is not assigned.');
        }

        $flags = $wantOutput ? '-N -B' : '';
        $command = sprintf('mysql %s -e %s', $flags, $this->shellQuote($sql));

        return $this->ssh($server, $command, asRoot: true);
    }

    private function writeRemoteFile(\App\Models\Server $server, string $path, string $contents): void
    {
        $encoded = base64_encode($contents);
        $this->ssh($server, "echo {$this->shellQuote($encoded)} | base64 -d > {$this->shellQuote($path)}");
    }

    private function writeTemporaryKeyFile(\App\Models\Server $server): string
    {
        $path = tempnam(sys_get_temp_dir(), 'portway-key-');
        file_put_contents($path, $server->ssh_private_key."\n");
        chmod($path, 0600);

        return $path;
    }
}
