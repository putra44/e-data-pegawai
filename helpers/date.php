<?php

function tanggal($datetime)
{
    if (!$datetime) return '-';

    $hari = [
        'Sun' => 'Min',
        'Mon' => 'Sen',
        'Tue' => 'Sel',
        'Wed' => 'Rab',
        'Thu' => 'Kam',
        'Fri' => 'Jum',
        'Sat' => 'Sab'
    ];

    $bulan = [
        'January' => 'Januari',
        'February' => 'Februari',
        'March' => 'Maret',
        'April' => 'April',
        'May' => 'Mei',
        'June' => 'Juni',
        'July' => 'Juli',
        'August' => 'Agustus',
        'September' => 'September',
        'October' => 'Oktober',
        'November' => 'November',
        'December' => 'Desember'
    ];

    $d = date('D', strtotime($datetime));
    $tgl = date('j', strtotime($datetime));
    $bln = date('F', strtotime($datetime));
    $thn = date('Y', strtotime($datetime));

    return $hari[$d] . ', ' . $tgl . ' ' . $bulan[$bln] . ' ' . $thn;
}