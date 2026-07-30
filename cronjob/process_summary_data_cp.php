<?php

ini_set('display_errors', 1);
error_reporting(~0);

include("../config/connect_db.php");

$year = date("Y");

$sql_find = "SELECT COUNT(*) FROM ims_report_product_sale_summary_2 WHERE SLMN_MAN = :SLMN_MAN AND DI_MONTH = :DI_MONTH AND DI_YEAR = :DI_YEAR";
$stmt_find = $conn->prepare($sql_find);

// P1, P2, P3 total
$stmt_get_all = $conn->prepare("SELECT SLMN_MAN,DI_MONTH,DI_MONTH_NAME,DI_YEAR,
 sum(CAST(TRD_QTY AS DECIMAL(10,2))) as TRD_QTY,
 sum(CAST(TRD_G_KEYIN AS DECIMAL(10,2))) as TRD_G_KEYIN
 FROM ims_product_sale_sac 
 WHERE DI_MONTH = :month AND DI_YEAR = :year AND PGROUP IN ('P1','P2','P3')
 GROUP BY SLMN_MAN,DI_MONTH,DI_MONTH_NAME,DI_YEAR 
 ORDER BY DI_MONTH, TRD_G_KEYIN DESC");

$stmt_update_all = $conn->prepare("UPDATE ims_report_product_sale_summary_2 SET total_amt=:total_amt WHERE SLMN_MAN = :SLMN_MAN AND DI_MONTH=:DI_MONTH AND DI_YEAR=:DI_YEAR");
$stmt_insert_all = $conn->prepare("INSERT INTO ims_report_product_sale_summary_2(SLMN_MAN,DI_MONTH,DI_MONTH_NAME,DI_YEAR,total_amt) VALUES (:SLMN_MAN,:DI_MONTH,:DI_MONTH_NAME,:DI_YEAR,:total_amt)");

// P1 (tires)
$stmt_get_p1 = $conn->prepare("SELECT SLMN_MAN,DI_MONTH,DI_MONTH_NAME,DI_YEAR,
 sum(CAST(TRD_QTY AS DECIMAL(10,2))) as TRD_QTY,
 sum(CAST(TRD_G_KEYIN AS DECIMAL(10,2))) as TRD_G_KEYIN
 FROM ims_product_sale_sac 
 WHERE DI_MONTH = :month AND DI_YEAR = :year AND PGROUP = 'P1'
 GROUP BY SLMN_MAN,DI_MONTH,DI_MONTH_NAME,DI_YEAR 
 ORDER BY DI_MONTH, TRD_G_KEYIN DESC");

$stmt_update_p1 = $conn->prepare("UPDATE ims_report_product_sale_summary_2 SET DI_MONTH_NAME=:DI_MONTH_NAME,tires_total_qty=:tires_total_qty,tires_total_amt=:tires_total_amt WHERE SLMN_MAN = :SLMN_MAN AND DI_MONTH=:DI_MONTH AND DI_YEAR=:DI_YEAR");
$stmt_insert_p1 = $conn->prepare("INSERT INTO ims_report_product_sale_summary_2(SLMN_MAN,DI_MONTH,DI_YEAR,tires_total_qty,tires_total_amt) VALUES (:SLMN_MAN,:DI_MONTH,:DI_YEAR,:tires_total_qty,:tires_total_amt)");

// P2 (parts)
$stmt_get_p2 = $conn->prepare("SELECT SLMN_MAN,DI_MONTH,DI_MONTH_NAME,DI_YEAR,
 sum(CAST(TRD_QTY AS DECIMAL(10,2))) as TRD_QTY,
 sum(CAST(TRD_G_KEYIN AS DECIMAL(10,2))) as TRD_G_KEYIN
 FROM ims_product_sale_sac 
 WHERE DI_MONTH = :month AND DI_YEAR = :year AND PGROUP = 'P2'
 GROUP BY SLMN_MAN,DI_MONTH,DI_MONTH_NAME,DI_YEAR 
 ORDER BY DI_MONTH, TRD_G_KEYIN DESC");

$stmt_update_p2 = $conn->prepare("UPDATE ims_report_product_sale_summary_2 SET part_total_amt=:part_total_amt WHERE SLMN_MAN = :SLMN_MAN AND DI_MONTH=:DI_MONTH AND DI_YEAR=:DI_YEAR");

// P3 (services)
$stmt_get_p3 = $conn->prepare("SELECT SLMN_MAN,DI_MONTH,DI_MONTH_NAME,DI_YEAR,
 sum(CAST(TRD_QTY AS DECIMAL(10,2))) as TRD_QTY,
 sum(CAST(TRD_G_KEYIN AS DECIMAL(10,2))) as TRD_G_KEYIN
 FROM ims_product_sale_sac 
 WHERE DI_MONTH = :month AND DI_YEAR = :year AND PGROUP = 'P3'
 GROUP BY SLMN_MAN,DI_MONTH,DI_MONTH_NAME,DI_YEAR 
 ORDER BY DI_MONTH, TRD_G_KEYIN DESC");

$stmt_update_p3 = $conn->prepare("UPDATE ims_report_product_sale_summary_2 SET svr_total_amt=:svr_total_amt WHERE SLMN_MAN = :SLMN_MAN AND DI_MONTH=:DI_MONTH AND DI_YEAR=:DI_YEAR");

$conn->beginTransaction();
try {
    for ($month = 1; $month <= 12; $month++) {
        // Step 1: All P1, P2, P3
        $stmt_get_all->execute([':month' => $month, ':year' => $year]);
        while ($row = $stmt_get_all->fetch(PDO::FETCH_ASSOC)) {
            $stmt_find->execute([':SLMN_MAN' => $row["SLMN_MAN"], ':DI_MONTH' => $row["DI_MONTH"], ':DI_YEAR' => $row["DI_YEAR"]]);
            if ($stmt_find->fetchColumn() > 0) {
                $stmt_update_all->execute([':total_amt' => $row["TRD_G_KEYIN"], ':SLMN_MAN' => $row["SLMN_MAN"], ':DI_MONTH' => $row["DI_MONTH"], ':DI_YEAR' => $row["DI_YEAR"]]);
            } else {
                $stmt_insert_all->execute([':SLMN_MAN' => $row["SLMN_MAN"], ':DI_MONTH' => $row["DI_MONTH"], ':DI_MONTH_NAME' => $row["DI_MONTH_NAME"], ':DI_YEAR' => $row["DI_YEAR"], ':total_amt' => $row["TRD_G_KEYIN"]]);
            }
        }

        // Step 2: P1
        $stmt_get_p1->execute([':month' => $month, ':year' => $year]);
        while ($row = $stmt_get_p1->fetch(PDO::FETCH_ASSOC)) {
            $stmt_find->execute([':SLMN_MAN' => $row["SLMN_MAN"], ':DI_MONTH' => $row["DI_MONTH"], ':DI_YEAR' => $row["DI_YEAR"]]);
            if ($stmt_find->fetchColumn() <= 0) {
                $stmt_insert_p1->execute([':SLMN_MAN' => $row["SLMN_MAN"], ':DI_MONTH' => $row["DI_MONTH"], ':DI_YEAR' => $row["DI_YEAR"], ':tires_total_qty' => $row["TRD_QTY"], ':tires_total_amt' => $row["TRD_G_KEYIN"]]);
            } else {
                $stmt_update_p1->execute([':DI_MONTH_NAME' => $row["DI_MONTH_NAME"], ':tires_total_qty' => $row["TRD_QTY"], ':tires_total_amt' => $row["TRD_G_KEYIN"], ':SLMN_MAN' => $row["SLMN_MAN"], ':DI_MONTH' => $row["DI_MONTH"], ':DI_YEAR' => $row["DI_YEAR"]]);
            }
        }

        // Step 3: P2
        $stmt_get_p2->execute([':month' => $month, ':year' => $year]);
        while ($row = $stmt_get_p2->fetch(PDO::FETCH_ASSOC)) {
            $stmt_find->execute([':SLMN_MAN' => $row["SLMN_MAN"], ':DI_MONTH' => $row["DI_MONTH"], ':DI_YEAR' => $row["DI_YEAR"]]);
            if ($stmt_find->fetchColumn() > 0) {
                $stmt_update_p2->execute([':part_total_amt' => $row["TRD_G_KEYIN"], ':SLMN_MAN' => $row["SLMN_MAN"], ':DI_MONTH' => $row["DI_MONTH"], ':DI_YEAR' => $row["DI_YEAR"]]);
            }
        }

        // Step 4: P3
        $stmt_get_p3->execute([':month' => $month, ':year' => $year]);
        while ($row = $stmt_get_p3->fetch(PDO::FETCH_ASSOC)) {
            $stmt_find->execute([':SLMN_MAN' => $row["SLMN_MAN"], ':DI_MONTH' => $row["DI_MONTH"], ':DI_YEAR' => $row["DI_YEAR"]]);
            if ($stmt_find->fetchColumn() > 0) {
                $stmt_update_p3->execute([':svr_total_amt' => $row["TRD_G_KEYIN"], ':SLMN_MAN' => $row["SLMN_MAN"], ':DI_MONTH' => $row["DI_MONTH"], ':DI_YEAR' => $row["DI_YEAR"]]);
            }
        }
    }
    $conn->commit();
    echo "Process summary data CP completed.\n\r";
} catch (Exception $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}
