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
include_once('../cond_file/query-product-price-main.php');

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

// Parse Category Parameter (Supports Array / Comma-Separated String)
$cat_list = [];
if (!empty($input['iccat_code'])) {
    $raw_cat = $input['iccat_code'];
    if (is_array($raw_cat)) {
        foreach ($raw_cat as $item) {
            $val = trim((string)$item);
            if ($val !== '') $cat_list[] = $val;
        }
    } else if (is_string($raw_cat)) {
        $parts = explode(',', $raw_cat);
        foreach ($parts as $p) {
            $val = trim($p);
            if ($val !== '') $cat_list[] = $val;
        }
    }
}

// Build SQL Category Filter
$sql_cat_filter = "";
$params = [];
if (!empty($cat_list)) {
    $cat_conds = [];
    foreach ($cat_list as $idx => $c_val) {
        $param_key = ":cat_" . $idx;
        $cat_conds[] = "(ICCAT.ICCAT_CODE LIKE " . $param_key . " OR ICCAT.ICCAT_NAME LIKE " . $param_key . ")";
        $params[$param_key] = '%' . $c_val . '%';
    }
    $sql_cat_filter = " AND (" . implode(" OR ", $cat_conds) . ") ";
}

$sql = "SELECT DISTINCT ICCAT.ICCAT_CODE, ICCAT.ICCAT_NAME "
     . " FROM SKUMASTER WITH (NOLOCK), SKUALT WITH (NOLOCK), UOFQTY WITH (NOLOCK), BRAND WITH (NOLOCK), ICCAT WITH (NOLOCK), GOODSMASTER WITH (NOLOCK), PRICETAG WITH (NOLOCK), ARPLU WITH (NOLOCK), ARPRICETAB WITH (NOLOCK), ICDEPT WITH (NOLOCK) "
     . $sql_cond
     . $sql_cat_filter
     . " ORDER BY ICCAT.ICCAT_CODE ASC ";

try {
    $query = $conn_sqlsvr->prepare($sql);
    foreach ($params as $k => $v) {
        $query->bindValue($k, $v, PDO::PARAM_STR);
    }
    $query->execute();

    $result_data = [];
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $result_data[] = [
            "ICCAT_CODE" => $clean_str($row['ICCAT_CODE'] ?? ''),
            "ICCAT_NAME" => $clean_str($row['ICCAT_NAME'] ?? '')
        ];
    }

    $response = [
        "status"        => "success",
        "code"          => 200,
        "message"       => "Product categories retrieved successfully",
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
