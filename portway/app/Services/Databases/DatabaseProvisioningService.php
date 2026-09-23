<?php

namespace App\Services\Databases;

use App\Exceptions\QuotaExceededException;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Site;
use App\Models\User;
use App\Services\Provisioning\ProvisionerDriver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseProvisioningService
{
    public function __construct(private ProvisionerDriver $driver)
    {
    }

    public function create(User $user, string $label, ?Site $site = null): array
    {
        if ($user->databases()->count() >= $user->max_databases) {
            throw QuotaExceededException::databaseLimit($user->max_databases);
        }

        return DB::transaction(function () use ($user, $label, $site) {
            $name = $this->uniqueDatabaseName($user, $label);

            $database = Database::create([
                'user_id' => $user->id,
                'site_id' => $site?->id,
                'server_id' => $site?->server_id,
                'name' => $name,
                'status' => 'provisioning',
            ]);

            $this->driver->createDatabase($database);

            $username = 'pw_'.Str::lower(Str::random(10));
            $plainPassword = Str::password(20);

            $databaseUser = DatabaseUser::create([
                'database_id' => $database->id,
                'username' => $username,
                'password' => $plainPassword,
                'privileges' => ['ALL'],
            ]);

            $this->driver->createDatabaseUser($databaseUser, $database);

            $database->update(['status' => 'active']);

            return ['database' => $database, 'user' => $databaseUser, 'plain_password' => $plainPassword];
        });
    }

    public function delete(Database $database): void
    {
        foreach ($database->databaseUsers as $databaseUser) {
            $this->driver->deleteDatabaseUser($databaseUser);
        }

        $this->driver->deleteDatabase($database);
        $database->delete();
    }

    public function regeneratePassword(DatabaseUser $databaseUser): string
    {
        $plainPassword = Str::password(20);
        $this->driver->updateDatabaseUserPassword($databaseUser, $plainPassword);
        $databaseUser->update(['password' => $plainPassword]);

        return $plainPassword;
    }

    public function refreshSize(Database $database): void
    {
        $database->update([
            'size_bytes' => $this->driver->databaseSizeBytes($database),
            'size_calculated_at' => now(),
        ]);
    }

    private function uniqueDatabaseName(User $user, string $label): string
    {
        $base = 'pw_'.$user->id.'_'.Str::slug($label, '_');
        $base = substr($base, 0, 50);
        $name = $base;
        $suffix = 1;

        while (Database::withTrashed()->where('name', $name)->exists()) {
            $name = $base.'_'.$suffix++;
        }

        return $name;
    }
}
