<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-KEY");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once('../config/connect_sqlserver.php');
include_once('../cond_file/doc_info_customer_ar.php');

$clean_str = function ($str) {
    if ($str === null || $str === false) return '';
    $str = trim((string)$str);
    if (!mb_check_encoding($str, 'UTF-8')) {
        $str = mb_convert_encoding($str, 'UTF-8', 'TIS-620, WINDOWS-874, ISO-8859-11, AUTO');
    }
    return $str;
};

$input = [];
$raw_input = file_get_contents('php://input');
if (!empty($raw_input)) {
    $json_decoded = json_decode($raw_input, true);
    if (is_array($json_decoded)) {
        $input = $json_decoded;
    }
}
if (empty($input)) {
    $input = array_merge($_GET, $_POST);
}

// Parse Salesman Parameter (Supports Array / Comma-Separated String)
$slmn_list = [];
if (!empty($input['slmn_name'])) {
    $raw_slmn = $input['slmn_name'];
    if (is_array($raw_slmn)) {
        foreach ($raw_slmn as $item) {
            $val = trim((string)$item);
            if ($val !== '') $slmn_list[] = $val;
        }
    } else if (is_string($raw_slmn)) {
        $parts = explode(',', $raw_slmn);
        foreach ($parts as $p) {
            $val = trim($p);
            if ($val !== '') $slmn_list[] = $val;
        }
    }
}

// Build SQL Salesman Filter
$sql_slmn_filter = "";
$params = [];
if (!empty($slmn_list)) {
    $slmn_conds = [];
    foreach ($slmn_list as $idx => $s_name) {
        $param_key = ":slmn_" . $idx;
        $slmn_conds[] = "(SALESMAN.SLMN_NAME LIKE " . $param_key . " OR SALESMAN.SLMN_CODE LIKE " . $param_key . ")";
        $params[$param_key] = '%' . $s_name . '%';
    }
    $sql_slmn_filter = " AND (" . implode(" OR ", $slmn_conds) . ") ";
}

$String_Sql = $select_query
    . $sql_cond
    . " AND SALESMAN.SLMN_ENABLE = 'Y' "
    . $sql_slmn_filter
    . $sql_order;

try {
    $query = $conn_sqlsvr->prepare($String_Sql);
    foreach ($params as $k => $v) {
        $query->bindValue($k, $v, PDO::PARAM_STR);
    }
    $query->execute();

    $result_data = [];
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $result_data[] = [
            "AR_CODE"         => $clean_str($row['AR_CODE'] ?? ''),
            "AR_NAME"         => $clean_str($row['AR_NAME'] ?? ''),
            "ARCAT_CODE"      => $clean_str($row['ARCAT_CODE'] ?? ''),
            "ARCAT_NAME"      => $clean_str($row['ARCAT_NAME'] ?? ''),
            "SLMN_CODE"       => $clean_str($row['SLMN_CODE'] ?? ''),
            "SLMN_NAME"       => $clean_str($row['SLMN_NAME'] ?? ''),
            "ADDB_COMPANY"    => $clean_str($row['ADDB_COMPANY'] ?? ''),
            "ADDB_BRANCH"     => $clean_str($row['ADDB_BRANCH'] ?? ''),
            "ADDB_TAX_ID"     => $clean_str($row['ADDB_TAX_ID'] ?? ''),
            "ADDB_PHONE"      => $clean_str($row['ADDB_PHONE'] ?? ''),
            "ADDB_FAX"        => $clean_str($row['ADDB_FAX'] ?? ''),
            "ADDB_EMAIL"      => $clean_str($row['ADDB_EMAIL'] ?? ''),
            "ADDB_ADDB_1"     => $clean_str($row['ADDB_ADDB_1'] ?? ''),
            "ADDB_ADDB_2"     => $clean_str($row['ADDB_ADDB_2'] ?? ''),
            "ADDB_ADDB_3"     => $clean_str($row['ADDB_ADDB_3'] ?? ''),
            "ADDB_PROVINCE"   => $clean_str($row['ADDB_PROVINCE'] ?? ''),
            "ADDB_POST"       => $clean_str($row['ADDB_POST'] ?? ''),
            "ARS_CRE_LIM"     => (float)($row['ARS_CRE_LIM'] ?? 0),
            "ARCD_TERM"       => (int)($row['ARCD_TERM'] ?? 0),
            "CT_NAME"         => $clean_str(($row['CT_NAME'] ?? '') . " " . ($row['CT_SURNME'] ?? '')),
            "CT_JOBTITLE"     => $clean_str($row['CT_JOBTITLE'] ?? ''),
            "CT_MOBILE"       => $clean_str($row['CT_MOBILE'] ?? ''),
            "CT_EMAIL"        => $clean_str($row['CT_EMAIL'] ?? '')
        ];
    }

    $response = [
        "status"        => "success",
        "code"          => 200,
        "message"       => "Customer data retrieved successfully",
        "total_records" => count($result_data),
        "data"          => $result_data
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "code"    => 500,
        "message" => "Database Query Error: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
