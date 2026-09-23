<?php

namespace App\Services\Provisioning;

use App\Models\Backup;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Domain;
use App\Models\Server;
use App\Models\Site;
use App\Models\SslCertificate;

/**
 * Every action that touches real hosting infrastructure goes through this
 * contract. The control panel (controllers, Livewire components, jobs)
 * never shells out, writes to a vhost file, or opens a raw database
 * connection directly — it always calls a driver method, so swapping
 * "local" for "ssh" (App\Services\Provisioning\Drivers\SshProvisionerDriver)
 * in config/portway.php is the only thing that changes when the panel
 * starts managing real Linux hosting nodes instead of running locally.
 *
 * Every method is expected to be idempotent where practical (safe to
 * retry after a job failure) and must never accept raw, unescaped user
 * input into a shell command — see CommandSanitizer.
 */
interface ProvisionerDriver
{
    // --- Filesystem ------------------------------------------------------

    /**
     * A point-in-time health snapshot for the admin Server Health cards
     * and ServerMetric history:
     * ['cpu_percent','memory_percent','disk_percent','load_1m','load_5m',
     *  'load_15m','network_rx_bytes','network_tx_bytes','service_status' => [...]].
     */
    public function collectServerMetrics(Server $server): array;

    public function createSiteDirectory(Site $site): void;

    public function deleteSiteDirectory(Site $site): void;

    public function diskUsageBytes(Site $site): int;

    // --- Web server / runtime --------------------------------------------

    public function writeVirtualHost(Site $site): void;

    public function removeVirtualHost(Site $site): void;

    public function setPhpVersion(Site $site, string $version): void;

    public function reloadWebServer(Site $site): void;

    public function writeEnvFile(Site $site, array $variables): void;

    public function startNodeProcess(Site $site): void;

    public function stopNodeProcess(Site $site): void;

    public function restartNodeProcess(Site $site): void;

    /**
     * Execute a single allowlisted command with the site's directory as
     * the working directory (used by cron, deployments and the web
     * terminal). Returns ['exit_code' => int, 'output' => string].
     */
    public function runCommand(Site $site, string $command, int $timeoutSeconds = 60): array;

    // --- DNS ---------------------------------------------------------------

    /**
     * Resolve the live DNS records for a hostname and compare them
     * against what Portway expects, returning a normalized report:
     * ['a' => [...], 'cname' => [...], 'matches_expected' => bool].
     */
    public function checkDnsPropagation(Domain $domain): array;

    public function applyDnsRecord(Domain $domain, string $type, string $name, string $content, int $ttl): void;

    public function removeDnsRecord(Domain $domain, string $type, string $name): void;

    // --- SSL -----------------------------------------------------------------

    public function issueSslCertificate(SslCertificate $certificate): void;

    public function revokeSslCertificate(SslCertificate $certificate): void;

    // --- Databases -----------------------------------------------------------

    public function createDatabase(Database $database): void;

    public function deleteDatabase(Database $database): void;

    public function createDatabaseUser(DatabaseUser $databaseUser, Database $database): void;

    public function deleteDatabaseUser(DatabaseUser $databaseUser): void;

    public function updateDatabaseUserPassword(DatabaseUser $databaseUser, string $plainPassword): void;

    public function databaseSizeBytes(Database $database): int;

    // --- Backups -------------------------------------------------------------

    /**
     * Create a backup archive and return its path relative to the
     * "backups" disk.
     */
    public function createBackupArchive(Backup $backup): string;

    public function restoreBackupArchive(Backup $backup): void;
}
