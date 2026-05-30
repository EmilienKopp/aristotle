<?php

declare(strict_types=1);

namespace Splitstack\Aristotle\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Splitstack\Aristotle\AristotleServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [AristotleServiceProvider::class];
    }
}
