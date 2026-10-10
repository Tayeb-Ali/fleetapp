<?php

return [
    'title' => 'التقارير',
    'all_reports' => 'كل التقارير',
    'records' => 'سجل',
    'generated' => 'تاريخ الإنشاء',
    'report_fallback' => 'تقرير',
    'unknown' => 'تقرير غير معروف',
    'bad_format' => 'صيغة غير مدعومة',
    'download_xlsx' => 'تنزيل XLSX',
    'download_pdf' => 'تنزيل PDF',
    'print' => 'طباعة',
    'rows' => 'الصفوف',
    'columns' => 'الأعمدة',

    'profit' => 'الربح حسب المركبة',
    'outstanding' => 'الأرصدة المستحقة',

    'titles' => [
        'summary' => 'ملخص مراحل المركبات',
        'stock' => 'المخزون الحالي في المستودع',
        'available' => 'مركبات متاحة للبيع',
        'sales' => 'المبيعات حسب المشتري',
        'shipments' => 'الشحنات والموانئ',
        'customs' => 'الجمارك والتخليص',
        'full' => 'التقرير الفني الكامل',
        'profit' => 'الربح حسب المركبة',
        'outstanding' => 'الأرصدة المستحقة',
        'quality' => 'فحص جودة البيانات',
    ],

    'labels' => [
        'count' => 'العدد',
        'share' => 'النسبة %',
        'total' => 'الإجمالي',
        'vehicle_count' => 'عدد المركبات',
        'still_in_transit' => 'قيد الشحن',
        'arrived_or_later' => 'وصلت أو متأخرة',
        'days_in_warehouse' => 'عدد الأيام في المستودع',
        'total_sale_price' => 'إجمالي سعر البيع',
        'total_paid' => 'إجمالي المدفوع',
        'delivered' => 'تم التسليم',
        'awaiting_delivery' => 'بانتظار التسليم',
        'total_cost' => 'إجمالي التكلفة',
        'profit' => 'الربح',
        'balance_due' => 'المتبقي',
        'issue' => 'الملاحظة',
    ],

    'issues' => [
        'shipping_future' => 'تاريخ الشحن في المستقبل',
        'arrival_future' => 'تاريخ الوصول في المستقبل بينما تجاوزت المركبة مرحلة الشحن',
        'arrival_before_shipping' => 'تاريخ الوصول قبل تاريخ الشحن',
        'missing_bl' => 'رقم بوليصة الشحن (B/L) مفقود',
        'sold_without_buyer' => 'مباعة بدون اسم المشتري',
        'delivered_incomplete' => 'تم التسليم بدون اسم المستلم أو تاريخ التسليم',
        'instock_without_location' => 'في المخزون بدون موقع مستودع',
        'sold_without_price' => 'مباعة بدون سعر بيع',
        'sold_without_paid' => 'مباعة بدون مبلغ مدفوع',
        'overpaid' => 'المدفوع يتجاوز سعر البيع',
    ],

    'quality_financial' => [
        'no_price' => 'مباع بدون سعر بيع',
        'no_paid' => 'مباع بدون مبلغ مدفوع',
        'overpaid' => 'المدفوع يتجاوز سعر البيع',
    ],
];
