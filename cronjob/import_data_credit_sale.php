<?php

ini_set('display_errors', 1);
error_reporting(~0);

include ("../config/connect_sqlserver.php");
include ("../config/connect_db.php");

include ("../cond_file/doc_info_credit_sale.php");

$doc_id_prefix = 'BKSV%';
$year = date("Y");
$month = date("m");

echo "Year = " . $year . "\n\r";
echo "Month = " . $month . "\n\r";

$sql_sqlsvr = $select_query . $sql_cond . " AND DI_REF like '" . $doc_id_prefix . "'"
            . " AND YEAR(DI_DATE) = " . $year
            . " AND MONTH(DI_DATE) = " . $month
            . $sql_order ;

$stmt_sqlsvr = $conn_sqlsvr->prepare($sql_sqlsvr);
$stmt_sqlsvr->execute();

$sql_find = "SELECT COUNT(*) FROM ims_price_approve_header WHERE DI_KEY = :DI_KEY";
$stmt_find = $conn->prepare($sql_find);

$sql_insert = "INSERT INTO ims_price_approve_header(DI_KEY,doc_no,doc_date,customer_id,customer_name) VALUES (:DI_KEY,:doc_no,:doc_date,:customer_id,:customer_name)";
$stmt_insert = $conn->prepare($sql_insert);

$insert_count = 0;
$dup_count = 0;

$conn->beginTransaction();
try {
    while ($result_sqlsvr = $stmt_sqlsvr->fetch(PDO::FETCH_ASSOC)) {
        $stmt_find->execute([':DI_KEY' => $result_sqlsvr["DI_KEY"]]);
        $nRows = $stmt_find->fetchColumn();

        if ($nRows > 0) {
            $dup_count++;
        } else {
            $doc_date = substr($result_sqlsvr["DI_DATE"],8,2) . "/" . substr($result_sqlsvr["DI_DATE"],5,2) . "/" . strval(intval(substr($result_sqlsvr["DI_DATE"],0,4))+543);

            $stmt_insert->execute([
                ':DI_KEY' => $result_sqlsvr["DI_KEY"],
                ':doc_no' => $result_sqlsvr["DI_REF"],
                ':doc_date' => $doc_date,
                ':customer_id' => $result_sqlsvr["AR_CODE"],
                ':customer_name' => $result_sqlsvr["AR_NAME"]
            ]);
            $insert_count++;
        }
    }
    $conn->commit();
    echo "Import credit sale completed. Inserted: $insert_count, Duplicates: $dup_count\n\r";
} catch (Exception $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}

$conn_sqlsvr=null;

