<?php

namespace Stevebauman\Inventory\Traits;


trait UserIdentificationTrait {

    public static function boot()
    {
        parent::boot();

        parent::creating(function($record){
            $record->user_id = parent::getCurrentUserId();
        });
    }

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
            if (class_exists($class = '\Cartalyst\Sentry\Facades\Laravel\Sentry')
                || class_exists($class = '\Cartalyst\Sentinel\Laravel\Facades\Sentinel')
            ) {
                return ($class::check()) ? $class::getUser()->id : null;
            } elseif (Auth::check()) {
                return Auth::user()->getAuthIdentifier();
            }
        } catch (\Exception $e) {
        }
        if (config('inventory::allow_no_user')) {
            return NULL;
        } else {
            $message = 'Cannot retrieve user ID';
            throw new NoUserLoggedInException($message);
        }
    }

}