<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Support\Facades\File;

class MakeMigrationCommand extends AbstractMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:migration
                            {name : The name of the migration}
                            {--module= : The module where the migration will be created}
                            {--table= : The table to be created or modified by the migration}
                            {--create= : Whether the migration should create a new table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration file in a module';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $module = $this->getModule();
        $name = $this->getResourceName();
        $table = $this->option('create');
        $table = $this->option('table') ?: ($table ?: $this->extractTableNameFromMigrationName($name));

        if (!$module || !$name) {
            $this->error('Module and migration name are required.');
            return self::FAILURE;
        }

        $timestamp = date('Y_m_d_His');
        $migrationName = "{$timestamp}_{$name}.php";
        $migrationPath = $this->modulePath("database/migrations/{$migrationName}");
        $this->ensureDirectory(dirname($migrationPath));

        $stubPath = $this->stubPath('migration.stub');

        if ($table) {
            $stubPath = $this->stubPath('migration.update.stub');
        }

        if ($this->isCreateMigration($name)) {
            $stubPath = $this->stubPath('migration.create.stub');
        }

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);
        $content = $this->replaceStubPlaceholders($stub, [
            '{{ table }}' => $table ?? 'table_name',
        ]);

        $this->writeFile($migrationPath, $content);

        $this->info("Migration created: {$migrationPath}");
        return self::SUCCESS;
    }

    /**
     * Extract the table name from the migration name.
     *
     * @param string $name
     * @return string|null
     */
    protected function extractTableNameFromMigrationName(string $name): ?string
    {
        if (preg_match('/(?:create|update)_([a-zA-Z0-9_]+)_table/', $name, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Determine if the migration is a "create" migration based on its name.
     *
     * @param string $name
     * @return bool
     */
    protected function isCreateMigration(string $name): bool
    {
        return str_starts_with($name, 'create_');
    }
}
