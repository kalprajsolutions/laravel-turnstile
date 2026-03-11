<?php

namespace KalprajSolutions\LaravelTurnstile\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \KalprajSolutions\LaravelTurnstile\Turnstile
 */
class Turnstile extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'turnstile';
    }
}
