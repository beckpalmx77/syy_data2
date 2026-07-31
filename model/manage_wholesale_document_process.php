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

    $sql_get = "SELECT * FROM ims_product_sale_syy_ks WHERE id = :id LIMIT 1";
    $stmt = $conn->prepare($sql_get);
    $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $result) {
        $return_arr[] = array(
            "id" => $result['id'],
            "DI_KEY" => $result['DI_KEY'],
            "DI_REF" => $result['DI_REF'],
            "DI_DATE" => $result['DI_DATE'],
            "DI_MONTH" => $result['DI_MONTH'] ?? '',
            "DI_MONTH_NAME" => $result['DI_MONTH_NAME'] ?? '',
            "DI_YEAR" => $result['DI_YEAR'] ?? '',
            "AR_CODE" => $result['AR_CODE'] ?? '',
            "AR_NAME" => $result['AR_NAME'] ?? '',
            "SLMN_SLT" => $result['SLMN_SLT'] ?? '',
            "SLMN_CODE" => $result['SLMN_CODE'] ?? '',
            "SLMN_NAME" => $result['SLMN_NAME'] ?? '',
            "SKU_CODE" => $result['SKU_CODE'] ?? $result['ICCAT_CODE'] ?? '',
            "SKU_NAME" => $result['SKU_NAME'] ?? '',
            "SKU_CAT" => $result['SKU_CAT'] ?? $result['ICCAT_NAME'] ?? '',
            "ICCAT_CODE" => $result['ICCAT_CODE'] ?? '',
            "ICCAT_NAME" => $result['ICCAT_NAME'] ?? '',
            "TRD_QTY" => number_format((float)($result['TRD_QTY'] ?? 0), 2, '.', ''),
            "TRD_U_PRC" => number_format((float)($result['TRD_U_PRC'] ?? 0), 2, '.', ''),
            "TRD_DSC_KEYINV" => $result['TRD_TDSC_KEYINV'] ?? 0,
            "TRD_B_SELL" => number_format((float)($result['TRD_B_SELL'] ?? 0), 2, '.', ''),
            "TRD_B_VAT" => number_format((float)($result['TRD_B_VAT'] ?? 0), 2, '.', ''),
            "TRD_G_KEYIN" => number_format((float)($result['TRD_G_KEYIN'] ?? $result['TRD_B_AMT'] ?? 0), 2, '.', ''),
            "WL_CODE" => $result['WL_CODE'] ?? '',
            "BRANCH" => $result['BRANCH'] ?? $result['DEPT_THAIDESC'] ?? '',
            "DT_DOCCODE" => $result['DT_DOCCODE'] ?? '',
            "TRD_SEQ" => $result['TRD_SEQ'] ?? 0,
            "BRN_CODE" => $result['BRN_CODE'] ?? '',
            "BRN_NAME" => $result['BRN_NAME'] ?? '',
            "DI_TIME_CHK" => $result['DI_TIME_CHK'] ?? '',
            "PGROUP" => $result['PGROUP'] ?? '',
            "TRD_Q_FREE" => $result['TRD_Q_FREE'] ?? 0,
            "DI_ACTIVE" => $result['DI_ACTIVE'] ?? 0
        );
    }

    echo json_encode($return_arr, JSON_UNESCAPED_UNICODE);
    exit();
}

if ($action === 'GET_DATA_SALE_SAC' || $action === 'GET_DATA_SALE_KS' || $action === 'GET_WHOLESALE_DOCUMENT') {
    ## Read value
    $draw = intval($_POST['draw'] ?? 1);
    $row = intval($_POST['start'] ?? 0);
    $rowperpage = intval($_POST['length'] ?? 25);
    if ($rowperpage <= 0) {
        $rowperpage = 25;
    }

    $columnIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : null;
    $columnName = isset($_POST['columns'][$columnIndex]['data']) ? $_POST['columns'][$columnIndex]['data'] : 'id';
    $columnSortOrder = isset($_POST['order'][0]['dir']) ? strtoupper($_POST['order'][0]['dir']) : 'DESC';
    if ($columnSortOrder !== 'ASC') {
        $columnSortOrder = 'DESC';
    }

    $searchValue = trim($_POST['search']['value'] ?? '');

    $searchArray = array();

    ## Search
    $searchQuery = " ";
    if ($searchValue != '') {
        $searchQuery = " AND (DI_DATE LIKE :DI_DATE OR DI_REF LIKE :DI_REF OR AR_CODE LIKE :AR_CODE 
                        OR AR_NAME LIKE :AR_NAME OR SKU_CODE LIKE :SKU_CODE OR SKU_NAME LIKE :SKU_NAME 
                        OR SLMN_NAME LIKE :SLMN_NAME OR DEPT_THAIDESC LIKE :BRANCH OR BRANCH LIKE :BRANCH2) ";
        $searchArray = array(
            'DI_DATE' => "%$searchValue%",
            'DI_REF' => "%$searchValue%",
            'AR_CODE' => "%$searchValue%",
            'AR_NAME' => "%$searchValue%",
            'SKU_CODE' => "%$searchValue%",
            'SKU_NAME' => "%$searchValue%",
            'SLMN_NAME' => "%$searchValue%",
            'BRANCH' => "%$searchValue%",
            'BRANCH2' => "%$searchValue%",
        );
    }

    ## Total number of records without filtering
    $stmt = $conn->prepare("SELECT COUNT(*) AS allcount FROM ims_product_sale_syy_ks WHERE 1");
    $stmt->execute();
    $records = $stmt->fetch();
    $totalRecords = (int)($records['allcount'] ?? 0);

    ## Total number of records with filtering
    $stmt = $conn->prepare("SELECT COUNT(*) AS allcount FROM ims_product_sale_syy_ks WHERE 1 " . $searchQuery);
    $stmt->execute($searchArray);
    $records = $stmt->fetch();
    $totalRecordwithFilter = (int)($records['allcount'] ?? 0);

    ## Map column index for sorting if available
    $validColumns = array(
        "id", "DI_KEY", "DI_DATE", "DI_TIME_CHK", "DI_REF", "AR_CODE", "AR_NAME", "SLMN_NAME", 
        "SKU_CODE", "SKU_NAME", "TRD_QTY", "TRD_U_PRC", "TRD_G_KEYIN", "TRD_B_AMT", "TRD_B_SELL", "TRD_B_VAT", "BRANCH", "DEPT_THAIDESC"
    );
    if ($columnIndex === null || !in_array($columnName, $validColumns)) {
        $columnName = "id";
        $columnSortOrder = "DESC";
    }

    ## Fetch records (Optimized with Deferred Join for high performance pagination)
    $sql_fetch = "SELECT main.* FROM ims_product_sale_syy_ks main 
                  JOIN (
                      SELECT id FROM ims_product_sale_syy_ks WHERE 1 " . $searchQuery . " 
                      ORDER BY " . $columnName . " " . $columnSortOrder . " 
                      LIMIT :offset, :limit
                  ) AS sub ON main.id = sub.id
                  ORDER BY main." . $columnName . " " . $columnSortOrder;

    $stmt = $conn->prepare($sql_fetch);

    // Bind values
    foreach ($searchArray as $key => $search) {
        $stmt->bindValue(':' . $key, $search, PDO::PARAM_STR);
    }

    $stmt->bindValue(':offset', (int)$row, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$rowperpage, PDO::PARAM_INT);
    $stmt->execute();
    $empRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $data = array();

    foreach ($empRecords as $r) {
        $branch_display = !empty($r['DEPT_THAIDESC']) ? $r['DEPT_THAIDESC'] : (!empty($r['BRANCH']) ? $r['BRANCH'] : '-');
        $sku_code_display = !empty($r['SKU_CODE']) ? $r['SKU_CODE'] : (!empty($r['ICCAT_CODE']) ? $r['ICCAT_CODE'] : '-');
        $amt_display = isset($r['TRD_G_KEYIN']) && (float)$r['TRD_G_KEYIN'] != 0 ? (float)$r['TRD_G_KEYIN'] : (float)($r['TRD_B_AMT'] ?? 0);

        $data[] = array(
            "id" => $r['id'],
            "DI_KEY" => $r['DI_KEY'],
            "DI_DATE" => $r['DI_DATE'],
            "DI_TIME_CHK" => $r['DI_TIME_CHK'],
            "DI_REF" => $r['DI_REF'],
            "AR_CODE" => $r['AR_CODE'] ?? '-',
            "AR_NAME" => $r['AR_NAME'],
            "SLMN_NAME" => $r['SLMN_NAME'],
            "SKU_CODE" => $sku_code_display,
            "SKU_NAME" => $r['SKU_NAME'],
            "TRD_QTY" => number_format((float)$r['TRD_QTY'], 2),
            "TRD_U_PRC" => number_format((float)$r['TRD_U_PRC'], 2),
            "TRD_G_KEYIN" => number_format($amt_display, 2),
            "TRD_B_SELL" => number_format((float)$r['TRD_B_SELL'], 2),
            "TRD_B_VAT" => number_format((float)$r['TRD_B_VAT'], 2),
            "BRANCH" => $branch_display,
            "update" => "<button type='button' name='info' id='" . $r['id'] . "' class='btn btn-info btn-xs info' data-toggle='tooltip' title='Info'>Info <i class='fa fa-info-circle'></i></button>",
            "delete" => "<button type='button' name='delete' id='" . $r['id'] . "' class='btn btn-danger btn-xs delete' data-toggle='tooltip' title='Delete'>Delete</button>"
        );
    }

    ## Response Return Value
    $response = array(
        "draw" => intval($draw),
        "iTotalRecords" => $totalRecords,
        "iTotalDisplayRecords" => $totalRecordwithFilter,
        "aaData" => $data
    );

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}
