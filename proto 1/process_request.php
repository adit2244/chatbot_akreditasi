<?php
// Set response type ke JSON
header('Content-Type: application/json');

// Include file koneksi database
include 'db_connection.php';

// 1. Ambil input pertanyaan dari frontend
$question = $_POST['question'] ?? null;

if (!$question) {
    echo json_encode(['error' => 'Pertanyaan tidak ditemukan']);
    exit();
}

// Tambahkan log untuk memeriksa pertanyaan yang diterima
error_log("DEBUG: Pertanyaan diterima dari frontend: $question");

// 2. Kirim pertanyaan ke API Python
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:5000/predict"); // URL API Python
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['question' => $question]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

$response = curl_exec($ch);
curl_close($ch);

$api_data = json_decode($response, true);

// Debug untuk memeriksa respon dari API Python
error_log("DEBUG: Respon dari API Python: " . json_encode($api_data));

// Jika API Python gagal memberikan respon
if (!isset($api_data['intent']) || !isset($api_data['entities'])) {
    echo json_encode(['error' => 'Respon API Python tidak valid']);
    exit();
}

$intent = $api_data['intent'];
$entities = $api_data['entities'];

// Debug intent dan entities
error_log("DEBUG: Intent diterima: $intent");
error_log("DEBUG: Entities diterima: " . json_encode($entities));

// 3. Proses intent dengan validasi entities
$result_data = [];
$conn = open_connection();

switch ($intent) {
    case 'sapaan':
        echo json_encode(['response' => 'Halo! Ada yang bisa saya bantu?']);
        close_connection($conn);
        exit();

    case 'permintaan_data_kelulusan':
        $query = "SELECT npm_mhs, nama_mhs, prodi, semester_lulus, status FROM data_mhs_akre WHERE 1=1";

        // Tambahkan filter berdasarkan entities jika ada
        if (!empty($entities['semester_lulus']) && is_array($entities['semester_lulus'])) {
            $semester_conditions = array_map(function ($semester) {
                return "semester_lulus = '" . addslashes($semester) . "'";
            }, $entities['semester_lulus']);
            $query .= " AND (" . implode(" OR ", $semester_conditions) . ")";
        }
        if (!empty($entities['prodi']) && is_array($entities['prodi'])) {
            $query .= " AND prodi = '" . addslashes($entities['prodi'][0]) . "'";
        }
        if (!empty($entities['angkatan']) && is_array($entities['angkatan'])) {
            $query .= " AND angkatan = '" . addslashes($entities['angkatan'][0]) . "'";
        }
        if (!empty($entities['status']) && is_array($entities['status'])) {
            $query .= " AND status = '" . addslashes($entities['status'][0]) . "'";
        }

        // Jalankan query
        $result = $conn->query($query);

        // Jika query gagal dijalankan, berikan respon error
        if (!$result) {
            echo json_encode(['error' => 'Terjadi kesalahan saat menjalankan query']);
            close_connection($conn);
            exit();
        }

        // Ambil hasil query
        $result_data = $result->fetch_all(MYSQLI_ASSOC);
        break;


    case 'permintaan_data_penelitian':
        $query = "SELECT nidn_dosen, judul_penelitian, tgl_terbit FROM data_penelitian_dosen WHERE 1=1";

        if (!empty($entities['nidn_dosen'])) {
            $query .= " AND nidn_dosen = '" . addslashes($entities['nidn_dosen'][0]) . "'";
        }
        if (!empty($entities['tahun'])) {
            $query .= " AND YEAR(tgl_terbit) = " . intval($entities['tahun'][0]);
        }

        error_log("DEBUG: SQL Query untuk data penelitian: $query");
        $result = $conn->query($query);
        $result_data = $result->fetch_all(MYSQLI_ASSOC);
        break;

    case 'permintaan_data_kegiatan':
        $query = "SELECT nama_kegiatan, tingkat_kegiatan, tgl_kegiatan FROM data_kegiatan_mhs WHERE 1=1";

        if (!empty($entities['tahun'])) {
            $query .= " AND YEAR(tgl_kegiatan) = " . intval($entities['tahun'][0]);
        }
        if (!empty($entities['tingkat_kegiatan'])) {
            $query .= " AND tingkat_kegiatan = '" . addslashes($entities['tingkat_kegiatan'][0]) . "'";
        }
        if (!empty($entities['nama_kegiatan'])) {
            $query .= " AND nama_kegiatan LIKE '%" . addslashes($entities['nama_kegiatan'][0]) . "%'";
        }

        error_log("DEBUG: SQL Query untuk data kegiatan: $query");
        $result = $conn->query($query);
        $result_data = $result->fetch_all(MYSQLI_ASSOC);
        break;

    case 'permintaan_data_ipk':
        $query = "SELECT nama_mhs, prodi, ipk FROM data_mhs_akre WHERE 1=1";

        if (!empty($entities['prodi'])) {
            $query .= " AND prodi = '" . addslashes($entities['prodi'][0]) . "'";
        }
        if (!empty($entities['angkatan'])) {
            $query .= " AND angkatan = '" . addslashes($entities['angkatan'][0]) . "'";
        }
        if (!empty($entities['nilai'])) {
            $query .= " AND ipk >= " . floatval($entities['nilai'][0]);
        }

        error_log("DEBUG: SQL Query untuk data IPK: $query");
        $result = $conn->query($query);
        $result_data = $result->fetch_all(MYSQLI_ASSOC);
        break;

    default:
        error_log("DEBUG: Intent tidak dikenali: $intent");
        echo json_encode(['error' => 'Intent tidak dikenali']);
        close_connection($conn);
        exit();
}

// 4. Tutup koneksi database dan kirim hasil
close_connection($conn);

// Kirim data hasil query atau pesan tidak ada data yang sesuai
if (empty($result_data)) {
    echo json_encode(['intent' => $intent, 'entities' => $entities, 'data' => ['message' => 'Tidak ada data yang sesuai']]);
} else {
    echo json_encode(['intent' => $intent, 'entities' => $entities, 'data' => $result_data]);
}
