<?php

ini_set('display_errors', 1);
error_reporting(~0);

include("../config/connect_sqlserver.php");
include("../config/connect_db.php");

include('../cond_file/doc_info_sale_daily_cp.php');
include('../util/month_util.php');

$str_doc1 = array_flip(array("30", "CS4", "CS5", "DS4", "IS3", "IS4", "ISC3", "ISC4"));
$str_doc2 = array_flip(array("CS.8", "CS.9", "IC.3", "IC.4", "IS.3", "IS.4", "S.5", "S.6"));
$str_doc3 = array_flip(array("CS.6", "CS.7", "IC.1", "IC.2", "IS.1", "IS.2", "S.1", "S.2"));
$str_doc4 = array_flip(array("CS.2", "CS.3", "IC.5", "IC.6", "IS.5", "IS.6", "S.3", "S.4"));

$str_group1 = array_flip(array("101-AP01","101-AT01","101-BKT01","101-BS01","101-BS02","101-BS03","101-BS04","101-BS05","101-BS06","101-DS01","101-DS02","101-DS03","101-FS01","101-FS02","101-FS03","101-FS04","101-LLIT01","101-ML01","101-O01","101-RS01"
,"101-SIM01","101-T%01","101-TR01","101-VR01","102-CAV01","102-CBG01","102-CBS01","102-CDS01","102-CKL01","102-CKS01","102-CML01","102-CMT01","102-CO01","102-T%01","103-LBS01","103-LDS01","103-LKS01","103-LML01","103-LMT01"
,"103-LO01","103-T%01","201-AP01","201-AT01","201-BF01","201-BS01","201-CT01","201-DL01","201-DS01","201-DT01","201-FK01","201-FS01","201-FT01","201-GY01","201-HK01","201-KENDA","201-LEAO01","201-LLIT01","201-ML01",
    "201-MS01","201-MX01","201-NT01","201-O01","201-SIM01","201-T%01","201-T01","201-TOYO01","201-VR01","201-YK01"));
$str_group2 = array_flip(array("402-J02","401-J01","401-KV01","401-WIL01","401-WIL02","401-WIL-03","601-1","601-2","602-1","602-2","602-3-ALL01","603-1","610-1","610-2","610-3","9-9402-ALL02"));
$str_group3 = array_flip(array("999-01","999-02","999-03","999-04","999-05","999-06","999-07","999-08","999-09","999-10","999-11","999-12","999-13","999-14","999-15","999-16","999-17","999-18","999-19","999-20","999-21","999-22","999-23","SY02-00140"));
$str_group4 = array_flip(array("999-26","999-28","A502-ALL03"));

echo "Today is " . date("Y/m/d") . "\n\r";
echo date("Y/m/d", strtotime("yesterday")) . "\n\r";

$query_daily_cond_ext = " AND (DOCTYPE.DT_DOCCODE in ('30','CS4','CS5','DS4','IS3','IS4','ISC3','ISC4','CS.8','CS.9','IC.3','IC.4','IS.3','IS.4','S.5','S.6','CS.6','CS.7','IC.1','IC.2','IS.1','IS.2','S.1','S.2','CS.2','CS.3','IC.5','IC.6','IS.5','IS.6','S.3','S.4')) ";
$query_year = " AND DI_DATE BETWEEN '" . date("Y/m/d", strtotime("yesterday")) . "' AND '" . date("Y/m/d") . "'";

$sql_sqlsvr = $select_query_daily . $select_query_daily_cond . $query_daily_cond_ext . $query_year . $select_query_daily_order;

echo $sql_sqlsvr . "\n\r";

$stmt_sqlsvr = $conn_sqlsvr->prepare($sql_sqlsvr);
$stmt_sqlsvr->execute();

$sql_find = "SELECT COUNT(*) FROM ims_product_sale_cockpit 
             WHERE DI_KEY = :DI_KEY AND DI_REF = :DI_REF AND DI_DATE = :DI_DATE AND DT_DOCCODE = :DT_DOCCODE AND TRD_SEQ = :TRD_SEQ";
$stmt_find = $conn->prepare($sql_find);

$sql_update = "UPDATE ims_product_sale_cockpit SET AR_CODE=:AR_CODE,AR_NAME=:AR_NAME,SLMN_CODE=:SLMN_CODE,SLMN_NAME=:SLMN_NAME
,SKU_CODE=:SKU_CODE,SKU_NAME=:SKU_NAME,SKU_CAT=:SKU_CAT,ICCAT_CODE=:ICCAT_CODE,ICCAT_NAME=:ICCAT_NAME,TRD_QTY=:TRD_QTY,TRD_U_PRC=:TRD_U_PRC
,TRD_DSC_KEYINV=:TRD_DSC_KEYINV,TRD_B_SELL=:TRD_B_SELL
,TRD_B_VAT=:TRD_B_VAT,TRD_G_KEYIN=:TRD_G_KEYIN,WL_CODE=:WL_CODE,BRANCH=:BRANCH,BRN_CODE=:BRN_CODE
,BRN_NAME=:BRN_NAME,DI_TIME_CHK=:DI_TIME_CHK,PGROUP=:PGROUP  
WHERE DI_KEY = :DI_KEY AND DI_REF = :DI_REF AND DI_DATE = :DI_DATE AND DT_DOCCODE = :DT_DOCCODE AND TRD_SEQ = :TRD_SEQ";
$stmt_update = $conn->prepare($sql_update);

$sql_insert = "INSERT INTO ims_product_sale_cockpit (DI_KEY,DI_REF,DI_DATE,DI_MONTH,DI_MONTH_NAME,DI_YEAR
,AR_CODE,AR_NAME,SLMN_CODE,SLMN_NAME,SKU_CODE,SKU_NAME,SKU_CAT,ICCAT_CODE,ICCAT_NAME,TRD_QTY,TRD_U_PRC
,TRD_DSC_KEYINV,TRD_B_SELL,TRD_B_VAT,TRD_G_KEYIN,WL_CODE,BRANCH,DT_DOCCODE,TRD_SEQ,BRN_CODE,BRN_NAME,DI_TIME_CHK,PGROUP)
VALUES (:DI_KEY,:DI_REF,:DI_DATE,:DI_MONTH,:DI_MONTH_NAME,:DI_YEAR,:AR_CODE,:AR_NAME,:SLMN_CODE,:SLMN_NAME,:SKU_CODE,:SKU_NAME,:SKU_CAT
,:ICCAT_CODE,:ICCAT_NAME,:TRD_QTY,:TRD_U_PRC,:TRD_DSC_KEYINV,:TRD_B_SELL,:TRD_B_VAT,:TRD_G_KEYIN
,:WL_CODE,:BRANCH,:DT_DOCCODE,:TRD_SEQ,:BRN_CODE,:BRN_NAME,:DI_TIME_CHK,:PGROUP)";
$stmt_insert = $conn->prepare($sql_insert);

$update_count = 0;
$insert_count = 0;

$conn->beginTransaction();
try {
    while ($result_sqlsvr = $stmt_sqlsvr->fetch(PDO::FETCH_ASSOC)) {
        $DT_DOCCODE = $result_sqlsvr["DT_DOCCODE"];
        $ICCAT_CODE = $result_sqlsvr["ICCAT_CODE"];

        $branch = "";
        if (isset($str_doc1[$DT_DOCCODE])) {
            $branch = "CP-340";
        } elseif (isset($str_doc2[$DT_DOCCODE])) {
            $branch = "CP-BY";
        } elseif (isset($str_doc3[$DT_DOCCODE])) {
            $branch = "CP-RP";
        } elseif (isset($str_doc4[$DT_DOCCODE])) {
            $branch = "CP-BB";
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

        $stmt_find->execute([
            ':DI_KEY' => $result_sqlsvr["DI_KEY"],
            ':DI_REF' => $result_sqlsvr["DI_REF"],
            ':DI_DATE' => $result_sqlsvr["DI_DATE"],
            ':DT_DOCCODE' => $result_sqlsvr["DT_DOCCODE"],
            ':TRD_SEQ' => $result_sqlsvr["TRD_SEQ"]
        ]);
        $nRows = $stmt_find->fetchColumn();

        if ($nRows > 0) {
            $stmt_update->execute([
                ':AR_CODE' => $result_sqlsvr["AR_CODE"],
                ':AR_NAME' => $result_sqlsvr["AR_NAME"],
                ':SLMN_CODE' => $result_sqlsvr["SLMN_CODE"],
                ':SLMN_NAME' => $result_sqlsvr["SLMN_NAME"],
                ':SKU_CODE' => $result_sqlsvr["SKU_CODE"],
                ':SKU_NAME' => $result_sqlsvr["SKU_NAME"],
                ':SKU_CAT' => $result_sqlsvr["ICCAT_CODE"],
                ':ICCAT_CODE' => $result_sqlsvr["ICCAT_CODE"],
                ':ICCAT_NAME' => $result_sqlsvr["ICCAT_NAME"],
                ':TRD_QTY' => $result_sqlsvr["TRD_QTY"],
                ':TRD_U_PRC' => $result_sqlsvr["TRD_U_PRC"],
                ':TRD_DSC_KEYINV' => $result_sqlsvr["TRD_DSC_KEYINV"],
                ':TRD_B_SELL' => $result_sqlsvr["TRD_B_SELL"],
                ':TRD_B_VAT' => $result_sqlsvr["TRD_B_VAT"],
                ':TRD_G_KEYIN' => $result_sqlsvr["TRD_G_KEYIN"],
                ':WL_CODE' => $result_sqlsvr["WL_CODE"],
                ':BRANCH' => $branch,
                ':BRN_CODE' => $result_sqlsvr["BRN_CODE"],
                ':BRN_NAME' => $result_sqlsvr["BRN_NAME"],
                ':DI_TIME_CHK' => $result_sqlsvr["DI_TIME_CHK"],
                ':PGROUP' => $p_group,
                ':DI_KEY' => $result_sqlsvr["DI_KEY"],
                ':DI_REF' => $result_sqlsvr["DI_REF"],
                ':DI_DATE' => $result_sqlsvr["DI_DATE"],
                ':DT_DOCCODE' => $result_sqlsvr["DT_DOCCODE"],
                ':TRD_SEQ' => $result_sqlsvr["TRD_SEQ"]
            ]);
            $update_count++;
        } else {
            $month_name = isset($month_arr[$result_sqlsvr["DI_MONTH"]]) ? $month_arr[$result_sqlsvr["DI_MONTH"]] : "";
            $stmt_insert->execute([
                ':DI_KEY' => $result_sqlsvr["DI_KEY"],
                ':DI_REF' => $result_sqlsvr["DI_REF"],
                ':DI_DATE' => $result_sqlsvr["DI_DATE"],
                ':DI_MONTH' => $result_sqlsvr["DI_MONTH"],
                ':DI_MONTH_NAME' => $month_name,
                ':DI_YEAR' => $result_sqlsvr["DI_YEAR"],
                ':AR_CODE' => $result_sqlsvr["AR_CODE"],
                ':AR_NAME' => $result_sqlsvr["AR_NAME"],
                ':SLMN_CODE' => $result_sqlsvr["SLMN_CODE"],
                ':SLMN_NAME' => $result_sqlsvr["SLMN_NAME"],
                ':SKU_CODE' => $result_sqlsvr["SKU_CODE"],
                ':SKU_NAME' => $result_sqlsvr["SKU_NAME"],
                ':SKU_CAT' => $result_sqlsvr["ICCAT_CODE"],
                ':ICCAT_CODE' => $result_sqlsvr["ICCAT_CODE"],
                ':ICCAT_NAME' => $result_sqlsvr["ICCAT_NAME"],
                ':TRD_QTY' => $result_sqlsvr["TRD_QTY"],
                ':TRD_U_PRC' => $result_sqlsvr["TRD_U_PRC"],
                ':TRD_DSC_KEYINV' => $result_sqlsvr["TRD_DSC_KEYINV"],
                ':TRD_B_SELL' => $result_sqlsvr["TRD_B_SELL"],
                ':TRD_B_VAT' => $result_sqlsvr["TRD_B_VAT"],
                ':TRD_G_KEYIN' => $result_sqlsvr["TRD_G_KEYIN"],
                ':WL_CODE' => $result_sqlsvr["WL_CODE"],
                ':BRANCH' => $branch,
                ':DT_DOCCODE' => $DT_DOCCODE,
                ':TRD_SEQ' => $result_sqlsvr["TRD_SEQ"],
                ':BRN_CODE' => $result_sqlsvr["BRN_CODE"],
                ':BRN_NAME' => $result_sqlsvr["BRN_NAME"],
                ':DI_TIME_CHK' => $result_sqlsvr["DI_TIME_CHK"],
                ':PGROUP' => $p_group
            ]);
            $insert_count++;
        }
    }
    $conn->commit();
    echo "Import completed. Updated: $update_count, Inserted: $insert_count\n\r";
} catch (Exception $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}

$conn_sqlsvr = null;

