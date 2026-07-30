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

$data = "DI_REF,DI_DATE,AR_NAME,DEPT_CODE,DEPT_THAIDESC,ICCAT_CODE,ICCAT_NAME,SKU_NAME,SKU_E_NAME,BRN_NAME,TRD_QTY,TRD_Q_FREE,TRD_TDSC_KEYINV,TRD_B_AMT,SLMN_CODE,SLMN_NAME\n";

$query = $conn_sqlsvr->prepare($String_Sql);
$query->execute();

$clean_str = function($val) {
    return str_replace(",", "^", (string)($val ?? ''));
};

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $dt_doccode = (string)($row['DT_DOCCODE'] ?? '');
    $is_minus_doc = (strpos($dt_doccode, 'IS') !== false || strpos($dt_doccode, 'ISO') !== false);

    $trd_qty = (float)($row['TRD_QTY'] ?? 0);
    $trd_q_free = (float)($row['TRD_Q_FREE'] ?? 0);
    $trd_tdsc_keyinv = (float)($row['TRD_TDSC_KEYINV'] ?? 0);
    $trd_b_amt = (float)($row['TRD_B_AMT'] ?? 0);

    if ($is_minus_doc) {
        if ($trd_qty > 0) {
            $trd_qty = -$trd_qty;
        }
        if ($trd_q_free > 0) {
            $trd_q_free = -$trd_q_free;
        }
        if ($trd_tdsc_keyinv > 0) {
            $trd_tdsc_keyinv = -$trd_tdsc_keyinv;
        }
        if ($trd_b_amt > 0) {
            $trd_b_amt = -$trd_b_amt;
        }
    }

    $line = [
        $clean_str($row['DI_REF'] ?? ''),
        $clean_str($row['DI_DATE'] ?? ''),
        $clean_str($row['AR_NAME'] ?? ''),
        $clean_str($row['DEPT_CODE'] ?? ''),
        $clean_str($row['DEPT_THAIDESC'] ?? ''),
        $clean_str($row['ICCAT_CODE'] ?? ''),
        $clean_str($row['ICCAT_NAME'] ?? ''),
        $clean_str($row['SKU_NAME'] ?? ''),
        $clean_str($row['SKU_E_NAME'] ?? ''),
        $clean_str($row['BRN_NAME'] ?? ''),
        $clean_str($trd_qty),
        $clean_str($trd_q_free),
        $clean_str($trd_tdsc_keyinv),
        $clean_str($trd_b_amt),
        $clean_str($row['SLMN_CODE'] ?? ''),
        $clean_str($row['SLMN_NAME'] ?? '')
    ];
    $data .= implode(",", $line) . "\n";
}

$data = iconv("utf-8", "windows-874//IGNORE", $data);
echo $data;

exit();