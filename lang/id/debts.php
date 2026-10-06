<?php

use App\Enums\DebtActionTypeEnum;
use App\Enums\DebtTypeEnum;

return [
    'title' => 'Utang & Piutang',
    'title_singular' => 'Utang / Piutang',
    'actions' => [
        'debt_transaction' => 'Transaksi Utang',
    ],
    'fields' => [
        'name' => 'Nama',
        'type' => 'Tipe',
        'amount' => 'Jumlah',
        'description' => 'Deskripsi',
        'start_at' => 'Tanggal Mulai',
        'color' => 'Warna',
        'wallet' => 'Dompet',
        'initial_wallet' => 'Dompet Awal',
        'happened_at' => 'Waktu',
        'debt' => 'Utang / Piutang',
        'action_type' => 'Tipe Tindakan',
        'from_wallet' => 'Dari Dompet',
        'total_debt_amount' => 'Total Jumlah Utang',
    ],
    'types' => [
        DebtTypeEnum::PAYABLE->value => 'Utang (Harus Dibayar)',
        DebtTypeEnum::RECEIVABLE->value => 'Piutang (Diterima)',
    ],
    'action_types' => [
        DebtTypeEnum::RECEIVABLE->value => [
            DebtActionTypeEnum::DEBT_COLLECTION->value => 'Penagihan Piutang',
            DebtActionTypeEnum::LOAN_INCREASE->value   => 'Penambahan Pinjaman',
            DebtActionTypeEnum::LOAN_INTEREST->value   => 'Bunga Pinjaman',
        ],
        DebtTypeEnum::PAYABLE->value => [
            DebtActionTypeEnum::REPAYMENT->value     => 'Pelunasan / Cicilan',
            DebtActionTypeEnum::DEBT_INCREASE->value => 'Penambahan Utang',
            DebtActionTypeEnum::DEBT_INTEREST->value => 'Bunga Utang',
        ],
    ]
];
