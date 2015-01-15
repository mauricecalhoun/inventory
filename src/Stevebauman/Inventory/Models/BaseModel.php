<?php

namespace Stevebauman\Inventory\Models;


use Stevebauman\Inventory\Exceptions\NoUserLoggedInException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class BaseModel {

    /**
     * Attempt to find the user id of the currently logged in user
     * Supports Cartalyst Sentry/Sentinel based authentication, as well as stock Auth
     *
     * Thanks to https://github.com/VentureCraft/revisionable/blob/master/src/Venturecraft/Revisionable/RevisionableTrait.php
     *
     * @return null
     * @throws NoUserLoggedInException
     */
    protected function getCurrentUserId()
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

            throw new NoUserLoggedInException;

        }

    }

    /**
     * Alias for firing events easily that implement this trait
     *
     * @param string $name
     * @param array $args
     * @return type
     */
    protected function fireEvent($name, $args = array())
    {
        return Event::fire((string) $name, (array) $args);
    }

    /**
     * Alias for beginning a database transaction
     *
     * @return mixed
     */
    protected function dbStartTransaction()
    {
        return DB::beginTransaction();
    }

    /**
     * Alias for committing a database transaction
     *
     * @return mixed
     */
    protected function dbCommitTransaction()
    {
        return DB::commit();
    }

    /**
     * Alias for rolling back a transaction
     *
     * @return mixed
     */
    protected function dbRollbackTransaction()
    {
        return DB::rollback();
    }

}