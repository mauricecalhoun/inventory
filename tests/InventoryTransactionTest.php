<?php

use Stevebauman\Inventory\Models\InventoryStock;
use Stevebauman\Inventory\Models\InventoryTransactionHistory;
use Stevebauman\Inventory\Models\InventoryTransaction;

class InventoryTransactionTest extends InventoryStockTest
{
    public function setUp()
    {
        parent::setUp();

        InventoryTransaction::flushEventListeners();
        InventoryTransaction::boot();

        InventoryTransactionHistory::flushEventListeners();
        InventoryTransactionHistory::boot();
    }

    /**
     * Returns a new stock transaction for easier testing
     *
     * @return mixed
     */
    protected function newTransaction()
    {
        $this->testInventoryStockCreation();

        $stock = InventoryStock::find(1);

        return $stock->newTransaction();
    }

    public function testInventoryTransactionSetStateFailure()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidTransactionStateException');

        $transaction->state = 'test';
    }

    public function testInventoryTransactionSetStateSuccess()
    {
        $transaction = $this->newTransaction();

        $transaction->state = InventoryTransaction::STATE_COMMERCE_RESERVED;
    }
}