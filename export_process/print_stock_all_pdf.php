<?php
// =========================================================
// 1. ตั้งค่าพื้นฐาน และ Database
// =========================================================
require_once '../vendor/autoload.php';

// เพิ่มเวลา Time Limit และ Memory Limit
set_time_limit(300);
ini_set('memory_limit', '512M');

date_default_timezone_set('Asia/Bangkok');
include('../config/connect_sqlserver.php');

// เตรียมไฟล์สำหรับ Debug SQL
$debug_file = __DIR__ . '/debug_sql_pdf.txt';
file_put_contents($debug_file, "--- Log Start: " . date('Y-m-d H:i:s') . " ---\n");

// =========================================================
// 2. Logic การเตรียม Query (Filter Data)
// =========================================================

function quoteArray($arr, $conn)
{
    return array_map(function ($v) use ($conn) {
        return $conn->quote(trim($v));
    }, $arr);
}

$where = [];
$filter_desc_arr = [];

// --- รับค่า Filter สต็อก (gt_0, eq_0, all) ---
$stock_filter = $_POST['stock_filter'] ?? 'all';
$having_sql = "";
$filter_stock_desc = "ทั้งหมด";

if ($stock_filter === 'gt_0') {
    $having_sql = " HAVING SUM(SKM.SKM_QTY) > 0 ";
    $filter_stock_desc = "ยอดคงเหลือ > 0";
} elseif ($stock_filter === 'eq_0') {
    $having_sql = " HAVING SUM(SKM.SKM_QTY) = 0 ";
    $filter_stock_desc = "ยอดคงเหลือ = 0";
}

// WH_CODE
if (!empty($_POST['wh_codes'])) {
    $in = implode(',', quoteArray($_POST['wh_codes'], $conn_sqlsvr));
    $where[] = "WH.WH_CODE IN ($in)";
    $filter_desc_arr[] = "คลัง: " . implode(',', $_POST['wh_codes']);
}

// ICCAT
if (!empty($_POST['icc_codes'])) {
    $in = implode(',', quoteArray($_POST['icc_codes'], $conn_sqlsvr));
    $where[] = "ICCAT.ICCAT_CODE IN ($in)";
    $filter_desc_arr[] = "ระบุหมวดสินค้า";
}

// BRAND
if (!empty($_POST['brn_codes'])) {
    $in = implode(',', quoteArray($_POST['brn_codes'], $conn_sqlsvr));
    $where[] = "BRAND.BRN_CODE IN ($in)";
}

// SKU Enable Check
$where[] = "SM.SKU_ENABLE = 'Y'";

$WHERE_SQL = (count($where) > 0) ? "WHERE " . implode(' AND ', $where) : "";
$filter_display = empty($filter_desc_arr) ? "ทั้งหมด" : implode(', ', $filter_desc_arr);

// =========================================================
// 3. Query Data
// =========================================================

$String_Sql = "
SELECT 
    SM.SKU_CODE,
    SM.SKU_NAME,    
    WH.WH_CODE,
    WH.WH_NAME,
    WL.WL_CODE,
    UQ.UTQ_NAME AS UNIT_NAME,
    SUM(SKM.SKM_QTY) AS SUM_QTY
FROM 
    dbo.SKUMASTER SM WITH (NOLOCK)
    INNER JOIN dbo.ICCAT WITH (NOLOCK) ON SM.SKU_ICCAT = ICCAT.ICCAT_KEY
    INNER JOIN dbo.BRAND WITH (NOLOCK) ON SM.SKU_BRN = BRAND.BRN_KEY
    INNER JOIN dbo.UOFQTY UQ WITH (NOLOCK) ON SM.SKU_S_UTQ = UQ.UTQ_KEY
    INNER JOIN dbo.SKUMOVE SKM WITH (NOLOCK) ON SM.SKU_KEY = SKM.SKM_SKU
    INNER JOIN dbo.WARELOCATION WL WITH (NOLOCK) ON SKM.SKM_WL = WL.WL_KEY
    INNER JOIN dbo.WAREHOUSE WH WITH (NOLOCK) ON WL.WL_WH = WH.WH_KEY
    INNER JOIN dbo.DOCINFO WITH (NOLOCK) ON SKM.SKM_DI = DOCINFO.DI_KEY
$WHERE_SQL
GROUP BY 
    SM.SKU_CODE, SM.SKU_NAME, WH.WH_CODE, WH.WH_NAME, WL.WL_CODE, UQ.UTQ_NAME
$having_sql
ORDER BY 
    WH.WH_CODE ASC, WL.WL_CODE ASC, SM.SKU_CODE ASC
";

// Debug SQL ลงไฟล์
file_put_contents($debug_file, "\n[MAIN QUERY]\n" . $String_Sql . "\n", FILE_APPEND);

$query = $conn_sqlsvr->prepare($String_Sql);
$query->execute();
$results = $query->fetchAll(PDO::FETCH_ASSOC);

// =========================================================
// 4. mPDF Setup
// =========================================================

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4-L',
    'margin_top' => 45,
    'default_font' => 'prompt',
    'fontDir' => array_merge((new Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'], ['../fonts']),
    'fontdata' => (new Mpdf\Config\FontVariables())->getDefaults()['fontdata'] + [
            'prompt' => [
                'R' => 'Prompt-Regular.ttf',
                'B' => 'Prompt-Bold.ttf',
            ]
        ],
]);

$css = '
<style>
    body { font-family: "prompt"; }
    table { width: 100%; border-collapse: collapse; }
    th { border: 1px solid #333; background-color: #f2f2f2; padding: 5px; text-align: center; }
    td { border-bottom: 1px dotted #ccc; border-left: 1px solid #eee; border-right: 1px solid #eee; padding: 5px; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .group-header td { background-color: #e3f2fd; font-weight: bold; border-top: 1px solid #999; }
    .group-total td { background-color: #fff8e1; font-weight: bold; border-top: 1px solid #333; }
    .grand-total td { background-color: #333; color: #fff; font-weight: bold; }
</style>';

$thaiDate = date('d/m/') . (date('Y') + 543);
$headerHtml = '
<div style="text-align:center; font-weight:bold;">
    <div style="float:left; width:30%; text-align:left;">บริษัท สงวนยางยนต์ชุมพร จำกัด</div>
    <div style="float:right; width:30%; text-align:right;">พิมพ์เมื่อ: ' . $thaiDate . ' ' . date('H:i:s') . '</div>
    <div style="clear:both; font-size:16pt;">รายงานตรวจสอบยอดสินค้าคงเหลือ</div>
    <div style="text-align:left; font-size:10pt; margin-top:10px;">
        เงื่อนไข: ' . htmlspecialchars($filter_display) . ' | สต็อก: ' . $filter_stock_desc . '
        <span style="float:right;">หน้า {PAGENO} / {nbpg}</span>
    </div>
</div>';

$mpdf->SetHTMLHeader($headerHtml);

// =========================================================
// 5. Table Generation
// =========================================================

$html = '<table>
    <thead>
        <tr>
            <th width="15%">รหัสสินค้า</th>
            <th width="40%">ชื่อสินค้า</th>
            <th width="10%">คลัง</th>          
            <th width="15%">ตำแหน่งเก็บ</th>   
            <th width="10%">หน่วย</th>
            <th width="10%">คงเหลือ</th>
        </tr>
    </thead>
    <tbody>';

$current_wh = null;
$sub_qty = 0; $grand_qty = 0;
$hasData = false;

foreach ($results as $row) {
    $hasData = true;

    if ($row['WH_CODE'] !== $current_wh) {
        if ($current_wh !== null) {
            $html .= '<tr class="group-total"><td colspan="5" class="text-right">รวมคลัง ' . htmlspecialchars($current_wh) . '</td><td class="text-right">' . number_format($sub_qty, 2) . '</td></tr>';
        }
        $current_wh = $row['WH_CODE'];
        $sub_qty = 0;
        $html .= '<tr class="group-header"><td colspan="6">คลังสินค้า: ' . htmlspecialchars($current_wh) . ' - ' . htmlspecialchars($row['WH_NAME']) . '</td></tr>';
    }

    $html .= '<tr>
        <td>' . htmlspecialchars($row['SKU_CODE']) . '</td>
        <td>' . htmlspecialchars($row['SKU_NAME']) . '</td>
        <td class="text-center">' . htmlspecialchars($row['WH_CODE']) . '</td>
        <td class="text-center">' . htmlspecialchars($row['WL_CODE']) . '</td>
        <td class="text-center">' . htmlspecialchars($row['UNIT_NAME']) . '</td>
        <td class="text-right">' . number_format($row['SUM_QTY'], 2) . '</td>
    </tr>';

    $sub_qty += $row['SUM_QTY'];
    $grand_qty += $row['SUM_QTY'];
}

if ($current_wh !== null) {
    $html .= '<tr class="group-total"><td colspan="5" class="text-right">รวมคลัง ' . htmlspecialchars($current_wh) . '</td><td class="text-right">' . number_format($sub_qty, 2) . '</td></tr>';
}

if (!$hasData) {
    $html .= '<tr><td colspan="6" class="text-center" style="padding:20px;">-- ไม่พบข้อมูลตามเงื่อนไข --</td></tr>';
}

$html .= '</tbody>
    <tfoot>
        <tr class="grand-total">
            <td colspan="5" class="text-right">รวมสุทธิทุกคลัง</td>
            <td class="text-right">' . number_format($grand_qty, 2) . '</td>
        </tr>
    </tfoot>
</table>';

$mpdf->WriteHTML($css);
$mpdf->WriteHTML($html);
$mpdf->Output("Stock_Report_" . date('Ymd_His') . ".pdf", 'I');
exit();