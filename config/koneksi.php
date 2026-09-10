<?php

$host = "localhost";
$name = "root";
$pass = "";
$db = "db_spp";

$koneksi = new mysqli($host, $name, $pass, $db); 
if($koneksi->connect_error) {
    die('Koneksi gagal' . $koneksi->connect_error);
}
?>