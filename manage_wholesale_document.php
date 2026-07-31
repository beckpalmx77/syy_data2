<?php
include('includes/Header.php');
include_once('config/connect_db.php');

if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php");
} else {
    // Fetch Active Salesmen from MySQL ims_product_sale_syy_ks (Cached in Session)
    if (!isset($_SESSION['salesmen_list_cache']) || empty($_SESSION['salesmen_list_cache'])) {
        try {
            if (isset($conn)) {
                $stmt_slmn = $conn->query("SELECT DISTINCT SLMN_CODE, SLMN_NAME FROM ims_product_sale_syy_ks WHERE SLMN_NAME IS NOT NULL AND SLMN_NAME != '' ORDER BY SLMN_NAME");
                if ($stmt_slmn) {
                    $_SESSION['salesmen_list_cache'] = $stmt_slmn->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        } catch (Exception $e) {}
    }
    $salesmen_list = $_SESSION['salesmen_list_cache'] ?? [];

    // Fetch Product Categories from MySQL ims_product_sale_syy_ks (Cached in Session)
    if (!isset($_SESSION['iccat_list_cache']) || empty($_SESSION['iccat_list_cache'])) {
        try {
            if (isset($conn)) {
                $sql_iccat_select = "SELECT DISTINCT ICCAT_CODE, ICCAT_NAME FROM ims_product_sale_syy_ks 
                                     WHERE ICCAT_NAME IS NOT NULL AND ICCAT_NAME != ''
                                       AND (ICCAT_NAME LIKE 'ยาง%' 
                                         OR ICCAT_NAME LIKE '%น้ำมัน%' 
                                         OR ICCAT_NAME LIKE 'กระทะล้อ%' 
                                         OR ICCAT_NAME LIKE '%กระทะ%')
                                     ORDER BY ICCAT_CODE";
                $stmt_iccat = $conn->query($sql_iccat_select);
                if ($stmt_iccat) {
                    $_SESSION['iccat_list_cache'] = $stmt_iccat->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        } catch (Exception $e) {}
    }
    $iccat_list_options = $_SESSION['iccat_list_cache'] ?? [];
    ?>

    <!DOCTYPE html>
    <html lang="th">
    <head>
        <link href="js/select2/dist/css/select2.min.css" rel="stylesheet" type="text/css">
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
            .icon-input-btn {
                display: inline-block;
                position: relative;
            }
            .icon-input-btn input[type="submit"] {
                padding-left: 2em;
            }
            .icon-input-btn .fa {
                display: inline-block;
                position: absolute;
                left: 0.65em;
                top: 30%;
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
                        <h1 class="h3 mb-0 text-gray-800"><?php echo urldecode($_GET['s'] ?? 'แสดงรายการขายส่ง (Document Line Items)'); ?></h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo $_SESSION['dashboard_page']; ?>">Home</a></li>
                            <li class="breadcrumb-item"><?php echo urldecode($_GET['m'] ?? 'รายงาน'); ?></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo urldecode($_GET['s'] ?? 'แสดงรายการขายส่ง (Document Line Items)'); ?></li>
                        </ol>
                    </div>

                    <!-- Filter Control Panel Card (Matching manage-unit.php Interface Style) -->
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
                                                    <label for="slmn_name" class="control-label font-weight-bold">ชื่อพนักงานขาย</label>
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

                    <!-- Data Table Section Card (Matching manage-unit.php Interface Style) -->
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
                                                        <th class="text-center">เวลาสร้าง</th>
                                                        <th>ชื่อลูกค้า</th>
                                                        <th>สาขา/แผนก</th>
                                                        <th>รหัสประเภท</th>
                                                        <th>ประเภทสินค้า</th>
                                                        <th>ชื่อสินค้า</th>
                                                        <th>ยี่ห้อ</th>
                                                        <th class="text-right">จำนวนขาย</th>
                                                        <th class="text-right">จำนวนแถม</th>
                                                        <th class="text-right">ราคา/หน่วย (บาท)</th>
                                                        <th class="text-right">ส่วนลด (บาท)</th>
                                                        <th class="text-right">จำนวนเงิน (บาท)</th>
                                                        <th>ชื่อเซลส์</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Data injected by DataTables AJAX from model/manage_wholesale_document_process.php -->
                                                </tbody>
                                                <tfoot class="font-weight-bold">
                                                    <tr>
                                                        <th colspan="10" class="text-right">รวมทั้งสิ้น (Grand Total):</th>
                                                        <th class="text-right text-primary" id="ftTotalQty">0</th>
                                                        <th class="text-right text-info" id="ftTotalFreeQty">0</th>
                                                        <th></th>
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

                    <!-- Record Detail Modal (Matching manage-unit.php Record Modal Pattern) -->
                    <div class="modal fade" id="recordModal">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title">รายละเอียดเอกสารขายส่ง</h4>
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                </div>
                                <div class="modal-body">
                                    <form id="recordForm">
                                        <div class="form-group">
                                            <label for="modal_di_ref" class="control-label font-weight-bold">เลขที่เอกสาร (DI_REF)</label>
                                            <input type="text" class="form-control" id="modal_di_ref" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label for="modal_ar_name" class="control-label font-weight-bold">ชื่อลูกค้า (AR_NAME)</label>
                                            <input type="text" class="form-control" id="modal_ar_name" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label for="modal_sku_name" class="control-label font-weight-bold">ชื่อสินค้า (SKU_NAME)</label>
                                            <input type="text" class="form-control" id="modal_sku_name" readonly>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label for="modal_trd_qty" class="control-label font-weight-bold">จำนวนขาย</label>
                                                <input type="text" class="form-control text-right" id="modal_trd_qty" readonly>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label for="modal_trd_u_prc" class="control-label font-weight-bold">ราคา/หน่วย</label>
                                                <input type="text" class="form-control text-right" id="modal_trd_u_prc" readonly>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label for="modal_trd_b_amt" class="control-label font-weight-bold">จำนวนเงินรวม</label>
                                                <input type="text" class="form-control text-right font-weight-bold" id="modal_trd_b_amt" readonly>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close <i class="fa fa-times"></i></button>
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

    <!-- Scripts (Matching manage-unit.php Vendor Dependencies) -->
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

    <!-- DataTables v11 (Same as manage-unit.php) -->
    <script src="vendor/datatables/v11/bootbox.min.js"></script>
    <script src="vendor/datatables/v11/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="vendor/datatables/v11/jquery.dataTables.min.css"/>
    <link rel="stylesheet" href="vendor/datatables/v11/buttons.dataTables.min.css"/>

    <script src="js/myadmin.min.js"></script>
    <script src="js/MyFrameWork/framework_util.js"></script>
    <script src="js/util.js"></script>

    <script>
        let dataTableInstance = null;

        $(document).ready(function () {
            // Icon button alignment helper matching manage-unit.php
            $(".icon-input-btn").each(function () {
                let btnFont = $(this).find(".btn").css("font-size");
                let btnColor = $(this).find(".btn").css("color");
                $(this).find(".fa").css({'font-size': btnFont, 'color': btnColor});
            });

            // Initialize Select2 dropdowns
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

            // Initialize DataTable calling backend model/manage_wholesale_document_process.php
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
            let formData = {
                action: "GET_WHOLESALE_DOCUMENT"
            };

            dataTableInstance = $('#TableRecordList').DataTable({
                lengthMenu: [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]],
                language: {
                    search: 'ค้นหา',
                    lengthMenu: 'แสดง _MENU_ รายการ',
                    info: 'หน้าที่ _PAGE_ จาก _PAGES_',
                    infoEmpty: 'ไม่มีข้อมูล',
                    zeroRecords: "ไม่มีข้อมูลตามเงื่อนไข",
                    infoFiltered: '(กรองข้อมูลจากทั้งหมด _MAX_ รายการ)',
                    paginate: {
                        previous: 'ก่อนหน้า',
                        last: 'สุดท้าย',
                        next: 'ต่อไป'
                    }
                },
                processing: true,
                serverSide: true,
                serverMethod: 'post',
                ajax: {
                    url: 'model/manage_wholesale_document_process.php',
                    data: function (d) {
                        d.action = "GET_WHOLESALE_DOCUMENT";
                        d.doc_date_start = $('#doc_date_start').val();
                        d.doc_date_to = $('#doc_date_to').val();
                        d.slmn_name = $('#slmn_name').val() || null;
                        d.iccat_code = $('#iccat_code').val() || null;
                    },
                    dataSrc: function (json) {
                        if (json && json.aaData) {
                            const dataList = json.aaData || [];
                            $('#totalRecordsBadge').text(numberWithCommas(json.iTotalDisplayRecords || dataList.length) + ' รายการ');
                            updateFooterTotals(dataList);
                            return dataList;
                        }
                        return [];
                    }
                },
                columns: [
                    {
                        data: null,
                        render: function (data, type, row, meta) {
                            return meta.settings._iDisplayStart + meta.row + 1;
                        },
                        className: "text-center"
                    },
                    {
                        data: "DI_REF",
                        render: function (data, type, row) {
                            let str = data || '';
                            if (row.DT_DOCCODE) {
                                str += ` <span class="badge badge-secondary" style="font-size:0.75rem;">${row.DT_DOCCODE}</span>`;
                            }
                            return str;
                        }
                    },
                    { data: "DI_DATE" },
                    { data: "DI_TIME_CHK", className: "text-center" },
                    { data: "AR_NAME" },
                    {
                        data: null,
                        render: function (data, type, row) {
                            let code = row.DEPT_CODE || '';
                            let name = row.DEPT_THAIDESC || '';
                            if (code && name) return `${name} (${code})`;
                            return name || code || '-';
                        }
                    },
                    { data: "ICCAT_CODE" },
                    { data: "ICCAT_NAME" },
                    {
                        data: "SKU_NAME",
                        render: function (data, type, row) {
                            let name = data || '';
                            if (row.SKU_E_NAME) {
                                name += `<br><small class="text-muted">${row.SKU_E_NAME}</small>`;
                            }
                            return name;
                        }
                    },
                    { data: "BRN_NAME" },
                    {
                        data: "TRD_QTY",
                        render: function (data, type, row) {
                            let val = parseFloat(data) || 0;
                            if (val < 0) {
                                return `<span class="badge badge-return">${numberWithCommas(val)}</span>`;
                            }
                            return numberWithCommas(val);
                        },
                        className: "text-right"
                    },
                    {
                        data: "TRD_Q_FREE",
                        render: function (data, type, row) {
                            let val = parseFloat(data) || 0;
                            return numberWithCommas(val);
                        },
                        className: "text-right"
                    },
                    {
                        data: "TRD_U_PRC",
                        render: function (data, type, row) {
                            return numberWithCommas(parseFloat(data) || 0, 2);
                        },
                        className: "text-right"
                    },
                    {
                        data: "TRD_TDSC_KEYINV",
                        render: function (data, type, row) {
                            return numberWithCommas(parseFloat(data) || 0, 2);
                        },
                        className: "text-right"
                    },
                    {
                        data: "TRD_B_AMT",
                        render: function (data, type, row) {
                            let val = parseFloat(data) || 0;
                            if (val < 0) {
                                return `<span class="badge badge-return">${numberWithCommas(val, 2)} ฿</span>`;
                            }
                            return numberWithCommas(val, 2) + " ฿";
                        },
                        className: "text-right font-weight-bold"
                    },
                    {
                        data: "SLMN_NAME",
                        render: function (data, type, row) {
                            let name = data || '';
                            let code = row.SLMN_CODE || '';
                            if (code) return `${name} (${code})`;
                            return name;
                        }
                    }
                ],
                order: [[1, 'desc']]
            });
        }

        function reloadDataTable() {
            if (dataTableInstance) {
                dataTableInstance.ajax.reload();
            } else {
                initDataTable();
            }
        }

        function updateFooterTotals(dataList) {
            let totalQty = 0;
            let totalFreeQty = 0;
            let totalDisc = 0;
            let totalAmt = 0;

            dataList.forEach(item => {
                totalQty += (parseFloat(item.TRD_QTY) || 0);
                totalFreeQty += (parseFloat(item.TRD_Q_FREE) || 0);
                totalDisc += (parseFloat(item.TRD_TDSC_KEYINV) || 0);
                totalAmt += (parseFloat(item.TRD_B_AMT) || 0);
            });

            $('#ftTotalQty').text(numberWithCommas(totalQty));
            $('#ftTotalFreeQty').text(numberWithCommas(totalFreeQty));
            $('#ftTotalDisc').text(numberWithCommas(totalDisc, 2));
            $('#ftTotalAmt').text(numberWithCommas(totalAmt, 2) + ' ฿');
        }

        function escapeAttr(str) {
            return String(str || '').replace(/"/g, '&quot;');
        }
    </script>

    </body>
    </html>
<?php } ?>
