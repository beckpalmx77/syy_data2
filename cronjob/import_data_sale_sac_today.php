<?php

ini_set('display_errors', 1);
error_reporting(~0);

include("../config/connect_sqlserver.php");
include("../config/connect_db.php");

include('../cond_file/doc_info_sale_daily_cp.php');
include('../util/month_util.php');


$DT_DOCCODE_MINUS = "IS";

$str_doc1 = array_flip(array("DS02", "IS01", "IS02", "IV01"));
$str_doc2 = array_flip(array("2"));
$str_doc3 = array_flip(array("IV3"));

$str_group1 = array_flip(array("1SAC01","1SAC02","1SAC03","1SAC04","1SAC05","1SAC06","1SAC07","1SAC08","1SAC09","1SAC10","1SAC11","1SAC12","1SAC13","1SAC14","2SAC01","2SAC02","2SAC03","2SAC04","2SAC05","2SAC06","2SAC07","2SAC08","2SAC09","2SAC10","2SAC11","2SAC12","2SAC13","2SAC14","2SAC15","3SAC01","3SAC02","3SAC03","3SAC04","3SAC05","3SAC06","4SAC01","4SAC02","4SAC03","4SAC04","4SAC05","4SAC06"));
$str_group2 = array_flip(array("5SAC01","5SAC02","6SAC08","8CPA01-001","8CPA01-002","8SAC09","8BTCA01-002","8BTCA01-001"));
$str_group3 = array_flip(array("TATA-004","999-08","999-07","999-14"));
$str_group4 = array_flip(array("SAC08","TATA-003","10SAC12"));

echo "Today is " . date("Y/m/d") . "\n\r";
echo date("Y/m/d", strtotime("yesterday")) . "\n\r";

$query_daily_cond_ext = " AND (DOCTYPE.DT_DOCCODE in ('2','DS02','IS01','IS02','IV01','IV3')) ";
$query_year = " AND DI_DATE BETWEEN '" . date("Y/m/d") . "' AND '" . date("Y/m/d") . "'";

$sql_sqlsvr = $select_query_daily . $select_query_daily_cond . $query_daily_cond_ext . $query_year . $select_query_daily_order;

$stmt_sqlsvr = $conn_sqlsvr->prepare($sql_sqlsvr);
$stmt_sqlsvr->execute();

$sql_find = "SELECT COUNT(*) FROM ims_product_sale_sac 
             WHERE DI_KEY = :DI_KEY AND DI_REF = :DI_REF AND DI_DATE = :DI_DATE AND DT_DOCCODE = :DT_DOCCODE AND TRD_SEQ = :TRD_SEQ";
$stmt_find = $conn->prepare($sql_find);

$sql_update = "UPDATE ims_product_sale_sac SET AR_CODE=:AR_CODE,AR_NAME=:AR_NAME,SLMN_SLT=:SLMN_SLT,SLMN_CODE=:SLMN_CODE,SLMN_NAME=:SLMN_NAME
,SKU_CODE=:SKU_CODE,SKU_NAME=:SKU_NAME,SKU_CAT=:SKU_CAT,ICCAT_CODE=:ICCAT_CODE,ICCAT_NAME=:ICCAT_NAME,TRD_QTY=:TRD_QTY,TRD_U_PRC=:TRD_U_PRC
,TRD_DSC_KEYINV=:TRD_DSC_KEYINV,TRD_B_SELL=:TRD_B_SELL
,TRD_B_VAT=:TRD_B_VAT,TRD_G_KEYIN=:TRD_G_KEYIN,WL_CODE=:WL_CODE,BRANCH=:BRANCH,BRN_CODE=:BRN_CODE
,BRN_NAME=:BRN_NAME,DI_TIME_CHK=:DI_TIME_CHK,PGROUP=:PGROUP,DI_ACTIVE=:DI_ACTIVE  
WHERE DI_KEY = :DI_KEY AND DI_REF = :DI_REF AND DI_DATE = :DI_DATE AND DT_DOCCODE = :DT_DOCCODE AND TRD_SEQ = :TRD_SEQ";
$stmt_update = $conn->prepare($sql_update);

$sql_insert = "INSERT INTO ims_product_sale_sac (DI_KEY,DI_REF,DI_DATE,DI_MONTH,DI_MONTH_NAME,DI_YEAR
,AR_CODE,AR_NAME,SLMN_SLT,SLMN_CODE,SLMN_NAME,SKU_CODE,SKU_NAME,SKU_CAT,ICCAT_CODE,ICCAT_NAME,TRD_QTY,TRD_U_PRC
,TRD_DSC_KEYINV,TRD_B_SELL,TRD_B_VAT,TRD_G_KEYIN,WL_CODE,BRANCH,DT_DOCCODE,TRD_SEQ,BRN_CODE,BRN_NAME,DI_TIME_CHK,PGROUP,DI_ACTIVE)
VALUES (:DI_KEY,:DI_REF,:DI_DATE,:DI_MONTH,:DI_MONTH_NAME,:DI_YEAR,:AR_CODE,:AR_NAME,:SLMN_SLT,:SLMN_CODE,:SLMN_NAME,:SKU_CODE,:SKU_NAME,:SKU_CAT
,:ICCAT_CODE,:ICCAT_NAME,:TRD_QTY,:TRD_U_PRC,:TRD_DSC_KEYINV,:TRD_B_SELL,:TRD_B_VAT,:TRD_G_KEYIN
,:WL_CODE,:BRANCH,:DT_DOCCODE,:TRD_SEQ,:BRN_CODE,:BRN_NAME,:DI_TIME_CHK,:PGROUP,:DI_ACTIVE)";
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
            $branch = "SAC";
        } elseif (isset($str_doc2[$DT_DOCCODE])) {
            $branch = "RQ";
        } elseif (isset($str_doc3[$DT_DOCCODE])) {
            $branch = "BTC";
        }

        if (strpos($result_sqlsvr['DT_DOCCODE'], $DT_DOCCODE_MINUS) !== false) {
            $TRD_QTY = (double)$result_sqlsvr["TRD_QTY"] > 0 ? "-" . $result_sqlsvr["TRD_QTY"] : $result_sqlsvr["TRD_QTY"];
            $TRD_U_PRC = (double)$result_sqlsvr["TRD_U_PRC"] > 0 ? "-" . $result_sqlsvr["TRD_U_PRC"] : $result_sqlsvr["TRD_U_PRC"];
            $TRD_DSC_KEYINV = (double)$result_sqlsvr["TRD_DSC_KEYINV"] > 0 ? "-" . $result_sqlsvr["TRD_DSC_KEYINV"] : $result_sqlsvr["TRD_DSC_KEYINV"];
            $TRD_B_SELL = (double)$result_sqlsvr["TRD_B_SELL"] > 0 ? "-" . $result_sqlsvr["TRD_B_SELL"] : $result_sqlsvr["TRD_B_SELL"];
            $TRD_B_VAT = (double)$result_sqlsvr["TRD_B_VAT"] > 0 ? "-" . $result_sqlsvr["TRD_B_VAT"] : $result_sqlsvr["TRD_B_VAT"];
            $TRD_G_KEYIN = (double)$result_sqlsvr["TRD_G_KEYIN"] > 0 ? "-" . $result_sqlsvr["TRD_G_KEYIN"] : $result_sqlsvr["TRD_G_KEYIN"];
        } else {
            $TRD_QTY = $result_sqlsvr["TRD_QTY"];
            $TRD_U_PRC = $result_sqlsvr["TRD_U_PRC"];
            $TRD_DSC_KEYINV = $result_sqlsvr["TRD_DSC_KEYINV"];
            $TRD_B_SELL = $result_sqlsvr["TRD_B_SELL"];
            $TRD_B_VAT = $result_sqlsvr["TRD_B_VAT"];
            $TRD_G_KEYIN = $result_sqlsvr["TRD_G_KEYIN"];
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
                ':SLMN_SLT' => $result_sqlsvr["SLMN_SLT"],
                ':SLMN_CODE' => $result_sqlsvr["SLMN_CODE"],
                ':SLMN_NAME' => $result_sqlsvr["SLMN_NAME"],
                ':SKU_CODE' => $result_sqlsvr["SKU_CODE"],
                ':SKU_NAME' => $result_sqlsvr["SKU_NAME"],
                ':SKU_CAT' => $result_sqlsvr["ICCAT_CODE"],
                ':ICCAT_CODE' => $result_sqlsvr["ICCAT_CODE"],
                ':ICCAT_NAME' => $result_sqlsvr["ICCAT_NAME"],
                ':TRD_QTY' => $TRD_QTY,
                ':TRD_U_PRC' => $TRD_U_PRC,
                ':TRD_DSC_KEYINV' => $TRD_DSC_KEYINV,
                ':TRD_B_SELL' => $TRD_B_SELL,
                ':TRD_B_VAT' => $TRD_B_VAT,
                ':TRD_G_KEYIN' => $TRD_G_KEYIN,
                ':WL_CODE' => $result_sqlsvr["WL_CODE"],
                ':BRANCH' => $branch,
                ':BRN_CODE' => $result_sqlsvr["BRN_CODE"],
                ':BRN_NAME' => $result_sqlsvr["BRN_NAME"],
                ':DI_TIME_CHK' => $result_sqlsvr["DI_TIME_CHK"],
                ':PGROUP' => $p_group,
                ':DI_ACTIVE' => isset($result_sqlsvr["DI_ACTIVE"]) ? $result_sqlsvr["DI_ACTIVE"] : 0,
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
                ':SLMN_SLT' => $result_sqlsvr["SLMN_SLT"],
                ':SLMN_CODE' => $result_sqlsvr["SLMN_CODE"],
                ':SLMN_NAME' => $result_sqlsvr["SLMN_NAME"],
                ':SKU_CODE' => $result_sqlsvr["SKU_CODE"],
                ':SKU_NAME' => $result_sqlsvr["SKU_NAME"],
                ':SKU_CAT' => $result_sqlsvr["ICCAT_CODE"],
                ':ICCAT_CODE' => $result_sqlsvr["ICCAT_CODE"],
                ':ICCAT_NAME' => $result_sqlsvr["ICCAT_NAME"],
                ':TRD_QTY' => $TRD_QTY,
                ':TRD_U_PRC' => $TRD_U_PRC,
                ':TRD_DSC_KEYINV' => $TRD_DSC_KEYINV,
                ':TRD_B_SELL' => $TRD_B_SELL,
                ':TRD_B_VAT' => $TRD_B_VAT,
                ':TRD_G_KEYIN' => $TRD_G_KEYIN,
                ':WL_CODE' => $result_sqlsvr["WL_CODE"],
                ':BRANCH' => $branch,
                ':DT_DOCCODE' => $DT_DOCCODE,
                ':TRD_SEQ' => $result_sqlsvr["TRD_SEQ"],
                ':BRN_CODE' => $result_sqlsvr["BRN_CODE"],
                ':BRN_NAME' => $result_sqlsvr["BRN_NAME"],
                ':DI_TIME_CHK' => $result_sqlsvr["DI_TIME_CHK"],
                ':PGROUP' => $p_group,
                ':DI_ACTIVE' => isset($result_sqlsvr["DI_ACTIVE"]) ? $result_sqlsvr["DI_ACTIVE"] : 0
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

