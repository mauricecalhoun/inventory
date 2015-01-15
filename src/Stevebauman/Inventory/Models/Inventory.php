<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Exceptions\StockAlreadyExistsException;
use Illuminate\Database\Eloquent\SoftDeletingTrait;
use Stevebauman\Inventory\Traits\UserTrait;
use Stevebauman\Inventory\Traits\LocationTrait;
use Stevebauman\Inventory\Exceptions\StockNotFoundException;
use Stevebauman\Inventory\Models\InventoryStock;
use Stevebauman\CoreHelper\Models\BaseModel;

/**
 * Class Inventory
 * @package Stevebauman\Inventory\Models
 */
class Inventory extends BaseModel
{

    /**
     * Soft deleting for inventory item recovery
     */
    use SoftDeletingTrait;

    /**
     * User for easily identifying the current logged in user
     */
    use UserTrait;

    /**
     * Location helper functions
     */
    use LocationTrait;

    /**
     * The database table to store inventory records
     *
     * @var string
     */
    protected $table = 'inventories';


    /**
     * The fillable eloquent attribute array for allowing mass assignments
     *
     * @var array
     */
    protected $fillable = array(
        'user_id',
        'metric_id',
        'category_id',
        'name',
        'description'
    );

    /**
     * The hasOne category relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function category()
    {
        return $this->hasOne('Stevebauman\Inventory\Models\Category', 'id', 'category_id');
    }

    /**
     * The hasOne metric relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function metric()
    {
        return $this->hasOne('Stevebauman\Inventory\Models\Metric', 'id', 'metric_id');
    }

    /**
     * The hasMany stocks relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stocks()
    {
        return $this->hasMany('Stevebauman\Inventory\Models\InventoryStock', 'inventory_id');
    }

    /**
     * Filters query by the inputted inventory item name
     *
     * @param $query
     * @param null $name
     * @return mixed
     */
    public function scopeName($query, $name = NULL)
    {
        if ($name) {
            return $query->where('name', 'LIKE', '%' . $name . '%');
        }
    }

    /**
     * Filters query by the inputted inventory item description
     *
     * @param $query
     * @param null $description
     * @return mixed
     */
    public function scopeDescription($query, $description = NULL)
    {
        if ($description) {
            return $query->where('description', 'LIKE', '%' . $description . '%');
        }
    }

    /**
     * Filters query by the inputted inventory item stock quantity
     *
     * @param $query
     * @param null $operator
     * @param null $stock
     * @return mixed
     */
    public function scopeStock($query, $operator = NULL, $stock = NULL)
    {
        if ($operator && $stock) {

            return $query->whereHas('stocks', function ($query) use ($operator, $stock) {

                if ($output = $this->getOperator($operator)) {

                    return $query->where('quantity', $output[0], $stock);

                } else {
                    return $query;
                }

            });
        }
    }

    /**
     * Filters inventory results by specified category
     *
     * @param $query
     * @param null $category_id
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

    /**
     * Filters inventory results by specified location
     *
     * @param $query
     * @param null $location_id
     */
    public function scopeLocation($query, $location_id = NULL)
    {
        if ($location_id) {

            /*
             * Get descendants and self inventory category nodes
             */
            $locations = Location::find($location_id)->getDescendantsAndSelf();

            /*
             * Perform a subquery on main query
             */
            $query->where(function ($query) use ($locations) {

                /*
                 * For each category, apply a orWhere query to the subquery
                 */
                foreach ($locations as $location) {
                    $query->orWhere('location_id', $location->id);
                }

                return $query;

            });
        }
    }

    /**
     * Mutator for showing the total current stock of the inventory item
     *
     * @return int|string
     */
    public function getCurrentStockAttribute()
    {
        $stock = $this->getTotalStock();

        if ($this->hasMetric()) {
            return sprintf('%s %s', $stock, $this->getMetricSymbol());
        }

        return $stock;
    }

    /**
     * Mutator for showing the inventories metric symbol
     *
     * @return null|string
     */
    public function getMetricSymbolAttribute()
    {
        if ($this->hasMetric()) {
            return $this->getMetricSymbol();
        }

        return NULL;
    }

    /**
     * Returns the total sum of the current stock
     *
     * @return mixed
     */
    public function getTotalStock()
    {
        return $this->stocks->sum('quantity');
    }

    /**
     * Returns true/false if the inventory has a metric present
     *
     * @return bool
     */
    public function hasMetric()
    {
        return ($this->metric ? true : false);
    }

    /**
     * Returns the inventory's metric symbol
     *
     * @return mixed
     */
    public function getMetricSymbol()
    {
        return $this->metric->symbol;
    }

    /**
     * Returns true/false if the inventory has stock
     *
     * @return bool
     */
    public function isInStock()
    {
        return ($this->getTotalStock() > 0 ? true : false);
    }

    /**
     * Adds a stock record to the current inventory item
     *
     * @param $quantity
     * @param $location
     * @param string $reason
     * @param int $cost
     * @param null $aisle
     * @param null $row
     * @param null $bin
     * @return mixed
     * @throws StockAlreadyExistsException
     * @throws StockNotFoundException
     * @throws \Stevebauman\Inventory\Traits\InvalidLocationException
     * @throws \Stevebauman\Inventory\Traits\NoUserLoggedInException
     */
    public function addStock($quantity, $location, $reason = '', $cost = 0, $aisle = NULL, $row = NULL, $bin = NULL)
    {
        $location = $this->getLocation($location);

        try{

            if($this->getStockFromLocation($location)) {

                $message = sprintf('Stock already exists on location %s', $location->name);

                throw new StockAlreadyExistsException($message);

            }

        } catch(StockNotFoundException $e) {

            $insert = array(
                'user_id' => $this->getCurrentUserId(),
                'inventory_id' => $this->id,
                'location_id' => $location->id,
                'quantity' => 0,
                'aisle' => $aisle,
                'row' => $row,
                'bin' => $bin,
            );

            $stock = InventoryStock::create($insert);

            return $stock->put($quantity, $reason, $cost);

        }
    }

    /**
     * Takes the specified amount ($quantity) of stock from specified stock location
     *
     * @param string|int $quantity
     * @param $location
     * @param string $reason
     * @return array
     * @throws StockNotFoundException
     */
    public function take($quantity, $location, $reason = '')
    {
        if(is_array($location)) {

            return $this->takeFromMany($quantity, $location, $reason);

        } else {

            $stock = $this->getStockFromLocation($location);

            if($stock->take($quantity, $reason)) {

                return $this;

            }

        }
    }

    /**
     * Takes the specified amount ($quantity) of stock from the specified stock locations
     *
     * @param string|int $quantity
     * @param array $locations
     * @param string $reason
     * @return array
     * @throws StockNotFoundException
     */
    public function takeFromMany($quantity, $locations =  array(), $reason = '')
    {
        $stocks = array();

        foreach($locations as $location) {

            $stock = $this->getStockFromLocation($location);

            $stocks[] = $stock->take($quantity, $reason);

        }

        return $stocks;
    }

    /**
     * Alias for the `take` function
     *
     * @param $quantity
     * @param $location
     * @param string $reason
     * @return array
     */
    public function remove($quantity, $location, $reason = '')
    {
        return $this->take($quantity, $location, $reason);
    }

    /**
     * Alias for the `takeFromMany` function
     *
     * @param $quantity
     * @param array $locations
     * @param string $reason
     * @return array
     */
    public function removeFromMany($quantity, $locations =  array(), $reason = '')
    {
        return $this->takeFromMany($quantity, $locations, $reason);
    }

    /**
     * Puts the specified amount ($quantity) of stock into the specified stock location(s)
     *
     * @param string|int $quantity
     * @param $location
     * @param string $reason
     * @param int $cost
     * @return array
     * @throws StockNotFoundException
     */
    public function put($quantity, $location, $reason = '', $cost = 0)
    {
        if(is_array($location)) {

            return $this->putToMany($quantity, $location);

        } else {

            $stock = $this->getStockFromLocation($location);

            if($stock->put($quantity, $reason, $cost)) {

                return $this;

            }

        }
    }

    /**
     * Puts the specified amount ($quantity) of stock into the specified stock locations
     *
     * @param $quantity
     * @param array $locations
     * @param string $reason
     * @param int $cost
     * @return array
     * @throws StockNotFoundException
     */
    public function putToMany($quantity, $locations = array(), $reason = '', $cost = 0)
    {
        $stocks = array();

        foreach($locations as $location) {

            $stock = $this->getStockFromLocation($location);

            $stocks[] = $stock->put($quantity, $reason, $cost);

        }

        return $stocks;
    }

    /**
     * Alias for the `put` function
     *
     * @param $quantity
     * @param $location
     * @param string $reason
     * @param int $cost
     * @return array
     */
    public function add($quantity, $location, $reason = '', $cost = 0)
    {
        return $this->put($quantity, $location, $reason, $cost);
    }

    /**
     * Alias for the `putToMany` function
     *
     * @param $quantity
     * @param array $locations
     * @param string $reason
     * @param int $cost
     * @return array
     */
    public function addToMany($quantity, $locations = array(), $reason = '', $cost = 0)
    {
        return $this->putToMany($quantity, $locations, $reason, $cost);
    }

    /**
     * Moves a stock from one location to another
     *
     * @param $fromLocation
     * @param $toLocation
     * @return mixed
     * @throws StockNotFoundException
     */
    public function moveStock($fromLocation, $toLocation)
    {
        $stock = $this->getStockFromLocation($fromLocation);

        $toLocation = $this->getLocation($toLocation);

        return $stock->moveTo($toLocation);
    }

    /**
     * Retrieves an inventory stock from a given location
     *
     * @param $location
     * @return mixed
     * @throws StockNotFoundException
     */
    public function getStockFromLocation($location)
    {
        $location = $this->getLocation($location);

        $stock = InventoryStock::
            where('inventory_id', $this->id)
            ->where('location_id', $location->id)
            ->first();

        if($stock) {

            return $stock;

        } else {

            $message = sprintf('No stock was found from location %s', $location->name);

            throw new StockNotFoundException($message);

        }
    }



}