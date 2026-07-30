<?php

ini_set('display_errors', 1);
error_reporting(~0);

include("../config/connect_sqlserver.php");
include("../config/connect_db_wp.php");
include('../cond_file/query-product-price-main.php');
include("../util/getdata_field.php");

$IMG_DIR = "http://171.100.56.194:8999/sac_tires/wp-content/uploads/products/";
$price_code = "S3";

$sql_where_ext = " AND SKUMASTER.SKU_ENABLE = 'Y' AND ICCAT_CODE  in ('1SAC14','4SAC01','3SAC01','1SAC06','1SAC05','1SAC01','1SAC02','1SAC03','1SAC04','1SAC08','1SAC07',
'1SAC09','1SAC10','1SAC11','1SAC12','1SAC13','2SAC09','2SAC04','2SAC13','2SAC14','2SAC02','2SAC03',
'2SAC10','2SAC06','2SAC05','2SAC07','2SAC08','3SAC02','3SAC06','3SAC03','3SAC04','4SAC02','4SAC03',
'4SAC04','4SAC06','3SAC05','4SAC05') AND ARPRB_CODE like '" . $price_code . "'";

$sql_order = " ORDER BY SKU_KEY DESC ";
$sql_sqlsvr = $select_query . $sql_cond . $sql_where_ext . $sql_order;

$stmt_sqlsvr = $conn_sqlsvr->prepare($sql_sqlsvr);
$stmt_sqlsvr->execute();

$sql_find_meta = "SELECT post_id FROM wp_postmeta WHERE meta_key = '_sku' AND meta_value = :sku";
$stmt_find_meta = $conn->prepare($sql_find_meta);

$sql_update_meta = "UPDATE wp_postmeta SET meta_value = :price WHERE post_id = :post_id AND meta_key IN ('_regular_price', '_price')";
$stmt_update_meta = $conn->prepare($sql_update_meta);

$sql_insert_post = "INSERT INTO wp_posts (post_author,post_type,post_title,post_content,sku,post_name,post_parent,post_status,comment_status,ping_status)
VALUES (:post_author,:post_type,:post_title,:post_content,:sku,:post_name,:post_parent,:post_status,:comment_status,:ping_status)";
$stmt_insert_post = $conn->prepare($sql_insert_post);

$sql_update_guid = "UPDATE wp_posts SET guid = :guid WHERE ID = :post_id";
$stmt_update_guid = $conn->prepare($sql_update_guid);

$sql_insert_meta_single = "INSERT INTO wp_postmeta (post_id,meta_key,meta_value) VALUES (:post_id,:meta_key,:meta_value)";
$stmt_insert_meta_single = $conn->prepare($sql_insert_meta_single);

$sql_insert_term_rel = "INSERT INTO wp_term_relationships (object_id,term_taxonomy_id,term_order) VALUES (:object_id,:term_taxonomy_id,0)";
$stmt_insert_term_rel = $conn->prepare($sql_insert_term_rel);

$sql_find_terms = "SELECT term_id FROM wp_terms WHERE name = :name";
$stmt_find_terms = $conn->prepare($sql_find_terms);

$author = 1;
$post_type = "product";
$post_status = "publish";
$comment_status = "open";
$ping_status = "closed";
$post_parent = 7844;

$update_count = 0;
$insert_count = 0;

$conn->beginTransaction();
try {
    while ($result_sqlsvr = $stmt_sqlsvr->fetch(PDO::FETCH_ASSOC)) {
        $sku = $result_sqlsvr["SKU_CODE"];
        $price = $result_sqlsvr["ARPLU_U_PRC"];

        $stmt_find_meta->execute([':sku' => $sku]);
        $existing_posts = $stmt_find_meta->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($existing_posts)) {
            foreach ($existing_posts as $post_id) {
                $stmt_update_meta->execute([':price' => $price, ':post_id' => $post_id]);
            }
            $update_count++;
        } else {
            $post_name = strtolower(str_replace(array("/", " "), "-", $sku));

            $stmt_insert_post->execute([
                ':post_author' => $author,
                ':post_type' => $post_type,
                ':post_title' => $result_sqlsvr["SKU_NAME"],
                ':post_content' => $result_sqlsvr["SKU_NAME"],
                ':sku' => $sku,
                ':post_name' => $post_name,
                ':post_parent' => $post_parent,
                ':post_status' => $post_status,
                ':comment_status' => $comment_status,
                ':ping_status' => $ping_status
            ]);
            $post_id = $conn->lastInsertId();

            if ($post_id) {
                $BRN_CODE = substr($result_sqlsvr['BRN_CODE'], 0, 2);
                include 'cond_brand.php';

                $guid = "http://171.100.56.194:8999/sac_tires/?post_type=product&#038;p=" . $post_id;
                $stmt_update_guid->execute([':guid' => $guid, ':post_id' => $post_id]);

                $meta_entries = array(
                    '_product_version' => '8.02',
                    '_wc_review_count' => '0',
                    '_wc_average_rating' => '0',
                    '_stock_status' => 'onbackorder',
                    '_stock' => '0',
                    '_download_expiry' => '0',
                    '_download_limit' => '0',
                    '_downloadable' => 'no',
                    '_virtual' => 'no',
                    '_sold_individually' => 'no',
                    '_backorders' => 'yes',
                    '_manage_stock' => 'yes',
                    'total_sales' => 'yes',
                    '_tax_status' => 'taxable',
                    '_tax_class' => '',
                    '_sku' => $sku,
                    '_wp_old_slug' => '',
                    '_regular_price' => $price,
                    '_low_stock_amount' => '0',
                    '_weight' => '0',
                    '_length' => '0',
                    '_width' => '0',
                    '_height' => '0',
                    '_purchase_note' => '',
                    '_thumbnail_id' => isset($thumbnail_id) ? $thumbnail_id : 0,
                    '_product_attributes' => 'a:1:{s:0:\"\";a:6:{s:4:\"name\";s:1:\" \";s:5:\"value\";s:0:\"\";s:8:\"position\";i:0;s:10:\"is_visible\";i:0;s:12:\"is_taxonomy\";i:0;}}',
                    '_price' => $price
                );

                foreach ($meta_entries as $mk => $mv) {
                    $stmt_insert_meta_single->execute([
                        ':post_id' => $post_id,
                        ':meta_key' => $mk,
                        ':meta_value' => $mv
                    ]);
                }

                $stmt_insert_term_rel->execute([':object_id' => $post_id, ':term_taxonomy_id' => 54]);
                $stmt_insert_term_rel->execute([':object_id' => $post_id, ':term_taxonomy_id' => 55]);

                if (!empty($BRN_NAME)) {
                    $stmt_find_terms->execute([':name' => $BRN_NAME]);
                    $term_id = $stmt_find_terms->fetchColumn();
                    if ($term_id) {
                        $stmt_insert_term_rel->execute([':object_id' => $post_id, ':term_taxonomy_id' => $term_id]);
                    }
                }
                $insert_count++;
            }
        }
    }
    $conn->commit();
    echo "Load WP product completed. Updated: $update_count, Inserted: $insert_count\n\r";
} catch (Exception $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}

$conn_sqlsvr = null;

