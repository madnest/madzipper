<?php

namespace Madnest\Madzipper\Tests;

use Madnest\Madzipper\MadzipperServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            MadzipperServiceProvider::class,
        ];
    }
}
