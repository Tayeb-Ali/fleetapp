<?php

return [
    'title' => 'Notifications',
    'subtitle' => 'All system activity that concerns you',
    'unread_count' => ':count unread',
    'empty' => 'No notifications yet',
    'empty_hint' => 'Vehicle moves, registrations and account changes will appear here.',
    'mark_read' => 'Mark as read',
    'mark_all' => 'Mark all as read',
    'mark_all_done' => 'All notifications marked as read',
    'view_all' => 'View all notifications',
    'system_actor' => 'System',
    'filters' => [
        'all' => 'All',
        'unread' => 'Unread',
        'vehicle' => 'Vehicles',
        'user' => 'Accounts',
        'system' => 'System',
    ],
    'categories' => [
        'vehicle' => 'Vehicle',
        'user' => 'Account',
        'system' => 'System',
    ],
    'events' => [
        'vehicle' => [
            'transitioned' => ':actor — :transition',
            'created' => ':actor registered a new vehicle',
            'deleted' => ':actor deleted a vehicle',
            'restored' => ':actor restored a vehicle',
        ],
        'user' => [
            'created' => ':actor created the account :name',
            'updated' => ':actor updated the account :name',
            'enabled' => ':actor enabled the account :name',
            'disabled' => ':actor disabled the account :name',
            'password_reset' => ':actor reset the password for :name',
        ],
        'system' => [
            'settings_updated' => ':actor updated system settings',
        ],
    ],
];
