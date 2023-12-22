<?php
header('Content-Type: application/json');

include("../config/connect_db.php");

$date = date("Y/m/d");

//$myfile = fopen("param.txt", "w") or die("Unable to open file!");
//fwrite($myfile, $month  . "| Year = " . $year . "| SLMN_NAME" . $SLMN_NAME );
//fclose($myfile);

$sql_get = "
 SELECT SLMN_NAME,sum(CAST(TRD_G_KEYIN AS DECIMAL(10,2))) as  TRD_G_KEYIN
 FROM ims_product_sale_sac 
 WHERE STR_TO_DATE(DI_DATE,'%d/%m/%Y') BETWEEN CAST('" . $date . "' AS DATE) AND CAST('" . $date . "' AS DATE)
 GROUP BY  SLMN_NAME
 ORDER BY SLMN_NAME
";

//$myfile = fopen("qry_file1.txt", "w") or die("Unable to open file!");
//fwrite($myfile, $sql_get);
//fclose($myfile);

$return_arr = array();

$statement = $conn->query($sql_get);
$results = $statement->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $result) {
    $return_arr[] = array("SLMN_NAME" => $result['SLMN_NAME'],
        "TRD_G_KEYIN" => $result['TRD_G_KEYIN']);

}

echo json_encode($return_arr);
