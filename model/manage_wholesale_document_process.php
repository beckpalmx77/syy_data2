<?php
session_start();
error_reporting(0);

include(__DIR__ . '/../config/connect_db.php');
include(__DIR__ . '/../config/lang.php');
include(__DIR__ . '/../util/record_util.php');

$action = $_POST["action"] ?? $_GET["action"] ?? '';

if ($action === 'GET_DATA') {
    $id = $_POST["id"] ?? $_GET["id"] ?? 0;
    $return_arr = array();

    $sql_get = "SELECT * FROM ims_product_sale_syy_ks WHERE id = :id OR DI_KEY = :dikey LIMIT 1";
    $statement = $conn->prepare($sql_get);
    $statement->bindValue(':id', (int)$id, PDO::PARAM_INT);
    $statement->bindValue(':dikey', (int)$id, PDO::PARAM_INT);
    $statement->execute();
    $results = $statement->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $result) {
        $return_arr[] = $result;
    }

    echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
    exit();
}

if ($action === 'GET_WHOLESALE_DOCUMENT' || $action === 'GET_DATA_LIST') {

    ## DataTables Read values
    $draw = intval($_POST['draw'] ?? 1);
    $row = intval($_POST['start'] ?? 0);
    $rowperpage = intval($_POST['length'] ?? 25);
    if ($rowperpage <= 0) {
        $rowperpage = 25;
    }

    $columnIndex = intval($_POST['order'][0]['column'] ?? 0);
    $columnSortOrder = strtolower($_POST['order'][0]['dir'] ?? 'desc');
    if ($columnSortOrder !== 'asc') {
        $columnSortOrder = 'desc';
    }

    $searchValue = trim($_POST['search']['value'] ?? '');

    // Column mapping for ordering
    $columns = array(
        0 => 'id',
        1 => 'DI_KEY',
        2 => 'DI_REF',
        3 => 'DI_DATE',
        4 => 'DI_TIME_CHK',
        5 => 'AR_NAME',
        6 => 'DEPT_CODE',
        7 => 'ICCAT_CODE',
        8 => 'ICCAT_NAME',
        9 => 'SKU_NAME',
        10 => 'BRN_NAME',
        11 => 'TRD_QTY',
        12 => 'TRD_Q_FREE',
        13 => 'TRD_U_PRC',
        14 => 'TRD_TDSC_KEYINV',
        15 => 'TRD_B_AMT',
        16 => 'SLMN_NAME'
    );

    $columnName = $columns[$columnIndex] ?? 'DI_KEY';

    // Parse input filters
    $doc_date_start_input = $_POST['doc_date_start'] ?? $_GET['doc_date_start'] ?? date('d-m-Y');
    $doc_date_to_input = $_POST['doc_date_to'] ?? $_GET['doc_date_to'] ?? date('d-m-Y');

    function parse_date_formats($date_str) {
        $date_str = trim((string)$date_str);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_str)) {
            $parts = explode('-', $date_str);
            return ['ymd' => $date_str, 'dmy' => $parts[2] . '/' . $parts[1] . '/' . $parts[0]];
        } elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date_str)) {
            $parts = explode('-', $date_str);
            return ['ymd' => $parts[2] . '-' . $parts[1] . '-' . $parts[0], 'dmy' => $parts[0] . '/' . $parts[1] . '/' . $parts[2]];
        } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date_str)) {
            $parts = explode('/', $date_str);
            return ['ymd' => $parts[2] . '-' . $parts[1] . '-' . $parts[0], 'dmy' => $date_str];
        }
        return ['ymd' => date('Y-m-d'), 'dmy' => date('d/m/Y')];
    }

    $doc_date_start_input = $_POST['doc_date_start'] ?? $_GET['doc_date_start'] ?? null;
    $doc_date_to_input = $_POST['doc_date_to'] ?? $_GET['doc_date_to'] ?? null;

    $where_clauses = [];
    $params = [];

    // Date Range Filter (Optional)
    if (!empty($doc_date_start_input) && !empty($doc_date_to_input)) {
        $start_info = parse_date_formats($doc_date_start_input);
        $to_info = parse_date_formats($doc_date_to_input);

        if ($start_info['dmy'] === $to_info['dmy']) {
            $where_clauses[] = "DI_DATE = :single_date";
            $params[':single_date'] = $start_info['dmy'];
        } else {
            $st = strtotime($start_info['ymd']);
            $et = strtotime($to_info['ymd']);
            $diff_days = round(($et - $st) / 86400);

            if ($st && $et && $diff_days >= 0 && $diff_days <= 90) {
                $dates_list = [];
                for ($curr = $st; $curr <= $et; $curr += 86400) {
                    $dates_list[] = date('d/m/Y', $curr);
                }
                $in_placeholders = [];
                foreach ($dates_list as $idx => $dt_val) {
                    $pname = ":dt_" . $idx;
                    $in_placeholders[] = $pname;
                    $params[$pname] = $dt_val;
                }
                $where_clauses[] = "DI_DATE IN (" . implode(',', $in_placeholders) . ")";
            } else {
                $where_clauses[] = "STR_TO_DATE(DI_DATE, '%d/%m/%Y') BETWEEN :ymd_start AND :ymd_to";
                $params[':ymd_start'] = $start_info['ymd'];
                $params[':ymd_to'] = $to_info['ymd'];
            }
        }
    }

    // Salesman Filter
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

    if (!empty($slmn_list)) {
        $slmn_conds = [];
        foreach ($slmn_list as $sidx => $slmn_val) {
            $spname = ":slmn_" . $sidx;
            $slmn_conds[] = "(SLMN_NAME LIKE " . $spname . " OR SLMN_CODE LIKE " . $spname . ")";
            $params[$spname] = "%" . $slmn_val . "%";
        }
        $where_clauses[] = "(" . implode(" OR ", $slmn_conds) . ")";
    }

    // Category Filter
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

    if (!empty($iccat_list)) {
        $iccat_conds = [];
        foreach ($iccat_list as $cidx => $cat_val) {
            $cpname = ":cat_" . $cidx;
            $iccat_conds[] = "(ICCAT_CODE LIKE " . $cpname . " OR ICCAT_NAME LIKE " . $cpname . ")";
            $params[$cpname] = "%" . $cat_val . "%";
        }
        $where_clauses[] = "(" . implode(" OR ", $iccat_conds) . ")";
    }

    // DataTables Global Search Filter
    if ($searchValue !== '') {
        $where_clauses[] = "(DI_REF LIKE :search OR AR_NAME LIKE :search OR SKU_NAME LIKE :search OR SLMN_NAME LIKE :search OR ICCAT_NAME LIKE :search OR BRN_NAME LIKE :search)";
        $params[':search'] = "%" . $searchValue . "%";
    }

    $where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

    // 1. Total Records Count (without filter)
    $stmt_tot = $conn->query("SELECT COUNT(*) AS allcount FROM ims_product_sale_syy_ks");
    $totalRecords = (int)($stmt_tot->fetchColumn() ?? 0);

    // 2. Filtered Records Count
    $sql_count = "SELECT COUNT(*) AS allcount FROM ims_product_sale_syy_ks " . $where_sql;
    $stmt_filt = $conn->prepare($sql_count);
    foreach ($params as $key => $val) {
        $stmt_filt->bindValue($key, $val);
    }
    $stmt_filt->execute();
    $totalRecordwithFilter = (int)($stmt_filt->fetchColumn() ?? 0);

    // 3. Fetch Data Rows
    $sql_data = "SELECT DI_KEY, DI_REF, DI_DATE, DI_TIME_CHK, DI_ACTIVE, AR_NAME, DEPT_CODE, DEPT_THAIDESC,
                        ICCAT_CODE, ICCAT_NAME, SKU_NAME, SKU_E_NAME, BRN_NAME, TRD_QTY, TRD_Q_FREE,
                        TRD_U_PRC, TRD_TDSC_KEYINV, TRD_B_AMT, SLMN_CODE, SLMN_NAME, DT_DOCCODE
                 FROM ims_product_sale_syy_ks "
                 . $where_sql
                 . " ORDER BY " . $columnName . " " . $columnSortOrder
                 . " LIMIT :offset, :limit";

    $stmt_data = $conn->prepare($sql_data);
    foreach ($params as $key => $val) {
        $stmt_data->bindValue($key, $val);
    }
    $stmt_data->bindValue(':offset', $row, PDO::PARAM_INT);
    $stmt_data->bindValue(':limit', $rowperpage, PDO::PARAM_INT);
    $stmt_data->execute();
    $empRecords = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

    $data = array();
    foreach ($empRecords as $r) {
        $data[] = array(
            "id"              => (int)($r['DI_KEY'] ?? 0),
            "DI_KEY"          => (int)($r['DI_KEY'] ?? 0),
            "DI_REF"          => (string)($r['DI_REF'] ?? ''),
            "DI_DATE"         => (string)($r['DI_DATE'] ?? ''),
            "DI_TIME_CHK"     => (string)($r['DI_TIME_CHK'] ?? ''),
            "DI_ACTIVE"       => (int)($r['DI_ACTIVE'] ?? 0),
            "AR_NAME"         => (string)($r['AR_NAME'] ?? ''),
            "DEPT_CODE"       => (string)($r['DEPT_CODE'] ?? ''),
            "DEPT_THAIDESC"   => (string)($r['DEPT_THAIDESC'] ?? ''),
            "ICCAT_CODE"      => (string)($r['ICCAT_CODE'] ?? ''),
            "ICCAT_NAME"      => (string)($r['ICCAT_NAME'] ?? ''),
            "SKU_NAME"        => (string)($r['SKU_NAME'] ?? ''),
            "SKU_E_NAME"      => (string)($r['SKU_E_NAME'] ?? ''),
            "BRN_NAME"        => (string)($r['BRN_NAME'] ?? ''),
            "TRD_QTY"         => (float)($r['TRD_QTY'] ?? 0),
            "TRD_Q_FREE"      => (float)($r['TRD_Q_FREE'] ?? 0),
            "TRD_U_PRC"       => (float)($r['TRD_U_PRC'] ?? 0),
            "TRD_TDSC_KEYINV" => (float)($r['TRD_TDSC_KEYINV'] ?? 0),
            "TRD_B_AMT"       => (float)($r['TRD_B_AMT'] ?? 0),
            "SLMN_CODE"       => (string)($r['SLMN_CODE'] ?? ''),
            "SLMN_NAME"       => (string)($r['SLMN_NAME'] ?? ''),
            "DT_DOCCODE"      => (string)($r['DT_DOCCODE'] ?? '')
        );
    }

    $response = array(
        "draw"                 => intval($draw),
        "iTotalRecords"        => $totalRecords,
        "iTotalDisplayRecords" => $totalRecordwithFilter,
        "aaData"               => $data
    );

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}
