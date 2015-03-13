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

    public function testInventoryTransactionSetStateFailure()
    {
        $this->testInventoryStockCreation();

        $stock = InventoryStock::find(1);

        $transaction = $stock->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidTransactionStateException');

        $transaction->state = 'test';
    }

    public function testInventoryTransactionSetStateSuccess()
    {
        $this->testInventoryStockCreation();

        $stock = InventoryStock::find(1);

        $transaction = $stock->newTransaction();

        $transaction->state = InventoryTransaction::STATE_COMMERCE_RESERVERD;
    }

    public function testInventoryTransactionCheckout()
    {
        $this->testInventoryStockCreation();

        $stock = InventoryStock::find(1);

        $transaction = $stock->newTransaction();

        $transaction->checkout(5);

        $this->assertEquals(5, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_CHECKOUT, $transaction->state);
    }

    public function testInventoryTransactionCheckoutFailure()
    {
        $this->testInventoryStockCreation();

        $stock = InventoryStock::find(1);

        $transaction = $stock->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\NotEnoughStockException');

        $transaction->checkout(5000);
    }

    public function testInventoryTransactionQuantityFailure()
    {
        $this->testInventoryStockCreation();

        $stock = InventoryStock::find(1);

        $transaction = $stock->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $transaction->checkout('30as');
    }
}