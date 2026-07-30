<?php

ini_set('display_errors', 1);
error_reporting(~0);

include ("../config/connect_sqlserver.php");
include ("../config/connect_db.php");

include ("../cond_file/doc_info_customer_ar.php");

$sql_mysql= " SELECT * FROM v_customer_ar ";

$stmt_mysql = $conn->prepare($sql_mysql);
$stmt_mysql->execute();

$sql_find = "SELECT COUNT(*) FROM ims_customer_arcode WHERE AR_CODE = :AR_CODE";
$stmt_find = $conn->prepare($sql_find);

$sql_update = "UPDATE ims_customer_arcode SET AR_NAME=:AR_NAME WHERE AR_CODE = :AR_CODE";
$stmt_update = $conn->prepare($sql_update);

$sql_insert = "INSERT INTO ims_customer_arcode(AR_CODE,AR_NAME) VALUES (:AR_CODE,:AR_NAME)";
$stmt_insert = $conn->prepare($sql_insert);

$update_count = 0;
$insert_count = 0;

$conn->beginTransaction();
try {
    while ($result_mysql = $stmt_mysql->fetch(PDO::FETCH_ASSOC)) {
        $stmt_find->execute([':AR_CODE' => $result_mysql["AR_CODE"]]);
        $nRows = $stmt_find->fetchColumn();

        if ($nRows > 0) {
            $stmt_update->execute([
                ':AR_NAME' => $result_mysql["AR_NAME"],
                ':AR_CODE' => $result_mysql["AR_CODE"]
            ]);
            $update_count++;
        } else {
            $stmt_insert->execute([
                ':AR_CODE' => $result_mysql["AR_CODE"],
                ':AR_NAME' => $result_mysql["AR_NAME"]
            ]);
            $insert_count++;
        }
    }
    $conn->commit();
    echo "Import customer_arcode completed. Updated: $update_count, Inserted: $insert_count\n\r";
} catch (Exception $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}

$conn_sqlsvr=null;

