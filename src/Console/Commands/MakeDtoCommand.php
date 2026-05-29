<?php

declare(strict_types=1);

namespace Splitstack\Aristotle\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;
use Splitstack\Aristotle\Concerns\HasDomainPrompts;
use Splitstack\Aristotle\Support\CastTypeResolver;

final class MakeDtoCommand extends Command
{
    use HasDomainPrompts;

    protected $signature = 'aristotle:dto
        {name : The DTO class name}
        {--model= : Scaffold properties from this Eloquent model\'s fillable + casts}
        {--domain= : The bounded context / domain folder}
        {--namespace= : Override the full namespace (skips domain prompt)}
        {--force : Overwrite the DTO if it already exists}';

    protected $description = 'Create a new DTO class, optionally scaffolded from an Eloquent model';

    public function handle(Filesystem $files, CastTypeResolver $resolver): int
    {
        if ($this->option('namespace')) {
            $namespace = (string) $this->option('namespace');
        } else {
            $baseNamespace = (string) config('aristotle.namespace', 'App\\Domain');
            $dtosFolder = (string) config('aristotle.dtos_folder', '');
            $domain = $this->resolveDomain($baseNamespace);
            $namespace = $baseNamespace.'\\'.$domain;
            if ($dtosFolder !== '') {
                $namespace .= '\\'.$dtosFolder;
            }
        }

        $className = Str::studly((string) $this->argument('name'));
        $targetPath = $this->targetPath($namespace, $className);

        if ($files->exists($targetPath) && ! $this->option('force')) {
            $this->components->error('DTO already exists.');

            return self::FAILURE;
        }

        $properties = [];
        $assignments = [];
        $imports = ['Splitstack\\Aristotle\\BaseDTO'];

        if ($modelOption = $this->option('model')) {
            [$properties, $assignments, $imports] = $this->buildFromModel(
                $this->resolveModelClass((string) $modelOption),
                $resolver,
            );
        }

        $stub = $files->get($this->stubPath($files));
        $contents = $this->fillStub($stub, $namespace, $className, $imports, $properties, $assignments);

        $files->ensureDirectoryExists(dirname($targetPath));
        $files->put($targetPath, $contents);

        $this->components->info("DTO [{$targetPath}] created successfully.");

        return self::SUCCESS;
    }

    /**
     * @return array{list<string>, list<string>, list<string>}
     */
    private function buildFromModel(string $modelClass, CastTypeResolver $resolver): array
    {
        $model = $this->laravel->make($modelClass);

        if (! $model instanceof Model) {
            throw new RuntimeException("[{$modelClass}] is not an Eloquent model.");
        }

        $imports = ['Splitstack\\Aristotle\\BaseDTO'];
        $properties = [];
        $assignments = [];
        $fillable = $model->getFillable();
        $casts = $model->getCasts();

        foreach ($fillable as $field) {
            $column = ['name' => $field, 'type_name' => 'string', 'nullable' => true, 'auto_increment' => false];
            $generatedType = $resolver->handle($field, $column, $model);
            $type = $generatedType->type;
            $nullable = ! in_array($field, $this->requiredFields($model), true);
            $prefix = $nullable && $type !== 'mixed' && ! str_starts_with($type, '?') ? '?' : '';
            $default = $nullable ? ' = null' : '';

            $imports = [...$imports, ...$generatedType->imports];
            $properties[] = "        public private(set) {$prefix}{$type} \${$field}{$default},";
            $assignments[] = $nullable
                ? "            {$field}: \$data['{$field}'] ?? null,"
                : "            {$field}: \$data['{$field}'],";
        }

        return [$properties, $assignments, $imports];
    }

    /** @return list<string> */
    private function requiredFields(Model $model): array
    {
        return array_values(array_filter(
            $model->getFillable(),
            fn (string $field): bool => ! in_array($field, array_keys($model->getCasts()), true)
                && ! str_ends_with($field, '_id')
                && $field !== 'id',
        ));
    }

    /**
     * @param  list<string>  $imports
     * @param  list<string>  $properties
     * @param  list<string>  $assignments
     */
    private function fillStub(
        string $stub,
        string $namespace,
        string $class,
        array $imports,
        array $properties,
        array $assignments,
    ): string {
        $formattedImports = collect($imports)
            ->unique()
            ->sort()
            ->map(static fn (string $import): string => "use {$import};")
            ->implode("\n");

        return str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ imports }}', '{{ properties }}', '{{ assignments }}'],
            [$namespace, $class, $formattedImports, implode("\n", $properties), implode("\n", $assignments)],
            $stub,
        );
    }

    private function resolveModelClass(string $model): string
    {
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

    private function targetPath(string $namespace, string $class): string
    {
        $appNamespace = rtrim((string) $this->laravel->getNamespace(), '\\');
        $relativePath = Str::replaceFirst($appNamespace, '', $namespace);
        $relativePath = str_replace('\\', '/', ltrim($relativePath, '\\'));

        $base = $relativePath !== ''
            ? app_path($relativePath)
            : app_path();

        return $base.'/'.$class.'DTO.php';
    }

    private function stubPath(Filesystem $files): string
    {
        $published = base_path('stubs/dto.stub');

        return $files->exists($published)
            ? $published
            : __DIR__.'/../../../stubs/dto.stub';
    }
}
