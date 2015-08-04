<?php

/**
 * The Inventory reasons language file.
 *
 * @author Steve Bauman
 */
return [

    'first_record' => 'First Item Record; Stock Increase',

    'change' => 'Stock Adjustment',

    'rollback' => 'Rolled back to movement ID: :id on :date',

    'transactions' => [

        'checkout' => 'Checkout occurred on Transaction ID: :id on :date',

        'sold-amount' => 'Partial sale occurred on Transaction ID: :id on :date',

        'returned' => 'Full return occurred on Transaction ID: :id on :date',

        'returned-partial' => 'Partial return occurred on Transaction ID: :id on :date',

        'reserved' => 'Reservation occurred on Transaction ID: :id on :date',

        'received' => 'Order fully received on Transaction ID :id on :date',

        'received-partial' => 'Order partially received on Transaction ID :id on :date',

        'back-order-filled' => 'Back-order filled on Transaction ID :id on :date',

        'hold' => 'Stock hold occurred on Transaction ID :id on :date',

        'released' => 'Release occurred on Transaction ID :id on :date',

        'released-partial' => 'Partial release occurred on Transaction ID :id on :date',

        'removed' => 'Removal occurred on Transaction ID :id on :date',

        'cancelled' => 'Cancellation occurred on Transaction ID :id on :date',
    ],
];
