<?php

return [
    'stages' => [
        'purchased' => 'Purchased',
        'shipped' => 'In transit',
        'arrived' => 'Arrived at port',
        'clearing' => 'Clearing customs',
        'cleared' => 'Customs cleared',
        'instock' => 'In warehouse - available for sale',
        'sold' => 'Sold - awaiting delivery',
        'delivered' => 'Delivered',
    ],
    'roles' => [
        'admin' => 'System Administrator',
        'purchaser' => 'Purchaser',
        'tracker' => 'Tracker',
        'customs' => 'Customs & Clearance',
        'sales' => 'Sales',
        'warehouse' => 'Warehouse',
        'accountant' => 'Accountant',
        'viewer' => 'View only',
    ],
    'transitions' => [
        'ship' => 'Register shipment',
        'arrive' => 'Confirm arrival at port',
        'clear1' => 'Start customs clearance',
        'clear2' => 'Complete customs clearance',
        'receive' => 'Receive into Warehouse / Showroom',
        'sell' => 'Issue sale order',
        'deliver' => 'Deliver to buyer',
    ],
];
