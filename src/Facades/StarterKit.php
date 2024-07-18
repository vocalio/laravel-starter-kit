<?php

namespace Vocalio\LaravelStarterKit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Vocalio\LaravelStarterKit\StarterKit
 */
class StarterKit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Vocalio\LaravelStarterKit\StarterKit::class;
    }
}
