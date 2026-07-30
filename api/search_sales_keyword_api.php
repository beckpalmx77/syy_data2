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

$ar_code_input = trim((string)($input['ar_code'] ?? ''));
$keyword_input = trim((string)($input['keyword'] ?? ''));
$limit_input   = (int)($input['limit'] ?? 50);

if ($limit_input <= 0) $limit_input = 50;
if ($limit_input > 500) $limit_input = 500;

$where_conds = ["DOCINFO.DI_ACTIVE = 0"];
$params = [];

// Filter Customer Code / Name
if ($ar_code_input !== '') {
    $where_conds[] = "(ARFILE.AR_CODE = :ar_exact OR ARFILE.AR_CODE LIKE :ar_like OR ARFILE.AR_NAME LIKE :ar_name_like)";
    $params[':ar_exact'] = $ar_code_input;
    $params[':ar_like']  = '%' . $ar_code_input . '%';
    $params[':ar_name_like'] = '%' . $ar_code_input . '%';
}

// Filter Keyword (SKU Code / Name)
if ($keyword_input !== '') {
    $keyword_clean = str_replace(['/', '-', ' '], '', $keyword_input);
    $where_conds[] = "(SKUMASTER.SKU_CODE LIKE :kw1 OR SKUMASTER.SKU_NAME LIKE :kw2 OR REPLACE(REPLACE(REPLACE(SKUMASTER.SKU_NAME, '/', ''), '-', ''), ' ', '') LIKE :kw3)";
    $params[':kw1'] = '%' . $keyword_input . '%';
    $params[':kw2'] = '%' . $keyword_input . '%';
    $params[':kw3'] = '%' . $keyword_clean . '%';
}

$where_sql = implode(" AND ", $where_conds);

$sql = "SELECT TOP " . $limit_input . "
    DOCINFO.DI_REF,
    FORMAT(DOCINFO.DI_DATE, 'dd/MM/yyyy') as DI_DATE,
    FORMAT(cast(DOCINFO.DI_CRE_DATE as datetime),'HH:mm') as DI_TIME_CHK,
    ARFILE.AR_CODE,
    ARFILE.AR_NAME,
    SKUMASTER.SKU_CODE,
    SKUMASTER.SKU_NAME,
    SKUMASTER.SKU_STOCK,
    TRANSTKD.TRD_QTY,
    TRANSTKD.TRD_Q_FREE,
    TRANSTKD.TRD_U_PRC,
    TRANSTKD.TRD_TDSC_KEYINV,
    TRANSTKD.TRD_B_AMT,
    TRANSTKD.TRD_UTQNAME,
    SALESMAN.SLMN_NAME,
    DOCTYPE.DT_DOCCODE
FROM DOCINFO WITH (NOLOCK)
JOIN DOCTYPE WITH (NOLOCK) ON (DOCINFO.DI_DT = DOCTYPE.DT_KEY)
JOIN ARDETAIL WITH (NOLOCK) ON (DOCINFO.DI_KEY = ARDETAIL.ARD_DI)
JOIN ARFILE WITH (NOLOCK) ON (ARDETAIL.ARD_AR = ARFILE.AR_KEY)
JOIN TRANSTKH WITH (NOLOCK) ON (DOCINFO.DI_KEY = TRANSTKH.TRH_DI)
JOIN TRANSTKD WITH (NOLOCK) ON (TRANSTKH.TRH_KEY = TRANSTKD.TRD_TRH)
JOIN SKUMASTER WITH (NOLOCK) ON (TRANSTKD.TRD_SKU = SKUMASTER.SKU_KEY)
LEFT JOIN SLDETAIL WITH (NOLOCK) ON (DOCINFO.DI_KEY = SLDETAIL.SLD_DI)
LEFT JOIN SALESMAN WITH (NOLOCK) ON (SLDETAIL.SLD_SLMN = SALESMAN.SLMN_KEY)
WHERE " . $where_sql . "
ORDER BY DOCINFO.DI_DATE DESC, DOCINFO.DI_KEY DESC";

try {
    $query = $conn_sqlsvr->prepare($sql);
    foreach ($params as $k => $v) {
        $query->bindValue($k, $v, PDO::PARAM_STR);
    }
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
            if ($trd_qty > 0) $trd_qty = -$trd_qty;
            if ($trd_q_free > 0) $trd_q_free = -$trd_q_free;
            if ($trd_tdsc_keyinv > 0) $trd_tdsc_keyinv = -$trd_tdsc_keyinv;
            if ($trd_b_amt > 0) $trd_b_amt = -$trd_b_amt;
        }

        $result_data[] = [
            "DI_REF"          => $clean_str($row['DI_REF'] ?? ''),
            "DI_DATE"         => $clean_str($row['DI_DATE'] ?? ''),
            "DI_TIME_CHK"     => $clean_str($row['DI_TIME_CHK'] ?? ''),
            "AR_CODE"         => $clean_str($row['AR_CODE'] ?? ''),
            "AR_NAME"         => $clean_str($row['AR_NAME'] ?? ''),
            "SKU_CODE"        => $clean_str($row['SKU_CODE'] ?? ''),
            "SKU_NAME"        => $clean_str($row['SKU_NAME'] ?? ''),
            "TRD_U_PRC"       => (float)($row['TRD_U_PRC'] ?? 0),
            "TRD_QTY"         => $trd_qty,
            "TRD_Q_FREE"      => $trd_q_free,
            "TRD_TDSC_KEYINV" => $trd_tdsc_keyinv,
            "TRD_B_AMT"       => $trd_b_amt,
            "SKU_STOCK"       => (float)($row['SKU_STOCK'] ?? 0),
            "TRD_UTQNAME"     => $clean_str($row['TRD_UTQNAME'] ?? ''),
            "SLMN_NAME"       => $clean_str($row['SLMN_NAME'] ?? '')
        ];
    }

    $response = [
        "status"        => "success",
        "code"          => 200,
        "message"       => "Sales keyword search completed successfully",
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
