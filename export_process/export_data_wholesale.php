<?php
date_default_timezone_set('Asia/Bangkok');

$filename = "Data_Wholesale-" . date('Y-m-d_H-i-s') . ".csv";

@header('Content-Type: text/csv; charset=windows-874');
@header("Content-Disposition: attachment; filename=" . $filename);

include('../config/connect_sqlserver.php');
include('../cond_file/doc_info_wholesale.php');

$table_filed_where = "DOCINFO.DI_DATE";

$doc_date_start_raw = $_POST['doc_date_start'] ?? date('d-m-Y');
$doc_date_to_raw = $_POST['doc_date_to'] ?? date('d-m-Y');

$doc_date_start = substr($doc_date_start_raw, 6, 4) . "/" . substr($doc_date_start_raw, 3, 2) . "/" . substr($doc_date_start_raw, 0, 2);
$doc_date_to = substr($doc_date_to_raw, 6, 4) . "/" . substr($doc_date_to_raw, 3, 2) . "/" . substr($doc_date_to_raw, 0, 2);

$String_Sql = $select_query_sale
    . $sql_cond_sale
    . " AND " . $table_filed_where . " BETWEEN '" . $doc_date_start . "' AND '" . $doc_date_to . "' "
    . $sql_order_sale;

$data = "DI_REF,DI_DATE,AR_NAME,ICCAT_CODE,ICCAT_NAME,SKU_NAME,SKU_E_NAME,BRN_NAME,TRD_B_AMT,SLMN_CODE,SLMN_NAME\n";

$query = $conn_sqlsvr->prepare($String_Sql);
$query->execute();

$clean_str = function($val) {
    return str_replace(",", "^", (string)($val ?? ''));
};

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $line = [
        $clean_str($row['DI_REF'] ?? ''),
        $clean_str($row['DI_DATE'] ?? ''),
        $clean_str($row['AR_NAME'] ?? ''),
        $clean_str($row['ICCAT_CODE'] ?? ''),
        $clean_str($row['ICCAT_NAME'] ?? ''),
        $clean_str($row['SKU_NAME'] ?? ''),
        $clean_str($row['SKU_E_NAME'] ?? ''),
        $clean_str($row['BRN_NAME'] ?? ''),
        $clean_str($row['TRD_B_AMT'] ?? ''),
        $clean_str($row['SLMN_CODE'] ?? ''),
        $clean_str($row['SLMN_NAME'] ?? '')
    ];
    $data .= implode(",", $line) . "\n";
}

$data = iconv("utf-8", "windows-874//IGNORE", $data);
echo $data;

exit();