<?php

ini_set('display_errors', 1);
error_reporting(~0);

include("../config/connect_sqlserver.php");
include("../config/connect_db.php");
include('../util/month_util.php');

$sql_query_data = " SELECT 
 DOCINFO.DI_REF,
 DOCINFO.DI_DATE,
 DOCINFO.DI_ACTIVE,
 ARFILE.AR_CODE,
 ARFILE.AR_NAME,
 TRANPAYH.TPH_LSTATUS,
 TRANPAYA.TPA_REFER_REF,
 TRANPAYA.TPA_REFER_DATE,
 TRANPAYA.TPA_REFER_DI,
 ARDETAIL.ARD_LASTPM_DI,
 TRANPAYA.TPA_B4_A_SNV,
 TRANPAYA.TPA_B4_A_SV,
 TRANPAYA.TPA_B4_A_VAT,
 TRANPAYA.TPA_B4_A_AMT,
 TRANPAYA.TPA_B4_P_AMT,
 TRANPAYA.TPA_B4_Q_AMT,
 TRANPAYA.TPA_WHTX_R,
 TRANPAYA.TPA_DISC_A,
 TRANPAYA.TPA_NOTE_A,
 TRANPAYA.TPA_CASH_A,
 TRANPAYA.TPA_CHEQ_A,
 TRANPAYA.TPA_WHTX_A,
 TRANPAYA.TPA_TRFR_A,
 TRANPAYA.TPA_OTHR_A,
 TRANPAYA.TPA_SHRT_A,
 TRANPAYA.TPA_GROUP,
 TRANPAYA.TPA_VAT_DATE,
 TRANPAYA.TPA_VAT_REF,
 ARDETAIL.ARD_DUE_DA,
 ARDETAIL.ARD_BIL_DA,
 ARDETAIL.ARD_G_SNV,
 ARDETAIL.ARD_G_SV,
 ARDETAIL.ARD_G_VAT,
 ARDETAIL.ARD_G_KEYIN,
 ARDETAIL.ARD_TDSC_KEYIN,
 ARDETAIL.ARD_TDSC_KEYINV,
 ARDETAIL.ARD_N_SNV,
 ARDETAIL.ARD_N_SV,
 ARDETAIL.ARD_N_VAT,
 ARDETAIL.ARD_N_AMT,
 ARDETAIL.ARD_XCHG,
 ARDETAIL.ARD_A_SNV,
 ARDETAIL.ARD_A_SV,
 ARDETAIL.ARD_A_VAT,
 ARDETAIL.ARD_A_AMT,
 ARDETAIL.ARD_P_AMT,
 ARDETAIL.ARD_Q_AMT,
 ARDETAIL.ARD_BILL_DI
 
FROM
 DOCINFO WITH (NOLOCK) 
 JOIN DOCTYPE WITH (NOLOCK) ON DI_DT = DT_KEY
 JOIN TRANPAYH WITH (NOLOCK) ON DI_KEY = TPH_DI
 JOIN TRANPAYA WITH (NOLOCK) ON TPH_KEY = TPA_TPH
 JOIN ARDETAIL WITH (NOLOCK) ON TPA_REFER_ARPD = ARD_KEY
 JOIN ARFILE WITH (NOLOCK) ON TPH_AR = AR_KEY
 JOIN ARCAT WITH (NOLOCK) ON AR_ARCAT = ARCAT_KEY

WHERE
 (DOCTYPE.DT_PROPERTIES = '406') AND
 (DOCINFO.DI_ACTIVE = 0) ";

$order_by = " ORDER BY DOCINFO.DI_DATE ASC, DOCINFO.DI_REF ASC ";

echo "Today is " . date("Y/m/d") . "\n\r";
echo date("Y/m/d", strtotime("yesterday")) . "\n\r";

$select_query_daily_cond = " AND DOCINFO.DI_DATE BETWEEN '" . date("Y/m/d", strtotime("yesterday")) . "' AND '" . date("Y/m/d") . "'";

$sql_sqlsvr = $sql_query_data . $select_query_daily_cond . $order_by ;

$stmt_sqlsvr = $conn_sqlsvr->prepare($sql_sqlsvr);
$stmt_sqlsvr->execute();

$sql_find = "SELECT COUNT(*) FROM ims_document_bill_load WHERE DI_REF = :DI_REF";
$stmt_find = $conn->prepare($sql_find);

$sql_update = "UPDATE ims_document_bill_load SET TPA_REFER_REF=:TPA_REFER_REF,TPA_REFER_DATE=:TPA_REFER_DATE,DI_ACTIVE=:DI_ACTIVE,
ARD_A_SV=:ARD_A_SV,ARD_A_VAT=:ARD_A_VAT,ARD_A_AMT=:ARD_A_AMT WHERE DI_REF = :DI_REF";
$stmt_update = $conn->prepare($sql_update);

$sql_insert = "INSERT INTO ims_document_bill_load (DI_REF,DI_DATE,TPA_REFER_REF,TPA_REFER_DATE,AR_CODE,AR_NAME
,ARD_BIL_DA,ARD_DUE_DA,ARD_A_SV,ARD_A_VAT,ARD_A_AMT,DI_ACTIVE)
VALUES (:DI_REF,:DI_DATE,:TPA_REFER_REF,:TPA_REFER_DATE,:AR_CODE,:AR_NAME
,:ARD_BIL_DA,:ARD_DUE_DA,:ARD_A_SV,:ARD_A_VAT,:ARD_A_AMT,:DI_ACTIVE)";
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
                ':TPA_REFER_REF' => $result_sqlsvr["TPA_REFER_REF"],
                ':TPA_REFER_DATE' => $result_sqlsvr["TPA_REFER_DATE"],
                ':DI_ACTIVE' => $result_sqlsvr["DI_ACTIVE"],
                ':ARD_A_SV' => $result_sqlsvr["ARD_A_SV"],
                ':ARD_A_VAT' => $result_sqlsvr["ARD_A_VAT"],
                ':ARD_A_AMT' => $result_sqlsvr["ARD_A_AMT"],
                ':DI_REF' => $result_sqlsvr["DI_REF"]
            ]);
            $update_count++;
        } else {
            $stmt_insert->execute([
                ':DI_REF' => $result_sqlsvr["DI_REF"],
                ':DI_DATE' => $result_sqlsvr["DI_DATE"],
                ':TPA_REFER_REF' => $result_sqlsvr["TPA_REFER_REF"],
                ':TPA_REFER_DATE' => $result_sqlsvr["TPA_REFER_DATE"],
                ':AR_CODE' => $result_sqlsvr["AR_CODE"],
                ':AR_NAME' => $result_sqlsvr["AR_NAME"],
                ':ARD_BIL_DA' => $result_sqlsvr["ARD_BIL_DA"],
                ':ARD_DUE_DA' => $result_sqlsvr["ARD_DUE_DA"],
                ':ARD_A_SV' => $result_sqlsvr["ARD_A_SV"],
                ':ARD_A_VAT' => $result_sqlsvr["ARD_A_VAT"],
                ':ARD_A_AMT' => $result_sqlsvr["ARD_A_AMT"],
                ':DI_ACTIVE' => $result_sqlsvr["DI_ACTIVE"]
            ]);
            $insert_count++;
        }
    }
    $conn->commit();
    echo "Import bill load completed. Updated: $update_count, Inserted: $insert_count\n\r";
} catch (Exception $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}

$conn_sqlsvr = null;


