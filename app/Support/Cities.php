<?php

namespace App\Support;

/**
 * Daftar kota cadangan untuk mode simulasi ongkir (RAJAONGKIR_API_KEY kosong).
 * ID mengikuti penomoran kota RajaOngkir versi lama (starter) supaya mudah
 * dicocokkan; koordinat dipakai untuk memperkirakan jarak → ongkir.
 */
class Cities
{
    public const LIST = [
        '151' => ['Jakarta Barat', 'DKI Jakarta', -6.168, 106.759],
        '152' => ['Jakarta Pusat', 'DKI Jakarta', -6.186, 106.834],
        '153' => ['Jakarta Selatan', 'DKI Jakarta', -6.261, 106.810],
        '154' => ['Jakarta Timur', 'DKI Jakarta', -6.225, 106.900],
        '155' => ['Jakarta Utara', 'DKI Jakarta', -6.138, 106.863],
        '78'  => ['Kabupaten Bogor', 'Jawa Barat', -6.595, 106.817],
        '79'  => ['Kota Bogor', 'Jawa Barat', -6.597, 106.806],
        '115' => ['Kota Depok', 'Jawa Barat', -6.402, 106.794],
        '55'  => ['Kota Bekasi', 'Jawa Barat', -6.238, 106.976],
        '23'  => ['Kota Bandung', 'Jawa Barat', -6.917, 107.619],
        '22'  => ['Kabupaten Bandung', 'Jawa Barat', -7.025, 107.520],
        '109' => ['Kota Cirebon', 'Jawa Barat', -6.732, 108.552],
        '456' => ['Kota Tangerang', 'Banten', -6.178, 106.630],
        '457' => ['Kota Tangerang Selatan', 'Banten', -6.288, 106.718],
        '403' => ['Kota Serang', 'Banten', -6.120, 106.150],
        '399' => ['Kota Semarang', 'Jawa Tengah', -6.966, 110.417],
        '445' => ['Kota Surakarta (Solo)', 'Jawa Tengah', -7.575, 110.824],
        '501' => ['Kota Yogyakarta', 'DI Yogyakarta', -7.797, 110.371],
        '419' => ['Kabupaten Sleman', 'DI Yogyakarta', -7.716, 110.356],
        '444' => ['Kota Surabaya', 'Jawa Timur', -7.257, 112.752],
        '256' => ['Kota Malang', 'Jawa Timur', -7.966, 112.633],
        '409' => ['Kabupaten Sidoarjo', 'Jawa Timur', -7.447, 112.718],
        '114' => ['Kota Denpasar', 'Bali', -8.670, 115.212],
        '17'  => ['Kabupaten Badung', 'Bali', -8.581, 115.177],
        '276' => ['Kota Mataram', 'Nusa Tenggara Barat', -8.583, 116.116],
        '212' => ['Kota Kupang', 'Nusa Tenggara Timur', -10.178, 123.607],
        '278' => ['Kota Medan', 'Sumatera Utara', 3.595, 98.672],
        '318' => ['Kota Padang', 'Sumatera Barat', -0.947, 100.417],
        '350' => ['Kota Pekanbaru', 'Riau', 0.507, 101.447],
        '48'  => ['Kota Batam', 'Kepulauan Riau', 1.045, 104.030],
        '327' => ['Kota Palembang', 'Sumatera Selatan', -2.976, 104.775],
        '21'  => ['Kota Bandar Lampung', 'Lampung', -5.397, 105.266],
        '156' => ['Kota Jambi', 'Jambi', -1.610, 103.613],
        '62'  => ['Kota Bengkulu', 'Bengkulu', -3.800, 102.265],
        '3'   => ['Kota Banda Aceh', 'Nanggroe Aceh Darussalam', 5.548, 95.323],
        '365' => ['Kota Pontianak', 'Kalimantan Barat', -0.027, 109.342],
        '36'  => ['Kota Banjarmasin', 'Kalimantan Selatan', -3.316, 114.590],
        '19'  => ['Kota Balikpapan', 'Kalimantan Timur', -1.237, 116.853],
        '387' => ['Kota Samarinda', 'Kalimantan Timur', -0.502, 117.154],
        '329' => ['Kota Palangka Raya', 'Kalimantan Tengah', -2.210, 113.920],
        '254' => ['Kota Makassar', 'Sulawesi Selatan', -5.148, 119.432],
        '267' => ['Kota Manado', 'Sulawesi Utara', 1.474, 124.842],
        '325' => ['Kota Palu', 'Sulawesi Tengah', -0.899, 119.870],
        '182' => ['Kota Kendari', 'Sulawesi Tenggara', -3.997, 122.513],
        '14'  => ['Kota Ambon', 'Maluku', -3.695, 128.181],
        '157' => ['Kota Jayapura', 'Papua', -2.533, 140.718],
    ];
}
