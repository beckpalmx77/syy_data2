<?php

ini_set('display_errors', 1);
error_reporting(~0);

include ("../config/connect_sqlserver.php");
include ("../config/connect_db.php");

include ("../cond_file/doc_info-query-001.php");

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

$sql_find = "SELECT COUNT(*) FROM ims_price_approve_detail WHERE DI_KEY = :DI_KEY AND line_no = :line_no";
$stmt_find = $conn->prepare($sql_find);

$sql_insert = "INSERT INTO ims_price_approve_detail(DI_KEY,doc_no,line_no,doc_date,customer_id,customer_name,product_id,product_name,price_normal,price_special,remark) 
        VALUES (:DI_KEY,:doc_no,:line_no,:doc_date,:customer_id,:customer_name,:product_id,:product_name,:price_normal,:price_special,:remark)";
$stmt_insert = $conn->prepare($sql_insert);

$insert_count = 0;
$dup_count = 0;

$conn->beginTransaction();
try {
    while ($result_sqlsvr = $stmt_sqlsvr->fetch(PDO::FETCH_ASSOC)) {
        $stmt_find->execute([
            ':DI_KEY' => $result_sqlsvr["DI_KEY"],
            ':line_no' => $result_sqlsvr["TRD_SEQ"]
        ]);
        $nRows = $stmt_find->fetchColumn();

        if ($nRows > 0) {
            $dup_count++;
        } else {
            $doc_date = substr($result_sqlsvr["DI_DATE"],8,2) . "/" . substr($result_sqlsvr["DI_DATE"],5,2) . "/" . strval(intval(substr($result_sqlsvr["DI_DATE"],0,4))+543);
            $remark = "Price/Unit ^^ TRD_K_U_PRC = " . $result_sqlsvr["TRD_K_U_PRC"] . " | TRD_U_PRC = " . $result_sqlsvr["TRD_U_PRC"];

            $stmt_insert->execute([
                ':DI_KEY' => $result_sqlsvr["DI_KEY"],
                ':doc_no' => $result_sqlsvr["DI_REF"],
                ':line_no' => $result_sqlsvr["TRD_SEQ"],
                ':doc_date' => $doc_date,
                ':customer_id' => $result_sqlsvr["AR_CODE"],
                ':customer_name' => $result_sqlsvr["AR_NAME"],
                ':product_id' => $result_sqlsvr["TRD_SH_CODE"],
                ':product_name' => $result_sqlsvr["TRD_SH_NAME"],
                ':price_normal' => $result_sqlsvr["TRD_K_U_PRC"],
                ':price_special' => $result_sqlsvr["TRD_K_U_PRC"],
                ':remark' => $remark
            ]);
            $insert_count++;
        }
    }
    $conn->commit();
    echo "Import reserve detail completed. Inserted: $insert_count, Duplicates: $dup_count\n\r";
} catch (Exception $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}

$conn_sqlsvr=null;

