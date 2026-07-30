<?php

ini_set('display_errors', 1);
error_reporting(~0);

include ("../config/connect_sqlserver.php");
include ("../config/connect_db.php");

include ("../cond_file/doc_info_customer_ar.php");

$sql_sqlsvr = $select_query . $sql_cond . $sql_order ;

$stmt_sqlsvr = $conn_sqlsvr->prepare($sql_sqlsvr);
$stmt_sqlsvr->execute();

$sql_find = "SELECT COUNT(*) FROM ims_customer_ar WHERE customer_id = :customer_id";
$stmt_find = $conn->prepare($sql_find);

$sql_update = "UPDATE ims_customer_ar SET tax_id=:tax_id,f_name=:f_name,credit=:credit,
phone=:phone,address=:address,tumbol=:tumbol,amphure=:amphure,province=:province,zipcode=:zipcode,ARCD_NAME=:ARCD_NAME,
sale_name=:sale_name,contact_name=:contact_name
WHERE customer_id = :customer_id";
$stmt_update = $conn->prepare($sql_update);

$sql_insert = "INSERT INTO ims_customer_ar(customer_id,tax_id,f_name,credit,phone,address,tumbol,amphure,province
,zipcode,ARCD_NAME,sale_name,contact_name)
VALUES (:customer_id,:tax_id,:f_name,:credit,:phone,:address,:tumbol,:amphure,:province
,:zipcode,:ARCD_NAME,:sale_name,:contact_name)";
$stmt_insert = $conn->prepare($sql_insert);

$update_count = 0;
$insert_count = 0;

$conn->beginTransaction();
try {
    while ($result_sqlsvr = $stmt_sqlsvr->fetch(PDO::FETCH_ASSOC)) {
        $contact_name = trim($result_sqlsvr["CT_INTL"] . " " . $result_sqlsvr["CT_NAME"] . " " . $result_sqlsvr["CT_SURNME"]) . " - " . $result_sqlsvr["CT_JOBTITLE"];

        $stmt_find->execute([':customer_id' => $result_sqlsvr["AR_CODE"]]);
        $nRows = $stmt_find->fetchColumn();

        if ($nRows > 0) {
            $stmt_update->execute([
                ':tax_id' => $result_sqlsvr["ADDB_TAX_ID"],
                ':f_name' => $result_sqlsvr["AR_NAME"],
                ':credit' => $result_sqlsvr["ARS_CRE_LIM"],
                ':phone' => $result_sqlsvr["ADDB_PHONE"],
                ':address' => $result_sqlsvr["ADDB_ADDB_1"],
                ':tumbol' => $result_sqlsvr["ADDB_ADDB_2"],
                ':amphure' => $result_sqlsvr["ADDB_ADDB_3"],
                ':province' => $result_sqlsvr["ADDB_PROVINCE"],
                ':zipcode' => $result_sqlsvr["ADDB_POST"],
                ':ARCD_NAME' => $result_sqlsvr["ARCD_NAME"],
                ':sale_name' => $result_sqlsvr["SLMN_NAME"],
                ':contact_name' => $contact_name,
                ':customer_id' => $result_sqlsvr["AR_CODE"]
            ]);
            $update_count++;
        } else {
            $stmt_insert->execute([
                ':customer_id' => $result_sqlsvr["AR_CODE"],
                ':tax_id' => $result_sqlsvr["ADDB_TAX_ID"],
                ':f_name' => $result_sqlsvr["AR_NAME"],
                ':credit' => $result_sqlsvr["ARS_CRE_LIM"],
                ':phone' => $result_sqlsvr["ADDB_PHONE"],
                ':address' => $result_sqlsvr["ADDB_ADDB_1"],
                ':tumbol' => $result_sqlsvr["ADDB_ADDB_2"],
                ':amphure' => $result_sqlsvr["ADDB_ADDB_3"],
                ':province' => $result_sqlsvr["ADDB_PROVINCE"],
                ':zipcode' => $result_sqlsvr["ADDB_POST"],
                ':ARCD_NAME' => $result_sqlsvr["ARCD_NAME"],
                ':sale_name' => $result_sqlsvr["SLMN_NAME"],
                ':contact_name' => $contact_name
            ]);
            $insert_count++;
        }
    }
    $conn->commit();
    echo "Import customer_ar completed. Updated: $update_count, Inserted: $insert_count\n\r";
} catch (Exception $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}

$conn_sqlsvr = null;

