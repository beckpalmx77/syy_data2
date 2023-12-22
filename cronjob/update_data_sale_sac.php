<?php

ini_set('display_errors', 1);
error_reporting(~0);

include("../config/connect_sqlserver40.php");
include("../config/connect_db.php");

include('../cond_file/doc_info_sale_daily_cp.php');
include('../util/month_util.php');


$DT_DOCCODE_MINUS = "IS";

$str_doc1 = array("DS02", "IS01", "IS02", "IV01");
$str_doc2 = array("2");
$str_doc3 = array("IV3");

$str_group1 = array("1SAC01","1SAC02","1SAC03","1SAC04","1SAC05","1SAC06","1SAC07","1SAC08","1SAC09","1SAC10","1SAC11","1SAC12","1SAC13","1SAC14","2SAC01","2SAC02","2SAC03","2SAC04","2SAC05","2SAC06","2SAC07","2SAC08","2SAC09","2SAC10","2SAC11","2SAC12","2SAC13","2SAC14","2SAC15","3SAC01","3SAC02","3SAC03","3SAC04","3SAC05","3SAC06","4SAC01","4SAC02","4SAC03","4SAC04","4SAC05","4SAC06");
$str_group2 = array("5SAC01","5SAC02","6SAC08","8CPA01-001","8CPA01-002","8SAC09","8BTCA01-002","8BTCA01-001");
$str_group3 = array("TATA-004","999-08","999-07","999-14");
$str_group4 = array("SAC08","TATA-003","10SAC12");

echo "Today is " . date("Y/m/d");
echo "\n\r" . date("Y/m/d", strtotime("yesterday"));


$query_daily_cond_ext = " AND (DOCTYPE.DT_DOCCODE in ('2','DS02','IS01','IS02','IV01','IV3')) ";

//$query_year = " AND DI_KEY >= " . "109007 AND DI_KEY <= 865494 " ;

//$query_year = " AND DI_KEY >= " . "170108 AND DI_KEY <= 865494 " ;

//$query_year = " AND DI_KEY >= " . "389724 AND DI_KEY <= 865494 " ;

$query_year = " AND DI_KEY >= " . "861654 AND DI_KEY <= 861655 " ;


//$query_year = " AND DI_DATE BETWEEN '" . date("Y/m/d", strtotime("yesterday")) . "' AND '" . date("Y/m/d") . "'";
//$query_year = " AND DI_DATE BETWEEN '2018/05/30' AND '2018/05/31'";
//$query_year = " AND DI_DATE BETWEEN '2022/05/22' AND '" . date("Y/m/d") . "'";

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

    //$myfile = fopen("../logs/qry_file_mssql_server_update.txt", "w") or die("Unable to open file!");
    //fwrite($myfile, $sql_sqlsvr . "\n\r" ." - " . $result_sqlsvr["DI_DATE"] . " | " . $result_sqlsvr["DI_KEY"] . ":" . $result_sqlsvr["DI_REF"]);
    //fclose($myfile);

}

$conn_sqlsvr = null;

