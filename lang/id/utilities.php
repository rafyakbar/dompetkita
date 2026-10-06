<?php

use App\Enums\MonthEnum;
use App\Enums\QuarterEnum;
use App\Enums\VisibilityStatusEnum;
use App\Enums\WeekdayEnum;

return [
    'visibility_statuses' => [
        VisibilityStatusEnum::ACTIVE->value   => 'Aktif',
        VisibilityStatusEnum::INACTIVE->value => 'Tidak Aktif',
    ],
    'weekdays' => [
        WeekdayEnum::SUNDAY->value    => 'Minggu',
        WeekdayEnum::MONDAY->value    => 'Senin',
        WeekdayEnum::TUESDAY->value   => 'Selasa',
        WeekdayEnum::WEDNESDAY->value => 'Rabu',
        WeekdayEnum::THURSDAY->value  => 'Kamis',
        WeekdayEnum::FRIDAY->value    => 'Jumat',
        WeekdayEnum::SATURDAY->value  => 'Sabtu',
    ],
    'months' => [
        MonthEnum::JANUARY->value   => 'Januari',
        MonthEnum::FEBRUARY->value  => 'Februari',
        MonthEnum::MARCH->value     => 'Maret',
        MonthEnum::APRIL->value     => 'April',
        MonthEnum::MAY->value       => 'Mei',
        MonthEnum::JUNE->value      => 'Juni',
        MonthEnum::JULY->value      => 'Juli',
        MonthEnum::AUGUST->value    => 'Agustus',
        MonthEnum::SEPTEMBER->value => 'September',
        MonthEnum::OCTOBER->value   => 'Oktober',
        MonthEnum::NOVEMBER->value  => 'November',
        MonthEnum::DECEMBER->value  => 'Desember',
    ],
    'quarter_months' => [
        QuarterEnum::FIRST_MONTH->value  => 'Bulan Pertama',
        QuarterEnum::SECOND_MONTH->value => 'Bulan Kedua',
        QuarterEnum::THIRD_MONTH->value  => 'Bulan Ketiga',
    ]
];
