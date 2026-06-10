<?php

$serverName = "localhost\\MSSQLSERVER01";
$connectionOptions = array(
    "Database" => "sekar_bouquet",
    "TrustServerCertificate" => true
);

$koneksi = sqlsrv_connect($serverName, $connectionOptions);

if ($koneksi == false) {

    die(print_r(sqlsrv_errors(), true));

}
?>