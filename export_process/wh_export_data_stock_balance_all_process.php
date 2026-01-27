<?php
date_default_timezone_set('Asia/Bangkok');
include('../config/connect_sqlserver.php');

// =========================================================
// 1. รับค่า Filter
// =========================================================

// ฟังก์ชัน Helper
function quoteArray($arr, $conn) {
    return array_map(function ($v) use ($conn) {
        return $conn->quote(trim($v));
    }, $arr);
}

// ตัวแปรสำหรับชื่อไฟล์
$date_suffix = date('Ymd');
$as_of_date_show = date('d/m/Y'); // ใช้วันปัจจุบันเป็น Default

// --- เตรียมเงื่อนไข SQL (Dynamic SQL Parts) ---
$sql_condition = "";

// 1. Date Range: น้อยกว่าหรือเท่ากับวันที่สิ้นสุด (Stock Balance As Of)
if (!empty($_POST['end_date'])) {
    $end = $conn_sqlsvr->quote($_POST['end_date']);

    // เพิ่มเงื่อนไขวันที่เข้าไป
    $sql_condition .= " AND DOCINFO.DI_DATE <= $end ";

    // ตั้งชื่อไฟล์และตัวแปรสำหรับแสดงใน CSV
    $date_suffix = $_POST['end_date'];
    if(!empty($_POST['start_date'])) {
        $date_suffix = $_POST['start_date'] . "_" . $_POST['end_date'];
    }
    $as_of_date_show = date_format(date_create($_POST['end_date']), "d/m/Y");
}

// 2. ICCAT: จากหน้าจอ
if (!empty($_POST['icc_codes'])) {
    $in = implode(',', quoteArray($_POST['icc_codes'], $conn_sqlsvr));
    $sql_condition .= " AND ICCAT.ICCAT_CODE IN ($in) ";
} else {
    // กรณีไม่ได้เลือก (หรือจะใช้ Default list ก็ใส่ตรงนี้ได้)
    // แต่ตาม Logic หน้าจอคือเลือกทั้งหมดอยู่แล้ว
}


// =========================================================
// 2. ตั้งค่า Header CSV
// =========================================================
$filename = "Stock_Balance_K3_" . $date_suffix . "_" . date('His') . ".csv";

header('Content-type: text/csv; charset=UTF-8');
header('Content-Encoding: UTF-8');
header("Content-Disposition: attachment; filename=\"$filename\"");


// =========================================================
// 3. SQL Query (ใช้ SQL ตามที่คุณระบุเท่านั้น)
// =========================================================
// หมายเหตุ: ตัด Hardcode List เดิมออก แล้วใส่ $sql_condition แทน
$String_Sql = "
SELECT 
    SM.SKU_CODE,
    SM.SKU_NAME,    
    WH.WH_CODE,
    WH.WH_NAME,
    WL.WL_CODE,
    ICCAT_CODE,
    UQ.UTQ_NAME AS UNIT_NAME,
    SUM(SKM.SKM_QTY) AS SUM_QTY
FROM 
    dbo.SKUMASTER SM WITH (NOLOCK)
    INNER JOIN dbo.ICCAT WITH (NOLOCK) ON SM.SKU_ICCAT = ICCAT.ICCAT_KEY
    INNER JOIN dbo.ICDEPT WITH (NOLOCK) ON SM.SKU_ICDEPT = ICDEPT.ICDEPT_KEY
    INNER JOIN dbo.BRAND WITH (NOLOCK) ON SM.SKU_BRN = BRAND.BRN_KEY
    INNER JOIN dbo.UOFQTY UQ WITH (NOLOCK) ON SM.SKU_S_UTQ = UQ.UTQ_KEY
    INNER JOIN dbo.SKUMOVE SKM WITH (NOLOCK) ON SM.SKU_KEY = SKM.SKM_SKU
    INNER JOIN dbo.WARELOCATION WL WITH (NOLOCK) ON SKM.SKM_WL = WL.WL_KEY
    INNER JOIN dbo.WAREHOUSE WH WITH (NOLOCK) ON WL.WL_WH = WH.WH_KEY
    INNER JOIN dbo.DOCINFO WITH (NOLOCK) ON SKM.SKM_DI = DOCINFO.DI_KEY
    INNER JOIN dbo.DOCTYPE WITH (NOLOCK) ON DOCINFO.DI_DT = DOCTYPE.DT_KEY
WHERE WL.WL_CODE = 'K3' AND SM.SKU_ENABLE = 'Y'
    $sql_condition
GROUP BY 
    SM.SKU_CODE,
    SM.SKU_NAME,
    WH.WH_CODE,
    WH.WH_NAME,
    WL.WL_CODE,
    UQ.UTQ_NAME,
    ICCAT_CODE
HAVING 
    SUM(SKM.SKM_QTY) > 0
ORDER BY 
    WH.WH_CODE ASC,
    WL.WL_CODE ASC,
    SM.SKU_CODE ASC
";

// =========================================================
// 4. ดึงข้อมูลและสร้าง Content CSV
// =========================================================
$query = $conn_sqlsvr->prepare($String_Sql);
$query->execute();

// หัวตาราง
$data = "ยอดคงเหลือ ณ วันที่,รหัสสินค้า,สินค้า,คลัง,ที่เก็บ,หน่วยนับ,จำนวน\n";

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    // CSV Content
    $sku_code = str_replace(",", "^", $row['SKU_CODE']);
    $sku_name = str_replace(",", "^", $row['SKU_NAME']);
    $wh_code  = str_replace(",", "^", $row['WH_CODE']);
    $wl_code  = str_replace(",", "^", $row['WL_CODE']);
    $utq_name = str_replace(",", "^", $row['UNIT_NAME']); // ใช้ Alias UNIT_NAME ตาม SQL
    $qty      = $row['SUM_QTY'];

    // วันที่ในที่นี้คือ "ยอดคงเหลือ ณ วันที่" (End Date)
    $data .= "$as_of_date_show,$sku_code,$sku_name,$wh_code,$wl_code,$utq_name,$qty\n";
}

// =========================================================
// 5. Output
// =========================================================
echo iconv("utf-8", "windows-874//IGNORE", $data);
exit();