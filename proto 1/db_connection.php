<?php
// Fungsi untuk membuka koneksi ke database
function open_connection()
{
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "ai_db";

    // Membuat koneksi
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Periksa koneksi
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Koneksi ke database gagal: ' . $conn->connect_error]));
    }

    return $conn;
}

// Fungsi untuk menutup koneksi database
function close_connection($conn)
{
    $conn->close();
}
