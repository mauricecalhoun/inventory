<?php

namespace Stevebauman\Inventory\Services;

use Stevebauman\Inventory\Exceptions\InventoryStockNotFoundException;
use Stevebauman\Inventory\Models\InventoryStock;
use Stevebauman\CoreHelper\Services\AbstractModelService;

/**
 * Handles inventory stock interactions
 *
 * Class StockService
 * @package Stevebauman\Maintenance\Services\Inventory
 */
class InventoryStockService extends AbstractModelService
{

    public function __construct(
        InventoryStock $inventoryStock,
        InventoryStockMovementService $inventoryStockMovement,
        InventoryStockNotFoundException $notFoundException
    )
    {
        $this->model = $inventoryStock;
        $this->inventoryStockMovement = $inventoryStockMovement;
        $this->notFoundException = $notFoundException;
    }

    /**
     * Creates a stock record as well as a first record movement
     *
     * @return boolean OR object
     */
    public function create()
    {

        $this->dbStartTransaction();

        try {

            /*
             * Set insert data
             */
            $insert = array(
                'inventory_id' => $this->getInput('inventory_id'),
                'location_id' => $this->getInput('location_id'),
                'quantity' => $this->getInput('quantity')
            );

            /*
             * Create the stock record
             */
            $record = $this->model->create($insert);

            if ($record) {

                /*
                 * Set first movement data
                 */
                $movement = array(
                    'stock_id' => $record->id,
                    'before' => 0,
                    'after' => $record->quantity,
                    'reason' => 'First Item Record; Stock Increase',
                    'cost' => $this->getInput('cost'),
                );

                /*
                 * If the inventory movement has been successfully created, return the record. 
                 * Otherwise delete it.
                 */
                if ($this->inventoryStockMovement->setInput($movement)->create()) {

                    /*
                     * Fire stock created event
                     */
                    $this->fireEvent('maintenance.inventory.stock.created', array(
                        'stock' => $record
                    ));

                    $this->dbCommitTransaction();

                    return $record;

                }
            }

            $this->dbRollbackTransaction();

            return false;


        } catch (\Exception $e) {

            $this->dbRollbackTransaction();

            return false;

        }
    }

    /**
     * Updates the current stock record and creates a stock movement when it has
     * been updated.
     *
     * @param type $id
     * @return boolean OR object
     */
    public function update($id)
    {

        $this->dbStartTransaction();

        try {

            $record = $this->find($id);

            /*
             * Set update data
             */
            $insert = array(
                'location_id' => $this->getInput('location_id', $record->location_id),
                'quantity' => $this->getInput('quantity', $record->quantity),
            );

            /*
             * Update the stock record
             */
            if ($record->update($insert)) {

                /*
                 * Create the movement
                 */
                if ($this->createUpdateMovement($record)) {

                    /*
                     * Fire stock updated event
                     */
                    $this->fireEvent('maintenance.inventory.stock.updated', array(
                        'stock' => $record
                    ));

                    $this->dbCommitTransaction();

                    /*
                     * Return updated stock record
                     */
                    return $record;

                }

            }

            /*
             * Rollback on failure to update the stock record
             */
            $this->dbRollbackTransaction();

            return false;

        } catch (\Exception $e) {

            $this->dbRollbackTransaction();

            return false;
        }
    }

    /**
     * Creates a stock movement record
     *
     * @param type $record
     * @return boolean
     */
    private function createUpdateMovement($record)
    {

        $this->dbStartTransaction();

        try {

            /*
             * Set movement insert data
             */
            $movement = array(
                'stock_id' => $record->id,
                'before' => $record->movements->first()->after,
                'after' => $record->quantity,
                'reason' => $this->getInput('reason', NULL, true),
                'cost' => $this->getInput('cost'),
            );

            /*
             * Create the stock movement
             */
            $this->inventoryStockMovement->setInput($movement)->create();

            $this->dbCommitTransaction();

            return true;

        } catch (\Exception $e) {

            $this->dbRollbackTransaction();

            return false;
        }
    }

    /**
     * Updates the stock record by taking away the inputted stock by the current stock,
     * effectively processing a "taking from stock" action.
     *
     * @param type $id
     * @return boolean OR object
     */
    public function take($id)
    {

        $this->dbStartTransaction();

        try {
            /*
             * Find the stock record
             */
            $record = $this->find($id);

            /*
             * Set update data
             */
            $insert = array(
                'quantity' => $record->quantity - $this->getInput('quantity'),
            );

            /*
             * Update stock record
             */
            if ($record->update($insert)) {

                /*
                 * Create the movement
                 */
                if ($this->createUpdateMovement($record)) {

                    /*
                     * Fire stock taken event
                     */
                    $this->fireEvent('maintenance.inventory.stock.taken', array(
                        'stock' => $record
                    ));

                    $this->dbCommitTransaction();

                    return $record;

                }

            }

            /*
             * Rollback on failure to update the record
             */
            $this->dbRollbackTransaction();

            return false;

        } catch (\Exception $e) {

            $this->dbRollbackTransaction();

            return false;
        }

    }

    /**
     * Updates the stock record by adding the inputted stock to the current stock,
     * effectively processing a "putting into the stock" action.
     *
     * @param type $id
     * @return mixed
     */
    public function put($id)
    {

        $this->dbStartTransaction();

        try {

            /*
             * Find the stock record
             */
            $record = $this->find($id);

            /*
             * Set update data
             */
            $insert = array(
                'quantity' => $record->quantity + $this->getInput('quantity'),
            );

            /*
             * Update the record
             */
            if ($record->update($insert)) {

                /*
                 * Create the movement
                 */
                if ($this->createUpdateMovement($record)) {

                    /*
                     * Fire stock put event
                     */
                    $this->fireEvent('maintenance.inventory.stock.put', array(
                        'stock' => $record
                    ));

                    $this->dbCommitTransaction();

                    /*
                     * Return the record
                     */
                    return $record;

                }

            }

            $this->dbRollbackTransaction();

            return false;

        } catch (\Exception $e) {

            $this->dbRollbackTransaction();

            return false;
        }

        /*
         * Stock record not found
         */
        return false;
    }

}