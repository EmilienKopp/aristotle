<?php

declare(strict_types=1);

namespace Splitstack\Aristotle\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Splitstack\Aristotle\Support\CastTypeResolver;

use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;

final class MakeEntityCommand extends Command
{
    protected $signature = 'make:entity
        {name? : The entity class name}
        {--model= : The Eloquent model to scaffold from}
        {--domain= : The bounded context / domain folder}
        {--namespace= : Override the full namespace (skips domain prompt)}
        {--database= : The database to connect to, useful in multitenancy contexts}
        {--force : Overwrite the entity if it already exists}';

    protected $description = 'Create a new DDD entity class from an Eloquent model';

    public function handle(Filesystem $files, CastTypeResolver $resolver): int
    {
        $modelClass = $this->resolveModelClass();
        $model = $this->laravel->make($modelClass);

        if (! $model instanceof Model) {
            $this->components->error("[{$modelClass}] is not an Eloquent model.");

            return self::FAILURE;
        }

        if ($this->option('namespace')) {
            $namespace = (string) $this->option('namespace');
        } else {
            $baseNamespace = (string) config('aristotle.namespace', 'App\\Domain');
            $entitiesFolder = (string) config('aristotle.entities_folder', '');
            $domain = $this->resolveDomain($baseNamespace);
            $namespace = $baseNamespace.'\\'.$domain;
            if ($entitiesFolder !== '') {
                $namespace .= '\\'.$entitiesFolder;
            }
        }

        $className = (string) ($this->argument('name') ?: class_basename($modelClass));
        $targetPath = $this->targetPath($namespace, $className);

        if ($files->exists($targetPath) && ! $this->option('force')) {
            $this->components->error('Entity already exists.');

            return self::FAILURE;
        }

        $connection = $model->getConnection();

        if ($database = $this->option('database')) {
            $connectionName = $connection->getName();
            DB::purge($connectionName);
            $connection = DB::connectUsing(
                name: $connectionName,
                config: [...$connection->getConfig(), 'database' => $database],
            );
        }

        if (! $connection->getSchemaBuilder()->hasTable($model->getTable())) {
            $this->components->error("Table [{$model->getTable()}] does not exist on the [{$connection->getDatabaseName()}] database.");

            return self::FAILURE;
        }

        $columns = $connection->getSchemaBuilder()->getColumns($model->getTable());
        $properties = $this->buildProperties($columns, $model, $resolver, $imports);

        $suffix = config('aristotle.entity_suffix', '');
        $finalClassName = $className.($suffix ? ucfirst((string) $suffix) : '');

        $stub = $files->get($this->stubPath($files));
        $contents = $this->fillStub($stub, $namespace, $finalClassName, $modelClass, $imports, $properties);

        $files->ensureDirectoryExists(dirname($targetPath));
        $files->put($targetPath, $contents);

        $this->components->info("Entity [{$targetPath}] created successfully.");

        return self::SUCCESS;
    }

    /**
     * @param  list<array<string, mixed>>  $columns
     * @param  list<string>  $imports
     * @return list<string>
     */
    private function buildProperties(array $columns, Model $model, CastTypeResolver $resolver, mixed &$imports): array
    {
        $imports = ['Splitstack\\Aristotle\\BaseEntity'];
        $properties = [];

        foreach ($columns as $column) {
            $name = (string) $column['name'];
            $generatedType = $resolver->handle($name, $column, $model);
            $nullable = (bool) ($column['nullable'] ?? false) || (bool) ($column['auto_increment'] ?? false);
            $type = $generatedType->type;
            $prefix = $nullable && $type !== 'mixed' && ! str_starts_with($type, '?') ? '?' : '';
            $default = $nullable ? ' = null' : '';

            $imports = [...$imports, ...$generatedType->imports];
            $properties[] = "        public {$prefix}{$type} \${$name}{$default},";
        }

        return $properties;
    }

    /**
     * @param  list<string>  $imports
     * @param  list<string>  $properties
     */
    private function fillStub(string $stub, string $namespace, string $class, string $modelClass, array $imports, array $properties): string
    {
        $formattedImports = collect($imports)
            ->unique()
            ->sort()
            ->map(static fn (string $import): string => "use {$import};")
            ->implode("\n");

        return str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ imports }}', '{{ properties }}'],
            [$namespace, $class, $formattedImports, implode("\n", $properties)],
            $stub,
        );
    }

    private function resolveModelClass(): string
    {
        $default = (string) ($this->argument('name') ?: '');
        $models = $this->discoverModels();

        $model = (string) ($this->option('model') ?: (
            $models !== []
                ? suggest(
                    label: 'Which model should this entity be scaffolded from?',
                    options: $models,
                    default: $default,
                )
                : text(
                    label: 'Which model should this entity be scaffolded from?',
                    default: $default,
                )
        ));

        if (class_exists($model)) {
            return $model;
        }

        $appNamespace = rtrim((string) $this->laravel->getNamespace(), '\\');
        $qualified = $appNamespace.'\\Models\\'.$model;

        if (class_exists($qualified)) {
            return $qualified;
        }

        throw new RuntimeException("Model [{$model}] could not be resolved.");
    }

    private function resolveDomain(string $baseNamespace): string
    {
        $default = '';
        $existing = $this->discoverDomains($baseNamespace);

        $domain = (string) ($this->option('domain') ?: (
            $existing !== []
                ? suggest(
                    label: 'Which domain does this entity belong to?',
                    options: $existing,
                    default: $default,
                )
                : text(
                    label: 'Which domain does this entity belong to?',
                )
        ));

        return Str::studly($domain);
    }

    /** @return list<string> */
    private function discoverModels(): array
    {
        $modelsPath = app_path('Models');

        if (! is_dir($modelsPath)) {
            return [];
        }

        return collect(glob($modelsPath.'/*.php') ?: [])
            ->map(fn (string $file): string => pathinfo($file, PATHINFO_FILENAME))
            ->sort()
            ->values()
            ->all();
    }

    /** @return list<string> */
    private function discoverDomains(string $baseNamespace): array
    {
        $appNamespace = rtrim((string) $this->laravel->getNamespace(), '\\');
        $relative = str_replace('\\', '/', ltrim(Str::replaceFirst($appNamespace, '', $baseNamespace), '\\'));
        $domainRoot = $relative !== '' ? app_path($relative) : app_path();

        if (! is_dir($domainRoot)) {
            return [];
        }

        return collect(scandir($domainRoot) ?: [])
            ->filter(fn (string $item): bool => ! in_array($item, ['.', '..'], true) && is_dir($domainRoot.'/'.$item))
            ->values()
            ->all();
    }

    private function targetPath(string $namespace, string $class): string
    {
        $appNamespace = rtrim((string) $this->laravel->getNamespace(), '\\');
        $relativePath = Str::replaceFirst($appNamespace, '', $namespace);
        $relativePath = str_replace('\\', '/', ltrim($relativePath, '\\'));

        $base = $relativePath !== ''
            ? app_path($relativePath)
            : app_path();

        $suffix = config('aristotle.entity_suffix', '');
        $class = $class.($suffix ? ucfirst($suffix) : '');

        return $base.'/'.$class.'.php';
    }

    private function stubPath(Filesystem $files): string
    {
        $published = base_path('stubs/entity.stub');

        return $files->exists($published)
            ? $published
            : __DIR__.'/../../../stubs/entity.stub';
    }
}
