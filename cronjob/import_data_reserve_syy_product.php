<?php

ini_set('display_errors', 1);
error_reporting(~0);

include ("../config/connect_sqlserver.php");
include ("../config/connect_db.php");
include('../cond_file/query_reserve_sac.php');

$query_year = " AND DI_DATE BETWEEN '" . date("Y/m/d", strtotime("yesterday")) . "' AND '" . date("Y/m/d") . "'";
$sql_sqlsvr = $sql_reserve . $query_year ;
$stmt_sqlsvr = $conn_sqlsvr->prepare($sql_sqlsvr);
$stmt_sqlsvr->execute();

$sql_find = "SELECT COUNT(*) FROM ims_reserve_product_sac WHERE DI_KEY = :DI_KEY
    AND TRD_U_PRC = :TRD_U_PRC
    AND SKU_CODE = :SKU_CODE
    AND TRD_SEQ = :TRD_SEQ
    AND AR_NAME = :AR_NAME
    AND WL_CODE = :WL_CODE";
$stmt_find = $conn->prepare($sql_find);

$sql_insert = "INSERT INTO ims_reserve_product_sac(DI_KEY,TRD_SEQ,DI_DATE,SKU_CODE,SKU_NAME,TRD_QTY,TRD_U_PRC,WL_CODE,DI_REF,SLMN_NAME,AR_NAME) 
        VALUES (:DI_KEY,:TRD_SEQ,:DI_DATE,:SKU_CODE,:SKU_NAME,:TRD_QTY,:TRD_U_PRC,:WL_CODE,:DI_REF,:SLMN_NAME,:AR_NAME)";
$stmt_insert = $conn->prepare($sql_insert);

$insert_count = 0;
$dup_count = 0;

$conn->beginTransaction();
try {
    while ($result_sqlsvr = $stmt_sqlsvr->fetch(PDO::FETCH_ASSOC)) {
        $stmt_find->execute([
            ':DI_KEY' => $result_sqlsvr["DI_KEY"],
            ':TRD_U_PRC' => $result_sqlsvr["TRD_U_PRC"],
            ':SKU_CODE' => $result_sqlsvr["SKU_CODE"],
            ':TRD_SEQ' => $result_sqlsvr["TRD_SEQ"],
            ':AR_NAME' => $result_sqlsvr["AR_NAME"],
            ':WL_CODE' => $result_sqlsvr["WL_CODE"]
        ]);
        $nRows = $stmt_find->fetchColumn();

        if ($nRows > 0) {
            $dup_count++;
        } else {
            $DI_DATE = str_replace('/', '-', substr($result_sqlsvr["DI_DATE"], 0, 10));

            $stmt_insert->execute([
                ':DI_KEY' => $result_sqlsvr["DI_KEY"],
                ':TRD_SEQ' => $result_sqlsvr["TRD_SEQ"],
                ':DI_DATE' => $DI_DATE,
                ':SKU_CODE' => $result_sqlsvr["SKU_CODE"],
                ':SKU_NAME' => $result_sqlsvr["SKU_NAME"],
                ':TRD_QTY' => $result_sqlsvr["TRD_QTY"],
                ':TRD_U_PRC' => $result_sqlsvr["TRD_U_PRC"],
                ':WL_CODE' => $result_sqlsvr["WL_CODE"],
                ':DI_REF' => $result_sqlsvr["DI_REF"],
                ':SLMN_NAME' => $result_sqlsvr["SLMN_NAME"],
                ':AR_NAME' => $result_sqlsvr["AR_NAME"]
            ]);
            $insert_count++;
        }
    }
    $conn->commit();
    echo "Import reserve syy product completed. Inserted: $insert_count, Duplicates: $dup_count\n\r";
} catch (Exception $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}

$conn_sqlsvr=null;

