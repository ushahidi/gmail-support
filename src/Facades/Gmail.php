<?php

namespace Ushahidi\Gmail\Facades;

use Illuminate\Support\Facades\Facade;

class Gmail
{
    protected static function getFacadeAccessor()
    {
        return 'gmail';
    }
}