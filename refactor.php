#!/usr/bin/php
<?php

// Disable script timeout
set_time_limit(0);

if (php_sapi_name() !== 'cli') {
    echo 'This script is only can running in CLI mode';
    exit(1);
}

try {
    $mysqli = mysqli_connect(
        '127.0.0.1',
        'root',
        '12345678',
        'wilayah',
        3306
    );
} catch (\Throwable $th) {
    echo 'Connection failed : [' . $th->getCode() . '] ' . $th->getMessage() . "\n";
    exit(1);
}

if (!file_exists('csv')) {
    mkdir('csv');
}

if (! is_writable('csv')) {
    echo 'CSV folder is not writeable !';
    exit(1);
}

function writeCsv(string $fileName, mysqli_result $result, callable $callback = null): void {
    $f = fopen('csv/' . $fileName . '.csv', 'w');

    foreach ($result->fetch_all(MYSQLI_NUM) as $row) {
        if (is_callable($callback)) {
            $row = $callback($row);
        }

        $row[1] = preg_replace('/\s+/', ' ', $row[1]);

        $codesArr = explode('.', $row[0]);
        $codesLength = count($codesArr) - 1;

        if ($codesLength > 0) {
            $codes = [
                $codesArr[$codesLength],
                $codesArr[$codesLength - 1]
            ];
    
            $row = array_merge($codes, [$row[1]]);
        }


        fputcsv($f, $row);
    }

    fclose($f);
} 

// Generate Provinces
echo "Generate provinces file\n";
/**
 * Penjelasan urutan kolom
 * 
 * Kolom 1 berisi kode wilayah administrasi pemerintahan (Kemendagri) untuk provinsi yang tertera
 * Kolom 2 berisi nama provinsi
 */
writeCsv(
    'provinces', 
    $mysqli->query("SELECT * FROM `wilayah_2022` WHERE `kode` REGEXP '^[0-9]{2}$' ORDER BY kode ASC")
);

echo "Generate cities file\n";
/**
 * Penjelasan urutan kolom
 * 
 * Kolom 1 berisi kode wilayah administrasi pemerintahan (Kemendagri) untuk kabupaten yang tertera
 * Kolom 2 berisi kode wilayah administrasi pemerintahan (Kemendagri) untuk provinsi pada kabupaten tersebut
 * Kolom 3 berisi nama kabupaten
 */
writeCsv(
    'cities',
    $mysqli->query("SELECT * FROM `wilayah_2022` WHERE `kode` REGEXP '^[0-9]{2}\.[0-9]{2}$' ORDER BY kode ASC"),
    function ($row) {
        $row[1] = preg_replace('/(KAB)(\.)?/', 'KABUPATEN', $row[1]);
        return $row;
    }
);

echo "Generate districts file\n";
/**
 * Penjelasan urutan kolom
 * 
 * Kolom 1 berisi kode wilayah administrasi pemerintahan (Kemendagri) untuk kecamatan yang tertera
 * Kolom 2 berisi kode wilayah administrasi pemerintahan (Kemendagri) untuk kabupaten pada kecamatan tersebut
 * Kolom 3 berisi nama kecamatan
 */
writeCsv(
    'districts',
    $mysqli->query("SELECT * FROM `wilayah_2022` WHERE `kode` REGEXP '^[0-9]{2}\.[0-9]{2}\.[0-9]{2}$' ORDER BY kode ASC")
);

echo "Generate villages file\n";
/**
 * Penjelasan urutan kolom
 * 
 * Kolom 1 berisi kode wilayah administrasi pemerintahan (Kemendagri) untuk desa yang tertera
 * Kolom 2 berisi kode wilayah administrasi pemerintahan (Kemendagri) untuk kecamatan pada desa tersebut
 * Kolom 3 berisi nama desa
 */
writeCsv(
    'villages',
    $mysqli->query("SELECT * FROM `wilayah_2022` WHERE `kode` REGEXP '^[0-9]{2}\.[0-9]{2}\.[0-9]{2}\.[0-9]{4}$' ORDER BY kode ASC")
);
