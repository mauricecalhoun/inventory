<?php

namespace Stevebauman\Inventory\Traits;

use Stevebauman\Inventory\Models\Category;

trait HasScopeCategory {

    /**
     * Filters inventory results by specified category
     *
     * @return object
     */
    public function scopeCategory($query, $category_id = NULL)
    {

        if ($category_id) {

            /*
             * Get descendants and self inventory category nodes
             */
            $categories = Category::find($category_id)->getDescendantsAndSelf();

            /*
             * Perform a subquery on main query
             */
            $query->where(function ($query) use ($categories) {

                /*
                 * For each category, apply a orWhere query to the subquery
                 */
                foreach ($categories as $category) {
                    $query->orWhere('category_id', $category->id);
                }

                return $query;

            });

        }
    }

}