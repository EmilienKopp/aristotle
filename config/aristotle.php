<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Domain Root Namespace
    |--------------------------------------------------------------------------
    |
    | The root namespace for all domains. The bounded context name is appended
    | automatically: e.g. "App\Domain" + "Auth" → "App\Domain\Auth\Entities".
    |
    */

    'namespace' => 'App\\Domain',

    /*
    |--------------------------------------------------------------------------
    | Entities Sub-Folder
    |--------------------------------------------------------------------------
    |
    | The sub-folder/namespace appended after the bounded context name.
    | Set to null or empty string to place entities directly under the domain.
    | e.g. "Entities" → "App\Domain\Auth\Entities"
    |
    */

    'entities_folder' => 'Entities',

    /*
    |--------------------------------------------------------------------------
    | DTOs Sub-Folder
    |--------------------------------------------------------------------------
    |
    | The sub-folder/namespace appended after the bounded context name for DTOs.
    | e.g. "DTOs" → "App\Domain\Auth\DTOs"
    |
    */

    'dtos_folder' => 'DTOs',

    /*
    |--------------------------------------------------------------------------
    | Entity Class Suffix
    |--------------------------------------------------------------------------
    |
    | Optionally append a suffix to generated entity class names, e.g. "Entity".
    | This can help distinguish entities from other classes in your codebase.
    |
    */

    'entity_suffix' => env('ARISTOTLE_ENTITY_SUFFIX', 'Entity'),
];
