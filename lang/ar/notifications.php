<?php

return [
    'title' => 'الإشعارات',
    'subtitle' => 'كل نشاط النظام الذي يخصّك',
    'unread_count' => ':count غير مقروء',
    'empty' => 'لا توجد إشعارات بعد',
    'empty_hint' => 'ستظهر هنا حركات المركبات والتسجيلات وتغييرات الحسابات.',
    'mark_read' => 'تعيين كمقروء',
    'mark_all' => 'تعيين الكل كمقروء',
    'mark_all_done' => 'تم تعيين جميع الإشعارات كمقروءة',
    'view_all' => 'عرض كل الإشعارات',
    'system_actor' => 'النظام',
    'filters' => [
        'all' => 'الكل',
        'unread' => 'غير المقروء',
        'vehicle' => 'المركبات',
        'user' => 'الحسابات',
        'system' => 'النظام',
    ],
    'categories' => [
        'vehicle' => 'مركبة',
        'user' => 'حساب',
        'system' => 'النظام',
    ],
    'events' => [
        'vehicle' => [
            'transitioned' => ':actor — :transition',
            'created' => 'قام :actor بتسجيل مركبة جديدة',
            'deleted' => 'قام :actor بحذف مركبة',
            'restored' => 'قام :actor باستعادة مركبة',
        ],
        'user' => [
            'created' => 'قام :actor بإنشاء الحساب :name',
            'updated' => 'قام :actor بتحديث الحساب :name',
            'enabled' => 'قام :actor بتفعيل الحساب :name',
            'disabled' => 'قام :actor بتعطيل الحساب :name',
            'password_reset' => 'قام :actor بإعادة تعيين كلمة مرور :name',
        ],
        'system' => [
            'settings_updated' => 'قام :actor بتحديث إعدادات النظام',
        ],
    ],
];
