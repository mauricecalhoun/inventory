<?php

namespace Stevebauman\Inventory\Models;

use Baum\Node;

/**
 * Class Category
 * @package Stevebauman\Maintenance\Models
 */
class Category extends Node {

    protected $table = 'categories';

    protected $scoped = array('belongs_to');

    protected $fillable = array(
        'name',
        'belongs_to',
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