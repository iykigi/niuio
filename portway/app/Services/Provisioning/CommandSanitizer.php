<?php

namespace App\Services\Provisioning;

use InvalidArgumentException;

/**
 * A defense-in-depth allowlist checked before any user-supplied command
 * string (a cron job, a Git build command, a terminal line) is handed to
 * a ProvisionerDriver. This is NOT the platform's only isolation layer —
 * on real hosting nodes the "ssh" driver additionally runs every command
 * as the account's own unprivileged system user, inside its own
 * directory, under resource limits (see docs/ARCHITECTURE.md §Isolation)
 * — but no user input reaches exec()/proc_open() without passing here
 * first, in either driver.
 */
class CommandSanitizer
{
    /**
     * Binaries a hosting account is allowed to invoke. Anything else
     * (sudo, su, chmod on paths outside the site root, systemctl, mount,
     * shutdown, useradd, iptables, docker, ...) is rejected outright.
     */
    public const ALLOWED_BINARIES = [
        'php', 'php8.1', 'php8.2', 'php8.3', 'php8.4',
        'composer', 'artisan',
        'node', 'npm', 'npx', 'yarn', 'pnpm',
        'git',
        'ls', 'cat', 'head', 'tail', 'grep', 'find', 'wc', 'pwd', 'echo',
        'mkdir', 'touch', 'cp', 'mv', 'rm', 'unzip', 'zip', 'tar',
        'mysql', 'mysqldump', 'sqlite3',
        'wp', // WordPress CLI
    ];

    /**
     * Substrings that are never permitted anywhere in a command, even
     * quoted — they indicate an attempt to escape the site's directory,
     * touch platform files, or escalate privileges.
     */
    private const FORBIDDEN_PATTERNS = [
        '/\bsudo\b/i',
        '/\bsu\b\s/i',
        '/\bchown\b/i',
        '/\bsystemctl\b/i',
        '/\bshutdown\b/i',
        '/\breboot\b/i',
        '/\buseradd\b/i',
        '/\bpasswd\b/i',
        '/\bmount\b/i',
        '/\bdocker\b/i',
        '/\biptables\b/i',
        '/\bnc\b\s+-l/i',       // netcat listener
        '/\/etc\/(passwd|shadow|sudoers)/i',
        '/(?:^|[\s\/\\\\])\.\.(?:[\s\/\\\\]|$)/', // parent-directory traversal, as a whole path segment
        '/\$\(/',                // command substitution
        '/`/',                    // backtick substitution
        '/<\(|>\(/',              // process substitution
        '/\brm\s+-rf\s+\/(?!\S)/', // rm -rf / (root)
        '/>\s*\/dev\/sd/i',
        '/(?:^|[\s"\'])~(?:[\/\s"\']|$)/', // shell tilde-expansion to another user's home dir
        '/(?:^|[\s<>])\/(?!dev\/null\b)/', // absolute path anywhere outside the sandboxed cwd (except /dev/null)
        '/\bext::/i',              // git "ext" remote helper — runs an arbitrary local command
        '/\bfd::/i',               // git "fd" remote helper — reads an arbitrary file descriptor
        '/\bfile:\/\//i',          // local-file transport — arbitrary local file read via git/curl-style tools
    ];

    public static function assertSafe(string $command): void
    {
        $command = trim($command);

        if ($command === '') {
            throw new InvalidArgumentException('Command cannot be empty.');
        }

        foreach (self::FORBIDDEN_PATTERNS as $pattern) {
            if (preg_match($pattern, $command)) {
                throw new InvalidArgumentException("Command rejected: contains a disallowed pattern ({$pattern}).");
            }
        }

        foreach (self::splitIntoSegments($command) as $segment) {
            if ($segment === '') {
                continue;
            }

            $binary = self::firstToken($segment);

            // A segment that doesn't start with a plain program name —
            // "(whoami)", "{ whoami; }", "\"whoami\"", "\\whoami" — must be
            // rejected, not skipped: the shell still runs whatever is inside.
            if ($binary === '') {
                throw new InvalidArgumentException('Command rejected: start each command with the name of an allowed program.');
            }

            if (! in_array($binary, self::ALLOWED_BINARIES, true)) {
                throw new InvalidArgumentException("Command rejected: \"{$binary}\" is not an allowed program.");
            }
        }
    }

    public static function isSafe(string $command): bool
    {
        try {
            self::assertSafe($command);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Naively splits on shell chaining operators so each stage's leading
     * binary can be checked. This is intentionally conservative — it is
     * one guardrail among several, not a shell parser.
     *
     * Includes a single "&" (background) and raw newlines/carriage
     * returns: a shell treats both as statement separators exactly like
     * ";", and leaving them out of this list used to let an unchecked
     * second command ride along after an allowlisted first one — e.g.
     * "ls & rm -rf ./somewhere" only ever had "ls" validated.
     */
    private static function splitIntoSegments(string $command): array
    {
        $segments = preg_split('/&&|\|\||;|\||&|\r\n|\r|\n/', $command) ?: [$command];

        return array_map('trim', $segments);
    }

    private static function firstToken(string $segment): string
    {
        $segment = trim($segment);

        if ($segment === '') {
            return '';
        }

        // The whole first word must be a plain name/path; anything else in
        // it (quotes, parentheses, braces, backslashes, "VAR=value") makes
        // the program the shell will actually run impossible to tell here.
        if (! preg_match('/^([A-Za-z0-9_.\/-]+)(?:\s|$)/', $segment, $matches)) {
            return '';
        }

        $token = $matches[1];

        // Allow "php artisan migrate" style invocations and paths like
        // ./vendor/bin/pest by comparing only the basename.
        return basename($token);
    }
}
