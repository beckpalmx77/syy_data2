<?php
include('includes/Header.php');
include_once('config/connect_sqlserver.php');

if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php");
} else {
    $menu_title = isset($_GET['m']) ? htmlspecialchars(urldecode($_GET['m']), ENT_QUOTES, 'UTF-8') : 'รายงาน';
    $sub_title = isset($_GET['s']) ? htmlspecialchars(urldecode($_GET['s']), ENT_QUOTES, 'UTF-8') : 'แสดงรายการขายส่ง (Document Line Items)';

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
        <link rel="stylesheet" href="vendor/datatables/v11/jquery.dataTables.min.css"/>
        <link rel="stylesheet" href="vendor/datatables/v11/buttons.dataTables.min.css"/>
        <link rel="stylesheet" href="css/datatables-bootstrap5.css"/>

        <style>
            .select2-container--default .select2-selection--multiple {
                border-color: #d1d3e2;
                min-height: 38px;
                border-radius: 0.35rem;
            }
            .table-responsive {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
            }
            table.dataTable,
            table.dataTable th,
            table.dataTable td,
            table.dataTable tfoot th {
                white-space: nowrap !important;
                vertical-align: middle;
            }
            table.dataTable thead th {
                background-color: #f8f9fc;
            }
            .badge-return {
                background-color: #e74a3b;
                color: #fff;
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
                        <h1 class="h3 mb-0 text-gray-800"><?php echo $sub_title; ?></h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo $_SESSION['dashboard_page']; ?>">Home</a></li>
                            <li class="breadcrumb-item"><?php echo $menu_title; ?></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo $sub_title; ?></li>
                        </ol>
                    </div>

                    <!-- Filter Control Panel Card (Template from manage-menu-main.php) -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-12">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fa fa-filter"></i> ตัวกรองค้นหาข้อมูล (Filter Controls)</h6>
                                </div>
                                <div class="card-body">
                                    <section class="container-fluid">
                                        <form id="filterForm">
                                            <div class="row">
                                                <div class="col-md-3 col-sm-6 mb-3">
                                                    <label for="doc_date_start" class="control-label font-weight-bold">จากวันที่</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" id="doc_date_start" name="doc_date_start" readonly required placeholder="จากวันที่">
                                                        <div class="input-group-append"><span class="input-group-text"><i class="fa fa-calendar"></i></span></div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 col-sm-6 mb-3">
                                                    <label for="doc_date_to" class="control-label font-weight-bold">ถึงวันที่</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" id="doc_date_to" name="doc_date_to" readonly required placeholder="ถึงวันที่">
                                                        <div class="input-group-append"><span class="input-group-text"><i class="fa fa-calendar"></i></span></div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="slmn_name" class="control-label font-weight-bold">ชื่อพนักงานขาย (SLMN_ENABLE = 'Y')</label>
                                                    <select class="form-control select2" id="slmn_name" name="slmn_name[]" multiple="multiple">
                                                        <?php foreach ($salesmen_list as $slmn): ?>
                                                            <option value="<?php echo htmlspecialchars($slmn['SLMN_NAME']); ?>">
                                                                <?php echo htmlspecialchars($slmn['SLMN_NAME'] . " (" . $slmn['SLMN_CODE'] . ")"); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-9 mb-3">
                                                    <label for="iccat_code" class="control-label font-weight-bold">ประเภทสินค้า (ยาง / น้ำมันเครื่อง / กระทะล้อ)</label>
                                                    <select class="form-control select2" id="iccat_code" name="iccat_code[]" multiple="multiple">
                                                        <?php foreach ($iccat_list_options as $cat): ?>
                                                            <option value="<?php echo htmlspecialchars($cat['ICCAT_CODE']); ?>">
                                                                <?php echo htmlspecialchars("[" . $cat['ICCAT_CODE'] . "] " . $cat['ICCAT_NAME']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3 mb-3 d-flex align-items-end">
                                                    <button type="submit" class="btn btn-primary btn-sm btn-block mr-2" id="btnSearch">
                                                        <i class="fa fa-search"></i> ค้นหาข้อมูล
                                                    </button>
                                                    <button type="button" class="btn btn-success btn-sm" id="btnExportExcel" title="ดาวน์โหลดไฟล์ Excel/CSV">
                                                        <i class="fa fa-file-excel-o"></i> Excel
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </section>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden Export Form -->
                    <form id="excelExportForm" method="post" action="export_process/export_data_wholesale.php" target="_blank" style="display:none;">
                        <input type="hidden" name="doc_date_start" id="exp_doc_date_start">
                        <input type="hidden" name="doc_date_to" id="exp_doc_date_to">
                        <div id="exp_slmn_container"></div>
                        <div id="exp_iccat_container"></div>
                    </form>

                    <!-- Data Table Section Card (Template from manage-menu-main.php) -->
                    <div class="row mt-4">
                        <div class="col-lg-12">
                            <div class="card mb-12">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fa fa-table"></i> รายการเอกสารขายส่ง (Wholesale Document Items)</h6>
                                    <span class="badge badge-primary" id="totalRecordsBadge">0 รายการ</span>
                                </div>
                                <div class="card-body">
                                    <div class="col-md-12 col-md-offset-2">
                                        <div class="table-responsive">
                                            <table id="TableRecordList" class="display dataTable table table-bordered table-hover text-nowrap w-100">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center">#</th>
                                                        <th>เลขที่เอกสาร</th>
                                                        <th>วันที่</th>
                                                        <th>ชื่อลูกค้า</th>
                                                        <th>สาขา/แผนก</th>
                                                        <th>รหัสประเภท</th>
                                                        <th>ประเภทสินค้า</th>
                                                        <th>ชื่อสินค้า</th>
                                                        <th>ยี่ห้อ</th>
                                                        <th class="text-right">จำนวนขาย</th>
                                                        <th class="text-right">จำนวนแถม</th>
                                                        <th class="text-right">ส่วนลด (บาท)</th>
                                                        <th class="text-right">จำนวนเงิน (บาท)</th>
                                                        <th>ชื่อเซลส์</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Data injected by DataTables AJAX -->
                                                </tbody>
                                                <tfoot class="font-weight-bold">
                                                    <tr>
                                                        <th colspan="9" class="text-right">รวมทั้งสิ้น (Grand Total):</th>
                                                        <th class="text-right text-primary" id="ftTotalQty">0</th>
                                                        <th class="text-right text-info" id="ftTotalFreeQty">0</th>
                                                        <th class="text-right text-secondary" id="ftTotalDisc">0.00</th>
                                                        <th class="text-right text-success" id="ftTotalAmt">0.00 ฿</th>
                                                        <th></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                        <div id="result"></div>
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

    <!-- DataTables v11 (Same as manage-menu-main.php) -->
    <script src="vendor/datatables/v11/bootbox.min.js"></script>
    <script src="vendor/datatables/v11/jquery.dataTables.min.js"></script>

    <script src="js/myadmin.min.js"></script>
    <script src="js/MyFrameWork/framework_util.js"></script>
    <script src="js/util.js"></script>

    <script>
        let dataTableInstance = null;

        $(document).ready(function () {
            // Initialize Select2 dropdowns (default = ไม่เลือก)
            $('.select2').select2({
                placeholder: "--- ไม่เลือก = ดึงทั้งหมด ---",
                allowClear: true,
                width: '100%'
            });
            $('#slmn_name').val(null).trigger('change');
            $('#iccat_code').val(null).trigger('change');

            // Datepicker defaults to today
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

            // Initialize DataTable
            initDataTable();

            // Form Search Submit
            $('#filterForm').on('submit', function (e) {
                e.preventDefault();
                reloadDataTable();
            });

            // Export Excel Button Handler
            $('#btnExportExcel').on('click', function () {
                $('#exp_doc_date_start').val($('#doc_date_start').val());
                $('#exp_doc_date_to').val($('#doc_date_to').val());

                const slmnVals = $('#slmn_name').val() || [];
                let slmnHtml = '';
                slmnVals.forEach(v => {
                    slmnHtml += `<input type="hidden" name="slmn_name[]" value="${escapeAttr(v)}">`;
                });
                $('#exp_slmn_container').html(slmnHtml);

                const iccatVals = $('#iccat_code').val() || [];
                let iccatHtml = '';
                iccatVals.forEach(v => {
                    iccatHtml += `<input type="hidden" name="iccat_code[]" value="${escapeAttr(v)}">`;
                });
                $('#exp_iccat_container').html(iccatHtml);

                $('#excelExportForm').submit();
            });
        });

        function initDataTable() {
            dataTableInstance = $('#TableRecordList').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: 'api/export_wholesale_api.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: function (d) {
                        return JSON.stringify({
                            doc_date_start: $('#doc_date_start').val(),
                            doc_date_to: $('#doc_date_to').val(),
                            slmn_name: $('#slmn_name').val() || null,
                            iccat_code: $('#iccat_code').val() || null
                        });
                    },
                    dataSrc: function (json) {
                        if (json.status === 'success') {
                            const dataList = json.data || [];
                            $('#totalRecordsBadge').text(numberWithCommas(dataList.length) + ' รายการ');
                            updateFooterTotals(dataList);
                            return dataList;
                        } else {
                            alert('API Error: ' + (json.message || 'ไม่สามารถดึงข้อมูลได้'));
                            return [];
                        }
                    }
                },
                columns: [
                    {
                        data: null,
                        render: function (data, type, row, meta) {
                            return meta.settings._iDisplayStart + meta.row + 1;
                        },
                        className: 'text-center'
                    },
                    {
                        data: 'DI_REF',
                        render: function (data, type, row) {
                            const isReturn = (data && (data.indexOf('IS') === 0 || data.indexOf('ISO') === 0));
                            if (isReturn) {
                                return `<span class="badge badge-danger">${escapeHtml(data)}</span>`;
                            }
                            return `<span class="font-weight-bold text-primary">${escapeHtml(data)}</span>`;
                        }
                    },
                    { data: 'DI_DATE', className: 'text-nowrap' },
                    { data: 'AR_NAME' },
                    {
                        data: 'DEPT_THAIDESC',
                        render: function (data, type, row) {
                            return `<span class="badge badge-light border">${escapeHtml(data || row.DEPT_CODE || '')}</span>`;
                        }
                    },
                    { data: 'ICCAT_CODE' },
                    { data: 'ICCAT_NAME' },
                    { data: 'SKU_NAME' },
                    { data: 'BRN_NAME' },
                    {
                        data: 'TRD_QTY',
                        className: 'text-right font-weight-bold',
                        render: function (data) {
                            const val = parseFloat(data || 0);
                            return val < 0 ? `<span class="text-danger">${numberWithCommas(val)}</span>` : numberWithCommas(val);
                        }
                    },
                    {
                        data: 'TRD_Q_FREE',
                        className: 'text-right text-info',
                        render: function (data) {
                            return numberWithCommas(parseFloat(data || 0));
                        }
                    },
                    {
                        data: 'TRD_TDSC_KEYINV',
                        className: 'text-right text-secondary',
                        render: function (data) {
                            return numberWithCommas(parseFloat(data || 0).toFixed(2));
                        }
                    },
                    {
                        data: 'TRD_B_AMT',
                        className: 'text-right font-weight-bold',
                        render: function (data) {
                            const val = parseFloat(data || 0);
                            if (val < 0) {
                                return `<span class="text-danger">${numberWithCommas(val.toFixed(2))} ฿</span>`;
                            }
                            return `<span class="text-success">${numberWithCommas(val.toFixed(2))} ฿</span>`;
                        }
                    },
                    {
                        data: 'SLMN_NAME',
                        render: function (data, type, row) {
                            return `<span class="badge badge-primary">${escapeHtml(data || row.SLMN_CODE || '')}</span>`;
                        }
                    }
                ],
                order: [[1, 'asc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, 500, -1], [10, 25, 50, 100, 500, "ทั้งหมด"]],
                language: {
                    processing: "กำลังโหลดข้อมูล...",
                    search: "ค้นหาในตาราง:",
                    lengthMenu: "แสดง _MENU_ รายการ",
                    info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                    infoEmpty: "ไม่มีรายการข้อมูล",
                    infoFiltered: "(กรองจากทั้งหมด _MAX_ รายการ)",
                    zeroRecords: "ไม่พบข้อมูลตามเงื่อนไขที่เลือก",
                    paginate: {
                        first: "หน้าแรก",
                        previous: "ก่อนหน้า",
                        next: "ถัดไป",
                        last: "หน้าสุดท้าย"
                    }
                }
            });
        }

        function reloadDataTable() {
            if (dataTableInstance) {
                dataTableInstance.ajax.reload();
            }
        }

        function updateFooterTotals(dataList) {
            let totalQty = 0;
            let totalFreeQty = 0;
            let totalDisc = 0;
            let totalAmt = 0;

            dataList.forEach(item => {
                totalQty += parseFloat(item.TRD_QTY || 0);
                totalFreeQty += parseFloat(item.TRD_Q_FREE || 0);
                totalDisc += parseFloat(item.TRD_TDSC_KEYINV || 0);
                totalAmt += parseFloat(item.TRD_B_AMT || 0);
            });

            $('#ftTotalQty').text(numberWithCommas(totalQty));
            $('#ftTotalFreeQty').text(numberWithCommas(totalFreeQty));
            $('#ftTotalDisc').text(numberWithCommas(totalDisc.toFixed(2)));
            $('#ftTotalAmt').html(totalAmt < 0 ? `<span class="text-danger">${numberWithCommas(totalAmt.toFixed(2))} ฿</span>` : `${numberWithCommas(totalAmt.toFixed(2))} ฿`);
        }

        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        function escapeHtml(text) {
            return text ? String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;") : '';
        }

        function escapeAttr(text) {
            return text ? String(text).replace(/"/g, "&quot;") : '';
        }
    </script>

    </body>

    </html>

<?php } ?>
