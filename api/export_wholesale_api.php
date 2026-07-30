<?php
date_default_timezone_set('Asia/Bangkok');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-API-KEY");

// Handle OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include('../config/connect_sqlserver.php');
include('../cond_file/doc_info_wholesale.php');

// Parse input parameters (from Form POST/GET or JSON Body)
$raw_input = file_get_contents('php://input');
$json_input = json_decode($raw_input, true) ?? [];

$doc_date_start_input = $_REQUEST['doc_date_start'] ?? $json_input['doc_date_start'] ?? date('d-m-Y');
$doc_date_to_input = $_REQUEST['doc_date_to'] ?? $json_input['doc_date_to'] ?? date('d-m-Y');

// Helper function to format date for SQL Server query (YYYY/MM/DD)
function format_date_sql($date_str) {
    $date_str = trim((string)$date_str);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_str)) {
        // YYYY-MM-DD -> YYYY/MM/DD
        return str_replace('-', '/', $date_str);
    } elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date_str)) {
        // DD-MM-YYYY -> YYYY/MM/DD
        return substr($date_str, 6, 4) . "/" . substr($date_str, 3, 2) . "/" . substr($date_str, 0, 2);
    } elseif (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $date_str)) {
        return $date_str;
    }
    return date('Y/m/d');
}

$doc_date_start = format_date_sql($doc_date_start_input);
$doc_date_to = format_date_sql($doc_date_to_input);

$table_filed_where = "DOCINFO.DI_DATE";

$String_Sql = $select_query_sale
    . $sql_cond_sale
    . " AND " . $table_filed_where . " BETWEEN '" . $doc_date_start . "' AND '" . $doc_date_to . "' "
    . $sql_order_sale;

try {
    $query = $conn_sqlsvr->prepare($String_Sql);
    $query->execute();

    $result_data = [];
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

        $result_data[] = [
            "DI_REF"          => (string)($row['DI_REF'] ?? ''),
            "DI_DATE"         => (string)($row['DI_DATE'] ?? ''),
            "AR_NAME"         => (string)($row['AR_NAME'] ?? ''),
            "DEPT_CODE"       => (string)($row['DEPT_CODE'] ?? ''),
            "DEPT_THAIDESC"   => (string)($row['DEPT_THAIDESC'] ?? ''),
            "ICCAT_CODE"      => (string)($row['ICCAT_CODE'] ?? ''),
            "ICCAT_NAME"      => (string)($row['ICCAT_NAME'] ?? ''),
            "SKU_NAME"        => (string)($row['SKU_NAME'] ?? ''),
            "SKU_E_NAME"      => (string)($row['SKU_E_NAME'] ?? ''),
            "BRN_NAME"        => (string)($row['BRN_NAME'] ?? ''),
            "TRD_QTY"         => $trd_qty,
            "TRD_Q_FREE"      => $trd_q_free,
            "TRD_TDSC_KEYINV" => $trd_tdsc_keyinv,
            "TRD_B_AMT"       => $trd_b_amt,
            "SLMN_CODE"       => (string)($row['SLMN_CODE'] ?? ''),
            "SLMN_NAME"       => (string)($row['SLMN_NAME'] ?? '')
        ];
    }

    $response = [
        "status"        => "success",
        "code"          => 200,
        "message"       => "Data retrieved successfully",
        "total_records" => count($result_data),
        "data"          => $result_data
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "code"    => 500,
        "message" => "Database Query Error: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

exit();
