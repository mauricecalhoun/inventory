<?php

namespace Stevebauman\Inventory;

use Stevebauman\Inventory\Exceptions\NoUserLoggedInException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;

class Helper
{
    /**
     * Attempt to find the user id of the currently logged in user
     * Supports Cartalyst Sentry/Sentinel based authentication, as well as stock Auth.
     *
     * @throws NoUserLoggedInException
     *
     * @return int|string|null
     */
    public static function getCurrentUserId()
    {
        // Check if we're allowed to return no user ID to the model, if so we'll return null.
        if (Config::get('inventory.allow_no_user', false)) {
            return null;
        }

        // Accountability is enabled, let's try and retrieve the current users ID.
        if (class_exists($class = '\Cartalyst\Sentry\Facades\Laravel\Sentry') || class_exists($class = '\Cartalyst\Sentinel\Laravel\Facades\Sentinel')) {
            if ($class::check()) {
                return $class::getUser()->id;
            }
        } else if (Auth::check()) {
            return Auth::user()->getAuthIdentifier();
        }

        // Couldn't get the current logged in users ID, throw exception
        $message = Lang::get('inventory::exceptions.NoUserLoggedInException');

        throw new NoUserLoggedInException($message);
    }
}
