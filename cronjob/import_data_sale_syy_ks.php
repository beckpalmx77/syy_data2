<?php

ini_set('display_errors', 1);
error_reporting(~0);
set_time_limit(0);
ini_set('memory_limit', '1024M');

// Record Start Time
$start_microtime = microtime(true);
$start_datetime = date("Y-m-d H:i:s");

echo "========================================================\n\r";
echo "Start Wholesale Sales Import Process\n\r";
echo "Start Time: " . $start_datetime . "\n\r";
echo "========================================================\n\r";

include(__DIR__ . "/../config/connect_sqlserver.php");
include(__DIR__ . "/../config/connect_db.php");

include(__DIR__ . '/../cond_file/doc_info_wholesale_ks.php');
include(__DIR__ . '/../util/month_util.php');

$DT_DOCCODE_MINUS = "IS";

$str_doc1 = array_flip(array("IV00", "DS00", "CV00", "CS00", "IS", "ISO"));

$str_group1 = array_flip(array(
    "101-AP01","101-AT01","101-BKT01","101-BS01","101-BS02","101-BS03","101-BS04","101-BS05","101-BS06","101-DS01","101-DS02","101-DS03","101-FS01","101-FS02","101-FS03","101-FS04","101-LLIT01","101-ML01","101-O01","101-RS01",
    "101-SIM01","101-T%01","101-TR01","101-VR01","102-CAV01","102-CBG01","102-CBS01","102-CDS01","102-CKL01","102-CKS01","102-CML01","102-CMT01","102-CO01","102-T%01","103-LBS01","103-LDS01","103-LKS01","103-LML01","103-LMT01",
    "103-LO01","103-T%01","201-AP01","201-AT01","201-BF01","201-BS01","201-CT01","201-DL01","201-DS01","201-DT01","201-FK01","201-FS01","201-FT01","201-GY01","201-HK01","201-KENDA","201-LEAO01","201-LLIT01","201-ML01",
    "201-MS01","201-MX01","201-NT01","201-O01","201-SIM01","201-T%01","201-T01","201-TOYO01","201-VR01","201-YK01"
));
$str_group2 = array_flip(array("402-J02","401-J01","401-KV01","401-WIL01","401-WIL02","401-WIL-03","601-1","601-2","602-1","602-2","602-3-ALL01","603-1","610-1","610-2","610-3","9-9402-ALL02"));
$str_group3 = array_flip(array("999-01","999-02","999-03","999-04","999-05","999-06","999-07","999-08","999-09","999-10","999-11","999-12","999-13","999-14","999-15","999-16","999-17","999-18","999-19","999-20","999-21","999-22","999-23","SY02-00140"));
$str_group4 = array_flip(array("999-26","999-28","A502-ALL03"));

$date_start_raw = isset($argv[1]) ? trim($argv[1]) : '2026/01/01';
$date_to_raw = isset($argv[2]) ? trim($argv[2]) : date("Y/m/d");

$date_start = str_replace('-', '/', $date_start_raw);
$date_to = str_replace('-', '/', $date_to_raw);

// Convert Y/m/d to d/m/Y for MySQL DI_DATE lookup
$ds_parts = explode('/', $date_start);
$dt_parts = explode('/', $date_to);
$date_start_disp = (count($ds_parts) == 3 && strlen($ds_parts[0]) == 4) ? $ds_parts[2] . '/' . $ds_parts[1] . '/' . $ds_parts[0] : $date_start;
$date_to_disp = (count($dt_parts) == 3 && strlen($dt_parts[0]) == 4) ? $dt_parts[2] . '/' . $dt_parts[1] . '/' . $dt_parts[0] : $date_to;

echo "Today is " . $date_to . "\n\r";
echo "Start Date: " . $date_start . " (MySQL Lookup: " . $date_start_disp . ")\n\r";
echo "End Date:   " . $date_to . " (MySQL Lookup: " . $date_to_disp . ")\n\r";

$query_year = " AND DOCINFO.DI_DATE BETWEEN '" . $date_start . "' AND '" . $date_to . "'";
$sql_sqlsvr = $select_query_sale . $sql_cond_sale . $query_year . $sql_order_sale;

$stmt_sqlsvr = $conn_sqlsvr->prepare($sql_sqlsvr);
$stmt_sqlsvr->execute();

// Pre-load existing keys into memory hash map for fast lookup
$existing_keys = [];
try {
    $stmt_exist = $conn->query("SELECT DI_KEY, DI_REF, DI_DATE, DT_DOCCODE, TRD_SEQ FROM ims_product_sale_syy_ks");
    while ($row_ex = $stmt_exist->fetch(PDO::FETCH_ASSOC)) {
        $key = $row_ex['DI_KEY'] . '|' . $row_ex['DI_REF'] . '|' . $row_ex['DI_DATE'] . '|' . $row_ex['DT_DOCCODE'] . '|' . $row_ex['TRD_SEQ'];
        $existing_keys[$key] = true;
    }
} catch (Exception $e) {
    $existing_keys = [];
}

$sql_update = "UPDATE ims_product_sale_syy_ks SET 
DI_TIME_CHK=:DI_TIME_CHK, DI_MONTH=:DI_MONTH, DI_MONTH_NAME=:DI_MONTH_NAME, DI_YEAR=:DI_YEAR, DI_ACTIVE=:DI_ACTIVE, DT_PROPERTIES=:DT_PROPERTIES,
AR_CODE=:AR_CODE, AR_NAME=:AR_NAME, AROE_B_AMT=:AROE_B_AMT, ARD_B_VAT=:ARD_B_VAT, ARD_B_SV=:ARD_B_SV, ARD_B_SNV=:ARD_B_SNV,
ARD_TDSC_KEYIN=:ARD_TDSC_KEYIN, ARD_TDSC_KEYINV=:ARD_TDSC_KEYINV, ARD_G_VAT=:ARD_G_VAT, ARD_G_SV=:ARD_G_SV, ARD_G_SNV=:ARD_G_SNV,
ARD_G_KEYIN=:ARD_G_KEYIN, ARD_DUE_DA=:ARD_DUE_DA, ARD_CRNCYCODE=:ARD_CRNCYCODE, ARD_XCHG=:ARD_XCHG,
SLMN_CODE=:SLMN_CODE, SLMN_NAME=:SLMN_NAME, TRH_REFER_XREF=:TRH_REFER_XREF, TRH_REFER_IREF=:TRH_REFER_IREF, TRH_REFER_PERSON=:TRH_REFER_PERSON,
TRH_REFER_XTRA1=:TRH_REFER_XTRA1, TRH_REFER_XTRA2=:TRH_REFER_XTRA2, TRH_SHIP_DATE=:TRH_SHIP_DATE, TRH_VAT_TY=:TRH_VAT_TY, TRH_VAT_R=:TRH_VAT_R,
TRH_VATIO=:TRH_VATIO, TRH_N_ITEMS=:TRH_N_ITEMS, TRH_N_QTY=:TRH_N_QTY, DEPT_CODE=:DEPT_CODE, DEPT_THAIDESC=:DEPT_THAIDESC, DEPT_ENGDESC=:DEPT_ENGDESC,
PRJ_CODE=:PRJ_CODE, PRJ_NAME=:PRJ_NAME, ICCAT_CODE=:ICCAT_CODE, ICCAT_NAME=:ICCAT_NAME, SKU_CODE=:SKU_CODE, SKU_NAME=:SKU_NAME,
SKU_E_NAME=:SKU_E_NAME, UTQ_NAME=:UTQ_NAME, UTQ_QTY=:UTQ_QTY, GOODS_CODE=:GOODS_CODE, TRD_VAT_TY=:TRD_VAT_TY, TRD_VAT=:TRD_VAT,
TRD_VAT_R=:TRD_VAT_R, TRD_LOT_NO=:TRD_LOT_NO, TRD_SERIAL=:TRD_SERIAL, TRD_SH_CODE=:TRD_SH_CODE, TRD_SH_NAME=:TRD_SH_NAME, BRN_CODE=:BRN_CODE,
BRN_NAME=:BRN_NAME, TRD_QTY=:TRD_QTY, TRD_Q_FREE=:TRD_Q_FREE, TRD_UTQNAME=:TRD_UTQNAME, TRD_UTQQTY=:TRD_UTQQTY, TRD_K_U_PRC=:TRD_K_U_PRC,
TRD_U_PRC=:TRD_U_PRC, TRD_U_VATIO=:TRD_U_VATIO, TRD_B_UPRC=:TRD_B_UPRC, TRD_DSC_KEYIN=:TRD_DSC_KEYIN, TRD_DSC_KEYINV=:TRD_DSC_KEYINV,
TRD_G_AMT=:TRD_G_AMT, TRD_G_KEYIN=:TRD_G_KEYIN, TRD_G_SELL=:TRD_G_SELL, TRD_G_VAT=:TRD_G_VAT, TRD_TDSC_KEYINV=:TRD_TDSC_KEYINV,
TRD_B_SELL=:TRD_B_SELL, TRD_B_VAT=:TRD_B_VAT, TRD_B_AMT=:TRD_B_AMT, WL_CODE=:WL_CODE, WH_CODE=:WH_CODE, ARCD_NAME=:ARCD_NAME,
BRANCH=:BRANCH, PGROUP=:PGROUP
WHERE DI_KEY = :DI_KEY AND DI_REF = :DI_REF AND DI_DATE = :DI_DATE AND DT_DOCCODE = :DT_DOCCODE AND TRD_SEQ = :TRD_SEQ";
$stmt_update = $conn->prepare($sql_update);

$sql_insert = "INSERT INTO ims_product_sale_syy_ks (
DI_KEY, DI_REF, DI_DATE, DI_TIME_CHK, DI_MONTH, DI_MONTH_NAME, DI_YEAR, DI_ACTIVE, DT_DOCCODE, DT_PROPERTIES,
AR_CODE, AR_NAME, AROE_B_AMT, ARD_B_VAT, ARD_B_SV, ARD_B_SNV, ARD_TDSC_KEYIN, ARD_TDSC_KEYINV, ARD_G_VAT, ARD_G_SV, ARD_G_SNV, ARD_G_KEYIN,
ARD_DUE_DA, ARD_CRNCYCODE, ARD_XCHG, SLMN_CODE, SLMN_NAME, TRH_REFER_XREF, TRH_REFER_IREF, TRH_REFER_PERSON, TRH_REFER_XTRA1, TRH_REFER_XTRA2,
TRH_SHIP_DATE, TRH_VAT_TY, TRH_VAT_R, TRH_VATIO, TRH_N_ITEMS, TRH_N_QTY, DEPT_CODE, DEPT_THAIDESC, DEPT_ENGDESC, PRJ_CODE, PRJ_NAME,
ICCAT_CODE, ICCAT_NAME, TRD_SEQ, SKU_CODE, SKU_NAME, SKU_E_NAME, UTQ_NAME, UTQ_QTY, GOODS_CODE, TRD_VAT_TY, TRD_VAT, TRD_VAT_R,
TRD_LOT_NO, TRD_SERIAL, TRD_SH_CODE, TRD_SH_NAME, BRN_CODE, BRN_NAME, TRD_QTY, TRD_Q_FREE, TRD_UTQNAME, TRD_UTQQTY, TRD_K_U_PRC,
TRD_U_PRC, TRD_U_VATIO, TRD_B_UPRC, TRD_DSC_KEYIN, TRD_DSC_KEYINV, TRD_G_AMT, TRD_G_KEYIN, TRD_G_SELL, TRD_G_VAT, TRD_TDSC_KEYINV,
TRD_B_SELL, TRD_B_VAT, TRD_B_AMT, WL_CODE, WH_CODE, ARCD_NAME, BRANCH, PGROUP
) VALUES (
:DI_KEY, :DI_REF, :DI_DATE, :DI_TIME_CHK, :DI_MONTH, :DI_MONTH_NAME, :DI_YEAR, :DI_ACTIVE, :DT_DOCCODE, :DT_PROPERTIES,
:AR_CODE, :AR_NAME, :AROE_B_AMT, :ARD_B_VAT, :ARD_B_SV, :ARD_B_SNV, :ARD_TDSC_KEYIN, :ARD_TDSC_KEYINV, :ARD_G_VAT, :ARD_G_SV, :ARD_G_SNV, :ARD_G_KEYIN,
:ARD_DUE_DA, :ARD_CRNCYCODE, :ARD_XCHG, :SLMN_CODE, :SLMN_NAME, :TRH_REFER_XREF, :TRH_REFER_IREF, :TRH_REFER_PERSON, :TRH_REFER_XTRA1, :TRH_REFER_XTRA2,
:TRH_SHIP_DATE, :TRH_VAT_TY, :TRH_VAT_R, :TRH_VATIO, :TRH_N_ITEMS, :TRH_N_QTY, :DEPT_CODE, :DEPT_THAIDESC, :DEPT_ENGDESC, :PRJ_CODE, :PRJ_NAME,
:ICCAT_CODE, :ICCAT_NAME, :TRD_SEQ, :SKU_CODE, :SKU_NAME, :SKU_E_NAME, :UTQ_NAME, :UTQ_QTY, :GOODS_CODE, :TRD_VAT_TY, :TRD_VAT, :TRD_VAT_R,
:TRD_LOT_NO, :TRD_SERIAL, :TRD_SH_CODE, :TRD_SH_NAME, :BRN_CODE, :BRN_NAME, :TRD_QTY, :TRD_Q_FREE, :TRD_UTQNAME, :TRD_UTQQTY, :TRD_K_U_PRC,
:TRD_U_PRC, :TRD_U_VATIO, :TRD_B_UPRC, :TRD_DSC_KEYIN, :TRD_DSC_KEYINV, :TRD_G_AMT, :TRD_G_KEYIN, :TRD_G_SELL, :TRD_G_VAT, :TRD_TDSC_KEYINV,
:TRD_B_SELL, :TRD_B_VAT, :TRD_B_AMT, :WL_CODE, :WH_CODE, :ARCD_NAME, :BRANCH, :PGROUP
)";
$stmt_insert = $conn->prepare($sql_insert);

$update_count = 0;
$insert_count = 0;

$conn->beginTransaction();
try {
    while ($result_sqlsvr = $stmt_sqlsvr->fetch(PDO::FETCH_ASSOC)) {
        $DT_DOCCODE = $result_sqlsvr["DT_DOCCODE"] ?? '';
        $ICCAT_CODE = $result_sqlsvr["ICCAT_CODE"] ?? '';

        $branch = "SYY";
        if (isset($str_doc1[$DT_DOCCODE])) {
            $branch = "SYY";
        }

        $p_group = "";
        if (isset($str_group1[$ICCAT_CODE])) {
            $p_group = "P1";
        } elseif (isset($str_group2[$ICCAT_CODE])) {
            $p_group = "P2";
        } elseif (isset($str_group3[$ICCAT_CODE])) {
            $p_group = "P3";
        } elseif (isset($str_group4[$ICCAT_CODE])) {
            $p_group = "P4";
        }

        if (strpos($DT_DOCCODE, $DT_DOCCODE_MINUS) !== false || strpos($DT_DOCCODE, 'IS') !== false || strpos($DT_DOCCODE, 'ISO') !== false) {
            $TRD_QTY = (double)($result_sqlsvr["TRD_QTY"] ?? 0) > 0 ? -$result_sqlsvr["TRD_QTY"] : ($result_sqlsvr["TRD_QTY"] ?? 0);
            $TRD_Q_FREE = (double)($result_sqlsvr["TRD_Q_FREE"] ?? 0) > 0 ? -$result_sqlsvr["TRD_Q_FREE"] : ($result_sqlsvr["TRD_Q_FREE"] ?? 0);
            $TRD_TDSC_KEYINV = (double)($result_sqlsvr["TRD_TDSC_KEYINV"] ?? 0) > 0 ? -$result_sqlsvr["TRD_TDSC_KEYINV"] : ($result_sqlsvr["TRD_TDSC_KEYINV"] ?? 0);
            $TRD_B_AMT = (double)($result_sqlsvr["TRD_B_AMT"] ?? 0) > 0 ? -$result_sqlsvr["TRD_B_AMT"] : ($result_sqlsvr["TRD_B_AMT"] ?? 0);
            $TRD_B_SELL = (double)($result_sqlsvr["TRD_B_SELL"] ?? 0) > 0 ? -$result_sqlsvr["TRD_B_SELL"] : ($result_sqlsvr["TRD_B_SELL"] ?? 0);
            $TRD_B_VAT = (double)($result_sqlsvr["TRD_B_VAT"] ?? 0) > 0 ? -$result_sqlsvr["TRD_B_VAT"] : ($result_sqlsvr["TRD_B_VAT"] ?? 0);
            $TRD_G_KEYIN = (double)($result_sqlsvr["TRD_G_KEYIN"] ?? 0) > 0 ? -$result_sqlsvr["TRD_G_KEYIN"] : ($result_sqlsvr["TRD_G_KEYIN"] ?? 0);
        } else {
            $TRD_QTY = $result_sqlsvr["TRD_QTY"] ?? 0;
            $TRD_Q_FREE = $result_sqlsvr["TRD_Q_FREE"] ?? 0;
            $TRD_TDSC_KEYINV = $result_sqlsvr["TRD_TDSC_KEYINV"] ?? 0;
            $TRD_B_AMT = $result_sqlsvr["TRD_B_AMT"] ?? 0;
            $TRD_B_SELL = $result_sqlsvr["TRD_B_SELL"] ?? 0;
            $TRD_B_VAT = $result_sqlsvr["TRD_B_VAT"] ?? 0;
            $TRD_G_KEYIN = $result_sqlsvr["TRD_G_KEYIN"] ?? 0;
        }

        $di_month = isset($result_sqlsvr["DI_MONTH"]) ? (int)$result_sqlsvr["DI_MONTH"] : 0;
        $month_name = isset($month_arr[$di_month]) ? $month_arr[$di_month] : "";

        $key = $result_sqlsvr["DI_KEY"] . '|' . $result_sqlsvr["DI_REF"] . '|' . $result_sqlsvr["DI_DATE"] . '|' . $result_sqlsvr["DT_DOCCODE"] . '|' . $result_sqlsvr["TRD_SEQ"];
        $is_update = isset($existing_keys[$key]);

        $params = [
            ':DI_KEY' => $result_sqlsvr["DI_KEY"] ?? 0,
            ':DI_REF' => $result_sqlsvr["DI_REF"] ?? '',
            ':DI_DATE' => $result_sqlsvr["DI_DATE"] ?? '',
            ':DI_TIME_CHK' => $result_sqlsvr["DI_TIME_CHK"] ?? '',
            ':DI_MONTH' => $di_month,
            ':DI_MONTH_NAME' => $month_name,
            ':DI_YEAR' => $result_sqlsvr["DI_YEAR"] ?? 0,
            ':DI_ACTIVE' => $result_sqlsvr["DI_ACTIVE"] ?? 0,
            ':DT_DOCCODE' => $result_sqlsvr["DT_DOCCODE"] ?? '',
            ':DT_PROPERTIES' => $result_sqlsvr["DT_PROPERTIES"] ?? 0,
            ':AR_CODE' => $result_sqlsvr["AR_CODE"] ?? '',
            ':AR_NAME' => $result_sqlsvr["AR_NAME"] ?? '',
            ':AROE_B_AMT' => $result_sqlsvr["AROE_B_AMT"] ?? 0,
            ':ARD_B_VAT' => $result_sqlsvr["ARD_B_VAT"] ?? 0,
            ':ARD_B_SV' => $result_sqlsvr["ARD_B_SV"] ?? 0,
            ':ARD_B_SNV' => $result_sqlsvr["ARD_B_SNV"] ?? 0,
            ':ARD_TDSC_KEYIN' => $result_sqlsvr["ARD_TDSC_KEYIN"] ?? 0,
            ':ARD_TDSC_KEYINV' => $result_sqlsvr["ARD_TDSC_KEYINV"] ?? 0,
            ':ARD_G_VAT' => $result_sqlsvr["ARD_G_VAT"] ?? 0,
            ':ARD_G_SV' => $result_sqlsvr["ARD_G_SV"] ?? 0,
            ':ARD_G_SNV' => $result_sqlsvr["ARD_G_SNV"] ?? 0,
            ':ARD_G_KEYIN' => $result_sqlsvr["ARD_G_KEYIN"] ?? 0,
            ':ARD_DUE_DA' => $result_sqlsvr["ARD_DUE_DA"] ?? '',
            ':ARD_CRNCYCODE' => $result_sqlsvr["ARD_CRNCYCODE"] ?? '',
            ':ARD_XCHG' => $result_sqlsvr["ARD_XCHG"] ?? 1,
            ':SLMN_CODE' => $result_sqlsvr["SLMN_CODE"] ?? '',
            ':SLMN_NAME' => $result_sqlsvr["SLMN_NAME"] ?? '',
            ':TRH_REFER_XREF' => $result_sqlsvr["TRH_REFER_XREF"] ?? '',
            ':TRH_REFER_IREF' => $result_sqlsvr["TRH_REFER_IREF"] ?? '',
            ':TRH_REFER_PERSON' => $result_sqlsvr["TRH_REFER_PERSON"] ?? '',
            ':TRH_REFER_XTRA1' => $result_sqlsvr["TRH_REFER_XTRA1"] ?? '',
            ':TRH_REFER_XTRA2' => $result_sqlsvr["TRH_REFER_XTRA2"] ?? '',
            ':TRH_SHIP_DATE' => $result_sqlsvr["TRH_SHIP_DATE"] ?? '',
            ':TRH_VAT_TY' => $result_sqlsvr["TRH_VAT_TY"] ?? 0,
            ':TRH_VAT_R' => $result_sqlsvr["TRH_VAT_R"] ?? 0,
            ':TRH_VATIO' => $result_sqlsvr["TRH_VATIO"] ?? 0,
            ':TRH_N_ITEMS' => $result_sqlsvr["TRH_N_ITEMS"] ?? 0,
            ':TRH_N_QTY' => $result_sqlsvr["TRH_N_QTY"] ?? 0,
            ':DEPT_CODE' => $result_sqlsvr["DEPT_CODE"] ?? '',
            ':DEPT_THAIDESC' => $result_sqlsvr["DEPT_THAIDESC"] ?? '',
            ':DEPT_ENGDESC' => $result_sqlsvr["DEPT_ENGDESC"] ?? '',
            ':PRJ_CODE' => $result_sqlsvr["PRJ_CODE"] ?? '',
            ':PRJ_NAME' => $result_sqlsvr["PRJ_NAME"] ?? '',
            ':ICCAT_CODE' => $result_sqlsvr["ICCAT_CODE"] ?? '',
            ':ICCAT_NAME' => $result_sqlsvr["ICCAT_NAME"] ?? '',
            ':TRD_SEQ' => $result_sqlsvr["TRD_SEQ"] ?? 0,
            ':SKU_CODE' => $result_sqlsvr["SKU_CODE"] ?? '',
            ':SKU_NAME' => $result_sqlsvr["SKU_NAME"] ?? '',
            ':SKU_E_NAME' => $result_sqlsvr["SKU_E_NAME"] ?? '',
            ':UTQ_NAME' => $result_sqlsvr["UTQ_NAME"] ?? '',
            ':UTQ_QTY' => $result_sqlsvr["UTQ_QTY"] ?? 0,
            ':GOODS_CODE' => $result_sqlsvr["GOODS_CODE"] ?? '',
            ':TRD_VAT_TY' => $result_sqlsvr["TRD_VAT_TY"] ?? 0,
            ':TRD_VAT' => $result_sqlsvr["TRD_VAT"] ?? 0,
            ':TRD_VAT_R' => $result_sqlsvr["TRD_VAT_R"] ?? 0,
            ':TRD_LOT_NO' => $result_sqlsvr["TRD_LOT_NO"] ?? '',
            ':TRD_SERIAL' => $result_sqlsvr["TRD_SERIAL"] ?? '',
            ':TRD_SH_CODE' => $result_sqlsvr["TRD_SH_CODE"] ?? '',
            ':TRD_SH_NAME' => $result_sqlsvr["TRD_SH_NAME"] ?? '',
            ':BRN_CODE' => $result_sqlsvr["BRN_CODE"] ?? '',
            ':BRN_NAME' => $result_sqlsvr["BRN_NAME"] ?? '',
            ':TRD_QTY' => $TRD_QTY,
            ':TRD_Q_FREE' => $TRD_Q_FREE,
            ':TRD_UTQNAME' => $result_sqlsvr["TRD_UTQNAME"] ?? '',
            ':TRD_UTQQTY' => $result_sqlsvr["TRD_UTQQTY"] ?? 0,
            ':TRD_K_U_PRC' => $result_sqlsvr["TRD_K_U_PRC"] ?? 0,
            ':TRD_U_PRC' => $result_sqlsvr["TRD_U_PRC"] ?? 0,
            ':TRD_U_VATIO' => $result_sqlsvr["TRD_U_VATIO"] ?? 0,
            ':TRD_B_UPRC' => $result_sqlsvr["TRD_B_UPRC"] ?? 0,
            ':TRD_DSC_KEYIN' => $result_sqlsvr["TRD_DSC_KEYIN"] ?? 0,
            ':TRD_DSC_KEYINV' => $result_sqlsvr["TRD_DSC_KEYINV"] ?? 0,
            ':TRD_G_AMT' => $result_sqlsvr["TRD_G_AMT"] ?? 0,
            ':TRD_G_KEYIN' => $TRD_G_KEYIN,
            ':TRD_G_SELL' => $result_sqlsvr["TRD_G_SELL"] ?? 0,
            ':TRD_G_VAT' => $result_sqlsvr["TRD_G_VAT"] ?? 0,
            ':TRD_TDSC_KEYINV' => $TRD_TDSC_KEYINV,
            ':TRD_B_SELL' => $TRD_B_SELL,
            ':TRD_B_VAT' => $TRD_B_VAT,
            ':TRD_B_AMT' => $TRD_B_AMT,
            ':WL_CODE' => $result_sqlsvr["WL_CODE"] ?? '',
            ':WH_CODE' => $result_sqlsvr["WH_CODE"] ?? '',
            ':ARCD_NAME' => $result_sqlsvr["ARCD_NAME"] ?? '',
            ':BRANCH' => $branch,
            ':PGROUP' => $p_group
        ];

        if ($is_update) {
            $stmt_update->execute($params);
            $update_count++;
        } else {
            $stmt_insert->execute($params);
            $existing_keys[$key] = true;
            $insert_count++;
        }

        if (($update_count + $insert_count) % 1000 === 0) {
            $conn->commit();
            $conn->beginTransaction();
        }
    }
    if ($conn->inTransaction()) {
        $conn->commit();
    }
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n\r";
}


$end_microtime = microtime(true);
$end_datetime = date("Y-m-d H:i:s");
$execution_time = round($end_microtime - $start_microtime, 2);

echo "\n\r========================================================\n\r";
echo "Import Summary Results:\n\r";
echo "Updated Records: " . number_format($update_count) . "\n\r";
echo "Inserted Records: " . number_format($insert_count) . "\n\r";
echo "Total Processed: " . number_format($update_count + $insert_count) . "\n\r";
echo "Start Time:     " . $start_datetime . "\n\r";
echo "End Time:       " . $end_datetime . "\n\r";
echo "Execution Time: " . $execution_time . " seconds\n\r";
echo "========================================================\n\r";

$conn_sqlsvr = null;
