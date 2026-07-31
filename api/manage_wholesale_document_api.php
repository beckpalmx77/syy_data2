<?php
date_default_timezone_set('Asia/Bangkok');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-API-KEY");

// Handle OPTIONS preflight request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include(__DIR__ . '/../config/connect_db.php');

// Parse input parameters (from Form POST/GET or JSON Body)
$raw_input = file_get_contents('php://input');
$json_input = json_decode($raw_input, true) ?? [];

$doc_date_start_input = $_REQUEST['doc_date_start'] ?? $json_input['doc_date_start'] ?? date('d-m-Y');
$doc_date_to_input = $_REQUEST['doc_date_to'] ?? $json_input['doc_date_to'] ?? date('d-m-Y');

function parse_date_formats($date_str) {
    $date_str = trim((string)$date_str);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_str)) {
        $parts = explode('-', $date_str);
        return [
            'ymd' => $date_str,
            'dmy' => $parts[2] . '/' . $parts[1] . '/' . $parts[0]
        ];
    } elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date_str)) {
        $parts = explode('-', $date_str);
        return [
            'ymd' => $parts[2] . '-' . $parts[1] . '-' . $parts[0],
            'dmy' => $parts[0] . '/' . $parts[1] . '/' . $parts[2]
        ];
    } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date_str)) {
        $parts = explode('/', $date_str);
        return [
            'ymd' => $parts[2] . '-' . $parts[1] . '-' . $parts[0],
            'dmy' => $date_str
        ];
    } elseif (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $date_str)) {
        $parts = explode('/', $date_str);
        return [
            'ymd' => $parts[0] . '-' . $parts[1] . '-' . $parts[2],
            'dmy' => $parts[2] . '/' . $parts[1] . '/' . $parts[0]
        ];
    }
    return [
        'ymd' => date('Y-m-d'),
        'dmy' => date('d/m/Y')
    ];
}

$start_info = parse_date_formats($doc_date_start_input);
$to_info = parse_date_formats($doc_date_to_input);

if ($start_info['dmy'] === $to_info['dmy']) {
    $sql_date_where = " DI_DATE = '" . $start_info['dmy'] . "' ";
} else {
    $st = strtotime($start_info['ymd']);
    $et = strtotime($to_info['ymd']);
    $diff_days = round(($et - $st) / 86400);

    if ($st && $et && $diff_days >= 0 && $diff_days <= 90) {
        $dates_list = [];
        for ($curr = $st; $curr <= $et; $curr += 86400) {
            $dates_list[] = date('d/m/Y', $curr);
        }
        $sql_date_where = " DI_DATE IN ('" . implode("','", $dates_list) . "') ";
    } else {
        $sql_date_where = " STR_TO_DATE(DI_DATE, '%d/%m/%Y') BETWEEN '" . $start_info['ymd'] . "' AND '" . $to_info['ymd'] . "' ";
    }
}

// Parse salesman filter
$slmn_input = $_REQUEST['slmn_name'] ?? $json_input['slmn_name'] ?? null;
$slmn_list = [];
if (!empty($slmn_input)) {
    if (is_array($slmn_input)) {
        foreach ($slmn_input as $item) {
            $val = trim((string)$item);
            if ($val !== '' && strtoupper($val) !== 'ALL') {
                $slmn_list[] = $val;
            }
        }
    } else {
        $raw_list = explode(',', (string)$slmn_input);
        foreach ($raw_list as $item) {
            $val = trim((string)$item);
            if ($val !== '' && strtoupper($val) !== 'ALL') {
                $slmn_list[] = $val;
            }
        }
    }
}

$sql_slmn_filter = "";
if (!empty($slmn_list)) {
    $slmn_conditions = [];
    foreach ($slmn_list as $slmn_val) {
        $escaped_val = str_replace("'", "''", $slmn_val);
        $slmn_conditions[] = "(SLMN_NAME LIKE '%" . $escaped_val . "%' OR SLMN_CODE LIKE '%" . $escaped_val . "%')";
    }
    $sql_slmn_filter = " AND (" . implode(" OR ", $slmn_conditions) . ") ";
}

// Parse product category filter
$iccat_input = $_REQUEST['iccat_code'] ?? $json_input['iccat_code'] ?? null;
$iccat_list = [];
if (!empty($iccat_input)) {
    if (is_array($iccat_input)) {
        foreach ($iccat_input as $item) {
            $val = trim((string)$item);
            if ($val !== '' && strtoupper($val) !== 'ALL') {
                $iccat_list[] = $val;
            }
        }
    } else {
        $raw_list = explode(',', (string)$iccat_input);
        foreach ($raw_list as $item) {
            $val = trim((string)$item);
            if ($val !== '' && strtoupper($val) !== 'ALL') {
                $iccat_list[] = $val;
            }
        }
    }
}

$sql_iccat_filter = "";
if (!empty($iccat_list)) {
    $iccat_conditions = [];
    foreach ($iccat_list as $iccat_val) {
        $escaped_val = str_replace("'", "''", $iccat_val);
        $iccat_conditions[] = "(ICCAT_CODE LIKE '%" . $escaped_val . "%' OR ICCAT_NAME LIKE '%" . $escaped_val . "%')";
    }
    $sql_iccat_filter = " AND (" . implode(" OR ", $iccat_conditions) . ") ";
}

$String_Sql = "SELECT DI_KEY, DI_REF, DI_DATE, DI_TIME_CHK, DI_ACTIVE, AR_NAME, DEPT_CODE, DEPT_THAIDESC,
                      ICCAT_CODE, ICCAT_NAME, SKU_NAME, SKU_E_NAME, BRN_NAME, TRD_QTY, TRD_Q_FREE,
                      TRD_U_PRC, TRD_TDSC_KEYINV, TRD_B_AMT, SLMN_CODE, SLMN_NAME, DT_DOCCODE
               FROM ims_product_sale_syy_ks
               WHERE " . $sql_date_where
               . $sql_slmn_filter
               . $sql_iccat_filter
               . " ORDER BY DI_KEY DESC
               LIMIT 10000";

try {
    $query = $conn->prepare($String_Sql);
    $query->execute();

    $result_data = [];
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $result_data[] = [
            "DI_KEY"          => (int)($row['DI_KEY'] ?? 0),
            "DI_REF"          => (string)($row['DI_REF'] ?? ''),
            "DI_DATE"         => (string)($row['DI_DATE'] ?? ''),
            "DI_TIME_CHK"     => (string)($row['DI_TIME_CHK'] ?? ''),
            "DI_ACTIVE"       => (int)($row['DI_ACTIVE'] ?? 0),
            "AR_NAME"         => (string)($row['AR_NAME'] ?? ''),
            "DEPT_CODE"       => (string)($row['DEPT_CODE'] ?? ''),
            "DEPT_THAIDESC"   => (string)($row['DEPT_THAIDESC'] ?? ''),
            "ICCAT_CODE"      => (string)($row['ICCAT_CODE'] ?? ''),
            "ICCAT_NAME"      => (string)($row['ICCAT_NAME'] ?? ''),
            "SKU_NAME"        => (string)($row['SKU_NAME'] ?? ''),
            "SKU_E_NAME"      => (string)($row['SKU_E_NAME'] ?? ''),
            "BRN_NAME"        => (string)($row['BRN_NAME'] ?? ''),
            "TRD_QTY"         => (float)($row['TRD_QTY'] ?? 0),
            "TRD_Q_FREE"      => (float)($row['TRD_Q_FREE'] ?? 0),
            "TRD_U_PRC"       => (float)($row['TRD_U_PRC'] ?? 0),
            "TRD_TDSC_KEYINV" => (float)($row['TRD_TDSC_KEYINV'] ?? 0),
            "TRD_B_AMT"       => (float)($row['TRD_B_AMT'] ?? 0),
            "SLMN_CODE"       => (string)($row['SLMN_CODE'] ?? ''),
            "SLMN_NAME"       => (string)($row['SLMN_NAME'] ?? ''),
            "DT_DOCCODE"      => (string)($row['DT_DOCCODE'] ?? '')
        ];
    }

    $response = [
        "status"        => "success",
        "code"          => 200,
        "message"       => "Data retrieved successfully",
        "total_records" => count($result_data),
        "data"          => $result_data
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "code"    => 500,
        "message" => "Database Query Error: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

exit();
