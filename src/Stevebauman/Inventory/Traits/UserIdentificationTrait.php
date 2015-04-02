<?php

namespace Stevebauman\Inventory\Traits;

use Stevebauman\Inventory\InventoryServiceProvider;
use Stevebauman\Inventory\Exceptions\NoUserLoggedInException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;

/**
 * Class UserIdentificationTrait
 * @package Stevebauman\Inventory\Traits
 */
trait UserIdentificationTrait
{
    /**
     * Attempt to find the user id of the currently logged in user
     * Supports Cartalyst Sentry/Sentinel based authentication, as well as stock Auth
     *
     * Thanks to https://github.com/VentureCraft/revisionable/blob/master/src/Venturecraft/Revisionable/RevisionableTrait.php
     *
     * @return null
     * @throws NoUserLoggedInException
     */
    protected static function getCurrentUserId()
    {
        /*
         * Check if we're allowed to return no user ID to the model, if so we'll return NULL
         */
        if (Config::get('inventory'. InventoryServiceProvider::$packageConfigSeparator .'allow_no_user')) return NULL;

        /*
         * Accountability is enabled, let's try and retrieve the current users ID
         */
        try
        {
            if(class_exists($class = '\Cartalyst\Sentry\Facades\Laravel\Sentry') || $class = class_exists('\Cartalyst\Sentinel\Laravel\Facades\Sentinel'))
            {
                if($class::check()) return $class::getUser()->id;

            } elseif (class_exists('Illuminate\Auth') || class_exists('Illuminate\Support\Facades\Auth'))
            {
                if(\Auth::check()) return \Auth::user()->getAuthIdentifier();
            }

        } catch (\Exception $e){}

        /*
         * Couldn't get the current logged in users ID, throw exception
         */
        $message = Lang::get('inventory::exceptions.NoUserLoggedInException');

        throw new NoUserLoggedInException($message);
    }
}
