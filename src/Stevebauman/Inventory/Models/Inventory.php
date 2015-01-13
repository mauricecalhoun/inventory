<?php

namespace Stevebauman\Inventory\Models;

use Illuminate\Database\Eloquent\SoftDeletingTrait;
use Stevebauman\CoreHelper\Models\BaseModel;
use Stevebauman\Inventory\Exceptions\InvalidLocationException;
use Stevebauman\Inventory\Exceptions\StockNotFoundException;

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
     * The hasOne location relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function location()
    {
        return $this->hasOne('Stevebauman\Inventory\Models\Location', 'id', 'location_id');
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
     * Mutator for showing the total current stock of the inventory item
     *
     * @return int|string
     */
    public function getCurrentStockAttribute()
    {
        if ($this->isInStock()) {

            $stock = $this->getTotalStock();

            if ($this->hasMetric()) {
                return sprintf('%s %s', $stock, $this->getMetricSymbol());
            }

            return $stock;

        }

        return 0;
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
        return ($this->getStock() > 0 ? true : false);
    }

    /**
     * Takes the specified amount of stock from specified stock location
     *
     * @param int $quantity
     * @param $location
     * @return mixed
     * @throws InvalidLocationException
     * @throws StockNotFoundException
     */
    public function take($quantity = 0, $location)
    {
        if($this->isCollection($location)) {

            $stock = $this->getStockFromLocation($location);

        } else if(is_numeric($location)) {

            $location = $this->getLocationById($location);

            $stock = $this->getStockFromLocation($location);

        } else if(is_array($location)) {

            return $this->takeFromMany($quantity, $location);

        } else {

            throw new InvalidLocationException;

        }

        return $stock->take($quantity);

    }

    /**
     * Takes the specified amount of stock from many stock locations
     *
     * @param int $quantity
     * @param array $locations
     * @return array
     * @throws InvalidLocationException
     * @throws StockNotFoundException
     */
    public function takeFromMany($quantity = 0, $locations =  array())
    {
        $stocks = array();

        foreach($locations as $location) {

            if($this->isCollection($location)) {

                $stock = $this->getStockFromLocation($location);

            } else if(is_numeric($location)) {

                $location = $this->getLocationById($location);

                $stock = $this->getStockFromLocation($location);

            } else {

                throw new InvalidLocationException;

            }

            $stocks[] = $stock->take($quantity);

        }

        return $stocks;
    }

    /**
     * @param int $quantity
     */
    public function put($quantity = 0, $location)
    {

    }


    /**
     * Retrieves an inventory stock from a given location
     *
     * @param \Illuminate\Support\Collection $location
     * @return mixed
     * @throws StockNotFoundException
     */
    public function getStockFromLocation(Collection $location)
    {
        $stock = InventoryStock::
            where('inventory_id', $this->id)
            ->where('location_id', $location->id)
            ->first();

        if($stock) {

            return $stock;

        } else {

            throw new StockNotFoundException;

        }
    }

    /**
     * Retrieves a location by it's ID
     *
     * @param id|string $id
     * @return \Illuminate\Support\Collection|null|static
     */
    public function getLocationById($id)
    {
        return Location::find($id);
    }


    /**
     * Returns true or false if the specified object is a collection
     *
     * @param $object
     * @return bool
     */
    private function isCollection($object)
    {
        return ($object instanceof Collection ? true : false);
    }

}