<?php

return [
    'title' => 'Audit log',
    'date' => 'Date',
    'user' => 'User',
    'action' => 'Action',
    'vehicle' => 'Vehicle',
    'from_stage' => 'From',
    'to_stage' => 'To',
    'note' => 'Note',
    'system' => 'System',
    'entries' => 'entries',

    'actions' => [
        'created' => 'Registered',
        'updated' => 'Updated',
        'transition' => 'Stage change',
        'deleted' => 'Deleted',
        'restored' => 'Restored',
    ],

    'notes' => [
        'vehicle_created' => 'Vehicle purchase registered',
        'stage_changed' => 'Stage changed',
        'data_updated' => 'Data updated',
        'vehicle_deleted' => 'Vehicle deleted',
        'vehicle_restored' => 'Vehicle restored',
    ],
];
