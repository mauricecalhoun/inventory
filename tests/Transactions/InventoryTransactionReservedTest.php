<?php

namespace Stevebauman\Inventory\Tests\Transactions;

use Illuminate\Support\Facades\Lang;
use Stevebauman\Inventory\Models\InventoryTransaction;
use Stevebauman\Inventory\Tests\FunctionalTestCase;

/**
 * Inventory Transaction Reserved Test
 * 
 * @coversDefaultClass \InventoryTransaction
 */
class InventoryTransactionReservedTest extends FunctionalTestCase
{
    public function testInventoryTransactionReserved()
    {
        $transaction = $this->newTransaction();

        $transaction->reserved(10, $backOrder = false, 'Reservation', 25);

        $this->assertEquals(10, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_RESERVED, $transaction->state);

        $stock = $transaction->getStockRecord();

        $this->assertEquals(10, $stock->quantity);
        $this->assertEquals('Reservation', $stock->reason);
        $this->assertEquals(25, $stock->cost);
    }

    public function testInventoryTransactionReservedNotEnoughStockException()
    {
        $transaction = $this->newTransaction();

        $this->expectException('Stevebauman\Inventory\Exceptions\NotEnoughStockException');

        $transaction->reserved(100);
    }

    public function testInventoryTransactionReservedInvalidQuantityException()
    {
        $transaction = $this->newTransaction();

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $transaction->reserved('40a');
    }

    public function testInventoryTransactionReservedInvalidTransactionStateException()
    {
        $transaction = $this->newTransaction();

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidTransactionStateException');

        $transaction->hold(5)->reserved(20);
    }

    public function testInventoryTransactionReservedCheckout()
    {
        $transaction = $this->newTransaction();

        $transaction->checkout(5)->reserved();

        $this->assertEquals(5, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_RESERVED, $transaction->state);
    }

    public function testInventoryTransactionReservedDefaultReason()
    {
        $transaction = $this->newTransaction();

        Lang::shouldReceive('get')->once()->andReturn('test');

        $transaction->reserved(5);

        $stock = $transaction->getStockRecord();

        $this->assertEquals('test', $stock->reason);
    }
}
