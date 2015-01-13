<?php

namespace Stevebauman\Inventory\Models;

use Baum\Node;

/**
 * Class Location
 * @package Stevebauman\Inventory\Models
 */
class Location extends Node
{

    protected $table = 'locations';

    protected $fillable = array(
        'name',
    );

    /**
     * Returns a single lined string with arrows indicating depth of the current category
     *
     * @return string
     */
    public function getTrailAttribute()
    {
        return renderNode($this);
    }

    /**
     * Compatibility with Revisionable
     *
     * @return string
     */
    public function identifiableName()
    {
        return $this->getTrailAttribute();
    }
}
