<?php
session_start(); // Memulai sesi untuk menyimpan data debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_input = $_POST['user_input'];

    $api_url = "http://127.0.0.1:8000/analyze";
    $response = sendToDjango($api_url, ['text' => $user_input]);

    if (!$response) {
        echo "<pre>API Response: Tidak ada respons dari API.</pre>";
        exit();
    }

    // Simpan hasil respons API dalam sesi untuk ditampilkan nanti di halaman result.php
    $_SESSION['api_response'] = $response;

    if ($response && isset($response['intent'], $response['entities'])) {
        $intent = $response['intent'];
        $entities = $response['entities'];

        // Simpan intent dan entities dalam sesi
        $_SESSION['intent'] = $intent;
        $_SESSION['entities'] = $entities;

        // Proses data CSV
        $data = queryCsv($intent, $entities);

        // Simpan hasil query dalam sesi
        $_SESSION['results'] = $data;

        // Redirect ke halaman result.php
        header('Location: result.php');
        exit();
    } else {
        echo "Terjadi kesalahan saat memproses input.";
        var_dump($response);
    }
}

function sendToDjango($url, $data)
{
    // Simulasi pengiriman data ke API Python
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    $response = curl_exec($ch);

    if (!$response) {
        echo "<pre>Tidak ada respons dari Python API</pre>";
        exit();
    }

    curl_close($ch);
    return json_decode($response, true);
}

function queryCsv($intent, $entities)
{
    $results = [];

    // Proses berdasarkan intent
    if ($intent === 'permintaan_data_kegiatan') {
        $file_path = __DIR__ . '/data/data-kegiatan-mahasiswa-for-akre.csv';
        $results = filterCsv($file_path, $entities, ['npm_mahasiswa', 'bank_id', 'nama_kegiatan', 'tingkat_kegiatan', 'tanggal_kegiatan']);
    } elseif ($intent === 'permintaan_data_penelitian') {
        $file_path = __DIR__ . '/data/data-penelitian-dosen.csv';
        $results = filterCsv($file_path, $entities, ['nidn_dosen', 'judul_penelitian', 'tanggal_terbit', 'jenis_publikasi', 'tingkat_publikasi']);
    } elseif ($intent === 'permintaan_data_ipk') {
        $file_path = __DIR__ . '/data/data-mahasiswa-for-akre.csv';
        $results = filterCsv($file_path, $entities, ['npm_mahasiswa', 'nama_mahasiswa', 'prodi_mahasiswa', 'angkatan_mahasiswa', 'ipk_mahasiswa', 'status_mahasiswa', 'semester_lulus']);
    } elseif ($intent === 'permintaan_data_kelulusan') {
        $file_path = __DIR__ . '/data/data-mahasiswa-for-akre.csv';
        $results = filterCsv($file_path, $entities, ['npm_mahasiswa', 'nama_mahasiswa', 'prodi_mahasiswa', 'angkatan_mahasiswa', 'ipk_mahasiswa', 'status_mahasiswa', 'semester_lulus']);
    }

    return $results;
}

function filterCsv($file_path, $entities, $columns)
{
    $results = [];
    if (!file_exists($file_path)) {
        echo "<pre>File CSV tidak ditemukan: $file_path</pre>";
        exit();
    }

    $file = fopen($file_path, 'r');
    $headers = fgetcsv($file, 0, ';');

    while (($row = fgetcsv($file, 0, ';')) !== false) {
        $data = array_combine($headers, $row);

        $match = true;
        foreach ($entities as $key => $value) {
            if ($key === 'tahun' && isset($data['tanggal_kegiatan'])) {
                if (strpos($data['tanggal_kegiatan'], $value) === false) {
                    $match = false;
                    break;
                }
            } elseif (isset($data[$key]) && stripos($data[$key], $value) === false) {
                $match = false;
                break;
            }
        }

        if ($match) {
            $results[] = array_intersect_key($data, array_flip($columns));
        }
    }

    fclose($file);
    return $results;
}
