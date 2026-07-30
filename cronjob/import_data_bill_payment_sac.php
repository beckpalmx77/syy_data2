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
 (DOCTYPE.DT_PROPERTIES = '404') AND
 (DOCINFO.DI_ACTIVE = 0) ";

$order_by = " ORDER BY DOCINFO.DI_DATE ASC, DOCINFO.DI_REF ASC ";

echo "Today is " . date("Y/m/d") . "\n\r";
echo date("Y/m/d", strtotime("yesterday")) . "\n\r";

$select_query_daily_cond = " AND DOCINFO.DI_DATE BETWEEN '" . date('Y/m/d', strtotime("-4 month")) . "' AND '" . date("Y/m/d") . "'";

$sql_sqlsvr = $sql_query_data . $select_query_daily_cond . $order_by ;

$stmt_sqlsvr = $conn_sqlsvr->prepare($sql_sqlsvr);
$stmt_sqlsvr->execute();

$sql_find = "SELECT COUNT(*) FROM ims_document_bill WHERE DI_REF = :TPA_REFER_REF";
$stmt_find = $conn->prepare($sql_find);

$sql_update = "UPDATE ims_document_bill SET PAYMENT_DOC_DI=:PAYMENT_DOC_DI,PAYMENT_DOC_DATE=:PAYMENT_DOC_DATE,PAYMENT_STATUS=:PAYMENT_STATUS WHERE DI_REF = :TPA_REFER_REF";
$stmt_update = $conn->prepare($sql_update);

$update_count = 0;
$miss_count = 0;
$payment_status = 'Y';

$conn->beginTransaction();
try {
    while ($result_sqlsvr = $stmt_sqlsvr->fetch(PDO::FETCH_ASSOC)) {
        $stmt_find->execute([':TPA_REFER_REF' => $result_sqlsvr["TPA_REFER_REF"]]);
        $nRows = $stmt_find->fetchColumn();

        if ($nRows > 0) {
            $stmt_update->execute([
                ':PAYMENT_DOC_DI' => $result_sqlsvr["DI_REF"],
                ':PAYMENT_DOC_DATE' => $result_sqlsvr["DI_DATE"],
                ':PAYMENT_STATUS' => $payment_status,
                ':TPA_REFER_REF' => $result_sqlsvr["TPA_REFER_REF"]
            ]);
            $update_count++;
        } else {
            $miss_count++;
        }
    }
    $conn->commit();
    echo "Import bill payment completed. Updated: $update_count, Not Matched: $miss_count\n\r";
} catch (Exception $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}

$conn_sqlsvr = null;


