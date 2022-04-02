<?php

namespace Stevebauman\Inventory\Traits;

use Illuminate\Database\Eloquent\Model;
use Stevebauman\Inventory\Exceptions\InvalidSupplierException;
use Stevebauman\Inventory\Exceptions\SkuAlreadyExistsException;
use Stevebauman\Inventory\Exceptions\StockNotFoundException;
use Stevebauman\Inventory\Exceptions\StockAlreadyExistsException;
use Stevebauman\Inventory\Exceptions\IsParentException;
use Stevebauman\Inventory\InventoryServiceProvider;
use Stevebauman\Inventory\Models\SupplierSKU;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;

/**
 * Trait InventoryTrait.
 */
trait InventoryTrait
{
    /*
     * Location helper functions
     */
    use LocationTrait;

    /*
     * Verification helper functions
     */
    use VerifyTrait;

    /*
     * Sets the model's constructor method to automatically assign the
     * created_by attribute to the current logged in user
     */
    use UserIdentificationTrait;

    /*
     * Helpers for starting database transactions
     */
    use DatabaseTransactionTrait;

    /**
     * The hasOne category relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    abstract public function category();

    /**
     * The hasOne metric relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    abstract public function metric();

    /**
     * The hasOne SKU relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    abstract public function sku();

    /**
     * The hasMany stocks relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    abstract public function stocks();

    /**
     * The belongsToMany suppliers relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    abstract public function suppliers();

    /**
     * The belongsToMany supplier SKU relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    abstract public function supplierSKUs();

    /**
     * The hasManyThrough attributes relationship.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    abstract public function customAttributes();

    /**
     * Overrides the models boot function to set the user
     * ID automatically to every new record.
     */
    public static function bootInventoryTrait()
    {
        /*
         * Assign the current users ID while the item
         * is being created
         */
        static::creating(function (Model $record) {
            $record->created_by = static::getCurrentUserId();
        });

        /*
         * Generate the items SKU once it's created
         */
        static::created(function (Model $record) {
            $record->generateSku();
        });

        /*
         * Generate an SKU if the item has been assigned a category,
         * this will not overwrite any SKU the item had previously
         */
        static::updated(function (Model $record) {
            if ($record->category_id !== null) {
                $record->generateSku();
            }
        });
    }

    /**
     * Returns an item record by the specified SKU code.
     *
     * @param string $sku
     *
     * @return bool
     */
    public static function findBySku($sku)
    {
        /*
         * Create a new static instance
         */
        $instance = new static();

        /*
         * Try and find the SKU record
         */
        $sku = $instance
            ->sku()
            ->getRelated()
            ->with('item')
            ->where('code', $sku)
            ->first();

        /*
         * Check if the SKU was found, and if an item is
         * attached to the SKU we'll return it
         */
        if ($sku && $sku->item) {
            return $sku->item;
        }

        /*
         * Return false on failure
         */
        return false;
    }

    /**
     * Returns the total sum of the current item stock.
     *
     * @return int|float
     */
    public function getTotalStock()
    {
        return $this->stocks->sum('quantity');
    }

    /**
     * Returns true/false if the inventory has a metric present.
     *
     * @return bool
     */
    public function hasMetric()
    {
        if ($this->metric) {
            return true;
        }

        return false;
    }

    /**
     * Returns true/false if the current item has an SKU.
     *
     * @return bool
     */
    public function hasSku()
    {
        if ($this->sku) {
            return true;
        }

        return false;
    }

    /**
     * Returns true/false if the current item has a category.
     *
     * @return bool
     */
    public function hasCategory()
    {
        if ($this->category) {
            return true;
        }

        return false;
    }

    /**
     * Returns the inventory's metric symbol.
     *
     * @return string|null
     */
    public function getMetricSymbol()
    {
        if ($this->hasMetric()) {
            return $this->metric->symbol;
        }

        return;
    }

    /**
     * Returns true/false if the inventory has stock.
     *
     * @return bool
     */
    public function isInStock()
    {
        return ($this->getTotalStock() > 0 ? true : false);
    }

    /**
     * Creates a stock record to the current inventory item.
     *
     * @param int|float|string $quantity
     * @param $location
     * @param string           $reason
     * @param int|float|string $cost
     * @param null             $aisle
     * @param null             $row
     * @param null             $bin
     *
     * @throws StockAlreadyExistsException
     * @throws StockNotFoundException
     * @throws \Stevebauman\Inventory\Exceptions\InvalidLocationException
     * @throws \Stevebauman\Inventory\Exceptions\NoUserLoggedInException
     * @throws \Stevebauman\Inventory\Exceptions\IsParentException
     *
     * @return Model
     */
    public function createStockOnLocation($quantity, $location, $reason = '', $cost = 0, $aisle = null, $row = null, $bin = null)
    {
        if (!$this->is_parent) {

            $location = $this->getLocation($location);
    
            try {
                /*
                 * We want to make sure stock doesn't exist on the specified location already
                 */
                if ($this->getStockFromLocation($location)) {
                    $message = Lang::get('inventory::exceptions.StockAlreadyExistsException', [
                        'location' => $location->name,
                    ]);
    
                    throw new StockAlreadyExistsException($message);
                }
            } catch (StockNotFoundException $e) {
                /*
                 * A stock record wasn't found on this location, we'll create one
                 */
                $insert = [
                    'inventory_id' => $this->getKey(),
                    'location_id' => $location->getKey(),
                    'quantity' => 0,
                    'aisle' => $aisle,
                    'row' => $row,
                    'bin' => $bin,
                ];
    
                /*
                 * We'll perform a create so a 'first' movement is generated
                 */
                $stock = $this->stocks()->create($insert);
    
                /*
                 * Now we'll 'put' the inserted quantity onto the generated stock
                 * and return the results
                 */
                return $stock->put($quantity, $reason, $cost);
            }
    
            return false;
        } else {
            $message = Lang::get('inventory::exceptions.IsParentException', [
                'parentName' => $this->name,
            ]);

            throw new IsParentException($message);
        }
    }

    /**
     * Instantiates a new stock on the specified
     * location on the current item.
     *
     * @param $location
     *
     * @throws StockAlreadyExistsException
     * @throws \Stevebauman\Inventory\Exceptions\InvalidLocationException
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function newStockOnLocation($location)
    {
        if (!$this->is_parent) {
            $location = $this->getLocation($location);
    
            try {
                /*
                 * We want to make sure stock doesn't exist on the specified location already
                 */
                if ($this->getStockFromLocation($location)) {
                    $message = Lang::get('inventory::exceptions.StockAlreadyExistsException', [
                        'location' => $location->name,
                    ]);
    
                    throw new StockAlreadyExistsException($message);
                }
            } catch (StockNotFoundException $e) {
                /*
                 * Create a new stock model instance
                 */
                $stock = $this->stocks()->getRelated();
    
                /*
                 * Assign the known attributes
                 * so devs don't have to
                 */
                $stock->inventory_id = $this->getKey();
                $stock->location_id = $location->getKey();
    
                return $stock;
            }
        } else {
            $message = Lang::get('inventory::exceptions.IsParentException', [
                'parentName' => $this->name,
            ]);

            throw new IsParentException($message);
        }
    }

    /**
     * Takes the specified amount ($quantity) of stock from specified stock location.
     *
     * @param int|float|string $quantity
     * @param $location
     * @param string           $reason
     *
     * @throws StockNotFoundException
     *
     * @return Model $this
     */
    public function takeFromLocation($quantity, $location, $reason = '')
    {
        /*
         * If the specified location is an array, we must be taking from
         * multiple locations
         */
        if (is_array($location)) {
            return $this->takeFromManyLocations($quantity, $location, $reason);
        } else {
            $stock = $this->getStockFromLocation($location);

            if ($stock->take($quantity, $reason)) {
                return $this;
            }
        }

        return false;
    }

    /**
     * Takes the specified amount ($quantity) of stock from the specified stock locations.
     *
     * @param int|float|string $quantity
     * @param array            $locations
     * @param string           $reason
     *
     * @throws StockNotFoundException
     *
     * @return array
     */
    public function takeFromManyLocations($quantity, $locations = [], $reason = '')
    {
        $stocks = [];

        foreach ($locations as $location) {
            $stock = $this->getStockFromLocation($location);

            $stocks[] = $stock->take($quantity, $reason);
        }

        return $stocks;
    }

    /**
     * Alias for the `take` function.
     *
     * @param int|float|string $quantity
     * @param $location
     * @param string           $reason
     *
     * @return array
     */
    public function removeFromLocation($quantity, $location, $reason = '')
    {
        return $this->takeFromLocation($quantity, $location, $reason);
    }

    /**
     * Alias for the `takeFromMany` function.
     *
     * @param int|float|string $quantity
     * @param array            $locations
     * @param string           $reason
     *
     * @return array
     */
    public function removeFromManyLocations($quantity, $locations = [], $reason = '')
    {
        return $this->takeFromManyLocations($quantity, $locations, $reason);
    }

    /**
     * Puts the specified amount ($quantity) of stock into the specified stock location(s).
     *
     * @param int|float|string $quantity
     * @param $location
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws StockNotFoundException
     *
     * @return array
     */
    public function putToLocation($quantity, $location, $reason = '', $cost = 0)
    {
        if (is_array($location)) {
            return $this->putToManyLocations($quantity, $location);
        } else {
            $stock = $this->getStockFromLocation($location);

            if ($stock->put($quantity, $reason, $cost)) {
                return $this;
            }
        }

        return false;
    }

    /**
     * Puts the specified amount ($quantity) of stock into the specified stock locations.
     *
     * @param int|float|string $quantity
     * @param array            $locations
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws StockNotFoundException
     *
     * @return array
     */
    public function putToManyLocations($quantity, $locations = [], $reason = '', $cost = 0)
    {
        $stocks = [];

        foreach ($locations as $location) {
            $stock = $this->getStockFromLocation($location);

            $stocks[] = $stock->put($quantity, $reason, $cost);
        }

        return $stocks;
    }

    /**
     * Alias for the `put` function.
     *
     * @param int|float|string $quantity
     * @param $location
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @return array
     */
    public function addToLocation($quantity, $location, $reason = '', $cost = 0)
    {
        return $this->putToLocation($quantity, $location, $reason, $cost);
    }

    /**
     * Alias for the `putToMany` function.
     *
     * @param int|float|string $quantity
     * @param array            $locations
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @return array
     */
    public function addToManyLocations($quantity, $locations = [], $reason = '', $cost = 0)
    {
        return $this->putToManyLocations($quantity, $locations, $reason, $cost);
    }

    /**
     * Moves a stock from one location to another.
     *
     * @param $fromLocation
     * @param $toLocation
     *
     * @throws StockNotFoundException
     *
     * @return mixed
     */
    public function moveStock($fromLocation, $toLocation)
    {
        $stock = $this->getStockFromLocation($fromLocation);

        $toLocation = $this->getLocation($toLocation);

        return $stock->moveTo($toLocation);
    }

    /**
     * Retrieves an inventory stock from a given location.
     *
     * @param $location
     *
     * @throws \Stevebauman\Inventory\Exceptions\InvalidLocationException
     * @throws StockNotFoundException
     *
     * @return mixed
     */
    public function getStockFromLocation($location)
    {
        $location = $this->getLocation($location);

        $stock = $this->stocks()
            ->where('inventory_id', $this->getKey())
            ->where('location_id', $location->getKey())
            ->first();

        if ($stock) {
            return $stock;
        } else {
            $message = Lang::get('inventory::exceptions.StockNotFoundException', [
                'location' => $location->name,
            ]);

            throw new StockNotFoundException($message);
        }
    }

    /**
     * Returns the item's SKU.
     *
     * @return null|string
     */
    public function getSku()
    {
        if ($this->hasSku()) {
            return $this->sku->code;
        }

        return;
    }

    /**
     * Laravel accessor for the current items SKU.
     *
     * @return null|string
     */
    public function getSkuCodeAttribute()
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
        /*
         * Make sure sku generation is enabled and the item has a category, if not we'll return false.
         */
        if (!$this->skusEnabled() || !$this->hasCategory()) {
            return false;
        }

        /*
         * If the item already has an SKU, we'll return it
         */
        if ($this->hasSku()) {
            return $this->sku;
        }

        /*
         * Get the set SKU code length from the configuration file
         */
        $codeLength = Config::get('inventory'.InventoryServiceProvider::$packageConfigSeparator.'sku_code_length');

        /*
         * Get the set SKU prefix length from the configuration file
         */
        $prefixLength = Config::get('inventory'.InventoryServiceProvider::$packageConfigSeparator.'sku_prefix_length');

        /*
         * Get the set SKU separator
         */
        $skuSeparator = Config::get('inventory'.InventoryServiceProvider::$packageConfigSeparator.'sku_separator');

        /*
         * Make sure we trim empty spaces in the separator if it's a string, otherwise we'll
         * set it to NULL
         */
        $skuSeparator = (is_string($skuSeparator) ? trim($skuSeparator) : null);

        /*
         * Trim the category name to remove blank spaces, then
         * grab the first 3 letters of the string, and uppercase them
         */
        $prefix = strtoupper(substr(trim($this->category->name), 0, intval($prefixLength)));

        /*
         * We'll make sure the prefix length is greater than zero before we try and
         * generate an SKU
         */
        if (strlen($prefix) > 0) {
            /*
             * Create the numerical code by the items ID
             * to accompany the prefix and pad left zeros
             */
            $code = str_pad($this->getKey(), $codeLength, '0', STR_PAD_LEFT);

            /*
             * Process the generation
             */
            return $this->processSkuGeneration($this->getKey(), $prefix.$skuSeparator.$code);
        }

        /*
         * Always return false on generation failure
         */
        return false;
    }

    /**
     * Regenerates the current items SKU by
     * deleting its current SKU and creating
     * another. This will also generate an SKU
     * if one does not exist.
     *
     * @return bool|mixed
     */
    public function regenerateSku()
    {
        $sku = $this->sku()->first();

        if ($sku) {
            /*
             * Capture current SKU
             */
            $previousSku = $sku;

            /*
             * Delete current SKU
             */
            $sku->delete();

            /*
             * Try to generate a new SKU
             */
            $newSku = $this->generateSku();

            /*
             * New sku generation successful, return it
             */
            if ($newSku) {
                return $newSku;
            }

            /*
             * Failed generating a new sku, we'll restore the old one
             */
            return $this->processSkuGeneration($this->getKey(), $previousSku->code);
        }

        /*
         * Always generate an SKU if one doesn't exist
         */
        return $this->generateSku();
    }

    /**
     * Creates an SKU with the specified code. If overwrite is true,
     * the current items SKU will be deleted if it exists before creating
     * then SKU. If overwrite is false but the item has an SKU, an exception
     * is thrown.
     *
     * @param string $code
     * @param bool   $overwrite
     *
     * @throws SkuAlreadyExistsException
     *
     * @return mixed|bool
     */
    public function createSku($code, $overwrite = false)
    {
        /*
         * Get the current SKU record
         */
        $sku = $this->sku()->first();

        if ($sku) {
            /*
             * The dev doesn't want the SKU overridden,
             * we'll thrown an exception
             */
            if (!$overwrite) {
                $message = Lang::get('inventory::exceptions.SkuAlreadyExistsException');

                throw new SkuAlreadyExistsException($message);
            }

            /*
             * Overwrite is true, lets update the current SKU
             */
            return $this->updateSku($code, $sku);
        }

        /*
         * No SKU exists, lets create one
         */
        return $this->processSkuGeneration($this->getKey(), $code);
    }

    /**
     * Updates the items current SKU or the SKU
     * supplied with the specified code.
     *
     * @param string $code
     * @param null   $sku
     *
     * @return mixed|bool
     */
    public function updateSku($code, $sku = null)
    {
        /*
         * Get the current SKU record if one isn't supplied
         */
        if (!$sku) {
            $sku = $this->sku()->first();
        }

        /*
         * If an SKU still doesn't exist after
         * trying to find one, we'll create one
         */
        if (!$sku) {
            return $this->processSkuGeneration($this->getKey(), $code);
        }

        return $this->processSkuUpdate($sku, $code);
    }

    /**
     * Adds all of the specified suppliers inside
     * the array to the current inventory item.
     *
     * @param array $suppliers
     *
     * @return bool
     */
    public function addSuppliers($suppliers = [])
    {
        if (!$this->is_parent) {
            foreach ($suppliers as $supplier) {
                $this->addSupplier($supplier);
            }
    
            return true;
        } else {
            $message = Lang::get('inventory::exceptions.IsParentException', [
                'parentName' => $this->name,
            ]);

            throw new IsParentException($message);
        }
    }

    /**
     * Removes all suppliers from the current item.
     *
     * @return bool
     */
    public function removeAllSuppliers()
    {
        $suppliers = $this->suppliers()->get();

        foreach ($suppliers as $supplier) {
            $this->removeSupplier($supplier);
        }

        return true;
    }

    /**
     * Removes all of the specified suppliers inside
     * the array from the current inventory item.
     *
     * @param array $suppliers
     *
     * @return bool
     */
    public function removeSuppliers($suppliers = [])
    {
        foreach ($suppliers as $supplier) {
            $this->removeSupplier($supplier);
        }

        return true;
    }

    /**
     * Adds the specified supplier to the current inventory item.
     *
     * @param $supplier
     *
     * @throws InvalidSupplierException
     *
     * @return bool
     */
    public function addSupplier($supplier)
    {
        if (!$this->is_parent) {
            $supplier = $this->getSupplier($supplier);
    
            return $this->processSupplierAttach($supplier);
        } else {
            $message = Lang::get('inventory::exceptions.IsParentException', [
                'parentName' => $this->name,
            ]);

            throw new IsParentException($message);
        }
    }

    /**
     * Removes the specified supplier from the current inventory item.
     *
     * @param $supplier
     *
     * @throws InvalidSupplierException
     *
     * @return bool
     */
    public function removeSupplier($supplier)
    {
        $supplier = $this->getSupplier($supplier);

        return $this->processSupplierDetach($supplier);
    }

    /**
     * Retrieves a supplier from the specified variable.
     *
     * @param $supplier
     *
     * @throws InvalidSupplierException
     *
     * @return mixed
     */
    public function getSupplier($supplier)
    {
        if ($this->isNumeric($supplier)) {
            return $this->getSupplierById($supplier);
        } elseif ($this->isModel($supplier)) {
            return $supplier;
        } else {
            $message = Lang::get('inventory::exceptions.InvalidSupplierException', [
                'supplier' => $supplier,
            ]);

            throw new InvalidSupplierException($message);
        }
    }

    public function addSupplierSKU($supplier, $sku)
    {
        $supplierModel = $this->resolveSupplier($supplier);

        $this->supplierSKUs()->updateOrCreate(['supplier_id'=>$supplierModel->id], ['supplier_id' => $supplierModel->id, 'supplier_sku' => $sku]);
    }

    /**
     * TODO:
     * Retrieves the sku code corresponding to this inventory item 
     * for the given supplier
     *
     * @param mixed $supplier
     * 
     * @return string
     */
    public function getSupplierSKU($supplier) 
    {
        $supplierModel = $this->resolveSupplier($supplier);
        $sku = $this->supplierSKUs->where('supplier_id', $supplierModel->id)->first();
        
        return $sku->supplier_sku;
    }

    public function updateSupplierSKU($supplier, $sku) {
        $supplierModel = $this->resolveSupplier($supplier);

        $skuModel = $this->supplierSKUs()->updateOrCreate(
            ['supplier_id'=>$supplierModel->id], 
            [
                'supplier_id' => $supplierModel->id, 
                'supplier_sku' => $sku
            ]
        );

        return $skuModel;
    }

    /**
     * Resolves the supplier model based on a supplier id
     * or just returns the model
     *
     * @param mixed $supplier
     * 
     * @return \Stevebauman\Inventory\Models\Supplier
     * 
     * @throws InvalidSupplierException
     */
    private function resolveSupplier($supplier) {
        $s = null;
        if ($this->isNumeric($supplier)) {
            $s = $this->getSupplierById($supplier);
        } elseif ($this->isModel($supplier)) {
            $s = $supplier;
        } elseif (is_string($supplier)) {
            $s = $this->suppliers->where('code', $supplier)->first();
        } 
        
        if(is_null($s)) {
            $message = "Supplier not found when attempting to resolve " . $supplier;

            throw new InvalidSupplierException($message);
        }

        return $s;
    }

    /**
     * Processes an SKU generation covered by database transactions.
     *
     * @param int|string $inventoryId
     * @param string     $code
     *
     * @return bool|mixed
     */
    private function processSkuGeneration($inventoryId, $code)
    {
        $this->dbStartTransaction();

        try {
            $insert = [
                'inventory_id' => $inventoryId,
                'code' => $code,
            ];

            $record = $this->sku()->create($insert);

            if ($record) {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.sku.generated', [
                    'item' => $this,
                ]);

                return $record;
            }
        } catch (\Exception $e) {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Processes updating the specified SKU
     * record with the specified code.
     *
     * @param Model  $sku
     * @param string $code
     *
     * @return mixed|bool
     */
    private function processSkuUpdate(Model $sku, $code)
    {
        $this->dbStartTransaction();

        try {
            if ($sku->update(compact('code'))) {
                $this->dbCommitTransaction();

                return $sku;
            }
        } catch (\Exception $e) {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Processes attaching a supplier to an inventory item.
     *
     * @param Model $supplier
     *
     * @return bool
     */
    private function processSupplierAttach(Model $supplier)
    {
        $this->dbStartTransaction();

        try {
            $this->suppliers()->attach($supplier);

            $this->dbCommitTransaction();

            $this->fireEvent('inventory.supplier.attached', [
                'item' => $this,
                'supplier' => $supplier,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Processes detaching a supplier.
     *
     * @param Model $supplier
     *
     * @return bool
     */
    private function processSupplierDetach(Model $supplier)
    {
        $this->dbStartTransaction();

        try {
            $this->suppliers()->detach($supplier);

            $this->dbCommitTransaction();

            $this->fireEvent('inventory.supplier.detached', [
                'item' => $this,
                'supplier' => $supplier,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Returns a supplier by the specified ID.
     *
     * @param int|string $id
     *
     * @return mixed
     */
    private function getSupplierById($id)
    {
        return $this->suppliers()->find($id);
    }

    /**
     * Returns the configuration option for the
     * enablement of automatic SKU generation.
     *
     * @return mixed
     */
    private function skusEnabled()
    {
        return Config::get('inventory'.InventoryServiceProvider::$packageConfigSeparator.'skus_enabled', false);
    }
}
