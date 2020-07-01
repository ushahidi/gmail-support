<?php

namespace Ushahidi\Gmail\Facades;

use Illuminate\Support\Facades\Facade;

class Gmail extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'gmail';
    }
}