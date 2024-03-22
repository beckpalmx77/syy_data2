<?php

$select_query = "
SELECT
 SKUMASTER.SKU_KEY, 
 SKUMASTER.SKU_CODE, 
 SKUMASTER.SKU_NAME,
 SKUMASTER.SKU_BRN,
 SKUMASTER.SKU_ICCAT,
 SKUMASTER.SKU_S_UTQ,
 SKUMASTER.SKU_K_UTQ,
 SKUMASTER.SKU_VAT_TY,
 SKUMASTER.SKU_VAT,
 SKUMASTER.SKU_COST_TY,
 SKUMASTER.SKU_STD_COST,
 SKUMASTER.SKU_STOCK,
 SKUMASTER.SKU_SKUALT,
 SKUMASTER.SKU_WH_TY,
 SKUMASTER.SKU_WH_RATE,
 SKUMASTER.SKU_MSG_1,
 SKUMASTER.SKU_MSG_2,
 SKUMASTER.SKU_MSG_3,
 BRAND.BRN_NAME,
 ICCAT.ICCAT_NAME,
 SKUALT.SKUALT_NAME,
 GOODSMASTER.GOODS_CODE,
 GOODSMASTER.GOODS_ALIAS, 
 GOODSMASTER.GOODS_WEIGHT,
 UOFQTY.UTQ_NAME,
 ARPLU.ARPLU_U_PRC,
 ARPLU.ARPLU_U_DSC,
 PRICETAG.TAG_NAME,
 ARPRICETAB.ARPRB_CODE,
 ARPRICETAB.ARPRB_NAME,
 PRICETAG.TAG_CODE,
 GOODSMASTER.GOODS_VOLUME,
 ICCAT.ICCAT_CODE,
 ICDEPT.ICDEPT_CODE,
 BRAND.BRN_CODE,
 ARPRB_VATIO,
 GOODSMASTER.GOODS_PRICE

FROM
 SKUMASTER,
 SKUALT,
 UOFQTY,
 BRAND,
 ICCAT,
 GOODSMASTER,
 PRICETAG,
 ARPLU,
 ARPRICETAB,
ICDEPT ";

$sql_cond = " WHERE
 (SKUMASTER.SKU_SKUALT= SKUALT.SKUALT_KEY) AND
 (SKUMASTER.SKU_BRN = BRAND.BRN_KEY)  AND
 (SKUMASTER.SKU_ICCAT = ICCAT.ICCAT_KEY) AND
 (SKUMASTER.SKU_KEY = GOODSMASTER.GOODS_SKU) AND
 (GOODSMASTER.GOODS_UTQ = UOFQTY.UTQ_KEY) AND
 (ARPLU.ARPLU_TAG = PRICETAG.TAG_KEY) AND
 (ARPLU.ARPLU_GOODS=GOODSMASTER.GOODS_KEY) AND
 (ARPLU.ARPLU_ARPRB = ARPRICETAB.ARPRB_KEY) AND
 (SKUMASTER.SKU_ICDEPT=ICDEPT.ICDEPT_KEY)";

$sql_order = " ORDER BY ARPLU_LASTUPD DESC ";
