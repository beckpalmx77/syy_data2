<?php

ini_set('display_errors', 1);
error_reporting(~0);

include("../config/connect_sqlserver.php");
include("../config/connect_db.php");
include("../cond_file/query-product-tires.php");

$sql_sqlsvr = $select_query . $sql_cond . $sql_order;

$stmt_sqlsvr = $conn_sqlsvr->prepare($sql_sqlsvr);
$stmt_sqlsvr->execute();

$sql_find = "SELECT COUNT(*) FROM ims_tires_master WHERE SKU_CODE = :SKU_CODE";
$stmt_find = $conn->prepare($sql_find);

$sql_update = "UPDATE ims_tires_master SET SKU_NAME=:SKU_NAME,SKU_KEY=:SKU_KEY,BRN_CODE=:BRN_CODE,BRN_NAME=:BRN_NAME,ICCAT_CODE=:ICCAT_CODE,ICCAT_NAME=:ICCAT_NAME 
               WHERE SKU_CODE = :SKU_CODE";
$stmt_update = $conn->prepare($sql_update);

$sql_insert = "INSERT INTO ims_tires_master(SKU_CODE,SKU_NAME,SKU_KEY,BRN_CODE,BRN_NAME,ICCAT_CODE,ICCAT_NAME)
               VALUES (:SKU_CODE,:SKU_NAME,:SKU_KEY,:BRN_CODE,:BRN_NAME,:ICCAT_CODE,:ICCAT_NAME)";
$stmt_insert = $conn->prepare($sql_insert);

$update_count = 0;
$insert_count = 0;

$conn->beginTransaction();
try {
    while ($result_sqlsvr = $stmt_sqlsvr->fetch(PDO::FETCH_ASSOC)) {
        $stmt_find->execute([':SKU_CODE' => $result_sqlsvr["SKU_CODE"]]);
        $nRows = $stmt_find->fetchColumn();

        if ($nRows > 0) {
            $stmt_update->execute([
                ':SKU_NAME' => $result_sqlsvr["SKU_NAME"],
                ':SKU_KEY' => $result_sqlsvr["SKU_KEY"],
                ':BRN_CODE' => $result_sqlsvr["BRN_CODE"],
                ':BRN_NAME' => $result_sqlsvr["BRN_NAME"],
                ':ICCAT_CODE' => $result_sqlsvr["ICCAT_CODE"],
                ':ICCAT_NAME' => $result_sqlsvr["ICCAT_NAME"],
                ':SKU_CODE' => $result_sqlsvr["SKU_CODE"]
            ]);
            $update_count++;
        } else {
            $stmt_insert->execute([
                ':SKU_CODE' => $result_sqlsvr["SKU_CODE"],
                ':SKU_NAME' => $result_sqlsvr["SKU_NAME"],
                ':SKU_KEY' => $result_sqlsvr["SKU_KEY"],
                ':BRN_CODE' => $result_sqlsvr["BRN_CODE"],
                ':BRN_NAME' => $result_sqlsvr["BRN_NAME"],
                ':ICCAT_CODE' => $result_sqlsvr["ICCAT_CODE"],
                ':ICCAT_NAME' => $result_sqlsvr["ICCAT_NAME"]
            ]);
            $insert_count++;
        }
    }
    $conn->commit();
    echo "Import tires master completed. Updated: $update_count, Inserted: $insert_count\n\r";
} catch (Exception $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}

$conn_sqlsvr = null;
