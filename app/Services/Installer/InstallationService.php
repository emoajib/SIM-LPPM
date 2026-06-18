<?php

declare(strict_types=1);

namespace App\Services\Installer;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class InstallationService
{
    private const LOCK_FILE = 'app/.installed';

    private const ENV_BACKUP_PREFIX = '.env.backup.';

    private const PROGRESS_CACHE_KEY = 'installer_progress';

    private const CONFIG_CACHE_KEY = 'installer_config';

    private const RUNNING_CACHE_KEY = 'installer_running';

    public function isInstalled(): bool
    {
        // Check lock file
        if (File::exists($this->getLockFilePath())) {
            return true;
        }

        // Check if users table exists and has records
        try {
            if (! Schema::hasTable('users')) {
                return false;
            }
            $userCount = DB::table('users')->count();

            return $userCount > 0;
        } catch (Exception) {
            return false;
        }
    }

    public function getInstallationStatus(): array
    {
        $status = [
            'env_exists' => File::exists(base_path('.env')),
            'env_example_exists' => File::exists(base_path('.env.example')),
            'key_generated' => ! empty(config('app.key')),
            'database_connected' => false,
            'migrations_run' => false,
            'users_exist' => false,
            'storage_linked' => File::exists(public_path('storage')),
        ];

        // Check database connection
        try {
            DB::connection()->getPdo();
            $status['database_connected'] = true;

            // Check if migrations table exists
            if (Schema::hasTable('migrations')) {
                $status['migrations_run'] = true;
            }

            // Check if users exist
            if (Schema::hasTable('users')) {
                $status['users_exist'] = DB::table('users')->count() > 0;
            }
        } catch (Exception) {
            // Leave defaults
        }

        return $status;
    }

    /**
     * Store installation progress in cache for real-time updates.
     */
    public function storeProgress(int $percent, string $message, ?string $error = null, bool $complete = false): void
    {
        Cache::put(self::PROGRESS_CACHE_KEY, [
            'percent' => $percent,
            'message' => $message,
            'error' => $error,
            'complete' => $complete,
            'updated_at' => now()->timestamp,
        ], 300); // 5 minutes TTL
    }

    /**
     * Get installation progress from cache.
     */
    public function getProgress(): array
    {
        return Cache::get(self::PROGRESS_CACHE_KEY, [
            'percent' => 0,
            'message' => '',
            'error' => null,
            'complete' => false,
            'updated_at' => null,
        ]);
    }

    /**
     * Clear installation progress from cache.
     */
    public function clearProgress(): void
    {
        Cache::forget(self::PROGRESS_CACHE_KEY);
    }

    /**
     * Store installation config in cache for deferred execution.
     */
    public function storeInstallationConfig(array $config): void
    {
        Cache::put(self::CONFIG_CACHE_KEY, $config, 300); // 5 minutes TTL
    }

    /**
     * Get installation config from cache.
     */
    public function getInstallationConfig(): array
    {
        return Cache::get(self::CONFIG_CACHE_KEY, []);
    }

    /**
     * Clear installation config from cache.
     */
    public function clearInstallationConfig(): void
    {
        Cache::forget(self::CONFIG_CACHE_KEY);
    }

    /**
     * Check if installation is currently running.
     */
    public function isInstallationRunning(): bool
    {
        return Cache::get(self::RUNNING_CACHE_KEY, false);
    }

    /**
     * Mark installation as running.
     */
    public function markInstallationRunning(): void
    {
        Cache::put(self::RUNNING_CACHE_KEY, true, 600); // 10 minutes TTL
    }

    /**
     * Mark installation as stopped.
     */
    public function markInstallationStopped(): void
    {
        Cache::forget(self::RUNNING_CACHE_KEY);
    }

    public function runInstallation(array $config, callable $onProgress, ?callable $onStep = null): void
    {
        $steps = [
            ['name' => 'backup_env', 'label' => 'Backing up existing configuration...', 'weight' => 5],
            ['name' => 'write_env', 'label' => 'Writing environment file...', 'weight' => 5],
            ['name' => 'clear_config', 'label' => 'Clearing configuration cache...', 'weight' => 2],
            ['name' => 'generate_key', 'label' => 'Generating application key...', 'weight' => 5],
            ['name' => 'run_migrations', 'label' => 'Running database migrations...', 'weight' => 50],
            ['name' => 'run_seeders', 'label' => 'Seeding master data...', 'weight' => 25],
            ['name' => 'create_admin', 'label' => 'Creating admin account...', 'weight' => 5],
            ['name' => 'storage_link', 'label' => 'Creating storage link...', 'weight' => 3],
            ['name' => 'finalize', 'label' => 'Finalizing installation...', 'weight' => 2],
        ];

        $totalWeight = array_sum(array_column($steps, 'weight'));
        $currentWeight = 0;

        foreach ($steps as $step) {
            $percent = $this->calculatePercent($currentWeight, $totalWeight);
            $onProgress($percent, $step['label']);
            $this->storeProgress($percent, $step['label']);

            $runStep = function () use ($step, $config, $currentWeight, $totalWeight) {
                match ($step['name']) {
                    'backup_env' => $this->backupEnvFile(),
                    'write_env' => $this->writeEnvFile($config),
                    'clear_config' => $this->clearConfigCache(),
                    'generate_key' => $this->generateKey(),
                    'run_migrations' => $this->runMigrations($currentWeight, $totalWeight, $step['weight']),
                    'run_seeders' => $this->runSeeders($currentWeight, $totalWeight, $step['weight'], $config),
                    'create_admin' => $this->createAdminUser($config['admin'] ?? []),
                    'storage_link' => $this->createStorageLink(),
                    'finalize' => $this->finalizeInstallation(),
                };
            };

            if ($onStep !== null) {
                $onStep($step, $runStep);
            } else {
                $runStep();
            }

            $currentWeight += $step['weight'];
        }

        $onProgress(100, 'Installation complete!');
        $this->storeProgress(100, 'Installation complete!', null, true);
    }

    private function backupEnvFile(): void
    {
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $backupPath = base_path(self::ENV_BACKUP_PREFIX.now()->format('Y-m-d-His'));
            File::copy($envPath, $backupPath);
        }
    }

    private function writeEnvFile(array $config): void
    {
        $writer = new EnvironmentWriter;
        $writer->write($config);
    }

    private function generateKey(): void
    {
        $envPath = base_path('.env');

        // Ensure .env file exists
        if (! File::exists($envPath)) {
            throw new Exception('.env file not found. Cannot generate application key.');
        }

        // Reload .env file to ensure we have latest values
        $this->reloadEnvFile();

        // Generate a new key manually and write it to .env
        $key = 'base64:'.base64_encode(random_bytes(32));

        // Read current .env content
        $content = File::get($envPath);

        // Replace APP_KEY line
        if (preg_match('/^APP_KEY=.*/m', $content)) {
            $content = preg_replace('/^APP_KEY=.*/m', "APP_KEY={$key}", $content);
        } else {
            // Add APP_KEY if it doesn't exist
            $content = "APP_KEY={$key}\n".$content;
        }

        // Write back
        File::put($envPath, $content);

        // Reload to get the new key
        $this->reloadEnvFile();

        // Also update Laravel's config directly
        config(['app.key' => $key]);
    }

    /**
     * Reload the .env file into the application config.
     */
    private function reloadEnvFile(): void
    {
        $envPath = base_path('.env');
        if (! File::exists($envPath)) {
            return;
        }

        // Parse .env file and update config
        $lines = explode("\n", File::get($envPath));
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                $parts = explode('=', $line, 2);
                $key = trim($parts[0]);
                $value = trim($parts[1] ?? '');

                // Remove quotes
                if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                    $value = substr($value, 1, -1);
                }

                // Set in environment
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }

    private function clearConfigCache(): void
    {
        try {
            // Clear cached config files directly (faster and safer during install)
            $cachedConfigPath = base_path('bootstrap/cache/config.php');
            if (File::exists($cachedConfigPath)) {
                File::delete($cachedConfigPath);
            }
        } catch (Exception) {
            // Ignore failures during installation.
        }
    }

    private function runMigrations(int $currentWeight, int $totalWeight, int $stepWeight): void
    {
        // Get migration files to calculate progress
        $migrationFiles = File::files(database_path('migrations'));
        $totalMigrations = count($migrationFiles);

        // Apply database env before running migrations
        $dbEnv = $this->buildDatabaseEnv();
        $this->applyDatabaseEnv($dbEnv);
        if (empty($dbEnv['DB_PASSWORD'])) {
            throw new Exception('Database password is empty. Please re-enter it in the installer.');
        }

        Artisan::call('migrate', ['--force' => true, '--step' => true]);

        // Simulate progress during migration (since artisan runs synchronously)
        for ($i = 0; $i < 10; $i++) {
            $processedMigrations = min($totalMigrations, (int) ($totalMigrations * ($i + 1) / 10));
            $progress = $currentWeight + ($stepWeight * ($i + 1) / 10);
            $this->storeProgress(
                $this->calculatePercent($progress, $totalWeight),
                "Running migrations... ({$processedMigrations}/{$totalMigrations})"
            );
            usleep(50000); // 50ms for visual effect
        }
    }

    private function runSeeders(int $currentWeight, int $totalWeight, int $stepWeight, array $config = []): void
    {
        // Production seeders (always run)
        $seeders = [
            // 1. Roles & Permissions
            'RoleSeeder',

            // 2. Master Data (No Dependencies)
            'InstitutionSeeder',
            'ResearchSchemeSeeder',
            'TktSeeder',
            'FocusAreaSeeder',
            'NationalPrioritySeeder',
            'KeywordSeeder',
            'MacroResearchGroupSeeder',
            'BudgetGroupSeeder',
            'BudgetComponentSeeder',
            'ReviewCriteriaSeeder',

            // 3. Hierarchical Data (Self-referencing)
            'ScienceClusterSeeder',

            // 4. Dependent Master Data
            'FacultySeeder',
            'StudyProgramSeeder',
            'ThemeSeeder',
            'TopicSeeder',

            // 5. Users & Identities
            'AdminUserSeeder',
        ];

        // Development seeders (only in non-production)
        if (! $this->isProductionEnv($config)) {
            $seeders = array_merge($seeders, [
                'PartnerSeeder',
                'UserSeeder',
                'ProposalSeeder',
            ]);
        }

        $totalSeeders = count($seeders);

        // Store dynamic config for seeders to use
        if (! empty($config)) {
            $this->storeDynamicSeedersConfig($config);
        }

        // Apply database env ONCE before running all seeders (not inside the loop)
        // This prevents DB::purge/reconnect from causing transaction/visibility issues
        $dbEnv = $this->buildDatabaseEnv();
        $this->applyDatabaseEnv($dbEnv);
        if (empty($dbEnv['DB_PASSWORD'])) {
            throw new Exception('Database password is empty. Please re-enter it in the installer.');
        }

        foreach ($seeders as $index => $seederClass) {
            Artisan::call('db:seed', ['--class' => $seederClass, '--force' => true]);

            $progress = $currentWeight + ($stepWeight * ($index + 1) / $totalSeeders);
            $this->storeProgress(
                $this->calculatePercent($progress, $totalWeight),
                "Running seeders... ({$seederClass})"
            );
        }

        // Clear the dynamic config after seeding
        $this->clearDynamicSeedersConfig();
    }

    private function createAdminUser(array $adminConfig): void
    {
        // Store admin config in a temporary location for the seeder to use
        cache()->put('installer_admin_config', $adminConfig, now()->addHour());

        // Update the admin user with custom details
        if (! empty($adminConfig['email'])) {
            User::where('email', 'superadmin@email.com')
                ->update([
                    'email' => $adminConfig['email'],
                    'name' => $adminConfig['name'] ?? 'Administrator',
                ]);
        }

        if (! empty($adminConfig['password'])) {
            User::where('email', $adminConfig['email'] ?? 'superadmin@email.com')
                ->update([
                    'password' => bcrypt($adminConfig['password']),
                ]);
        }

        cache()->forget('installer_admin_config');
    }

    private function isProductionEnv(array $config): bool
    {
        $env = $config['APP_ENV'] ?? $config['app_env'] ?? config('app.env', 'production');

        return strtolower((string) $env) === 'production';
    }

    private function storeDynamicSeedersConfig(array $config): void
    {
        // Store institution config for seeders
        if (! empty($config['institution'])) {
            cache()->put('installer_institution_config', $config['institution'], now()->addHour());
        }

        // Store faculties config for seeders
        if (! empty($config['faculties'])) {
            cache()->put('installer_faculties_config', $config['faculties'], now()->addHour());
        }

        // Store admin config for seeders
        if (! empty($config['admin'])) {
            cache()->put('installer_admin_config', $config['admin'], now()->addHour());
        }
    }

    private function clearDynamicSeedersConfig(): void
    {
        cache()->forget('installer_institution_config');
        cache()->forget('installer_faculties_config');
        cache()->forget('installer_admin_config');
    }

    private function createStorageLink(): void
    {
        try {
            Artisan::call('storage:link', ['--force' => true]);
        } catch (Exception) {
            // Storage link might already exist or fail silently
        }
    }

    /**
     * @return array<string, string>
     */
    private function buildDatabaseEnv(): array
    {
        $writer = new EnvironmentWriter;
        $current = $writer->readCurrent();

        return [
            'DB_CONNECTION' => $current['DB_CONNECTION'] ?? 'mariadb',
            'DB_HOST' => $current['DB_HOST'] ?? '127.0.0.1',
            'DB_PORT' => $current['DB_PORT'] ?? '3306',
            'DB_DATABASE' => $current['DB_DATABASE'] ?? 'laravel',
            'DB_USERNAME' => $current['DB_USERNAME'] ?? 'root',
            'DB_PASSWORD' => $current['DB_PASSWORD'] ?? '',
        ];
    }

    /**
     * @param  array<string, string>  $envVars
     */
    private function applyDatabaseEnv(array $envVars): void
    {
        foreach ($envVars as $key => $value) {
            if ($value === '') {
                continue;
            }

            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        $connection = $envVars['DB_CONNECTION'];

        // Get existing connection config and merge with new values
        $existingConfig = config("database.connections.{$connection}", []);

        $newConfig = array_merge($existingConfig, [
            'driver' => $connection,
            'host' => $envVars['DB_HOST'],
            'port' => $envVars['DB_PORT'],
            'database' => $envVars['DB_DATABASE'],
            'username' => $envVars['DB_USERNAME'],
            'password' => $envVars['DB_PASSWORD'],
            // Ensure required MariaDB/MySQL parameters are set
            'charset' => $existingConfig['charset'] ?? 'utf8mb4',
            'collation' => $existingConfig['collation'] ?? 'utf8mb4_unicode_ci',
            'prefix' => $existingConfig['prefix'] ?? '',
            'prefix_indexes' => $existingConfig['prefix_indexes'] ?? true,
            'strict' => $existingConfig['strict'] ?? true,
            'engine' => $existingConfig['engine'] ?? null,
        ]);

        config([
            'database.default' => $connection,
            "database.connections.{$connection}" => $newConfig,
        ]);

        DB::purge($connection);
        DB::reconnect($connection);
    }

    private function finalizeInstallation(): void
    {
        // Clear caches
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');

        // Create lock file
        File::put($this->getLockFilePath(), now()->toDateTimeString());
    }

    public function lockInstallation(): void
    {
        File::put($this->getLockFilePath(), now()->toDateTimeString());
    }

    public function unlockInstallation(): void
    {
        if (File::exists($this->getLockFilePath())) {
            File::delete($this->getLockFilePath());
        }
    }

    private function getLockFilePath(): string
    {
        return storage_path(self::LOCK_FILE);
    }

    private function calculatePercent(float $current, float $total): int
    {
        return (int) round(($current / $total) * 100);
    }

    public function checkEnvironment(): array
    {
        $checks = [];

        // PHP Version
        $phpVersion = PHP_VERSION;
        $checks['php_version'] = [
            'label' => 'PHP Version >= 8.2',
            'status' => version_compare($phpVersion, '8.2.0', '>='),
            'current' => $phpVersion,
            'required' => '8.2.0',
        ];

        // Required Extensions
        $requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'json', 'ctype', 'xml', 'bcmath', 'fileinfo'];
        foreach ($requiredExtensions as $extension) {
            $checks["ext_{$extension}"] = [
                'label' => "Extension: {$extension}",
                'status' => extension_loaded($extension),
                'current' => extension_loaded($extension) ? 'Installed' : 'Missing',
                'required' => 'Required',
            ];
        }

        // Writable Directories
        $writableDirs = [
            'storage' => storage_path(),
            'bootstrap_cache' => base_path('bootstrap/cache'),
            'storage_app' => storage_path('app'),
            'storage_logs' => storage_path('logs'),
        ];

        foreach ($writableDirs as $key => $path) {
            $checks["writable_{$key}"] = [
                'label' => 'Writable: '.basename($path),
                'status' => is_writable($path),
                'current' => is_writable($path) ? 'Writable' : 'Not Writable',
                'required' => 'Writable',
            ];
        }

        // .env.example exists
        $checks['env_example'] = [
            'label' => '.env.example exists',
            'status' => File::exists(base_path('.env.example')),
            'current' => File::exists(base_path('.env.example')) ? 'Found' : 'Missing',
            'required' => 'Required',
        ];

        return $checks;
    }

    public function allEnvironmentChecksPass(): bool
    {
        $checks = $this->checkEnvironment();

        return collect($checks)->every(fn ($check) => $check['status']);
    }
}
