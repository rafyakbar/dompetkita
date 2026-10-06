<?php

use App\Enums\SpendTypeEnum;
use App\Enums\VisibilityStatusEnum;

return [
    'title' => 'Target Keuangan',
    'title_singular' => 'Target',
    'actions' => [
        'deposit' => 'Setor Tabungan',
        'withdraw' => 'Tarik Tabungan',
    ],
    'fields' => [
        'name' => 'Nama Target',
        'amount' => 'Jumlah Target',
        'target_date' => 'Tanggal Target',
        'currency_code' => 'Mata Uang',
        'color' => 'Warna',
        'wallet' => 'Dompet',
        'from_wallet' => 'Dari Dompet',
        'to_wallet' => 'Ke Dompet',
        'goal' => 'Target',
        'target_amount' => 'Target Dana',
        'balance' => 'Saldo Terkumpul',
        'target_from' => 'Target Dari',
        'target_until' => 'Target Sampai',
    ],
];
