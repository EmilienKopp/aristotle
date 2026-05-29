<?php

declare(strict_types=1);

namespace Splitstack\Aristotle\Concerns;

use Illuminate\Support\Str;

use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;

trait HasDomainPrompts
{
    private function resolveDomain(string $baseNamespace): string
    {
        $existing = $this->discoverDomains($baseNamespace);

        $domain = (string) ($this->option('domain') ?: (
            $existing !== []
                ? suggest(
                    label: 'Which domain does this entity belong to?',
                    options: $existing,
                )
                : text(label: 'Which domain does this entity belong to?')
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
            ->filter(fn (string $item): bool => ! \in_array($item, ['.', '..'], true) && is_dir($domainRoot.'/'.$item))
            ->values()
            ->all();
    }
}
