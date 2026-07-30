<?php
include('includes/Header.php');
include_once('config/connect_sqlserver.php');

if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php");
} else {
    $menu_title = isset($_GET['m']) ? htmlspecialchars(urldecode($_GET['m']), ENT_QUOTES, 'UTF-8') : '';
    $sub_title = isset($_GET['s']) ? htmlspecialchars(urldecode($_GET['s']), ENT_QUOTES, 'UTF-8') : '';

    // Fetch Active Salesmen (SLMN_ENABLE = 'Y')
    $salesmen_list = [];
    try {
        if (isset($conn_sqlsvr)) {
            $stmt_slmn = $conn_sqlsvr->query("SELECT SLMN_CODE, SLMN_NAME FROM SALESMAN WITH (NOLOCK) WHERE SLMN_ENABLE = 'Y' ORDER BY SLMN_NAME");
            if ($stmt_slmn) {
                $salesmen_list = $stmt_slmn->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    } catch (Exception $e) {}

    // Fetch Product Categories starting with/containing 'ยาง', 'น้ำมัน', 'กระทะล้อ'
    $iccat_list_options = [];
    try {
        if (isset($conn_sqlsvr)) {
            $sql_iccat_select = "SELECT ICCAT_CODE, ICCAT_NAME FROM ICCAT WITH (NOLOCK) 
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
        </style>
    </head>

    <body id="page-top">
    <div id="wrapper">
        <?php
        include('includes/Side-Bar.php');
        ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php
                include('includes/Top-Bar.php');
                ?>

                <!-- Container Fluid-->
                <div class="container-fluid" id="container-wrapper">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800"><?php echo $sub_title; ?></h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo $_SESSION['dashboard_page']; ?>">Home</a>
                            </li>
                            <li class="breadcrumb-item"><?php echo $menu_title; ?></li>
                            <li class="breadcrumb-item active"
                                aria-current="page"><?php echo $sub_title; ?></li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-12">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                </div>
                                <div class="card-body">
                                    <section class="container-fluid">
                                        <div class="row">
                                            <div class="col-md-12 col-md-offset-2">
                                                <div class="panel">
                                                    <div class="panel-body">

                                                        <form id="from_data" method="post"
                                                              action="export_process/export_data_wholesale.php"
                                                              enctype="multipart/form-data">

                                                            <div class="modal-body">
                                                                <div class="form-group row">

                                                                    <div class="col-sm-3 mb-3">
                                                                        <label for="doc_date_start"
                                                                               class="control-label">จากวันที่</label>
                                                                        <i class="fa fa-calendar"
                                                                           aria-hidden="true"></i>
                                                                        <input type="text" class="form-control"
                                                                               id="doc_date_start"
                                                                               name="doc_date_start"
                                                                               required="required"
                                                                               readonly
                                                                               placeholder="จากวันที่">
                                                                    </div>

                                                                    <div class="col-sm-3 mb-3">
                                                                        <label for="doc_date_to"
                                                                               class="control-label">ถึงวันที่</label>
                                                                        <i class="fa fa-calendar"
                                                                           aria-hidden="true"></i>
                                                                        <input type="text" class="form-control"
                                                                               id="doc_date_to"
                                                                               name="doc_date_to"
                                                                               required="required"
                                                                               readonly
                                                                               placeholder="ถึงวันที่">
                                                                    </div>

                                                                    <div class="col-sm-6 mb-3">
                                                                        <label for="slmn_name"
                                                                               class="control-label">ชื่อพนักงานขาย (SLMN_ENABLE = 'Y')</label>
                                                                        <select class="form-control select2"
                                                                                id="slmn_name"
                                                                                name="slmn_name[]"
                                                                                multiple="multiple"
                                                                                data-placeholder="--- เลือกพนักงานขาย (เลือกได้หลายคน / ไม่เลือก = ทั้งหมด) ---">
                                                                            <?php foreach ($salesmen_list as $slmn): ?>
                                                                                <option value="<?php echo htmlspecialchars($slmn['SLMN_NAME']); ?>">
                                                                                    <?php echo htmlspecialchars($slmn['SLMN_NAME'] . " (" . $slmn['SLMN_CODE'] . ")"); ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>

                                                                    <div class="col-sm-12 mb-3">
                                                                        <label for="iccat_code"
                                                                               class="control-label">ประเภทสินค้า (ยาง / น้ำมันเครื่อง / กระทะล้อ)</label>
                                                                        <select class="form-control select2"
                                                                                id="iccat_code"
                                                                                name="iccat_code[]"
                                                                                multiple="multiple"
                                                                                data-placeholder="--- เลือกประเภทสินค้า (เลือกได้หลายประเภท / ไม่เลือก = ทั้งหมด) ---">
                                                                            <?php foreach ($iccat_list_options as $cat): ?>
                                                                                <option value="<?php echo htmlspecialchars($cat['ICCAT_CODE']); ?>">
                                                                                    <?php echo htmlspecialchars("[" . $cat['ICCAT_CODE'] . "] " . $cat['ICCAT_NAME']); ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>

                                                                </div>
                                                            </div>

                                                            <div class="modal-footer">
                                                                <input type="hidden" name="id" id="id"/>
                                                                <input type="hidden" name="save_status"
                                                                       id="save_status"/>
                                                                <input type="hidden" name="action" id="action"
                                                                       value=""/>
                                                                <button type="submit" class="btn btn-success"
                                                                        id="btnExport"> Export <i
                                                                            class="fa fa-check"></i>
                                                                </button>
                                                            </div>

                                                        </form>

                                                    </div>

                                                </div>

                                            </div>

                                        </div>

                                    </section>

                                </div>

                            </div>

                        </div>

                    </div>
                    <!--Row-->

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
    <!-- Bootstrap Touchspin -->
    <script src="vendor/bootstrap-touchspin/js/jquery.bootstrap-touchspin.js"></script>
    <!-- ClockPicker -->
    <script src="vendor/clock-picker/clockpicker.js"></script>
    <!-- RuangAdmin Javascript -->
    <script src="js/myadmin.min.js"></script>
    <!-- Javascript for this page -->

    <script src="vendor/date-picker-1.9/js/bootstrap-datepicker.js"></script>
    <script src="vendor/date-picker-1.9/locales/bootstrap-datepicker.th.min.js"></script>
    <link href="vendor/date-picker-1.9/css/bootstrap-datepicker.css" rel="stylesheet"/>

    <script src="js/MyFrameWork/framework_util.js"></script>
    <script src="js/util.js"></script>

    <script>
        $(document).ready(function () {
            // Initialize Select2 dropdowns (default = ไม่เลือก / empty selection)
            $('.select2').select2({
                allowClear: true,
                width: '100%'
            });
            $('#slmn_name').val(null).trigger('change');
            $('#iccat_code').val(null).trigger('change');

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
        });
    </script>

    </body>

    </html>

<?php } ?>