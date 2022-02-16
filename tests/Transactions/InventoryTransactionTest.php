<?php

namespace Stevebauman\Inventory\Tests\Transactions;

use Stevebauman\Inventory\Models\InventoryTransactionHistory;
use Stevebauman\Inventory\Models\InventoryTransaction;
use Stevebauman\Inventory\Tests\FunctionalTestCase;

/**
 * Inventory Transaction Test
 * 
 * @coversDefaultClass \InventoryTransaction
 */
class InventoryTransactionTest extends FunctionalTestCase
{
    public function testInventoryTransactionStockNotFoundException()
    {
        $transaction = $this->newTransaction();

        $transaction->stock_id = 15;

        $this->expectException('Stevebauman\Inventory\Exceptions\StockNotFoundException');

        $transaction->getStockRecord();
    }

    public function testInventoryTransactionSetStateFailure()
    {
        $transaction = $this->newTransaction();

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidTransactionStateException');

        $transaction->state = 'test';
    }

    public function testInventoryTransactionSetStateSuccess()
    {
        $transaction = $this->newTransaction();

        $transaction->state = InventoryTransaction::STATE_COMMERCE_RESERVED;

        $this->assertEquals($transaction->state, InventoryTransaction::STATE_COMMERCE_RESERVED);
    }

    public function testInventoryTransactionGetByState()
    {
        $transaction = $this->newTransaction();

        $transaction->reserved(2);

        $results = InventoryTransaction::getByState(InventoryTransaction::STATE_COMMERCE_RESERVED);

        $this->assertEquals(1, $results->count());
    }
}
