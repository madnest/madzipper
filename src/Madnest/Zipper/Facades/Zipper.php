<?php

namespace Madnest\Zipper\Facades;

use Illuminate\Support\Facades\Facade;

class Zipper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'zipper';
    }
}
