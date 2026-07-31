<?php
date_default_timezone_set('Asia/Bangkok');

$filename = "Data_Wholesale-" . date('Y-m-d_H-i-s') . ".csv";

@header('Content-Type: text/csv; charset=windows-874');
@header("Content-Disposition: attachment; filename=" . $filename);

include(__DIR__ . '/../config/connect_db.php');

$doc_date_start_raw = $_POST['doc_date_start'] ?? $_GET['doc_date_start'] ?? date('d-m-Y');
$doc_date_to_raw = $_POST['doc_date_to'] ?? $_GET['doc_date_to'] ?? date('d-m-Y');

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

$start_info = parse_date_formats($doc_date_start_raw);
$to_info = parse_date_formats($doc_date_to_raw);

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

// Filter by Salesman Name / Code (Supports single or multiple values)
$slmn_input = $_POST['slmn_name'] ?? $_GET['slmn_name'] ?? null;
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

// Filter by Product Category Code / Name (Supports single or multiple values)
$iccat_input = $_POST['iccat_code'] ?? $_GET['iccat_code'] ?? null;
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

$data = "DI_REF,DI_DATE,AR_NAME,DEPT_CODE,DEPT_THAIDESC,ICCAT_CODE,ICCAT_NAME,SKU_NAME,SKU_E_NAME,BRN_NAME,TRD_QTY,TRD_Q_FREE,TRD_U_PRC,TRD_TDSC_KEYINV,TRD_B_AMT,SLMN_CODE,SLMN_NAME\n";

$query = $conn->prepare($String_Sql);
$query->execute();

$clean_str = function($val) {
    return str_replace(",", "^", (string)($val ?? ''));
};

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
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
        $clean_str((float)($row['TRD_QTY'] ?? 0)),
        $clean_str((float)($row['TRD_Q_FREE'] ?? 0)),
        $clean_str((float)($row['TRD_U_PRC'] ?? 0)),
        $clean_str((float)($row['TRD_TDSC_KEYINV'] ?? 0)),
        $clean_str((float)($row['TRD_B_AMT'] ?? 0)),
        $clean_str($row['SLMN_CODE'] ?? ''),
        $clean_str($row['SLMN_NAME'] ?? '')
    ];
    $data .= implode(",", $line) . "\n";
}

$data = iconv("utf-8", "windows-874//IGNORE", $data);
echo $data;

exit();