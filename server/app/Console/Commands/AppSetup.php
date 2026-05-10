<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use PDO;
use PDOException;

#[Signature('app:setup')]
#[Description('Create the database, run migrations, seed boards, and link storage in one step')]
class AppSetup extends Command
{
    public function handle(): int
    {
        $this->line('');
        $this->line(' <fg=yellow>███╗   ███╗███████╗███╗   ███╗███████╗</>');
        $this->line(' <fg=yellow>████╗ ████║██╔════╝████╗ ████║██╔════╝</>');
        $this->line(' <fg=yellow>██╔████╔██║█████╗  ██╔████╔██║█████╗  </>');
        $this->line(' <fg=yellow>██║╚██╔╝██║██╔══╝  ██║╚██╔╝██║██╔══╝  </>');
        $this->line(' <fg=yellow>██║ ╚═╝ ██║███████╗██║ ╚═╝ ██║███████╗</>');
        $this->line(' <fg=yellow>╚═╝     ╚═╝╚══════╝╚═╝     ╚═╝╚══════╝</>');
        $this->line(' <fg=gray>  Museum  ·  First-time setup</>');
        $this->line('');

        $this->components->task('Create database', fn () => $this->createDatabase());

        $this->components->task('Run migrations', function () {
            $this->callSilently('migrate', ['--force' => true]);
        });

        $this->components->task('Seed boards', function () {
            $this->callSilently('db:seed', ['--force' => true]);
        });

        $this->components->task('Link storage', function () {
            $this->callSilently('storage:link');
        });

        $this->line('');
        $this->components->info('Setup complete.');
        $this->line(' Start the server with: <fg=cyan>php artisan serve</>');
        $this->line('');

        return self::SUCCESS;
    }

    private function createDatabase(): bool
    {
        $cfg    = config('database.connections.pgsql');
        $dbName = $cfg['database'];

        try {
            // connect to the postgres DB to issue CREATE DATABASE
            $pdo = new PDO(
                "pgsql:host={$cfg['host']};port={$cfg['port']};dbname=postgres",
                $cfg['username'],
                $cfg['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );

            $exists = $pdo
                ->query("SELECT 1 FROM pg_database WHERE datname = " . $pdo->quote($dbName))
                ->fetchColumn();

            if ($exists) {
                return true;
            }

            $pdo->exec('CREATE DATABASE ' . '"' . addslashes($dbName) . '"');
            return true;

        } catch (PDOException $e) {
            $this->newLine();
            $this->components->warn(
                "Could not auto-create the database: {$e->getMessage()}\n" .
                "  Create it manually:  psql -U {$cfg['username']} -c 'CREATE DATABASE \"{$dbName}\";'\n" .
                "  Then re-run:         php artisan app:setup"
            );
            return false;
        }
    }
}
