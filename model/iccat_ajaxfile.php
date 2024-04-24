<?php
session_start();
error_reporting(0);
include '../config/connect_db.php';

// Number of records fetch
$numberofrecords = 500000;

$pgroup_id = $_POST["ICCAT_ID"];

/*
$myfile = fopen("param_post_mysql.txt", "w") or die("Unable to open file!");
fwrite($myfile, $pgroup_id);
fclose($myfile);
*/

if(!isset($_POST['searchTerm'])){

	// Fetch records
	$stmt = $conn->prepare("SELECT pgroup_id , pgroup_name FROM ims_pgroup WHERE pgroup_id LIKE '". $pgroup_id . "' GROUP BY pgroup_id,pgroup_name LIMIT :limit");
	$stmt->bindValue(':limit', (int)$numberofrecords, PDO::PARAM_INT);
	$stmt->execute();
	$custsList = $stmt->fetchAll();

}else{

	$search = $_POST['searchTerm'];// Search text
	
	// Fetch records
    $stmt = $conn->prepare("SELECT pgroup_id , pgroup_name FROM ims_pgroup WHERE  pgroup_name like :pgroup_name ORDER BY pgroup_id LIMIT :limit");
	$stmt->bindValue(':pgroup_name', '%'.$search.'%', PDO::PARAM_STR);
	$stmt->bindValue(':limit', (int)$numberofrecords, PDO::PARAM_INT);
	$stmt->execute();
	$custsList = $stmt->fetchAll();

}
	
$response = array();

// Read Data
foreach($custsList as $cust){
	$response[] = array(
		"id" => $cust['pgroup_id'],
		"text" => $cust['pgroup_id'] . " [" . $cust['pgroup_name'] . "]"
    );
}

echo json_encode($response);
exit();
