<?php

namespace Stevebauman\Inventory\Traits;

use Stevebauman\Inventory\Exceptions\NoUserLoggedInException;

trait UserIdentificationTrait {

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
         * Check if we're allowed to return no user ID to the model. If not we'll throw an exception if
         * we can't grab the current authenticated user with sentry/sentinel/auth
         */
        if (config('inventory::allow_no_user')) {

            return NULL;

        } else {

            try {

                if(class_exists($class = '\Cartalyst\Sentry\Facades\Laravel\Sentry') || $class = class_exists('\Cartalyst\Sentinel\Laravel\Facades\Sentinel')) {

                    if($class::check()) return $class::getUser()->id;

                } elseif (class_exists('Illuminate\Auth')){

                    if(\Auth::check()) return \Auth::user()->getAuthIdentifier();

                } else {

                }

            } catch (\Exception $e) {

            }

            $message = trans('inventory::exceptions.NoUserLoggedInException');

            throw new NoUserLoggedInException($message);

        }

    }

}