<?php

namespace Stevebauman\Inventory\Services;

use Stevebauman\Inventory\Exceptions\InventoryNotFoundException;
use Stevebauman\CoreHelper\Services\SentryService;
use Stevebauman\Inventory\Models\Inventory;
use Stevebauman\CoreHelper\Services\AbstractModelService;

/**
 * Handles inventory interactions
 *
 * Class InventoryService
 * @package Stevebauman\Maintenance\Services\Inventory
 */
class InventoryService extends AbstractModelService
{

    public function __construct(
        Inventory $inventory,
        SentryService $sentry,
        InventoryNotFoundException $notFoundException
    )
    {
        $this->model = $inventory;
        $this->sentry = $sentry;
        $this->notFoundException = $notFoundException;
    }

    /**
     * Returns all inventory items paginated, with eager loaded relationships,
     * as well as scopes for search.
     *
     * @return type Collection
     */
    public function getByPageWithFilter($archived = NULL)
    {
        return $this->model
            ->with(array(
                'category',
                'user',
                'stocks',
            ))
            ->id($this->getInput('id'))
            ->name($this->getInput('name'))
            ->description($this->getInput('description'))
            ->category($this->getInput('category_id'))
            ->stock(
                $this->getInput('operator'),
                $this->getInput('quantity')
            )
            ->archived($archived)
            ->sort($this->getInput('field'), $this->getInput('sort'))
            ->paginate(25);
    }

    /**
     * Creates an item record
     *
     * @return boolean OR object
     */
    public function create()
    {

        $this->dbStartTransaction();

        try {

            /*
             * Set input data
             */
            $insert = array(
                'category_id' => $this->getInput('category_id'),
                'user_id' => $this->sentry->getCurrentUserId(),
                'metric_id' => $this->getInput('metric'),
                'name' => $this->getInput('name', NULL, true),
                'description' => $this->getInput('description', NULL, true),
            );

            /*
             * If the record is created, return it, otherwise return false
             */
            $record = $this->model->create($insert);

            if ($record) {

                /*
                 * Fire created event
                 */
                $this->fireEvent('maintenance.inventory.created', array(
                    'item' => $record
                ));

                $this->dbCommitTransaction();

                return $record;

            }

            $this->dbRollbackTransaction();

            return false;

        } catch (\Exception $e) {

            $this->dbRollbackTransaction();

            return false;
        }

    }

    /**
     * Updates an item record
     *
     * @param type $id
     * @return boolean
     */
    public function update($id)
    {

        $this->dbStartTransaction();

        try {

            /*
             * Find the item record
             */
            $record = $this->find($id);

            /*
             * Set update data
             */
            $insert = array(
                'category_id' => $this->getInput('category_id', $record->category_id),
                'metric_id' => $this->getInput('metric'),
                'name' => $this->getInput('name', $record->name, true),
                'description' => $this->getInput('description', $record->description, true),
            );

            /*
             * Update the record, return it upon success
             */
            if ($record->update($insert)) {

                /*
                 * Fire updated event
                 */
                $this->fireEvent('maintenance.inventory.updated', array(
                    'item' => $record
                ));

                $this->dbCommitTransaction();

                return $record;

            }

            $this->dbRollbackTransaction();

            return false;

        } catch (\Exception $e) {

            $this->dbRollbackTransaction();

            return false;
        }

    }

    /*
     * Archives an item record
     */
    public function destroy($id)
    {

        $record = $this->find($id);

        $record->delete();

        /*
         * Fire archived event
         */
        $this->fireEvent('maintenance.inventory.archived', array(
            'item' => $record
        ));

        return true;
    }
}