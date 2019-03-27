<?php

namespace Submtd\LaravelRequestScope\Traits;

use Submtd\LaravelRequestScope\Scopes\RequestScope;

trait UseRequestScope
{
    public static function bootUseRequestScope()
    {
        static::addGlobalScope(new RequestScope);
    }
}
