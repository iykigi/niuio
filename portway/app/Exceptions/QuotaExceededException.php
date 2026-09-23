<?php

namespace App\Exceptions;

use RuntimeException;

class QuotaExceededException extends RuntimeException
{
    public static function storage(): self
    {
        return new self('You have reached your storage quota. Free up space or remove unused files before continuing.');
    }

    public static function websiteLimit(int $limit): self
    {
        return new self("You've reached your limit of {$limit} websites on this account.");
    }

    public static function databaseLimit(int $limit): self
    {
        return new self("You've reached your limit of {$limit} databases on this account.");
    }

    public static function domainLimit(int $limit): self
    {
        return new self("You've reached your limit of {$limit} domains on this account.");
    }

    public static function backupLimit(int $limit): self
    {
        return new self("You've reached your limit of {$limit} backups for this website. Delete an old backup first.");
    }

    public static function cronJobLimit(int $limit): self
    {
        return new self("You've reached your limit of {$limit} cron jobs on this account.");
    }
}
