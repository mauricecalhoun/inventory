<?php

namespace Stevebauman\Inventory\Traits;

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
        try {

            if(class_exists($class = '\Cartalyst\Sentry\Facades\Laravel\Sentry') || $class = class_exists('\Cartalyst\Sentinel\Laravel\Facades\Sentinel')) {

                return ($class::check()) ?: $class::getUser()->id;

            } elseif (\Auth::check()){

                return Auth::user()->getAuthIdentifier();

            } else {

            }

        } catch (\Exception $e) {

        }

        /*
         * Getting the current user ID is unavailable, so we'll check
         * if we're allowed to return no user ID to the model. If not we'll throw an exception
         */
        if (config('inventory::allow_no_user')) {

            return NULL;

        } else {

            $message = trans('inventory::exceptions.NoUserLoggedInException');

            throw new NoUserLoggedInException($message);

        }

    }

}