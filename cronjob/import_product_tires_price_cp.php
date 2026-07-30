<?php

ini_set('display_errors', 1);
error_reporting(~0);

include("../config/connect_sqlserver.php");
include("../config/connect_db.php");
include("../cond_file/query-product-price-main.php");

$qeury_where = " AND ICCAT_CODE IN ('2SAC01','2SAC02','2SAC03','2SAC04','2SAC05','2SAC06'
,'2SAC07','2SAC08','2SAC09','2SAC10','2SAC11','2SAC12','2SAC13','2SAC14','2SAC15') 
AND ARPRB_CODE = 'CP1'";

$sql_sqlsvr = $select_query . $sql_cond . $qeury_where . " " . $sql_order;

$stmt_sqlsvr = $conn_sqlsvr->prepare($sql_sqlsvr);
$stmt_sqlsvr->execute();

$sql_find = "SELECT COUNT(*) FROM ims_tires_cockpit WHERE product_id = :product_id AND product_key = :product_key AND price_code = :price_code";
$stmt_find = $conn->prepare($sql_find);

$sql_update = "UPDATE ims_tires_cockpit SET name_t=:name_t , brand_id=:brand_id , price=:price 
               WHERE product_id = :product_id AND product_key = :product_key AND price_code = :price_code";
$stmt_update = $conn->prepare($sql_update);

$sql_insert = "INSERT INTO ims_tires_cockpit(product_key,product_id,name_t,brand_id,price_code,price) 
               VALUES (:product_key,:product_id,:name_t,:brand_id,:price_code,:price)";
$stmt_insert = $conn->prepare($sql_insert);

$update_count = 0;
$insert_count = 0;

$conn->beginTransaction();
try {
    while ($result_sqlsvr = $stmt_sqlsvr->fetch(PDO::FETCH_ASSOC)) {
        $stmt_find->execute([
            ':product_id' => $result_sqlsvr["SKU_CODE"],
            ':product_key' => $result_sqlsvr["SKU_KEY"],
            ':price_code' => $result_sqlsvr["ARPRB_CODE"]
        ]);
        $nRows = $stmt_find->fetchColumn();

        if ($nRows > 0) {
            $stmt_update->execute([
                ':name_t' => $result_sqlsvr["SKU_NAME"],
                ':brand_id' => $result_sqlsvr["BRN_CODE"],
                ':price' => $result_sqlsvr["ARPLU_U_PRC"],
                ':product_id' => $result_sqlsvr["SKU_CODE"],
                ':product_key' => $result_sqlsvr["SKU_KEY"],
                ':price_code' => $result_sqlsvr["ARPRB_CODE"]
            ]);
            $update_count++;
        } else {
            $stmt_insert->execute([
                ':product_key' => $result_sqlsvr["SKU_KEY"],
                ':product_id' => $result_sqlsvr["SKU_CODE"],
                ':name_t' => $result_sqlsvr["SKU_NAME"],
                ':brand_id' => $result_sqlsvr["BRN_CODE"],
                ':price_code' => $result_sqlsvr["ARPRB_CODE"],
                ':price' => $result_sqlsvr["ARPLU_U_PRC"]
            ]);
            $insert_count++;
        }
    }
    $conn->commit();
    echo "Import tires price CP completed. Updated: $update_count, Inserted: $insert_count\n\r";
} catch (Exception $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}

$conn_sqlsvr = null;


