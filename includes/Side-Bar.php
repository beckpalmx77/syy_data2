<?php
session_start();
error_reporting(0);
include('config/connect_db.php');
include('config/lang.php');
?>

<!--link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css" rel="stylesheet"-->

<link href="../vendor/fontawesome-free-5.15.4-web/css/fontawesome.css" rel="stylesheet">


<!-- Sidebar -->
<ul class="navbar-nav sidebar sidebar-light accordion" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo $_SESSION['dashboard_page']?>">
        <div class="sidebar-brand-icon">
            <img src="img/logo/Logo-01.png" width="60" height="100">
        </div>
        <div class="sidebar-brand-text mx-3">
        </div>
    </a>
    <hr class="sidebar-divider my-0">
    <li class="nav-item active">
        <a class="nav-link" href="<?php echo $_SESSION['dashboard_page'];?>">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>
    <hr class="sidebar-divider">

    <?php

    if ($_SESSION['account_type'] != "") {

        $permission_id = $_SESSION['account_type'];

        $sql = "SELECT sub_menu, main_menu, permission_detail FROM ims_permission where permission_id = '"
            . $permission_id . "' order by main_menu,sub_menu ";
        $query = $conn->prepare($sql);
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_OBJ);

        if ($query->rowCount() > 0) {
            $mains_by_id = [];
            $stmt_mm = $conn->query("SELECT * FROM menu_main ORDER BY main_menu_id");
            if ($stmt_mm) {
                while ($m = $stmt_mm->fetch(PDO::FETCH_OBJ)) {
                    $mains_by_id[$m->main_menu_id] = $m;
                }
            }

            $subs_by_key = [];
            $stmt_ms = $conn->query("SELECT * FROM menu_sub ORDER BY main_menu_id, sub_menu_id");
            if ($stmt_ms) {
                while ($s = $stmt_ms->fetch(PDO::FETCH_OBJ)) {
                    $subs_by_key[$s->main_menu_id . '_' . $s->sub_menu_id] = $s;
                }
            }

            foreach ($results as $result) {
                $main_menu_ids = (explode(",", $result->main_menu));
                $sub_menus_ids = (explode(",", $result->sub_menu));

                foreach ($main_menu_ids as $main_menu_id) {
                    $result_main = $mains_by_id[$main_menu_id] ?? null;
                    if (!$result_main) continue;

                    $main_menu = $_SESSION['lang'] == "th" ? $result_main->label : $result_main->label_en;
                    ?>

                    <li class="nav-item">

                        <a class="nav-link collapsed" href="<?php echo $result_main->link ?>" data-toggle="collapse"
                           data-target="<?php echo $result_main->data_target ?>"
                           aria-expanded="true"
                           aria-controls="<?php echo $result_main->aria_controls ?>">
                            <?php echo "<i class='$result_main->icon'></i>" ?>
                            <span><?php echo $main_menu; ?></span>
                        </a>

                        <div id="<?php echo $result_main->aria_controls ?>" class="collapse"
                             aria-labelledby="headingBootstrap"
                             data-parent="#accordionSidebar">
                            <div class="bg-white py-2 collapse-inner rounded">

                                <?php
                                foreach ($sub_menus_ids as $sub_menus_id) {
                                    $result_sub = $subs_by_key[$main_menu_id . '_' . $sub_menus_id] ?? null;
                                    if (!$result_sub) continue;

                                    $sub_menu = $_SESSION['lang'] == "th" ? $result_sub->label : $result_sub->label_en;
                                    $target_attr = '';
                                    if (!empty($result_sub->link_target) && $result_sub->link_target !== '_self') {
                                        $target_val = trim($result_sub->link_target);
                                        if ($target_val === 'blank' || $target_val === '_blank') {
                                            $target_val = '_blank';
                                        }
                                        $target_attr = ' target="' . htmlspecialchars($target_val) . '"';
                                    }
                                    ?>
                                    <a class="collapse-item"
                                       href="<?php echo $result_sub->link . '?m=' . urlencode($main_menu) . '&s=' . urlencode($sub_menu) ?>"<?php echo $target_attr; ?>>
                                        <?php echo "<i class='$result_sub->icon'></i>" ?>
                                        <span><?php echo $sub_menu; ?></span>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                    </li>
                <?php
                }
            }
            }
        }
    }?>
    <hr class="sidebar-divider">

    <!--li class="nav-item active">
        <?php $localIP = getHostByName(getHostName()); ?>
        <a class="nav-link" href="<?php echo "http://" . $localIP . ":8888/jaiPrompt_Front/"; ?>" target="_blank">
            <i class="fa fa-link"></i>
            <span>Front End</span></a>
    </li-->

    <li class="nav-item active">
        <a class="nav-link" href="javascript:void(0);" data-toggle="modal" data-target="#logoutModal">
            <i class="fas fa-sign-out-alt"></i>
            <span>Exit</span></a>
    </li>





</ul>
<!-- Sidebar -->