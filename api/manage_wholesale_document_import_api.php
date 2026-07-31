<?php
date_default_timezone_set('Asia/Bangkok');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-API-KEY");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include('../config/connect_sqlserver.php');
include('../cond_file/doc_info_wholesale_ks.php');

$raw_input = file_get_contents('php://input');
$json_input = json_decode($raw_input, true) ?? [];

$doc_date_start_input = $_REQUEST['doc_date_start'] ?? $json_input['doc_date_start'] ?? date('d-m-Y');
$doc_date_to_input = $_REQUEST['doc_date_to'] ?? $json_input['doc_date_to'] ?? date('d-m-Y');

function format_date_sql($date_str) {
    $date_str = trim((string)$date_str);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_str)) {
        return str_replace('-', '/', $date_str);
    } elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date_str)) {
        return substr($date_str, 6, 4) . "/" . substr($date_str, 3, 2) . "/" . substr($date_str, 0, 2);
    } elseif (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $date_str)) {
        return $date_str;
    }
    return date('Y/m/d');
}

$doc_date_start = format_date_sql($doc_date_start_input);
$doc_date_to = format_date_sql($doc_date_to_input);

// Optional Salesman Filter
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
        $slmn_conditions[] = "(SALESMAN.SLMN_NAME LIKE '%" . $escaped_val . "%' OR SALESMAN.SLMN_CODE LIKE '%" . $escaped_val . "%')";
    }
    $sql_slmn_filter = " AND (" . implode(" OR ", $slmn_conditions) . ") ";
}

// Optional Category Filter
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
        $iccat_conditions[] = "(ICCAT.ICCAT_CODE LIKE '%" . $escaped_val . "%' OR ICCAT.ICCAT_NAME LIKE '%" . $escaped_val . "%')";
    }
    $sql_iccat_filter = " AND (" . implode(" OR ", $iccat_conditions) . ") ";
}

$table_filed_where = "DOCINFO.DI_DATE";

$String_Sql = $select_query_sale
    . $sql_cond_sale
    . " AND " . $table_filed_where . " BETWEEN '" . $doc_date_start . "' AND '" . $doc_date_to . "' "
    . $sql_slmn_filter
    . $sql_iccat_filter
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
        $trd_b_sell = (float)($row['TRD_B_SELL'] ?? 0);
        $trd_b_vat = (float)($row['TRD_B_VAT'] ?? 0);
        $trd_g_keyin = (float)($row['TRD_G_KEYIN'] ?? 0);

        if ($is_minus_doc) {
            if ($trd_qty > 0) $trd_qty = -$trd_qty;
            if ($trd_q_free > 0) $trd_q_free = -$trd_q_free;
            if ($trd_tdsc_keyinv > 0) $trd_tdsc_keyinv = -$trd_tdsc_keyinv;
            if ($trd_b_amt > 0) $trd_b_amt = -$trd_b_amt;
            if ($trd_b_sell > 0) $trd_b_sell = -$trd_b_sell;
            if ($trd_b_vat > 0) $trd_b_vat = -$trd_b_vat;
            if ($trd_g_keyin > 0) $trd_g_keyin = -$trd_g_keyin;
        }

        $result_data[] = [
            "DI_KEY"          => (int)($row['DI_KEY'] ?? 0),
            "DI_REF"          => (string)($row['DI_REF'] ?? ''),
            "DI_DATE"         => (string)($row['DI_DATE'] ?? ''),
            "DI_TIME_CHK"     => (string)($row['DI_TIME_CHK'] ?? ''),
            "DI_MONTH"        => (int)($row['DI_MONTH'] ?? 0),
            "DI_YEAR"         => (int)($row['DI_YEAR'] ?? 0),
            "DI_ACTIVE"       => (int)($row['DI_ACTIVE'] ?? 0),
            "DT_DOCCODE"      => (string)($row['DT_DOCCODE'] ?? ''),
            "DT_PROPERTIES"   => (int)($row['DT_PROPERTIES'] ?? 0),
            "AR_CODE"         => (string)($row['AR_CODE'] ?? ''),
            "AR_NAME"         => (string)($row['AR_NAME'] ?? ''),
            "AROE_B_AMT"      => (float)($row['AROE_B_AMT'] ?? 0),
            "ARD_B_VAT"       => (float)($row['ARD_B_VAT'] ?? 0),
            "ARD_B_SV"        => (float)($row['ARD_B_SV'] ?? 0),
            "ARD_B_SNV"       => (float)($row['ARD_B_SNV'] ?? 0),
            "ARD_TDSC_KEYIN"  => (float)($row['ARD_TDSC_KEYIN'] ?? 0),
            "ARD_TDSC_KEYINV" => (float)($row['ARD_TDSC_KEYINV'] ?? 0),
            "ARD_G_VAT"       => (float)($row['ARD_G_VAT'] ?? 0),
            "ARD_G_SV"        => (float)($row['ARD_G_SV'] ?? 0),
            "ARD_G_SNV"       => (float)($row['ARD_G_SNV'] ?? 0),
            "ARD_G_KEYIN"     => (float)($row['ARD_G_KEYIN'] ?? 0),
            "ARD_DUE_DA"      => (string)($row['ARD_DUE_DA'] ?? ''),
            "ARD_CRNCYCODE"   => (string)($row['ARD_CRNCYCODE'] ?? ''),
            "ARD_XCHG"        => (float)($row['ARD_XCHG'] ?? 1),
            "SLMN_CODE"       => (string)($row['SLMN_CODE'] ?? ''),
            "SLMN_NAME"       => (string)($row['SLMN_NAME'] ?? ''),
            "TRH_REFER_XREF"  => (string)($row['TRH_REFER_XREF'] ?? ''),
            "TRH_REFER_IREF"  => (string)($row['TRH_REFER_IREF'] ?? ''),
            "TRH_REFER_PERSON"=> (string)($row['TRH_REFER_PERSON'] ?? ''),
            "TRH_REFER_XTRA1" => (string)($row['TRH_REFER_XTRA1'] ?? ''),
            "TRH_REFER_XTRA2" => (string)($row['TRH_REFER_XTRA2'] ?? ''),
            "TRH_SHIP_DATE"   => (string)($row['TRH_SHIP_DATE'] ?? ''),
            "TRH_VAT_TY"      => (int)($row['TRH_VAT_TY'] ?? 0),
            "TRH_VAT_R"       => (float)($row['TRH_VAT_R'] ?? 0),
            "TRH_VATIO"       => (int)($row['TRH_VATIO'] ?? 0),
            "TRH_N_ITEMS"     => (int)($row['TRH_N_ITEMS'] ?? 0),
            "TRH_N_QTY"       => (float)($row['TRH_N_QTY'] ?? 0),
            "DEPT_CODE"       => (string)($row['DEPT_CODE'] ?? ''),
            "DEPT_THAIDESC"   => (string)($row['DEPT_THAIDESC'] ?? ''),
            "DEPT_ENGDESC"    => (string)($row['DEPT_ENGDESC'] ?? ''),
            "PRJ_CODE"        => (string)($row['PRJ_CODE'] ?? ''),
            "PRJ_NAME"        => (string)($row['PRJ_NAME'] ?? ''),
            "ICCAT_CODE"      => (string)($row['ICCAT_CODE'] ?? ''),
            "ICCAT_NAME"      => (string)($row['ICCAT_NAME'] ?? ''),
            "TRD_SEQ"         => (int)($row['TRD_SEQ'] ?? 0),
            "SKU_CODE"        => (string)($row['SKU_CODE'] ?? ''),
            "SKU_NAME"        => (string)($row['SKU_NAME'] ?? ''),
            "SKU_E_NAME"      => (string)($row['SKU_E_NAME'] ?? ''),
            "UTQ_NAME"        => (string)($row['UTQ_NAME'] ?? ''),
            "UTQ_QTY"         => (float)($row['UTQ_QTY'] ?? 0),
            "GOODS_CODE"      => (string)($row['GOODS_CODE'] ?? ''),
            "TRD_VAT_TY"      => (int)($row['TRD_VAT_TY'] ?? 0),
            "TRD_VAT"         => (float)($row['TRD_VAT'] ?? 0),
            "TRD_VAT_R"       => (float)($row['TRD_VAT_R'] ?? 0),
            "TRD_LOT_NO"      => (string)($row['TRD_LOT_NO'] ?? ''),
            "TRD_SERIAL"      => (string)($row['TRD_SERIAL'] ?? ''),
            "TRD_SH_CODE"     => (string)($row['TRD_SH_CODE'] ?? ''),
            "TRD_SH_NAME"     => (string)($row['TRD_SH_NAME'] ?? ''),
            "BRN_CODE"        => (string)($row['BRN_CODE'] ?? ''),
            "BRN_NAME"        => (string)($row['BRN_NAME'] ?? ''),
            "TRD_QTY"         => $trd_qty,
            "TRD_Q_FREE"      => $trd_q_free,
            "TRD_UTQNAME"     => (string)($row['TRD_UTQNAME'] ?? ''),
            "TRD_UTQQTY"      => (float)($row['TRD_UTQQTY'] ?? 0),
            "TRD_K_U_PRC"     => (float)($row['TRD_K_U_PRC'] ?? 0),
            "TRD_U_PRC"       => (float)($row['TRD_U_PRC'] ?? 0),
            "TRD_U_VATIO"     => (int)($row['TRD_U_VATIO'] ?? 0),
            "TRD_B_UPRC"      => (float)($row['TRD_B_UPRC'] ?? 0),
            "TRD_DSC_KEYIN"   => (float)($row['TRD_DSC_KEYIN'] ?? 0),
            "TRD_DSC_KEYINV"  => (float)($row['TRD_DSC_KEYINV'] ?? 0),
            "TRD_G_AMT"       => (float)($row['TRD_G_AMT'] ?? 0),
            "TRD_G_KEYIN"     => $trd_g_keyin,
            "TRD_G_SELL"      => (float)($row['TRD_G_SELL'] ?? 0),
            "TRD_G_VAT"       => (float)($row['TRD_G_VAT'] ?? 0),
            "TRD_TDSC_KEYINV" => $trd_tdsc_keyinv,
            "TRD_B_SELL"      => $trd_b_sell,
            "TRD_B_VAT"       => $trd_b_vat,
            "TRD_B_AMT"       => $trd_b_amt,
            "WL_CODE"         => (string)($row['WL_CODE'] ?? ''),
            "WH_CODE"         => (string)($row['WH_CODE'] ?? ''),
            "ARCD_NAME"       => (string)($row['ARCD_NAME'] ?? '')
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
