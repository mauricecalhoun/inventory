<?php

/**
 * Helper for config facade. Checks if config helper function already exists
 * for Laravel 5 support
 *
 * @param string $key
 * @param string $default
 * @return mixed (array or string)
 */
if(!function_exists('config'))
{
    function config($key, $default = NULL)
    {
        return Config::get($key, $default);
    }
}