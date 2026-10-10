<?php

return [
    'title' => 'Reports',
    'all_reports' => 'All reports',
    'records' => 'records',
    'generated' => 'Generated',
    'report_fallback' => 'Report',
    'unknown' => 'Unknown report',
    'bad_format' => 'Unsupported format',
    'download_xlsx' => 'Download XLSX',
    'download_pdf' => 'Download PDF',
    'print' => 'Print',
    'rows' => 'Rows',
    'columns' => 'Columns',

    'profit' => 'Profit by vehicle',
    'outstanding' => 'Outstanding balances',

    'titles' => [
        'summary' => 'Vehicle stage summary',
        'stock' => 'Current warehouse stock',
        'available' => 'Vehicles available for sale',
        'sales' => 'Sales by buyer',
        'shipments' => 'Shipments and ports',
        'customs' => 'Customs & clearance',
        'full' => 'Full technical report',
        'profit' => 'Profit by vehicle',
        'outstanding' => 'Outstanding balances',
        'quality' => 'Data quality check',
    ],

    'labels' => [
        'count' => 'Count',
        'share' => 'Share %',
        'total' => 'Total',
        'vehicle_count' => 'Vehicle count',
        'still_in_transit' => 'Still in transit',
        'arrived_or_later' => 'Arrived or later',
        'days_in_warehouse' => 'Days in warehouse',
        'total_sale_price' => 'Total sale price',
        'total_paid' => 'Total paid',
        'delivered' => 'Delivered',
        'awaiting_delivery' => 'Awaiting delivery',
        'total_cost' => 'Total cost',
        'profit' => 'Profit',
        'balance_due' => 'Balance due',
        'issue' => 'Issue',
    ],

    'issues' => [
        'shipping_future' => 'Shipping date is in the future',
        'arrival_future' => 'Arrival date is in the future but the vehicle is past the shipping stage',
        'arrival_before_shipping' => 'Arrival date is before shipping date',
        'missing_bl' => 'Missing Bill of Lading (B/L) number',
        'sold_without_buyer' => 'Sold without buyer name',
        'delivered_incomplete' => 'Delivered without receiver name or delivery date',
        'instock_without_location' => 'In stock without warehouse location',
        'sold_without_price' => 'Sold without sale price',
        'sold_without_paid' => 'Sold without amount paid',
        'overpaid' => 'Amount paid exceeds sale price',
    ],

    'quality_financial' => [
        'no_price' => 'Sold without sale price',
        'no_paid' => 'Sold without amount paid',
        'overpaid' => 'Amount paid exceeds sale price',
    ],
];
