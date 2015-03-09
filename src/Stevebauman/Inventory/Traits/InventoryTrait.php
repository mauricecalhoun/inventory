<?php

namespace Stevebauman\Inventory\Traits;

use Stevebauman\Inventory\Exceptions\InvalidSupplierException;
use Stevebauman\Inventory\Exceptions\StockNotFoundException;
use Stevebauman\Inventory\Exceptions\StockAlreadyExistsException;
use Stevebauman\Inventory\InventoryServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;

/**
 * Class InventoryTrait
 * @package Stevebauman\Inventory\Traits
 */
trait InventoryTrait
{
    /**
     * Location helper functions
     */
    use LocationTrait;

    /**
     * Verification helper functions
     */
    use VerifyTrait;

    /**
     * Set's the models constructor method to automatically assign the
     * user_id's attribute to the current logged in user
     */
    use UserIdentificationTrait;

    /**
     * Helpers for starting database transactions
     */
    use DatabaseTransactionTrait;

    /**
     * The hasOne category relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    abstract public function category();

    /**
     * The hasOne metric relationship
     *
     * @return mixed
     */
    abstract public function metric();

    /**
     * The hasOne SKU relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    abstract public function sku();

    /**
     * The hasMany stocks relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    abstract public function stocks();

    /**
     * The belongsToMany suppliers relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    abstract public function suppliers();

    /**
     * Overrides the models boot function to set the user ID automatically
     * to every new record
     */
    public static function boot()
    {
        parent::boot();

        /*
         * Assign the current users ID while the item
         * is being created
         */
        parent::creating(function($record)
        {
            $record->user_id = parent::getCurrentUserId();
        });

        /*
         * Generate the items SKU once it's created
         */
        parent::created(function($record)
        {
            $record->generateSku();
        });

        /*
         * Generate an SKU if the item has been assigned a category,
         * this will not overwrite any SKU the item had previously
         */
        parent::updated(function($record)
        {
            if($record->category_id != NULL) $record->generateSku();
        });
    }

    /**
     * Returns an item record by the specified SKU code
     *
     * @param string $sku
     * @return bool
     */
    public static function findBySku($sku)
    {
        /*
         * Create a new static instance
         */
        $instance = new static;

        /*
         * Try and find the SKU record
         */
        $sku = $instance
            ->sku()
            ->getRelated()
            ->where('code', $sku)
            ->first();

        /*
         * Check if the SKU was found, and if an item is attached to the SKU
         * we'll return it
         */
        if($sku && $sku->item()) return $sku->item;

        /*
         * Return false on failure
         */
        return false;
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
        if($this->metric()->count() > 0) return true;

        return false;
    }

    /**
     * Returns true/false if the current item has an SKU
     *
     * @return bool
     */
    public function hasSku()
    {
        if($this->sku()->count() > 0) return true;

        return false;
    }

    /**
     * Returns true/false if the current item has a category
     *
     * @return bool
     */
    public function hasCategory()
    {
        if($this->category()->count() > 0) return true;

        return false;
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
     * Creates a stock record to the current inventory item
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
    public function createStockOnLocation($quantity, $location, $reason = '', $cost = 0, $aisle = NULL, $row = NULL, $bin = NULL)
    {
        $location = $this->getLocation($location);

        try
        {
            if ($this->getStockFromLocation($location))
            {
                $message = Lang::get('inventory::exceptions.StockAlreadyExistsException', array(
                    'location' => $location->name,
                ));

                throw new StockAlreadyExistsException($message);
            }
        } catch (StockNotFoundException $e)
        {
            $insert = array(
                'inventory_id' => $this->id,
                'location_id' => $location->id,
                'quantity' => 0,
                'aisle' => $aisle,
                'row' => $row,
                'bin' => $bin,
            );

            $stock = $this->stocks()->create($insert);

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
    public function takeFromLocation($quantity, $location, $reason = '')
    {
        if (is_array($location))
        {
            return $this->takeFromManyLocations($quantity, $location, $reason);
        } else
        {
            $stock = $this->getStockFromLocation($location);

            if ($stock->take($quantity, $reason)) return $this;
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
    public function takeFromManyLocations($quantity, $locations = array(), $reason = '')
    {
        $stocks = array();

        foreach ($locations as $location)
        {
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
    public function removeFromLocation($quantity, $location, $reason = '')
    {
        return $this->takeFromLocation($quantity, $location, $reason);
    }

    /**
     * Alias for the `takeFromMany` function
     *
     * @param $quantity
     * @param array $locations
     * @param string $reason
     * @return array
     */
    public function removeFromManyLocations($quantity, $locations = array(), $reason = '')
    {
        return $this->takeFromManyLocations($quantity, $locations, $reason);
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
    public function putToLocation($quantity, $location, $reason = '', $cost = 0)
    {
        if (is_array($location))
        {
            return $this->putToManyLocations($quantity, $location);
        } else
        {
            $stock = $this->getStockFromLocation($location);

            if ($stock->put($quantity, $reason, $cost)) return $this;
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
    public function putToManyLocations($quantity, $locations = array(), $reason = '', $cost = 0)
    {
        $stocks = array();

        foreach ($locations as $location)
        {
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
    public function addToLocation($quantity, $location, $reason = '', $cost = 0)
    {
        return $this->putToLocation($quantity, $location, $reason, $cost);
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
    public function addToManyLocations($quantity, $locations = array(), $reason = '', $cost = 0)
    {
        return $this->putToManyLocations($quantity, $locations, $reason, $cost);
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
     * @throws InvalidLocationException
     * @throws StockNotFoundException
     */
    public function getStockFromLocation($location)
    {
        $location = $this->getLocation($location);

        $stock = $this->stocks()
            ->where('inventory_id', $this->id)
            ->where('location_id', $location->id)
            ->first();

        if ($stock)
        {
            return $stock;
        } else
        {
            $message = Lang::get('inventory::exceptions.StockNotFoundException', array(
                'location' => $location->name,
            ));

            throw new StockNotFoundException($message);
        }
    }

    /**
     * Returns the item's SKU
     *
     * @return null|string
     */
    public function getSku()
    {
        if($this->hasSku())
        {
            $sku = $this->sku()->first();

            return $sku->code;
        }

        return NULL;
    }

    /**
     * Laravel accessor for the current items SKU
     *
     * @return null|string
     */
    public function getSkuAttribute()
    {
        return $this->getSku();
    }

    /**
     * Generates an item SKU record.
     *
     * If an item already has an SKU, the SKU record will be returned.
     *
     * If an item does not have a category, it will return false.
     *
     * @return bool|mixed
     */
    public function generateSku()
    {
        $skusEnabled = Config::get('inventory'. InventoryServiceProvider::$packageConfigSeparator .'skus_enabled', false);

        /*
         * Make sure sku generation is enabled and the item has a category, if not we'll return false.
         */
        if(!$skusEnabled || !$this->hasCategory()) return false;

        /*
         * If the item already has an SKU, we'll return it
         */
        if($this->hasSku()) return $this->sku()->first();

        /*
         * Get the set SKU code length from the configuration file
         */
        $codeLength = Config::get('inventory' . InventoryServiceProvider::$packageConfigSeparator . 'sku_code_length');

        /*
         * Get the set SKU prefix length from the configuration file
         */
        $prefixLength = Config::get('inventory' . InventoryServiceProvider::$packageConfigSeparator . 'sku_prefix_length');

        /*
         * Get the set SKU separator
         */
        $skuSeparator = Config::get('inventory' . InventoryServiceProvider::$packageConfigSeparator . 'sku_separator');

        /*
         * Make sure we trim empty spaces in the separator if it's a string, otherwise we'll
         * set it to NULL
         */
        $skuSeparator = (is_string($skuSeparator) ? trim($skuSeparator) : NULL);

        /*
         * Trim the category name to remove blank spaces, then
         * grab the first 3 letters of the string, and uppercase them
         */
        $prefix = strtoupper(substr(trim($this->category->name), 0, $prefixLength));

        /*
         * We'll make sure the prefix length is greater than zero before we try and
         * generate an SKU
         */
        if(strlen($prefix) > 0)
        {
            /*
             * Create the numerical code by the items ID
             * to accompany the prefix and pad left zeros
             */
            $code = str_pad($this->id, $codeLength, '0', STR_PAD_LEFT);

            /*
             * Process the generation
             */
            return $this->processSkuGeneration($this->id, $prefix.$skuSeparator.$code);
        }

        /*
         * Always return false on generation failure
         */
        return false;
    }

    /**
     * Regenerates the current items SKU by deleting its current SKU
     * and creating another
     *
     * @return bool|mixed
     */
    public function regenerateSku()
    {
        if($this->hasSku())
        {
            /*
             * Capture current SKU
             */
            $previousSku = $this->sku()->first();

            /*
             * Delete current SKU
             */
            $this->sku()->delete();

            /*
             * Try to generate a new SKU
             */
            $newSku = $this->generateSku();

            /*
             * New sku generation successful, return it
             */
            if($newSku) return $newSku;

            /*
             * Failed generating a new sku, we'll restore the old one
             */
            return $this->processSkuGeneration($this->id, $previousSku->code);
        }

        return false;
    }

    /**
     * Adds all of the specified suppliers inside the array to
     * the current inventory item
     *
     * @param array $suppliers
     * @return bool
     */
    public function addSuppliers($suppliers = array())
    {
        foreach($suppliers as $supplier)
        {
            $this->addSupplier($supplier);
        }

        return true;
    }

    /**
     * Removes all suppliers from the current item
     *
     * @return bool
     */
    public function removeAllSuppliers()
    {
        $suppliers = $this->suppliers()->get();

        foreach($suppliers as $supplier)
        {
            $this->removeSupplier($supplier);
        }

        return true;
    }

    /**
     * Removes all of the specified suppliers inside the array from
     * the current inventory item
     *
     * @param array $suppliers
     * @return bool
     */
    public function removeSuppliers($suppliers = array())
    {
        foreach($suppliers as $supplier)
        {
            $this->removeSupplier($supplier);
        }

        return true;
    }

    /**
     * Adds the specified supplier to the current inventory item
     *
     * @param $supplier
     * @return bool
     * @throws InvalidSupplierException
     */
    public function addSupplier($supplier)
    {
        $supplier = $this->getSupplier($supplier);

        return $this->processSupplierAttach($supplier);
    }

    /**
     * Removes the specified supplier from the current inventory item
     *
     * @param $supplier
     * @return bool
     * @throws InvalidSupplierException
     */
    public function removeSupplier($supplier)
    {
        $supplier = $this->getSupplier($supplier);

        return $this->processSupplierDetach($supplier);
    }

    /**
     * Retrieves a supplier from the specified variable
     *
     * @param $supplier
     * @return mixed
     * @throws InvalidSupplierException
     */
    public function getSupplier($supplier)
    {
        if($this->isNumeric($supplier))
        {
            return $this->getSupplierById($supplier);

        } else if($this->isModel($supplier))
        {
            return $supplier;
        } else
        {
            $message = Lang::get('inventory::exceptions.InvalidSupplierException', array(
                'supplier' => $supplier,
            ));

            throw new InvalidSupplierException($message);
        }
    }

    /**
     * Processes an SKU generation covered by database transactions
     *
     * @param int|string $inventoryId
     * @param string $code
     * @return bool|mixed
     */
    private function processSkuGeneration($inventoryId, $code)
    {
        $this->dbStartTransaction();

        try
        {
            $insert = array(
                'inventory_id' => $inventoryId,
                'code' => $code,
            );

            $record = $this->sku()->create($insert);

            if ($record)
            {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.sku.generated', array(
                    'item' => $this,
                ));

                return $record;
            }
        }
        catch(\Exception $e)
        {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Processes attaching a supplier to an inventory item
     *
     * @param $supplier
     * @return bool
     */
    private function processSupplierAttach($supplier)
    {
        $this->dbStartTransaction();

        try
        {
            $this->suppliers()->attach($supplier);

            $this->dbCommitTransaction();

            $this->fireEvent('inventory.supplier.attached', array(
                'item' => $this,
                'supplier' => $supplier,
            ));

            return true;
        } catch(\Exception $e)
        {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Processes detaching a supplier
     *
     * @param $supplier
     * @return bool
     */
    private function processSupplierDetach($supplier)
    {
        $this->dbStartTransaction();

        try
        {
            $this->suppliers()->detach($supplier);

            $this->dbCommitTransaction();

            $this->fireEvent('inventory.supplier.detached', array(
                'item' => $this,
                'supplier' => $supplier,
            ));

            return true;
        } catch(\Exception $e)
        {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Returns a supplier by the specified ID
     *
     * @param $id
     * @return mixed
     */
    private function getSupplierById($id)
    {
        return $this->suppliers()->find($id);
    }
}