<?php

use App\Enums\BudgetPeriodEnum;
use App\Enums\SpendTypeEnum;
use App\Enums\VisibilityStatusEnum;

return [
    'title' => 'Anggaran',
    'title_singular' => 'Anggaran',
    'fields' => [
        'name' => 'Nama',
        'amount' => 'Jumlah',
        'actual_amount' => 'Jumlah Anggaran',
        'spend_amount' => 'Jumlah Terpakai',
        'period' => 'Periode',
        'day_of_month' => 'Hari dalam Bulan',
        'day_of_week' => 'Hari dalam Minggu',
        'month_of_year' => 'Bulan dalam Tahun',
        'month_of_quarter' => 'Bulan dalam Kuartal',
        'status' => 'Status',
        'color' => 'Warna',
        'categories' => 'Kategori',
        'recurrence' => 'Pengulangan',
        'enabled' => 'Aktifkan?',
        'enabled_help_text' => 'Tampilkan anggaran ini di dasbor atau laporan',
    ],
    'periods' => [
        BudgetPeriodEnum::WEEKLY->value    => 'Mingguan',
        BudgetPeriodEnum::MONTHLY->value   => 'Bulanan',
        BudgetPeriodEnum::QUARTERLY->value => 'Kuartalan',
        BudgetPeriodEnum::YEARLY->value    => 'Tahunan',
    ],
];
