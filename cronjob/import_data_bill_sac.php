<?php

ini_set('display_errors', 1);
error_reporting(~0);

include("../config/connect_sqlserver.php");
include("../config/connect_db.php");
include('../util/month_util.php');

$sql_query_data = " SELECT DOCTYPE.DT_DOCCODE,DOCTYPE.DT_THAIDESC,DOCINFO.DI_REF,DOCINFO.DI_DATE,DOCINFO.DI_AMOUNT,ARFILE.AR_CODE,ARFILE.AR_NAME
,ARDETAIL.ARD_BIL_DA,ARDETAIL.ARD_DUE_DA,ARDETAIL.ARD_CHQ_DA,ARFILE.AR_SLMNCODE,SALESMAN.SLMN_NAME,ARFILE.AR_REMARK ,DOCINFO.DI_REMARK,DOCINFO.DI_ACTIVE 
FROM DOCINFO WITH (NOLOCK) 
LEFT JOIN ARDETAIL WITH (NOLOCK) ON DOCINFO.DI_KEY = ARDETAIL.ARD_DI
LEFT JOIN ARFILE WITH (NOLOCK) ON ARDETAIL.ARD_AR = ARFILE.AR_KEY 
LEFT JOIN SALESMAN WITH (NOLOCK) ON SALESMAN.SLMN_CODE = ARFILE.AR_SLMNCODE
LEFT JOIN DOCTYPE WITH (NOLOCK) ON DOCTYPE.DT_KEY = DOCINFO.DI_DT ";

echo "Today is " . date("Y/m/d") . "\n\r";
echo date("Y/m/d", strtotime("yesterday")) . "\n\r";

$query_daily_cond_doc_type = " WHERE (DOCTYPE.DT_DOCCODE in ('DS','DS1','IV01','DS02','30','DS4','DDS5','IV3','/SAC','S.1','S.2','S.3','S.4','S.5','S.6')) ";
$select_query_daily_cond = " AND DOCINFO.DI_DATE BETWEEN '" . date("Y/m/d", strtotime("yesterday")) . "' AND '" . date("Y/m/d") . "'";

$sql_sqlsvr = $sql_query_data . $query_daily_cond_doc_type . $select_query_daily_cond . " ORDER BY DOCINFO.DI_DATE , DOCINFO.DI_REF ";

$stmt_sqlsvr = $conn_sqlsvr->prepare($sql_sqlsvr);
$stmt_sqlsvr->execute();

$sql_find = "SELECT COUNT(*) FROM ims_document_bill WHERE DI_REF = :DI_REF";
$stmt_find = $conn->prepare($sql_find);

$sql_update = "UPDATE ims_document_bill SET DI_AMOUNT=:DI_AMOUNT,DI_ACTIVE=:DI_ACTIVE,AR_SLMNCODE=:AR_SLMNCODE,SLMN_NAME=:SLMN_NAME WHERE DI_REF = :DI_REF";
$stmt_update = $conn->prepare($sql_update);

$sql_insert = "INSERT INTO ims_document_bill (DT_DOCCODE,DT_THAIDESC,DI_REF,DI_DATE,DI_AMOUNT,AR_CODE,AR_NAME
,ARD_BIL_DA,ARD_DUE_DA,ARD_CHQ_DA,AR_SLMNCODE,SLMN_NAME,AR_REMARK ,DI_REMARK,DI_ACTIVE)
VALUES (:DT_DOCCODE,:DT_THAIDESC,:DI_REF,:DI_DATE,:DI_AMOUNT,:AR_CODE,:AR_NAME
,:ARD_BIL_DA,:ARD_DUE_DA,:ARD_CHQ_DA,:AR_SLMNCODE,:SLMN_NAME,:AR_REMARK ,:DI_REMARK,:DI_ACTIVE)";
$stmt_insert = $conn->prepare($sql_insert);

$update_count = 0;
$insert_count = 0;

$conn->beginTransaction();
try {
    while ($result_sqlsvr = $stmt_sqlsvr->fetch(PDO::FETCH_ASSOC)) {
        $stmt_find->execute([':DI_REF' => $result_sqlsvr["DI_REF"]]);
        $nRows = $stmt_find->fetchColumn();

        if ($nRows > 0) {
            $stmt_update->execute([
                ':DI_AMOUNT' => $result_sqlsvr["DI_AMOUNT"],
                ':DI_ACTIVE' => $result_sqlsvr["DI_ACTIVE"],
                ':AR_SLMNCODE' => $result_sqlsvr["AR_SLMNCODE"],
                ':SLMN_NAME' => $result_sqlsvr["SLMN_NAME"],
                ':DI_REF' => $result_sqlsvr["DI_REF"]
            ]);
            $update_count++;
        } else {
            $stmt_insert->execute([
                ':DT_DOCCODE' => $result_sqlsvr["DT_DOCCODE"],
                ':DT_THAIDESC' => $result_sqlsvr["DT_THAIDESC"],
                ':DI_REF' => $result_sqlsvr["DI_REF"],
                ':DI_DATE' => $result_sqlsvr["DI_DATE"],
                ':DI_AMOUNT' => $result_sqlsvr["DI_AMOUNT"],
                ':AR_CODE' => $result_sqlsvr["AR_CODE"],
                ':AR_NAME' => $result_sqlsvr["AR_NAME"],
                ':ARD_BIL_DA' => $result_sqlsvr["ARD_BIL_DA"],
                ':ARD_DUE_DA' => $result_sqlsvr["ARD_DUE_DA"],
                ':ARD_CHQ_DA' => $result_sqlsvr["ARD_CHQ_DA"],
                ':AR_SLMNCODE' => $result_sqlsvr["AR_SLMNCODE"],
                ':SLMN_NAME' => $result_sqlsvr["SLMN_NAME"],
                ':AR_REMARK' => $result_sqlsvr["AR_REMARK"],
                ':DI_REMARK' => $result_sqlsvr["DI_REMARK"],
                ':DI_ACTIVE' => $result_sqlsvr["DI_ACTIVE"]
            ]);
            $insert_count++;
        }
    }
    $conn->commit();
    echo "Import bill completed. Updated: $update_count, Inserted: $insert_count\n\r";
} catch (Exception $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}

$conn_sqlsvr = null;

