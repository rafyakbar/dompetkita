<?php

use App\Enums\SpendTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\VisibilityStatusEnum;

return [
    'title' => 'Transaksi',
    'title_singular' => 'Transaksi',
    'fields' => [
        'amount' => 'Jumlah',
        'confirmed' => 'Dikonfirmasi',
        'category' => 'Kategori',
        'account' => 'Akun',
        'happened_at' => 'Waktu Transaksi',
        'description' => 'Deskripsi',
        'type' => 'Tipe',
        'wallet' => 'Dompet',
        'from_wallet' => 'Dari Dompet',
        'to_wallet' => 'Ke Dompet',
        'note' => 'Catatan',
        'attachment' => 'Lampiran',
    ],
    'types' => [
        TransactionTypeEnum::DEPOSIT->value   => [
            'id' => TransactionTypeEnum::DEPOSIT->value,
            'label' => 'Pemasukan',
            'description' => 'Pemasukan ke dompet Anda',
        ],
        TransactionTypeEnum::WITHDRAW->value  => [
            'id' => TransactionTypeEnum::WITHDRAW->value,
            'label' => 'Pengeluaran',
            'description' => 'Pengeluaran dari dompet Anda',
        ],
        TransactionTypeEnum::TRANSFER->value  => [
            'id' => TransactionTypeEnum::TRANSFER->value,
            'label' => 'Transfer',
            'description' => 'Transfer antar dompet Anda',
        ],
        TransactionTypeEnum::PAYMENT->value  => [
            'id' => TransactionTypeEnum::PAYMENT->value,
            'label' => 'Pembayaran',
            'description' => 'Pembayaran dari satu dompet ke dompet lainnya',
        ],
    ]
];
