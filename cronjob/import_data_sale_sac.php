<?php

ini_set('display_errors', 1);
error_reporting(~0);

include("../config/connect_sqlserver.php");
include("../config/connect_db.php");

include('../cond_file/doc_info_sale_daily_cp.php');
include('../util/month_util.php');


$DT_DOCCODE_MINUS = "IS";

$str_doc1 = array("DS02", "IS01", "IS02", "IV01");
$str_doc2 = array("2");
$str_doc3 = array("IV3");

$str_group1 = array("101-AP01","101-AT01","101-BKT01","101-BS01","101-BS02","101-BS03","101-BS04","101-BS05","101-BS06","101-DS01","101-DS02","101-DS03","101-FS01","101-FS02","101-FS03","101-FS04","101-LLIT01","101-ML01","101-O01","101-RS01"
,"101-SIM01","101-T%01","101-TR01","101-VR01","102-CAV01","102-CBG01","102-CBS01","102-CDS01","102-CKL01","102-CKS01","102-CML01","102-CMT01","102-CO01","102-T%01","103-LBS01","103-LDS01","103-LKS01","103-LML01","103-LMT01"
,"103-LO01","103-T%01","201-AP01","201-AT01","201-BF01","201-BS01","201-CT01","201-DL01","201-DS01","201-DT01","201-FK01","201-FS01","201-FT01","201-GY01","201-HK01","201-KENDA","201-LEAO01","201-LLIT01","201-ML01",
    "201-MS01","201-MX01","201-NT01","201-O01","201-SIM01","201-T%01","201-T01","201-TOYO01","201-VR01","201-YK01");
$str_group2 = array("402-J02","401-J01","401-KV01","401-WIL01","401-WIL02","401-WIL-03","601-1","601-2","602-1","602-2","602-3-ALL01","603-1","610-1","610-2","610-3","9-9402-ALL02");
$str_group3 = array("999-01","999-02","999-03","999-04","999-05","999-06","999-07","999-08","999-09","999-10","999-11","999-12","999-13","999-14","999-15","999-16","999-17","999-18","999-19","999-20","999-21","999-22","999-23","SY02-00140");
$str_group4 = array("999-26","999-28","A502-ALL03");

echo "Today is " . date("Y/m/d");
echo "\n\r" . date("Y/m/d", strtotime("yesterday"));


$query_daily_cond_ext = " AND (DOCTYPE.DT_DOCCODE in ('2','DS02','IS01','IS02','IV01','IV3')) ";

$query_year = " AND DI_DATE BETWEEN '" . date("Y/m/d", strtotime("yesterday")) . "' AND '" . date("Y/m/d") . "'";
//$query_year = " AND DI_DATE BETWEEN '2018/01/01' AND '2023/12/31'";
//$query_year = " AND DI_DATE BETWEEN '2022/05/15' AND '" . date("Y/m/d") . "'";

//$query_year = " AND DI_DATE BETWEEN '2022/08/21' AND '" . date("Y/m/d") . "'";

$sql_sqlsvr = $select_query_daily . $select_query_daily_cond . $query_daily_cond_ext . $query_year . $select_query_daily_order;

//$myfile = fopen("qry_file_mssql_server.txt", "w") or die("Unable to open file!");
//fwrite($myfile, $sql_sqlsvr);
//fclose($myfile);


/*
 select * from ims_product_sale_sac
    order by
        STR_TO_DATE(DI_DATE, '%m/%d/%Y') desc
 */

$insert_data = "";
$update_data = "";

$res = "";

$stmt_sqlsvr = $conn_sqlsvr->prepare($sql_sqlsvr);
$stmt_sqlsvr->execute();

$return_arr = array();

while ($result_sqlsvr = $stmt_sqlsvr->fetch(PDO::FETCH_ASSOC)) {

    $ICCAT_CODE = "";

    $DT_DOCCODE = $result_sqlsvr["DT_DOCCODE"];
    $ICCAT_CODE = $result_sqlsvr["ICCAT_CODE"];

    $branch = "";

    if (in_array($DT_DOCCODE, $str_doc1)) {
        $branch = "SAC";
    }

    if (in_array($DT_DOCCODE, $str_doc2)) {
        $branch = "RQ";
    }

    if (in_array($DT_DOCCODE, $str_doc3)) {
        $branch = "BTC";
    }

    if(strpos($result_sqlsvr['DT_DOCCODE'], $DT_DOCCODE_MINUS) !== false){
        $TRD_QTY = (double)$result_sqlsvr["TRD_QTY"]>0 ? "-" . $result_sqlsvr["TRD_QTY"] : $result_sqlsvr["TRD_QTY"];
        $TRD_U_PRC = (double)$result_sqlsvr["TRD_U_PRC"]>0 ? "-" . $result_sqlsvr["TRD_U_PRC"] : $result_sqlsvr["TRD_U_PRC"];
        $TRD_DSC_KEYINV = (double)$result_sqlsvr["TRD_DSC_KEYINV"]>0 ? "-" . $result_sqlsvr["TRD_DSC_KEYINV"] : $result_sqlsvr["TRD_DSC_KEYINV"];
        $TRD_B_SELL = (double)$result_sqlsvr["TRD_B_SELL"]>0 ? "-" . $result_sqlsvr["TRD_B_SELL"] : $result_sqlsvr["TRD_B_SELL"];
        $TRD_B_VAT = (double)$result_sqlsvr["TRD_B_VAT"]>0 ? "-" . $result_sqlsvr["TRD_B_VAT"] : $result_sqlsvr["TRD_B_VAT"];
        $TRD_G_KEYIN = (double)$result_sqlsvr["TRD_G_KEYIN"]>0 ? "-" . $result_sqlsvr["TRD_G_KEYIN"] : $result_sqlsvr["TRD_G_KEYIN"];
/*
        $TRD_U_PRC = "-" . $result_sqlsvr["TRD_U_PRC"];
        $TRD_DSC_KEYINV = "-" . $result_sqlsvr["TRD_DSC_KEYINV"];
        $TRD_B_SELL = "-" . $result_sqlsvr["TRD_B_SELL"];
        $TRD_B_VAT = "-" . $result_sqlsvr["TRD_B_VAT"];
        $TRD_G_KEYIN = "-" . $result_sqlsvr["TRD_G_KEYIN"];
*/

    } else {
        $TRD_QTY =  $result_sqlsvr["TRD_QTY"];
        $TRD_U_PRC =  $result_sqlsvr["TRD_U_PRC"];
        $TRD_DSC_KEYINV =  $result_sqlsvr["TRD_DSC_KEYINV"];
        $TRD_B_SELL =  $result_sqlsvr["TRD_B_SELL"];
        $TRD_B_VAT =  $result_sqlsvr["TRD_B_VAT"];
        $TRD_G_KEYIN =  $result_sqlsvr["TRD_G_KEYIN"];
    }

    echo "[ " . $DT_DOCCODE . " | " . $branch . " ]" . "\n\r";
    echo "[ " . $TRD_QTY . " | " . $TRD_U_PRC . " | " . $TRD_DSC_KEYINV . " | " . $TRD_B_SELL . " | " . $TRD_B_VAT . " | " . $TRD_G_KEYIN . " ]" . "\n\r";

    $res = $res . $result_sqlsvr["DI_REF"] . "  *** " . $result_sqlsvr["DT_DOCCODE"] . " *** " . "\n\r";


    //$myfile = fopen("sql_get_DATA.txt", "w") or die("Unable to open file!");
    //fwrite($myfile, "[" . $res) ;
    //fclose($myfile);


    $p_group = "";

    if (in_array($ICCAT_CODE, $str_group1)) {
        $p_group = "P1";
    }

    if (in_array($ICCAT_CODE, $str_group2)) {
        $p_group = "P2";
    }

    if (in_array($ICCAT_CODE, $str_group3)) {
        $p_group = "P3";
    }

    if (in_array($ICCAT_CODE, $str_group4)) {
        $p_group = "P4";
    }

    $sql_find = "SELECT * FROM ims_product_sale_sac "
        . " WHERE DI_KEY = '" . $result_sqlsvr["DI_KEY"]
        . "' AND DI_REF = '" . $result_sqlsvr["DI_REF"]
        . "' AND DI_DATE = '" . $result_sqlsvr["DI_DATE"]
        . "' AND DT_DOCCODE = '" . $result_sqlsvr["DT_DOCCODE"]
        . "' AND TRD_SEQ = '" . $result_sqlsvr["TRD_SEQ"] . "'";

    //echo $sql_find . "\n\r";

    $nRows = $conn->query($sql_find)->fetchColumn();
    if ($nRows > 0) {

        $sql_update = " UPDATE ims_product_sale_sac  SET AR_CODE=:AR_CODE,AR_NAME=:AR_NAME,SLMN_SLT=:SLMN_SLT,SLMN_CODE=:SLMN_CODE,SLMN_NAME=:SLMN_NAME
,SKU_CODE=:SKU_CODE,SKU_NAME=:SKU_NAME,SKU_CAT=:SKU_CAT,ICCAT_CODE=:ICCAT_CODE,ICCAT_NAME=:ICCAT_NAME,TRD_QTY=:TRD_QTY,TRD_U_PRC=:TRD_U_PRC
,TRD_DSC_KEYINV=:TRD_DSC_KEYINV,TRD_B_SELL=:TRD_B_SELL
,TRD_B_VAT=:TRD_B_VAT,TRD_G_KEYIN=:TRD_G_KEYIN,WL_CODE=:WL_CODE,BRANCH=:BRANCH,BRN_CODE=:BRN_CODE
,BRN_NAME=:BRN_NAME,DI_TIME_CHK=:DI_TIME_CHK,PGROUP=:PGROUP  
        WHERE DI_KEY = :DI_KEY         
        AND DI_REF  = :DI_REF
        AND DI_DATE = :DI_DATE
        AND DT_DOCCODE = :DT_DOCCODE
        AND TRD_SEQ = :TRD_SEQ ";

        $query = $conn->prepare($sql_update);
        $query->bindParam(':AR_CODE', $result_sqlsvr["AR_CODE"], PDO::PARAM_STR);
        $query->bindParam(':AR_NAME', $result_sqlsvr["AR_NAME"], PDO::PARAM_STR);
        $query->bindParam(':SLMN_SLT', $result_sqlsvr["SLMN_SLT"], PDO::PARAM_STR);
        $query->bindParam(':SLMN_CODE', $result_sqlsvr["SLMN_CODE"], PDO::PARAM_STR);
        $query->bindParam(':SLMN_NAME', $result_sqlsvr["SLMN_NAME"], PDO::PARAM_STR);
        $query->bindParam(':SKU_CODE', $result_sqlsvr["SKU_CODE"], PDO::PARAM_STR);
        $query->bindParam(':SKU_NAME', $result_sqlsvr["SKU_NAME"], PDO::PARAM_STR);
        $query->bindParam(':SKU_CAT', $result_sqlsvr["ICCAT_CODE"], PDO::PARAM_STR);
        $query->bindParam(':ICCAT_CODE', $result_sqlsvr["ICCAT_CODE"], PDO::PARAM_STR);
        $query->bindParam(':ICCAT_NAME', $result_sqlsvr["ICCAT_NAME"], PDO::PARAM_STR);
        $query->bindParam(':TRD_QTY', $TRD_QTY, PDO::PARAM_STR);
        $query->bindParam(':TRD_U_PRC', $TRD_U_PRC, PDO::PARAM_STR);
        $query->bindParam(':TRD_DSC_KEYINV', $TRD_DSC_KEYINV, PDO::PARAM_STR);
        $query->bindParam(':TRD_B_SELL', $TRD_B_SELL, PDO::PARAM_STR);
        $query->bindParam(':TRD_B_VAT', $TRD_B_VAT, PDO::PARAM_STR);
        $query->bindParam(':TRD_G_KEYIN', $TRD_G_KEYIN, PDO::PARAM_STR);
        $query->bindParam(':WL_CODE', $result_sqlsvr["WL_CODE"], PDO::PARAM_STR);

        $query->bindParam(':BRANCH', $branch, PDO::PARAM_STR);
        $query->bindParam(':BRN_CODE', $result_sqlsvr["BRN_CODE"], PDO::PARAM_STR);
        $query->bindParam(':BRN_NAME', $result_sqlsvr["BRN_NAME"], PDO::PARAM_STR);
        $query->bindParam(':DI_TIME_CHK', $result_sqlsvr["DI_TIME_CHK"], PDO::PARAM_STR);
        $query->bindParam(':PGROUP', $p_group, PDO::PARAM_STR);

        $query->bindParam(':DI_KEY', $result_sqlsvr["DI_KEY"], PDO::PARAM_STR);
        $query->bindParam(':DI_REF', $result_sqlsvr["DI_REF"], PDO::PARAM_STR);
        $query->bindParam(':DI_DATE', $result_sqlsvr["DI_DATE"], PDO::PARAM_STR);
        $query->bindParam(':DT_DOCCODE', $result_sqlsvr["DT_DOCCODE"], PDO::PARAM_STR);
        $query->bindParam(':TRD_SEQ', $result_sqlsvr["TRD_SEQ"], PDO::PARAM_STR);

        $query->execute();

        $update_data .= $result_sqlsvr["DI_DATE"] . ":" . $result_sqlsvr["DI_REF"] . " |- " . $result_sqlsvr["ICCAT_CODE"] . "\n\r";

        echo " UPDATE DATA " . $update_data;

        //$myfile = fopen("update_chk.txt", "w") or die("Unable to open file!");
        //fwrite($myfile, $update_data);
        //fclose($myfile);

    } else {

        $sql = " INSERT INTO ims_product_sale_sac (DI_KEY,DI_REF,DI_DATE,DI_MONTH,DI_MONTH_NAME,DI_YEAR
        ,AR_CODE,AR_NAME,SLMN_SLT,SLMN_CODE,SLMN_NAME,SKU_CODE,SKU_NAME,SKU_CAT,ICCAT_CODE,ICCAT_NAME,TRD_QTY,TRD_U_PRC
        ,TRD_DSC_KEYINV,TRD_B_SELL,TRD_B_VAT,TRD_G_KEYIN,WL_CODE,BRANCH,DT_DOCCODE,TRD_SEQ,BRN_CODE,BRN_NAME,DI_TIME_CHK,PGROUP)
        VALUES (:DI_KEY,:DI_REF,:DI_DATE,:DI_MONTH,:DI_MONTH_NAME,:DI_YEAR,:AR_CODE,:AR_NAME,:SLMN_SLT,:SLMN_CODE,:SLMN_NAME,:SKU_CODE,:SKU_NAME,:SKU_CAT
        ,:ICCAT_CODE,:ICCAT_NAME,:TRD_QTY,:TRD_U_PRC,:TRD_DSC_KEYINV,:TRD_B_SELL,:TRD_B_VAT,:TRD_G_KEYIN
        ,:WL_CODE,:BRANCH,:DT_DOCCODE,:TRD_SEQ,:BRN_CODE,:BRN_NAME,:DI_TIME_CHK,:PGROUP) ";
        $query = $conn->prepare($sql);
        $query->bindParam(':DI_KEY', $result_sqlsvr["DI_KEY"], PDO::PARAM_STR);
        $query->bindParam(':DI_REF', $result_sqlsvr["DI_REF"], PDO::PARAM_STR);
        $query->bindParam(':DI_DATE', $result_sqlsvr["DI_DATE"], PDO::PARAM_STR);
        $query->bindParam(':DI_MONTH', $result_sqlsvr["DI_MONTH"], PDO::PARAM_STR);
        $query->bindParam(':DI_MONTH_NAME', $month_arr[$result_sqlsvr["DI_MONTH"]], PDO::PARAM_STR);
        $query->bindParam(':DI_YEAR', $result_sqlsvr["DI_YEAR"], PDO::PARAM_STR);
        $query->bindParam(':AR_CODE', $result_sqlsvr["AR_CODE"], PDO::PARAM_STR);
        $query->bindParam(':AR_NAME', $result_sqlsvr["AR_NAME"], PDO::PARAM_STR);
        $query->bindParam(':SLMN_SLT', $result_sqlsvr["SLMN_SLT"], PDO::PARAM_STR);
        $query->bindParam(':SLMN_CODE', $result_sqlsvr["SLMN_CODE"], PDO::PARAM_STR);
        $query->bindParam(':SLMN_NAME', $result_sqlsvr["SLMN_NAME"], PDO::PARAM_STR);
        $query->bindParam(':SKU_CODE', $result_sqlsvr["SKU_CODE"], PDO::PARAM_STR);
        $query->bindParam(':SKU_NAME', $result_sqlsvr["SKU_NAME"], PDO::PARAM_STR);
        $query->bindParam(':SKU_CAT', $result_sqlsvr["ICCAT_CODE"], PDO::PARAM_STR);
        $query->bindParam(':ICCAT_CODE', $result_sqlsvr["ICCAT_CODE"], PDO::PARAM_STR);
        $query->bindParam(':ICCAT_NAME', $result_sqlsvr["ICCAT_NAME"], PDO::PARAM_STR);
        $query->bindParam(':TRD_QTY', $TRD_QTY, PDO::PARAM_STR);
        $query->bindParam(':TRD_U_PRC', $TRD_U_PRC, PDO::PARAM_STR);
        $query->bindParam(':TRD_DSC_KEYINV', $TRD_DSC_KEYINV, PDO::PARAM_STR);
        $query->bindParam(':TRD_B_SELL', $TRD_B_SELL, PDO::PARAM_STR);
        $query->bindParam(':TRD_B_VAT', $TRD_B_VAT, PDO::PARAM_STR);
        $query->bindParam(':TRD_G_KEYIN', $TRD_G_KEYIN, PDO::PARAM_STR);
        $query->bindParam(':WL_CODE', $result_sqlsvr["WL_CODE"], PDO::PARAM_STR);

        $query->bindParam(':BRANCH', $branch, PDO::PARAM_STR);

        $query->bindParam(':DT_DOCCODE', $DT_DOCCODE, PDO::PARAM_STR);

        $query->bindParam(':TRD_SEQ', $result_sqlsvr["TRD_SEQ"], PDO::PARAM_STR);

        $query->bindParam(':BRN_CODE', $result_sqlsvr["BRN_CODE"], PDO::PARAM_STR);

        $query->bindParam(':BRN_NAME', $result_sqlsvr["BRN_NAME"], PDO::PARAM_STR);

        $query->bindParam(':DI_TIME_CHK', $result_sqlsvr["DI_TIME_CHK"], PDO::PARAM_STR);

        $query->bindParam(':PGROUP', $p_group, PDO::PARAM_STR);

        $query->execute();

        $lastInsertId = $conn->lastInsertId();

        if ($lastInsertId) {
            $insert_data .= $result_sqlsvr["DI_DATE"] . ":" . $result_sqlsvr["DI_REF"] . " | ";
            echo " Save OK " . $insert_data;
        } else {
            echo " Error ";
        }

    }

}

$conn_sqlsvr = null;

