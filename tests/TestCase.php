<?php

namespace Madnest\Madzipper\Tests;

use Madnest\Madzipper\MadzipperServiceProvider;
// use Mockery;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        // Debug helper
        // Mockery::setLoader(new Mockery\Loader\RequireLoader(sys_get_temp_dir()));
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            MadzipperServiceProvider::class,
        ];
    }
}
