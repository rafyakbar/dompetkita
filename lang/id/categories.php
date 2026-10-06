<?php

use App\Enums\SpendTypeEnum;

return [
    'title' => 'Kategori',
    'title_singular' => 'Kategori',
    'fields' => [
        'name' => 'Nama',
        'type' => 'Tipe',
        'icon' => 'Ikon',
        'color' => 'Warna',
        'monthly_balance' => 'Saldo Bulanan',
        'total' => 'Total',
        'is_visible' => 'Terlihat?',
        'is_visible_help_text' => 'Abaikan kategori ini dari total saldo dan sembunyikan dari daftar transaksi',
    ],
    'types' => [
        SpendTypeEnum::INCOME->value   => [
            'id' => SpendTypeEnum::INCOME->value,
            'label' => 'Pemasukan',
            'description' => 'kategori pemasukan Anda',
        ],
        SpendTypeEnum::EXPENSE->value   => [
            'id' => SpendTypeEnum::EXPENSE->value,
            'label' => 'Pengeluaran',
            'description' => 'kategori pengeluaran Anda',
        ],
    ]
];
