<?php
include('includes/Header.php');
include_once('config/connect_sqlserver.php');

if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php");
} else {
    $menu_title = isset($_GET['m']) ? htmlspecialchars(urldecode($_GET['m']), ENT_QUOTES, 'UTF-8') : 'รายงาน';
    $sub_title = isset($_GET['s']) ? htmlspecialchars(urldecode($_GET['s']), ENT_QUOTES, 'UTF-8') : 'สรุปยอดขายประจำวัน ตามพนักงานขาย';

    // Fetch Active Salesmen (SLMN_ENABLE = 'Y')
    $salesmen_list = [];
    try {
        if (isset($conn_sqlsvr)) {
            $stmt_slmn = $conn_sqlsvr->query("SELECT SLMN_CODE, SLMN_NAME FROM SALESMAN WHERE SLMN_ENABLE = 'Y' ORDER BY SLMN_NAME");
            if ($stmt_slmn) {
                $salesmen_list = $stmt_slmn->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    } catch (Exception $e) {}

    // Fetch Product Categories starting with/containing 'ยาง', 'น้ำมัน', 'กระทะล้อ'
    $iccat_list_options = [];
    try {
        if (isset($conn_sqlsvr)) {
            $sql_iccat_select = "SELECT ICCAT_CODE, ICCAT_NAME FROM ICCAT 
                                 WHERE ICCAT_NAME LIKE 'ยาง%' 
                                    OR ICCAT_NAME LIKE '%น้ำมัน%' 
                                    OR ICCAT_NAME LIKE 'กระทะล้อ%' 
                                    OR ICCAT_NAME LIKE '%กระทะ%'
                                 ORDER BY ICCAT_CODE";
            $stmt_iccat = $conn_sqlsvr->query($sql_iccat_select);
            if ($stmt_iccat) {
                $iccat_list_options = $stmt_iccat->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    } catch (Exception $e) {}
    ?>

    <!DOCTYPE html>
    <html lang="th">

    <head>
        <link href="js/select2/dist/css/select2.min.css" rel="stylesheet" type="text/css">
        <style>
            .select2-container--default .select2-selection--multiple {
                border-color: #d1d3e2;
                min-height: 40px;
                border-radius: 0.35rem;
            }
            .kpi-card {
                border-left: 4px solid;
                border-radius: 8px;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            .kpi-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 15px rgba(0,0,0,0.1);
            }
            .kpi-primary { border-left-color: #4e73df; }
            .kpi-success { border-left-color: #1cc88a; }
            .kpi-info { border-left-color: #36b9cc; }
            .kpi-warning { border-left-color: #f6c23e; }
            .chart-card {
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            .loading-overlay {
                display: none;
                position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(255, 255, 255, 0.85);
                z-index: 99;
                align-items: center;
                justify-content: center;
                border-radius: 12px;
            }
        </style>
    </head>

    <body id="page-top">
    <div id="wrapper">
        <?php include('includes/Side-Bar.php'); ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include('includes/Top-Bar.php'); ?>

                <!-- Container Fluid-->
                <div class="container-fluid" id="container-wrapper">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-chart-line text-primary mr-2"></i><?php echo $sub_title; ?></h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo $_SESSION['dashboard_page']; ?>">Home</a></li>
                            <li class="breadcrumb-item"><?php echo $menu_title; ?></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo $sub_title; ?></li>
                        </ol>
                    </div>

                    <!-- Filter Panel -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header py-3 bg-gradient-primary text-white d-flex align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold"><i class="fas fa-filter mr-2"></i>ตัวกรองค้นหาข้อมูล (Sales & Category Filter)</h6>
                        </div>
                        <div class="card-body">
                            <form id="filterForm">
                                <div class="row">
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <label for="doc_date_start" class="font-weight-bold text-gray-700">จากวันที่</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-calendar-alt"></i></span></div>
                                            <input type="text" class="form-control" id="doc_date_start" name="doc_date_start" readonly required placeholder="จากวันที่">
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <label for="doc_date_to" class="font-weight-bold text-gray-700">ถึงวันที่</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-calendar-alt"></i></span></div>
                                            <input type="text" class="form-control" id="doc_date_to" name="doc_date_to" readonly required placeholder="ถึงวันที่">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="slmn_name" class="font-weight-bold text-gray-700">ชื่อพนักงานขาย (SLMN_ENABLE = 'Y')</label>
                                        <select class="form-control select2" id="slmn_name" name="slmn_name[]" multiple="multiple">
                                            <?php foreach ($salesmen_list as $slmn): ?>
                                                <option value="<?php echo htmlspecialchars($slmn['SLMN_NAME']); ?>">
                                                    <?php echo htmlspecialchars($slmn['SLMN_NAME'] . " (" . $slmn['SLMN_CODE'] . ")"); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-10 mb-3">
                                        <label for="iccat_code" class="font-weight-bold text-gray-700">ประเภทสินค้า (ยาง / น้ำมันเครื่อง / กระทะล้อ)</label>
                                        <select class="form-control select2" id="iccat_code" name="iccat_code[]" multiple="multiple">
                                            <?php foreach ($iccat_list_options as $cat): ?>
                                                <option value="<?php echo htmlspecialchars($cat['ICCAT_CODE']); ?>">
                                                    <?php echo htmlspecialchars("[" . $cat['ICCAT_CODE'] . "] " . $cat['ICCAT_NAME']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary btn-block shadow-sm" id="btnFilter">
                                            <i class="fas fa-search mr-1"></i> ค้นหาข้อมูล
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- KPI Cards Row -->
                    <div class="row mb-4">
                        <!-- Revenue KPI -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card kpi-card kpi-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">ยอดขายรวม (Total Revenue)</div>
                                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="kpiTotalRevenue">0.00 ฿</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-coins fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quantity KPI -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card kpi-card kpi-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">จำนวนขายรวม (Total Qty)</div>
                                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="kpiTotalQty">0 ชิ้น</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-boxes fa-2x text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Free Qty KPI -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card kpi-card kpi-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">จำนวนแถมรวม (Total Free Qty)</div>
                                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="kpiTotalFreeQty">0 ชิ้น</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-gift fa-2x text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top Salesman KPI -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card kpi-card kpi-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">พนักงานขายยอดสูงสุด</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800 text-truncate" id="kpiTopSalesman">-</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-trophy fa-2x text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <!-- Bar Chart: Salesman -->
                        <div class="col-lg-8 mb-4">
                            <div class="card chart-card shadow h-100 position-relative">
                                <div class="loading-overlay" id="loadingChartSales">
                                    <div class="text-center">
                                        <div class="spinner-border text-primary" role="status"></div>
                                        <div class="mt-2 text-muted font-weight-bold">กำลังประมวลผลกราฟ...</div>
                                    </div>
                                </div>
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white border-bottom-0">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-chart-bar mr-2"></i>สรุปยอดขายรวมประจำวัน แยกตามพนักงานขาย (Baht)</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-bar" style="position: relative; height: 360px;">
                                        <canvas id="chartSalesBySalesman"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Doughnut Chart: Category Share -->
                        <div class="col-lg-4 mb-4">
                            <div class="card chart-card shadow h-100 position-relative">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white border-bottom-0">
                                    <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-chart-pie mr-2"></i>สัดส่วนยอดขายตามประเภทสินค้า</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-pie" style="position: relative; height: 320px;">
                                        <canvas id="chartSalesByCategory"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table Row -->
                    <div class="row mb-4">
                        <div class="col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 bg-white d-flex align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-list-alt mr-2"></i>ตารางสรุปยอดขายรวม แยกตามชื่อพนักงานขาย</h6>
                                    <span class="badge badge-primary px-3 py-2" id="recordCountBadge">0 รายการ</span>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover align-items-center" id="salesSummaryTable">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th class="text-center" style="width: 60px;">ลำดับ</th>
                                                    <th>รหัสพนักงาน</th>
                                                    <th>ชื่อพนักงานขาย (Salesman)</th>
                                                    <th class="text-right">จำนวนรายการ</th>
                                                    <th class="text-right">จำนวนขาย (ชิ้น)</th>
                                                    <th class="text-right">จำนวนแถม (ชิ้น)</th>
                                                    <th class="text-right">ส่วนลดรวม (บาท)</th>
                                                    <th class="text-right">ยอดขายรวม (บาท)</th>
                                                    <th class="text-center" style="width: 120px;">สัดส่วนยอดขาย</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tableBody">
                                                <tr>
                                                    <td colspan="9" class="text-center py-4 text-muted">กรุณากดปุ่มค้นหาข้อมูล...</td>
                                                </tr>
                                            </tbody>
                                            <tfoot class="font-weight-bold bg-light" id="tableFooter">
                                                <!-- Total Row injected by JS -->
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!---Container Fluid-->

            </div>

            <?php
            include('includes/Modal-Logout.php');
            include('includes/Footer.php');
            ?>

        </div>
    </div>

    <!-- Scroll to top -->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <!-- Select2 -->
    <script src="js/select2/dist/js/select2.min.js"></script>
    <!-- Bootstrap Datepicker -->
    <script src="vendor/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
    <script src="vendor/date-picker-1.9/js/bootstrap-datepicker.js"></script>
    <script src="vendor/date-picker-1.9/locales/bootstrap-datepicker.th.min.js"></script>
    <link href="vendor/date-picker-1.9/css/bootstrap-datepicker.css" rel="stylesheet"/>

    <!-- Chart.js -->
    <script src="vendor/chart.js/Chart.min.js"></script>

    <script src="js/myadmin.min.js"></script>
    <script src="js/MyFrameWork/framework_util.js"></script>
    <script src="js/util.js"></script>

    <script>
        let chartSalesBySalesman = null;
        let chartSalesByCategory = null;

        $(document).ready(function () {
            // Initialize Select2
            $('.select2').select2({
                placeholder: "--- ไม่เลือก = ดึงทั้งหมด ---",
                allowClear: true,
                width: '100%'
            });
            $('#slmn_name').val(null).trigger('change');
            $('#iccat_code').val(null).trigger('change');

            // Set Datepicker defaults to today
            let today = new Date();
            let doc_date = getDay2Digits(today) + "-" + getMonth2Digits(today) + "-" + today.getFullYear();
            $('#doc_date_start').val(doc_date);
            $('#doc_date_to').val(doc_date);

            $('#doc_date_start').datepicker({
                format: "dd-mm-yyyy",
                todayHighlight: true,
                language: "th",
                autoclose: true
            });

            $('#doc_date_to').datepicker({
                format: "dd-mm-yyyy",
                todayHighlight: true,
                language: "th",
                autoclose: true
            });

            // Auto fetch on load
            loadDashboardData();

            // Filter Form Submit
            $('#filterForm').on('submit', function (e) {
                e.preventDefault();
                loadDashboardData();
            });
        });

        function loadDashboardData() {
            $('#loadingChartSales').css('display', 'flex');

            const payload = {
                doc_date_start: $('#doc_date_start').val(),
                doc_date_to: $('#doc_date_to').val(),
                slmn_name: $('#slmn_name').val() || null,
                iccat_code: $('#iccat_code').val() || null
            };

            $.ajax({
                url: 'api/export_wholesale_api.php',
                type: 'POST',
                data: JSON.stringify(payload),
                contentType: 'application/json',
                dataType: 'json',
                success: function (res) {
                    $('#loadingChartSales').hide();
                    if (res.status === 'success') {
                        processAndRenderData(res.data || []);
                    } else {
                        alert('API Error: ' + (res.message || 'ไม่สามารถดึงข้อมูลได้'));
                    }
                },
                error: function (xhr, status, error) {
                    $('#loadingChartSales').hide();
                    console.error('AJAX Error:', error);
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อ API');
                }
            });
        }

        function processAndRenderData(rawData) {
            let totalRevenue = 0;
            let totalQty = 0;
            let totalFreeQty = 0;
            let totalDiscount = 0;

            const salesmanMap = {};
            const categoryMap = {};

            rawData.forEach(item => {
                const amt = parseFloat(item.TRD_B_AMT || 0);
                const qty = parseFloat(item.TRD_QTY || 0);
                const freeQty = parseFloat(item.TRD_Q_FREE || 0);
                const disc = parseFloat(item.TRD_TDSC_KEYINV || 0);

                totalRevenue += amt;
                totalQty += qty;
                totalFreeQty += freeQty;
                totalDiscount += disc;

                // Group by Salesman
                const slmnName = item.SLMN_NAME ? item.SLMN_NAME.trim() : 'ไม่ระบุชื่อ';
                const slmnCode = item.SLMN_CODE ? item.SLMN_CODE.trim() : '';

                if (!salesmanMap[slmnName]) {
                    salesmanMap[slmnName] = {
                        slmn_code: slmnCode,
                        slmn_name: slmnName,
                        record_count: 0,
                        total_qty: 0,
                        total_free_qty: 0,
                        total_discount: 0,
                        total_amt: 0
                    };
                }
                salesmanMap[slmnName].record_count += 1;
                salesmanMap[slmnName].total_qty += qty;
                salesmanMap[slmnName].total_free_qty += freeQty;
                salesmanMap[slmnName].total_discount += disc;
                salesmanMap[slmnName].total_amt += amt;

                // Group by Category
                const catName = item.ICCAT_NAME ? item.ICCAT_NAME.trim() : 'อื่นๆ';
                if (!categoryMap[catName]) {
                    categoryMap[catName] = 0;
                }
                categoryMap[catName] += amt;
            });

            // Update KPI Cards
            $('#kpiTotalRevenue').text(numberWithCommas(totalRevenue.toFixed(2)) + ' ฿');
            $('#kpiTotalQty').text(numberWithCommas(totalQty) + ' ชิ้น');
            $('#kpiTotalFreeQty').text(numberWithCommas(totalFreeQty) + ' ชิ้น');

            // Find Top Salesman
            let topSalesman = '-';
            let topAmt = -Infinity;
            const salesmanList = Object.values(salesmanMap);
            salesmanList.sort((a, b) => b.total_amt - a.total_amt);

            if (salesmanList.length > 0 && salesmanList[0].total_amt > 0) {
                topSalesman = salesmanList[0].slmn_name + ' (' + numberWithCommas(salesmanList[0].total_amt.toFixed(0)) + '฿)';
            }
            $('#kpiTopSalesman').text(topSalesman);

            // Render Table
            renderTable(salesmanList, totalRevenue, totalQty, totalFreeQty, totalDiscount);

            // Render Charts
            renderSalesmanBarChart(salesmanList);
            renderCategoryPieChart(categoryMap);
        }

        function renderTable(salesmanList, totalRevenue, totalQty, totalFreeQty, totalDiscount) {
            const tbody = $('#tableBody');
            tbody.empty();

            $('#recordCountBadge').text(salesmanList.length + ' พนักงานขาย');

            if (salesmanList.length === 0) {
                tbody.html('<tr><td colspan="9" class="text-center py-4 text-muted">ไม่พบข้อมูลตามเงื่อนไขที่เลือก</td></tr>');
                $('#tableFooter').empty();
                return;
            }

            let grandTotalCount = 0;
            salesmanList.forEach((item, idx) => {
                grandTotalCount += item.record_count;
                const sharePercent = totalRevenue > 0 ? ((item.total_amt / totalRevenue) * 100).toFixed(1) : '0.0';

                const tr = `
                    <tr>
                        <td class="text-center">${idx + 1}</td>
                        <td><span class="badge badge-light border">${escapeHtml(item.slmn_code)}</span></td>
                        <td class="font-weight-bold text-dark">${escapeHtml(item.slmn_name)}</td>
                        <td class="text-right">${numberWithCommas(item.record_count)}</td>
                        <td class="text-right font-weight-bold text-primary">${numberWithCommas(item.total_qty)}</td>
                        <td class="text-right text-info">${numberWithCommas(item.total_free_qty)}</td>
                        <td class="text-right text-secondary">${numberWithCommas(item.total_discount.toFixed(2))}</td>
                        <td class="text-right font-weight-bold text-success">${numberWithCommas(item.total_amt.toFixed(2))}</td>
                        <td class="text-center">
                            <div class="progress" style="height: 18px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: ${sharePercent}%;" aria-valuenow="${sharePercent}" aria-valuemin="0" aria-valuemax="100">${sharePercent}%</div>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.append(tr);
            });

            // Footer Total Row
            const footerHtml = `
                <tr class="table-secondary">
                    <td colspan="3" class="text-right font-weight-bold">รวมทั้งสิ้น (Grand Total):</td>
                    <td class="text-right font-weight-bold">${numberWithCommas(grandTotalCount)}</td>
                    <td class="text-right font-weight-bold text-primary">${numberWithCommas(totalQty)}</td>
                    <td class="text-right font-weight-bold text-info">${numberWithCommas(totalFreeQty)}</td>
                    <td class="text-right font-weight-bold text-secondary">${numberWithCommas(totalDiscount.toFixed(2))}</td>
                    <td class="text-right font-weight-bold text-success">${numberWithCommas(totalRevenue.toFixed(2))} ฿</td>
                    <td class="text-center font-weight-bold">100%</td>
                </tr>
            `;
            $('#tableFooter').html(footerHtml);
        }

        function renderSalesmanBarChart(salesmanList) {
            const ctx = document.getElementById('chartSalesBySalesman').getContext('2d');

            const labels = salesmanList.map(item => item.slmn_name);
            const dataValues = salesmanList.map(item => item.total_amt);

            if (chartSalesBySalesman) {
                chartSalesBySalesman.destroy();
            }

            chartSalesBySalesman = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'ยอดขายรวม (บาท)',
                        data: dataValues,
                        backgroundColor: 'rgba(78, 115, 223, 0.75)',
                        borderColor: '#4e73df',
                        borderWidth: 1.5,
                        hoverBackgroundColor: '#2e59d9',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { display: false },
                    scales: {
                        xAxes: [{
                            gridLines: { display: false },
                            ticks: { maxRotation: 45, minRotation: 0 }
                        }],
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                callback: function (value) {
                                    return numberWithCommas(value) + ' ฿';
                                }
                            }
                        }]
                    },
                    tooltips: {
                        callbacks: {
                            label: function (tooltipItem, data) {
                                return ' ยอดขาย: ' + numberWithCommas(tooltipItem.yLabel.toFixed(2)) + ' บาท';
                            }
                        }
                    }
                }
            });
        }

        function renderCategoryPieChart(categoryMap) {
            const ctx = document.getElementById('chartSalesByCategory').getContext('2d');

            const labels = Object.keys(categoryMap);
            const dataValues = Object.values(categoryMap);

            const bgColors = [
                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                '#858796', '#6f42c1', '#fd7e14', '#20c997', '#e83e8c'
            ];

            if (chartSalesByCategory) {
                chartSalesByCategory.destroy();
            }

            chartSalesByCategory = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: dataValues,
                        backgroundColor: bgColors.slice(0, labels.length),
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12 }
                    },
                    tooltips: {
                        callbacks: {
                            label: function (tooltipItem, data) {
                                const dataset = data.datasets[tooltipItem.datasetIndex];
                                const total = dataset.data.reduce((acc, curr) => acc + curr, 0);
                                const current = dataset.data[tooltipItem.index];
                                const percentage = total > 0 ? ((current / total) * 100).toFixed(1) : 0;
                                const catLabel = data.labels[tooltipItem.index];
                                return catLabel + ': ' + numberWithCommas(current.toFixed(2)) + ' ฿ (' + percentage + '%)';
                            }
                        }
                    }
                }
            });
        }

        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        function escapeHtml(text) {
            return text ? text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;") : '';
        }
    </script>

    </body>

    </html>

<?php } ?>
