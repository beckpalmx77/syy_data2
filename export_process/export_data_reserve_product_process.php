<?php

date_default_timezone_set('Asia/Bangkok');

$filename = "Exp-Reserve-" . date('m/d/Y H:i:s', time()) . ".csv";

@header('Content-type: text/csv; charset=UTF-8');
@header('Content-Encoding: UTF-8');
@header("Content-Disposition: attachment; filename=" . $filename);

include('../config/connect_sqlserver.php');

$doc_date_start = substr($_POST['doc_date_start'], 6, 4) . "/" . substr($_POST['doc_date_start'], 3, 2) . "/" . substr($_POST['doc_date_start'], 0, 2);
$doc_date_to = substr($_POST['doc_date_to'], 6, 4) . "/" . substr($_POST['doc_date_to'], 3, 2) . "/" . substr($_POST['doc_date_to'], 0, 2);



$sql_reserve = " SELECT
 DOCINFO.DI_REF,
 DOCINFO.DI_DATE,
 DOCINFO.DI_ACTIVE,
 ARFILE.AR_CODE,
 ARFILE.AR_NAME,
 AROE.AROE_B_AMT,
 AROE.AROE_B_VAT,
 AROE.AROE_B_SV,
 AROE.AROE_B_SNV,
 AROE.AROE_TDSC_KEYIN,
 AROE.AROE_TDSC_KEYINV,
 AROE.AROE_G_VAT,
 AROE.AROE_G_SV,
 AROE.AROE_G_SNV,
 AROE.AROE_G_KEYIN,
 AROE.AROE_DUE_DA,
 AROE_CRNCYCODE,
 AROE_XCHG,
 SALESMAN.SLMN_CODE,
 SALESMAN.SLMN_NAME,
 TRANSTKH.TRH_REFER_XREF,
 TRANSTKH.TRH_REFER_IREF,
 TRANSTKH.TRH_REFER_PERSON,
 TRANSTKH.TRH_REFER_XTRA1,
 TRANSTKH.TRH_REFER_XTRA2,
 TRANSTKH.TRH_SHIP_DATE,
 TRANSTKH.TRH_VAT_TY,
 TRANSTKH.TRH_VAT_R,
 TRANSTKH.TRH_VATIO,
 TRANSTKH.TRH_N_ITEMS,
 TRANSTKH.TRH_N_QTY,
 DEPTTAB.DEPT_CODE,
 DEPTTAB.DEPT_THAIDESC,
 DEPTTAB.DEPT_ENGDESC,
 PRJTAB.PRJ_CODE,
 PRJTAB.PRJ_NAME,
 TRANSTKD.TRD_SEQ,
 SKUMASTER.SKU_CODE,
 SKUMASTER.SKU_NAME,
 SKUMASTER.SKU_E_NAME,
 SKUMASTER.SKU_ICCAT,
 ICCAT.ICCAT_KEY,
 ICCAT.ICCAT_CODE,
 ICCAT.ICCAT_NAME,
 GOODSMASTER.GOODS_CODE,
 TRANSTKD.TRD_VAT_TY,
 TRANSTKD.TRD_VAT,
 TRANSTKD.TRD_VAT_R,
 TRANSTKD.TRD_LOT_NO,
 TRANSTKD.TRD_SERIAL,
 TRANSTKD.TRD_SH_CODE,
 TRANSTKD.TRD_SH_NAME,
 TRANSTKD.TRD_QTY,
 TRANSTKD.TRD_Q_FREE,
 TRANSTKD.TRD_UTQNAME,
 TRANSTKD.TRD_UTQQTY,
 TRANSTKD.TRD_K_U_PRC,
 TRANSTKD.TRD_U_PRC,
 TRANSTKD.TRD_U_VATIO,
 TRANSTKD.TRD_B_UPRC,
 TRANSTKD.TRD_DSC_KEYIN,
 TRANSTKD.TRD_DSC_KEYINV,
 TRANSTKD.TRD_G_AMT,
 TRANSTKD.TRD_G_KEYIN,
 TRANSTKD.TRD_G_SELL,
 TRANSTKD.TRD_G_VAT,
 TRANSTKD.TRD_G_AMT,
 TRANSTKD.TRD_TDSC_KEYINV,
 TRANSTKD.TRD_B_SELL,
 TRANSTKD.TRD_B_VAT,
 TRANSTKD.TRD_B_AMT,
 WARELOCATION.WL_CODE,
 WAREHOUSE.WH_CODE,
 BR_CODE,
BR_THAIDESC,
 TRANSTKH.TRH_CANCEL_DATE,
ARCD_NAME
FROM
 DOCINFO,
 DOCTYPE,
 AROE,
 ARFILE,
 SALESMAN,
 TRANSTKH,
 TRANSTKD,
 SHIPBY,
 WARELOCATION,
 WAREHOUSE,
 DEPTTAB,
 PRJTAB,
 SKUMASTER,
 GOODSMASTER,
 UOFQTY,
 ICCAT,
 ICDEPT,
 BRAND,
 SKUALT,
 ICSIZE,
 ICCOLOR,
BRANCH,

ARCONDITION
WHERE
 DOCINFO.DI_DT = DOCTYPE.DT_KEY AND
 DOCTYPE.DT_PROPERTIES = 207 AND
 DOCINFO.DI_KEY = AROE.AROE_DI AND
 AROE.AROE_AR = ARFILE.AR_KEY AND
 AROE.AROE_SLMN = SALESMAN.SLMN_KEY AND
 DOCINFO.DI_KEY = TRANSTKH.TRH_DI AND
 TRANSTKH.TRH_KEY = TRANSTKD.TRD_TRH AND
 TRANSTKD.TRD_WL = WARELOCATION.WL_KEY AND
 TRANSTKH.TRH_SB = SHIPBY.SB_KEY AND
 WARELOCATION.WL_WH = WAREHOUSE.WH_KEY AND
 TRANSTKH.TRH_DEPT = DEPTTAB.DEPT_KEY AND
 TRANSTKH.TRH_PRJ = PRJTAB.PRJ_KEY AND
 TRANSTKD.TRD_SKU = SKUMASTER.SKU_KEY AND
 SKUMASTER.SKU_S_UTQ = UOFQTY.UTQ_KEY AND
 TRANSTKD.TRD_GOODS = GOODSMASTER.GOODS_KEY AND
 SKUMASTER.SKU_ICCAT = ICCAT.ICCAT_KEY AND
 SKUMASTER.SKU_ICDEPT = ICDEPT.ICDEPT_KEY AND
 SKUMASTER.SKU_BRN = BRAND.BRN_KEY AND
 SKUMASTER.SKU_SKUALT = SKUALT.SKUALT_KEY AND
 SKUMASTER.SKU_ICSIZE = ICSIZE.ICSIZE_KEY AND
 SKUMASTER.SKU_ICCOLOR = ICCOLOR.ICCOLOR_KEY AND TRH_BR=BR_KEY AND
 AROE.AROE_ARCD=ARCONDITION.ARCD_KEY AND (DI_REF LIKE 'BK02%') AND TRD_UTQNAME like 'เส้น' ";


$String_Sql = $sql_reserve . " AND DI_DATE BETWEEN '" . $doc_date_start . "' AND '" . $doc_date_to . "' ";

// AROE.AROE_ARCD=ARCONDITION.ARCD_KEY AND (DI_REF LIKE 'BK02%' OR DI_REF LIKE 'BK03%' OR DI_REF LIKE 'BKSV%') AND TRD_UTQNAME like 'เส้น' ";

//$my_file = fopen("exp_qry_reserve.txt", "w") or die("Unable to open file!");
//fwrite($my_file, $String_Sql);
//fclose($my_file);



$data = "วันที่,ประเภท,รหัสสินค้า,รายละเอียดสินค้า,จำนวน,คลัง/ปี,LOCATION,เลขที่เอกสาร,คันที่,เทค,ชื่อลูกค้า,คงเหลือใน LO\n";

$query = $conn_sqlsvr->prepare($String_Sql);
$query->execute();

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {

    $doc_date = substr($row['DI_DATE'],8,2) . "/" . substr($row['DI_DATE'],5,2) . "/" . substr($row['DI_DATE'],0,4);

    $data .= " " . $doc_date . ",";
    $data .= " ,";
    $data .= str_replace(",", "^", $row['SKU_CODE']) . ",";
    $data .= str_replace(",", "^", $row['SKU_NAME']) . ",";
    $data .= str_replace(",", "^", $row['TRD_QTY']) . ",";
    $data .= str_replace(",", "^", $row['WL_CODE']) . ",";
    $data .= " ,";
    $data .= $row['DI_REF'] . ",";
    $data .= " ,";
    $data .= str_replace(",", "^", $row['SLMN_NAME']) . ",";
    $data .= str_replace(",", "^", $row['AR_NAME']) . ",";
    $data .= " " . "\n";

}

//$data = iconv("utf-8", "tis-620", $data);
$data = iconv("utf-8", "windows-874", $data);
echo $data;

exit();




