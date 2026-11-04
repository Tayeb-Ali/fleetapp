<?php

/*
 * Fleet domain registry — mirrors legacy server.js STAGES / ROLES / F / T.
 * TransitionForm + OverviewStats read from here with defensive fallbacks.
 */

return [
    'company' => env('FLEET_COMPANY', 'RASD Tech'),
    'currency' => env('FLEET_CURRENCY', 'USD'),

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

    'stage_colors' => [
        'purchased' => '#94a3b8',
        'shipped' => '#8b5cf6',
        'arrived' => '#0ea5e9',
        'clearing' => '#f59e0b',
        'cleared' => '#f97316',
        'instock' => '#14b8a6',
        'sold' => '#ec4899',
        'delivered' => '#22c55e',
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

    // field_key => [label, type]
    'fields' => [
        'brand' => ['Brand', 'text'],
        'model' => ['Model', 'text'],
        'vin' => ['Chassis No. (VIN)', 'text'],
        'fuel' => ['Fuel / Engine', 'text'],
        'mod' => ['Modification', 'text'],
        'pdate' => ['Purchase date', 'date'],
        'supplier' => ['Supplier / Overseas Showroom', 'text'],
        'cost' => ['Purchase price', 'number'],
        'purchase_currency' => ['Purchase currency', 'currency'],
        'purchase_fx_rate' => ['Purchase exchange rate (per 1 base)', 'number'],
        'bl' => ['Bill of Lading No. (B/L)', 'text'],
        'bl_release' => ['B/L release', 'yesno'],
        'voy' => ['Voyage No.', 'text'],
        'pol' => ['Port of loading', 'text'],
        'pod' => ['Port of discharge', 'text'],
        'dest' => ['Final destination', 'text'],
        'ship' => ['Shipping date', 'date'],
        'arr' => ['Arrival date', 'date'],
        'shipping_cost' => ['Shipping cost', 'number'],
        'shipping_currency' => ['Shipping currency', 'currency'],
        'shipping_fx_rate' => ['Shipping exchange rate (per 1 base)', 'number'],
        'cust_no' => ['Customs declaration no.', 'text'],
        'cust_date' => ['Clearance date', 'date'],
        'landed_cost' => ['Landed cost', 'number'],
        'modification_cost' => ['Modification cost', 'number'],
        'loc' => ['Warehouse location', 'text'],
        'in_date' => ['Warehouse entry date', 'date'],
        'ord_no' => ['Sale order no.', 'text'],
        'client' => ['Buyer / Organization', 'text'],
        'phone' => ['Buyer phone', 'text'],
        'price' => ['Sale price', 'number'],
        'sale_currency' => ['Sale currency', 'currency'],
        'sale_fx_rate' => ['Sale exchange rate (per 1 base)', 'number'],
        'paid' => ['Amount paid', 'number'],
        'sale_date' => ['Sale order date', 'date'],
        'recv' => ['Receiver name', 'text'],
        'del_date' => ['Delivery date', 'date'],
        'remarks' => ['Remarks', 'area'],
        'stage' => ['Stage', 'select'],
    ],

    'create_fields' => ['brand', 'model', 'vin', 'fuel', 'mod', 'pdate', 'supplier', 'cost', 'purchase_currency', 'purchase_fx_rate', 'modification_cost', 'bl', 'bl_release', 'voy', 'pol', 'pod', 'ship', 'arr', 'remarks'],
    'create_required' => ['model', 'vin'],

    'transitions' => [
        'ship' => ['label' => 'Register shipment', 'from' => ['purchased'], 'to' => 'shipped', 'roles' => ['purchaser', 'tracker'], 'fields' => ['bl', 'bl_release', 'voy', 'pol', 'pod', 'dest', 'ship', 'arr', 'shipping_cost', 'shipping_currency', 'shipping_fx_rate'], 'required' => ['bl', 'ship']],
        'arrive' => ['label' => 'Confirm arrival at port', 'from' => ['shipped'], 'to' => 'arrived', 'roles' => ['tracker'], 'fields' => ['arr', 'pod'], 'required' => ['arr']],
        'clear1' => ['label' => 'Start customs clearance', 'from' => ['arrived'], 'to' => 'clearing', 'roles' => ['customs'], 'fields' => ['cust_no'], 'required' => ['cust_no']],
        'clear2' => ['label' => 'Complete customs clearance', 'from' => ['clearing'], 'to' => 'cleared', 'roles' => ['customs'], 'fields' => ['cust_no', 'cust_date', 'landed_cost'], 'required' => ['cust_no', 'cust_date']],
        'receive' => ['label' => 'Receive into Warehouse / Showroom', 'from' => ['cleared'], 'to' => 'instock', 'roles' => ['warehouse'], 'fields' => ['loc', 'in_date'], 'required' => ['loc', 'in_date']],
        'sell' => ['label' => 'Issue sale order', 'from' => ['instock'], 'to' => 'sold', 'roles' => ['sales'], 'fields' => ['ord_no', 'client', 'phone', 'price', 'paid', 'sale_date', 'sale_currency', 'sale_fx_rate'], 'required' => ['ord_no', 'client', 'price', 'paid']],
        'deliver' => ['label' => 'Deliver to buyer', 'from' => ['sold'], 'to' => 'delivered', 'roles' => ['warehouse'], 'fields' => ['recv', 'del_date'], 'required' => ['recv', 'del_date']],
    ],
];
