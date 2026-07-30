<?php

ini_set('display_errors', 1);
error_reporting(~0);

include("../config/connect_db.php");

$cond_date = " STR_TO_DATE(DI_DATE,'%d/%m/%Y') BETWEEN 
STR_TO_DATE('" . date("d/m/Y", strtotime("yesterday")) . "','%d/%m/%Y') AND 
STR_TO_DATE('" . date("d/m/Y") . "','%d/%m/%Y') ";

$sql_main = " SELECT DI_DATE,BRANCH FROM ims_product_sale_cockpit WHERE " . $cond_date
            . " GROUP BY DI_DATE,BRANCH "
            . " ORDER BY STR_TO_DATE(DI_DATE,'%d/%m/%Y') ";

$stmt_main = $conn->prepare($sql_main);
$stmt_main->execute();

$sql_find = "SELECT COUNT(*) FROM ims_report_product_sale WHERE DI_DATE = :DI_DATE AND SALE_CODE = :SALE_CODE";
$stmt_find = $conn->prepare($sql_find);

$sql_ins = "INSERT INTO ims_report_product_sale (DI_DATE,SALE_CODE) VALUES (:DI_DATE,:SALE_CODE)";
$stmt_ins = $conn->prepare($sql_ins);

$insert_count = 0;
$dup_count = 0;

$conn->beginTransaction();
try {
    while ($result_main = $stmt_main->fetch(PDO::FETCH_ASSOC)) {
        $stmt_find->execute([
            ':DI_DATE' => $result_main["DI_DATE"],
            ':SALE_CODE' => $result_main["BRANCH"]
        ]);
        $nRows = $stmt_find->fetchColumn();

        if ($nRows > 0) {
            $dup_count++;
        } else {
            $stmt_ins->execute([
                ':DI_DATE' => $result_main["DI_DATE"],
                ':SALE_CODE' => $result_main["BRANCH"]
            ]);
            $insert_count++;
        }
    }
    $conn->commit();
    echo "Process product sale report completed. Inserted: $insert_count, Duplicates: $dup_count\n\r";
} catch (Exception $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}





    










