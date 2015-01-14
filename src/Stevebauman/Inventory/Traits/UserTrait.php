<?php

namespace Stevebauman\Inventory\Traits;

/**
 * Class UserTrait
 * @package Stevebauman\Inventory\Traits
 */
trait UserTrait {

    /**
     * Returns the current users ID
     *
     * @return null|int
     * @throws NoUserLoggedInException
     */
    private function getCurrentUserId()
    {
        /**
         * Check if sentry exists
         */
        if(class_exists('Cartalyst\Sentry\SentryServiceProvider')) {

            if(\Cartalyst\Sentry\Facades\Laravel\Sentry::check()) {

                return \Cartalyst\Sentry\Facades\Laravel\Sentry::getUser()->id;

            }

        } elseif (\Illuminate\Support\Facades\Auth::check()) {

            return \Illuminate\Support\Facades\Auth::user()->id;

        } else {

            if(config('inventory::allow_no_user')) {

                return NULL;

            } else {

                throw new NoUserLoggedInException;

            }

        }
    }

}