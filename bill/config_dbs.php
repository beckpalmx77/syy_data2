<?php

$host = "localhost"; /* Host name */
//$host = "192.168.39.13"; /* Host name */
$port = "3307"; /* Host name */
$user = "myadmin"; /* User */
$password = "myadmin"; /* Password */
$dbname = "syy_data2"; /* Database name */

$con = mysqli_connect($host, $user, $password,$dbname,$port);
// Check connection
if (!$con) {
 die("Connection failed: " . mysqli_connect_error());
}