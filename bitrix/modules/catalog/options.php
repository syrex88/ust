<?
$module_id = "catalog";

define('NO_AGENT_CHECK', true);

define('CATALOG_NEW_OFFERS_IBLOCK_NEED','-1');

if ($USER->CanDoOperation('catalog_read') || $USER->CanDoOperation('catalog_settings'))
{
	$bReadOnly = !$USER->CanDoOperation('catalog_settings');

	IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/options.php");
	IncludeModuleLangFile(__FILE__);

	include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/include.php");

	if ($_SERVER['REQUEST_METHOD']=="GET" && !empty($_REQUEST['RestoreDefaults']) && !$bReadOnly && check_bitrix_sessid())
	{
		if (!$USER->IsAdmin())
			$strValTmp = COption::GetOptionString("catalog", "avail_content_groups");

		COption::RemoveOption("catalog");
		$z = CGroup::GetList(($v1="id"),($v2="asc"), array("ACTIVE" => "Y", "ADMIN" => "N"));
		while($zr = $z->Fetch())
			$APPLICATION->DelGroupRight($module_id, array($zr["ID"]));

		if (!$USER->IsAdmin())
			COption::SetOptionString("catalog", "avail_content_groups", $strValTmp);
	}

	$arAllOptions = array(
		array("export_default_path", GetMessage("CAT_EXPORT_DEFAULT_PATH"), "/bitrix/catalog_export/", array("text", 30)),
		array("default_catalog_1c", GetMessage("CAT_DEF_IBLOCK"), "", array("text", 30)),
		array("deactivate_1c_no_price", GetMessage("CAT_DEACT_NOPRICE"), "N", array("checkbox")),
		array("yandex_xml_period", GetMessage("CAT_YANDEX_XML_PERIOD"), "24", array("text", 5)),
	);

	$strWarning = "";
	$strOK = "";
	if ($_SERVER['REQUEST_METHOD']=="POST" && !empty($_POST['Update']) && !$bReadOnly && check_bitrix_sessid())
	{
		for ($i=0, $cnt = count($arAllOptions); $i < $cnt; $i++)
		{
			$name = $arAllOptions[$i][0];
			$val = (isset($_POST[$name]) ? $_POST[$name] : '');
			if ($arAllOptions[$i][3][0]=="checkbox" && $val!="Y")
				$val = "N";
			if ($name == 'export_default_path')
			{
				$boolExpPath = true;
				if (empty($val))
				{
					$boolExpPath = false;
				}
				if ($boolExpPath)
				{
					$val = str_replace('//','/',Rel2Abs('/', $val.'/'));
					if (preg_match(BX_CATALOG_FILENAME_REG, $val))
						$boolExpPath = false;
				}
				if ($boolExpPath)
				{
					if (empty($val) || '/' == $val)
						$boolExpPath = false;
				}
				if ($boolExpPath)
				{
					if (!file_exists($_SERVER['DOCUMENT_ROOT'].$val) || !is_dir($_SERVER['DOCUMENT_ROOT'].$val))
						$boolExpPath = false;
				}
				if ($boolExpPath)
				{
					if ('W' > $APPLICATION->GetFileAccessPermission($val))
						$boolExpPath = false;
				}

				if ($boolExpPath)
				{
					COption::SetOptionString("catalog", $name, $val, $arAllOptions[$i][1]);
				}
				else
				{
					$strWarning .= GetMessage('CAT_PATH_ERR_EXPORT_FOLDER_BAD').'<br />';
				}
			}
			else
			{
				COption::SetOptionString("catalog", $name, $val, $arAllOptions[$i][1]);
			}
		}

		$default_outfile_action = (isset($_REQUEST['default_outfile_action']) ? (string)$_REQUEST['default_outfile_action'] : '');
		if ($default_outfile_action!="D" && $default_outfile_action!="H" && $default_outfile_action!="F")
		{
			$default_outfile_action = "D";
		}
		COption::SetOptionString("catalog", "default_outfile_action", $default_outfile_action, "");

		$strYandexAgent = '';
		$strYandexAgent = trim($_POST['yandex_agent_file']);
		if (!empty($strYandexAgent))
		{
			$strYandexAgent = Rel2Abs('/', $strYandexAgent);
			if (preg_match(BX_CATALOG_FILENAME_REG, $val) || (!file_exists($_SERVER['DOCUMENT_ROOT'].$strYandexAgent) || !is_file($_SERVER['DOCUMENT_ROOT'].$strYandexAgent)))
			{
				$strWarning .= GetMessage('CAT_PATH_ERR_YANDEX_AGENT').'<br />';
				$strYandexAgent = '';
			}
		}
		COption::SetOptionString('catalog','yandex_agent_file', $strYandexAgent,GetMessage("CAT_AGENT_FILE"));

		$strAllowedProductFields = "";
		$allowed_product_fields = (isset($_REQUEST['allowed_product_fields']) ? $_REQUEST['allowed_product_fields'] : array());
		if (!is_array($allowed_product_fields))
			$allowed_product_fields = array($allowed_product_fields);
		for ($i = 0, $intCount = count($allowed_product_fields); $i < $intCount; $i++)
		{
			$allowed_product_fields[$i] = trim($allowed_product_fields[$i]);
			if (strlen($allowed_product_fields[$i])>0)
			{
				if (strlen($strAllowedProductFields)>0) $strAllowedProductFields .= ",";
				$strAllowedProductFields .= $allowed_product_fields[$i];
			}
		}
		COption::SetOptionString("catalog", "allowed_product_fields", $strAllowedProductFields);

		$strAllowedPriceFields = "";
		$allowed_price_fields = (isset($_REQUEST['allowed_price_fields']) ? $_REQUEST['allowed_price_fields'] : array());
		if (!is_array($allowed_price_fields))
			$allowed_price_fields = array($allowed_price_fields);
		for ($i = 0, $intCount = count($allowed_price_fields); $i < $intCount; $i++)
		{
			$allowed_price_fields[$i] = trim($allowed_price_fields[$i]);
			if (strlen($allowed_price_fields[$i]) > 0)
			{
				if (strlen($strAllowedPriceFields) > 0)
					$strAllowedPriceFields .= ",";
				$strAllowedPriceFields .= $allowed_price_fields[$i];
			}
		}
		COption::SetOptionString("catalog", "allowed_price_fields", $strAllowedPriceFields);

		$num_catalog_levels = intval(isset($_REQUEST['num_catalog_levels']) ? $_REQUEST['num_catalog_levels'] : 3);
		if (0 >= $num_catalog_levels)
			$num_catalog_levels = 3;
		COption::SetOptionInt("catalog", "num_catalog_levels", $num_catalog_levels);

		$strAllowedGroupFields = "";
		$allowed_group_fields = (isset($_REQUEST['allowed_group_fields']) ? $_REQUEST['allowed_group_fields'] : array());
		if (!is_array($allowed_group_fields))
			$allowed_group_fields = array($allowed_group_fields);
		for ($i = 0, $intCount = count($allowed_group_fields); $i < $intCount; $i++)
		{
			$allowed_group_fields[$i] = trim($allowed_group_fields[$i]);
			if (strlen($allowed_group_fields[$i])>0)
			{
				if (strlen($strAllowedGroupFields)>0) $strAllowedGroupFields .= ",";
				$strAllowedGroupFields .= $allowed_group_fields[$i];
			}
		}
		COption::SetOptionString("catalog", "allowed_group_fields", $strAllowedGroupFields);

		$strAllowedCurrencies = "";
		$allowed_currencies = (isset($_REQUEST['allowed_currencies']) ? $_REQUEST['allowed_currencies'] : array());
		if (!is_array($allowed_currencies))
			$allowed_currencies = array($allowed_currencies);
		for ($i = 0, $intCount = count($allowed_currencies); $i < $intCount; $i++)
		{
			$allowed_currencies[$i] = trim($allowed_currencies[$i]);
			if (strlen($allowed_currencies[$i])>0)
			{
				if (strlen($strAllowedCurrencies)>0) $strAllowedCurrencies .= ",";
				$strAllowedCurrencies .= $allowed_currencies[$i];
			}
		}
		COption::SetOptionString("catalog", "allowed_currencies", $strAllowedCurrencies);

		if ($USER->IsAdmin() && CBXFeatures::IsFeatureEnabled('SaleRecurring'))
		{
			$arOldAvailContentGroups = array();
			$oldAvailContentGroups = COption::GetOptionString("catalog", "avail_content_groups");
			if (strlen($oldAvailContentGroups) > 0)
				$arOldAvailContentGroups = explode(",", $oldAvailContentGroups);

			$availContentGroups = "";
			if (isset($AVAIL_CONTENT_GROUPS) && is_array($AVAIL_CONTENT_GROUPS))
			{
				for ($i = 0, $intCount = count($AVAIL_CONTENT_GROUPS); $i < $intCount; $i++)
				{
					$AVAIL_CONTENT_GROUPS[$i] = intval($AVAIL_CONTENT_GROUPS[$i]);
					if ($AVAIL_CONTENT_GROUPS[$i] > 0)
					{
						if (strlen($availContentGroups) > 0)
							$availContentGroups .= ",";

						$availContentGroups .= $AVAIL_CONTENT_GROUPS[$i];

						if (in_array($AVAIL_CONTENT_GROUPS[$i], $arOldAvailContentGroups))
						{
							$ind = array_search($AVAIL_CONTENT_GROUPS[$i], $arOldAvailContentGroups);
							unset($arOldAvailContentGroups[$ind]);
						}
					}
				}
			}

			foreach ($arOldAvailContentGroups as $key => $value)
				CCatalogProductGroups::DeleteByGroup($value);

			COption::SetOptionString("catalog", "avail_content_groups", $availContentGroups);
		}

		$strSaveProductWithoutPrice = (!empty($_REQUEST['save_product_without_price']) && $_REQUEST['save_product_without_price'] == 'Y' ? 'Y' : 'N');
		COption::SetOptionString('catalog', 'save_product_without_price', $strSaveProductWithoutPrice);

		$strUseStoreControlBeforeSubmit = COption::GetOptionString('catalog', 'default_use_store_control', 'N');
		$strUseStoreControl = (!empty($_REQUEST['use_store_control']) && $_REQUEST['use_store_control'] == 'Y' ? 'Y' : 'N');

		$strAllowCanBuyZero = (!empty($_REQUEST['allow_can_buy_zero']) && $_REQUEST['allow_can_buy_zero'] == 'Y' ? 'Y' : 'N');
		COption::SetOptionString('catalog', 'default_can_buy_zero', $strAllowCanBuyZero);

		$strQuantityTrace = (!empty($_REQUEST['quantity_trace']) && $_REQUEST['quantity_trace'] == 'Y' ? 'Y' : 'N');
		COption::SetOptionString('catalog', 'default_quantity_trace', $strQuantityTrace);

		$strAllowNegativeAmount = (!empty($_REQUEST['allow_negative_amount']) && $_REQUEST['allow_negative_amount'] == 'Y' ? 'Y' : 'N');
		COption::SetOptionString('catalog', 'allow_negative_amount', $strAllowNegativeAmount);

		if (isset($_REQUEST['default_subscribe']) && !empty($_REQUEST['default_subscribe']))
		{
			$strSubscribe = ('N' == strval($_REQUEST['default_subscribe']) ? 'N' : 'Y');
			COption::SetOptionString('catalog', 'default_subscribe', $strSubscribe);
		}
		if (isset($_REQUEST['show_catalog_tab_with_offers']) && !empty($_REQUEST['show_catalog_tab_with_offers']))
		{
			$strShowCatalogTab = ('Y' == strval($_REQUEST['show_catalog_tab_with_offers']) ? 'Y' : 'N');
			Coption::SetOptionString('catalog', 'show_catalog_tab_with_offers', $strShowCatalogTab);
		}

		if ($strUseStoreControl == 'Y')
			$strEnableReservation = 'Y';
		else
			$strEnableReservation = (!empty($_REQUEST['enable_reservation']) && $_REQUEST['enable_reservation'] == 'Y' ? 'Y' : 'N');
		COption::SetOptionString('catalog', 'enable_reservation', $strEnableReservation);

		if (CBXFeatures::IsFeatureEnabled('CatDiscountSave'))
		{
			$strDiscSaveApply = (!empty($_REQUEST['discsave_apply']) && in_array($_REQUEST['discsave_apply'], array('R','A','D')) ? $_REQUEST['discsave_apply'] : 'R');
			COption::SetOptionString('catalog', 'discsave_apply', $strDiscSaveApply);
		}

		if ($strUseStoreControlBeforeSubmit != $strUseStoreControl)
		{
			if ($strUseStoreControl == 'Y')
			{
				$dbStores = CCatalogStore::GetList(array(), array("ACTIVE" => 'Y'));
				if (!$dbStores->Fetch())
				{
					$arStoreFields = array("TITLE" => GetMessage("CAT_STORE_NAME"), "ADDRESS" => " ");
					$newStoreId = CCatalogStore::Add($arStoreFields);
					if ($newStoreId)
					{
						CCatalogDocs::synchronizeStockQuantity($newStoreId);
					}
					else
					{
						$strWarning .= GetMessage("CAT_STORE_ACTIVE_ERROR");
						$strUseStoreControl = 'N';
					}
				}
				else
				{
					$strWarning .= GetMessage("CAT_STORE_SYNCHRONIZE_ERROR");
				}
			}
			elseif($strUseStoreControl == 'N')
			{
				$strWarning .= GetMessage("CAT_STORE_DEACTIVATE_NOTICE");
			}
		}

		COption::SetOptionString('catalog', 'default_use_store_control', $strUseStoreControl);

/*	$strDiscountVat = (!empty($_REQUEST['discount_vat']) && $_REQUEST['discount_vat'] == 'N' ? 'N' : 'Y');
	COption::SetOptionString('catalog', 'discount_vat', $strDiscountVat); */

		$bNeedAgent = false;

		$boolFlag = true;
		$arCurrentIBlocks = array();
		$arNewIBlocksList = array();
		$rsIBlocks = CIBlock::GetList(array());
		while ($arOneIBlock = $rsIBlocks->Fetch())
		{
			// Current info
			$arOneIBlock['ID'] = intval($arOneIBlock['ID']);
			$arIBlockItem = array();
			$arIBlockSitesList = array();
			$rsIBlockSites = CIBlock::GetSite($arOneIBlock['ID']);
			while ($arIBlockSite = $rsIBlockSites->Fetch())
			{
				$arIBlockSitesList[] = htmlspecialcharsbx($arIBlockSite['SITE_ID']);
			}

			$strInfo = '['.$arOneIBlock['IBLOCK_TYPE_ID'].'] '.htmlspecialcharsbx($arOneIBlock['NAME']).' ('.implode(' ',$arIBlockSitesList).')';

			$arIBlockItem = array(
				'INFO' => $strInfo,
				'ID' => $arOneIBlock['ID'],
				'NAME' => $arOneIBlock['NAME'],
				'SITE_ID' => $arIBlockSitesList,
				'IBLOCK_TYPE_ID' => $arOneIBlock['IBLOCK_TYPE_ID'],
				'CATALOG' => 'N',
				'PRODUCT_IBLOCK_ID' => 0,
				'SKU_PROPERTY_ID' => 0,
				'OFFERS_IBLOCK_ID' => 0,
				'OFFERS_PROPERTY_ID' => 0,
			);
			$arCurrentIBlocks[$arOneIBlock['ID']] = $arIBlockItem;
		}
		$arCatalogList = array();
		$rsCatalogs = CCatalog::GetList(
			array(),
			array(),
			false,
			false,
			array('IBLOCK_ID', 'SUBSCRIPTION', 'YANDEX_EXPORT', 'VAT_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID')
		);
		while ($arCatalog = $rsCatalogs->Fetch())
		{
			$arCatalog['IBLOCK_ID'] = intval($arCatalog['IBLOCK_ID']);
			$arCatalog['PRODUCT_IBLOCK_ID'] = intval($arCatalog['PRODUCT_IBLOCK_ID']);
			$arCatalog['SKU_PROPERTY_ID'] = intval($arCatalog['SKU_PROPERTY_ID']);
			$arCatalog['VAT_ID'] = intval($arCatalog['VAT_ID']);

			$arCatalogList[$arCatalog['IBLOCK_ID']] = $arCatalog;

			$arCurrentIBlocks[$arCatalog['IBLOCK_ID']]['CATALOG'] = 'Y';
			$arCurrentIBlocks[$arCatalog['IBLOCK_ID']]['PRODUCT_IBLOCK_ID'] = $arCatalog['PRODUCT_IBLOCK_ID'];
			$arCurrentIBlocks[$arCatalog['IBLOCK_ID']]['SKU_PROPERTY_ID'] = $arCatalog['SKU_PROPERTY_ID'];
			if (0 < $arCatalog['PRODUCT_IBLOCK_ID'])
			{
				$arCurrentIBlocks[$arCatalog['PRODUCT_IBLOCK_ID']]['OFFERS_IBLOCK_ID'] = $arCatalog['IBLOCK_ID'];
				$arCurrentIBlocks[$arCatalog['PRODUCT_IBLOCK_ID']]['OFFERS_PROPERTY_ID'] = $arCatalog['SKU_PROPERTY_ID'];
			}
		}

		foreach ($arCurrentIBlocks as &$arOneIBlock)
		{
			// From form
			$is_cat = ((${"IS_CATALOG_".$arOneIBlock["ID"]}=="Y") ? "Y" : "N" );
			$is_cont = ((${"IS_CONTENT_".$arOneIBlock["ID"]}!="Y") ? "N" : "Y" );
			$yan_exp = ((${"YANDEX_EXPORT_".$arOneIBlock["ID"]}!="Y") ? "N" : "Y" );
			$cat_vat = intval(${"VAT_ID_".$arOneIBlock["ID"]});

			$offer_name = trim(${"OFFERS_NAME_".$arOneIBlock["ID"]});
			$offer_type = trim(${"OFFERS_TYPE_".$arOneIBlock["ID"]});
			$offer_new_type = '';
			$offer_new_type = trim(${"OFFERS_NEWTYPE_".$arOneIBlock["ID"]});
			$flag_new_type = ('Y' == ${'CREATE_OFFERS_TYPE_'.$arOneIBlock["ID"]} ? 'Y' : 'N');

			$offers_iblock_id = intval(${"OFFERS_IBLOCK_ID_".$arOneIBlock["ID"]});

			$arNewIBlockItem = array(
				'ID' => $arOneIBlock['ID'],
				'CATALOG' => $is_cat,
				'SUBSCRIPTION' => $is_cont,
				'YANDEX_EXPORT' => $yan_exp,
				'VAT_ID' => $cat_vat,
				'OFFERS_IBLOCK_ID' => $offers_iblock_id,
				'OFFERS_NAME' => $offer_name,
				'OFFERS_TYPE' => $offer_type,
				'OFFERS_NEW_TYPE' => $offer_new_type,
				'CREATE_OFFERS_NEW_TYPE' => $flag_new_type,
				'NEED_IS_REQUIRED' => 'N',
				'NEED_UPDATE' => 'N',
				'NEED_LINK' => 'N',
				'OFFERS_PROP' => 0,
			);
			$arNewIBlocksList[$arOneIBlock['ID']] = $arNewIBlockItem;
		}
		if (isset($arOneIBlock))
			unset($arOneIBlock);

		// check for offers is catalog
		foreach ($arCurrentIBlocks as $intIBlockID => $arIBlockInfo)
		{
			if ((0 < $arIBlockInfo['PRODUCT_IBLOCK_ID']) && ('Y' != $arNewIBlocksList[$intIBlockID]['CATALOG']))
				$arNewIBlocksList[$intIBlockID]['CATALOG'] = 'Y';
		}
		// check for double using iblock and selfmade
		$arOffersIBlocks = array();
		foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
		{
			if (0 < $arIBlockInfo['OFFERS_IBLOCK_ID'])
			{
				// double
				if (isset($arOffersIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]))
				{
					$boolFlag = false;
					$strWarning .= str_replace('#OFFER#',$arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['INFO'],GetMessage('CAT_IBLOCK_OFFERS_ERR_TOO_MANY_PRODUCT_IBLOCK')).'<br />';
				}
				else
				{
					$arOffersIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']] = true;
				}
				// selfmade
				if ($arIBlockInfo['OFFERS_IBLOCK_ID'] == $intIBlockID)
				{
					$boolFlag = false;
					$strWarning .= str_replace('#PRODUCT#',$arCurrentIBlocks[$intIBlockID]['INFO'],GetMessage('CAT_IBLOCK_OFFERS_ERR_SELF_MADE')).'<br />';
				}
			}
		}
		unset($arOffersIBlocks);
		// check for rights
		if ($boolFlag)
		{
			if (!$USER->IsAdmin())
			{
				foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
				{
					if (CATALOG_NEW_OFFERS_IBLOCK_NEED == $arIBlockInfo['OFFERS_IBLOCK_ID'])
					{
						$boolFlag = false;
						$strWarning .= str_replace('#PRODUCT#',$arCurrentIBlocks[$intIBlockID]['INFO'],GetMessage('CAT_IBLOCK_OFFERS_ERR_CANNOT_CREATE_IBLOCK')).'<br />';
					}
				}
			}
		}
		// check for offers next offers
		if ($boolFlag)
		{
			foreach ($arCurrentIBlocks as $intIBlockID => $arIBlockInfo)
			{
				if (0 < $arIBlockInfo['PRODUCT_IBLOCK_ID'] && 0 != $arNewIBlocksList[$intIBlockID]['OFFERS_IBLOCK_ID'])
				{
					$boolFlag = false;
					$strWarning .= str_replace('#PRODUCT#',$arIBlockInfo['INFO'],GetMessage('CAT_IBLOCK_OFFERS_ERR_PRODUCT_AND_OFFERS')).'<br />';
				}
			}
		}
		// check for product as offer
		if ($boolFlag)
		{
			foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
			{
				if (0 < $arIBlockInfo['OFFERS_IBLOCK_ID'] && 0 < $arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['OFFERS_IBLOCK_ID'])
				{
					$boolFlag = false;
					$strWarning .= str_replace('#PRODUCT#',$arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['INFO'],GetMessage('CAT_IBLOCK_OFFERS_ERR_PRODUCT_AND_OFFERS')).'<br />';
				}
			}
		}
		if ($boolFlag)
		{
			foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
			{
				if (0 < $arIBlockInfo['OFFERS_IBLOCK_ID'] && 0 < $arNewIBlocksList[$arIBlockInfo['OFFERS_IBLOCK_ID']]['OFFERS_IBLOCK_ID'])
				{
					$boolFlag = false;
					$strWarning .= str_replace('#PRODUCT#',$arNewIBlocksList[$arIBlockInfo['OFFERS_IBLOCK_ID']]['INFO'],GetMessage('CAT_IBLOCK_OFFERS_ERR_PRODUCT_AND_OFFERS')).'<br />';
				}
			}
		}
		if ($boolFlag)
		{
			foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
			{
				if (0 < $arIBlockInfo['OFFERS_IBLOCK_ID'] && CATALOG_NEW_OFFERS_IBLOCK_NEED == $arNewIBlocksList[$arIBlockInfo['OFFERS_IBLOCK_ID']]['OFFERS_IBLOCK_ID'])
				{
					$boolFlag = false;
					$strWarning .= str_replace('#PRODUCT#',$arNewIBlocksList[$arIBlockInfo['OFFERS_IBLOCK_ID']]['INFO'],GetMessage('CAT_IBLOCK_OFFERS_ERR_PRODUCT_AND_OFFERS')).'<br />';
				}
			}
		}

		// check name and new iblock_type
		if ($boolFlag)
		{
			foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
			{
				if (CATALOG_NEW_OFFERS_IBLOCK_NEED == $arIBlockInfo['OFFERS_IBLOCK_ID'])
				{
					if ('' == trim($arIBlockInfo['OFFERS_NAME']))
					{
						$arNewIBlocksList[$intIBlockID]['OFFERS_NAME'] = str_replace('#PRODUCT#',$arCurrentIBlocks[$intIBlockID]['NAME'],GetMessage('CAT_IBLOCK_OFFERS_NAME_TEPLATE'));
					}
					if ('Y' == $arIBlockInfo['CREATE_OFFERS_NEW_TYPE'] && '' == trim($arIBlockInfo['OFFERS_NEW_TYPE']))
					{
						$arNewIBlocksList[$intIBlockID]['CREATE_OFFERS_NEW_TYPE'] = 'N';
						$arNewIBlocksList[$intIBlockID]['OFFERS_TYPE'] = $arCurrentIBlocks[$intIBlockID]['IBLOCK_TYPE_ID'];
					}
					if ('N' == $arIBlockInfo['CREATE_OFFERS_NEW_TYPE'] && '' == trim($arIBlockInfo['OFFERS_TYPE']))
					{
						$arNewIBlocksList[$intIBlockID]['CREATE_OFFERS_NEW_TYPE'] = 'N';
						$arNewIBlocksList[$intIBlockID]['OFFERS_TYPE'] = $arCurrentIBlocks[$intIBlockID]['IBLOCK_TYPE_ID'];
					}
				}
			}
		}
		// check for sites
		if ($boolFlag)
		{
			foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
			{
				if (0 < $arIBlockInfo['OFFERS_IBLOCK_ID'])
				{
					$arDiffParent = array();
					$arDiffParent = array_diff($arCurrentIBlocks[$intIBlockID]['SITE_ID'],$arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['SITE_ID']);
					$arDiffOffer = array();
					$arDiffOffer = array_diff($arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['SITE_ID'],$arCurrentIBlocks[$intIBlockID]['SITE_ID']);
					if (!empty($arDiffParent) || !empty($arDiffOffer))
					{
						$boolFlag = false;
						$strWarning .= str_replace(array('#PRODUCT#','#OFFERS#'),array($arCurrentIBlocks[$intIBlockID]['INFO'],$arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['INFO']),GetMessage('CAT_IBLOCK_OFFERS_ERR_SITELIST_DEFFERENT')).'<br />';
					}
				}
			}
		}
		// check properties
		if ($boolFlag)
		{
			foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
			{
				if (0 < $arIBlockInfo['OFFERS_IBLOCK_ID'])
				{
					// search properties
					$intCountProp = 0;
					$arLastProp = false;
					$rsProps = CIBlockProperty::GetList(array(),array('IBLOCK_ID' => $arIBlockInfo['OFFERS_IBLOCK_ID'],'PROPERTY_TYPE' => 'E','LINK_IBLOCK_ID' => $intIBlockID,'ACTIVE' => 'Y','USER_TYPE' => 'SKU'));
					if ($arProp = $rsProps->Fetch())
					{
						$intCountProp++;
						$arLastProp = $arProp;
						while ($arProp = $rsProps->Fetch())
						{
							if (false !== $arProp)
							{
								$arLastProp = $arProp;
								$intCountProp++;
							}
						}
					}
					if (1 < $intCountProp)
					{
						// too many links for catalog
						$boolFlag = false;
						$strWarning .= str_replace(array('#OFFER#','#PRODUCT#'),array($arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['INFO'],$arCurrentIBlocks[$intIBlockID]['INFO']),GetMessage('CAT_IBLOCK_OFFERS_ERR_TOO_MANY_LINKS')).'<br />';
					}
					elseif (1 == $intCountProp)
					{
						if ('Y' == $arLastProp['MULTIPLE'])
						{
							// link must single property
							$boolFlag = false;
							$strWarning .= str_replace(array('#OFFER#','#PRODUCT#'),array($arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['INFO'],$arCurrentIBlocks[$intIBlockID]['INFO']),GetMessage('CAT_IBLOCK_OFFERS_ERR_LINKS_MULTIPLE')).'<br />';
						}
						elseif (('SKU' != $arLastProp['USER_TYPE']) || ('CML2_LINK' != $arLastProp['XML_ID']))
						{
							// link must is updated
							$arNewIBlocksList[$intIBlockID]['NEED_UPDATE'] = 'Y';
							$arNewIBlocksList[$intIBlockID]['OFFERS_PROP'] = $arLastProp['ID'];
						}
						else
						{
							$arNewIBlocksList[$intIBlockID]['OFFERS_PROP'] = $arLastProp['ID'];
						}
					}
					elseif (0 == $intCountProp)
					{
						// create offers iblock
						$arNewIBlocksList[$intIBlockID]['NEED_IS_REQUIRED'] = 'N';
						$arNewIBlocksList[$intIBlockID]['NEED_UPDATE'] = 'Y';
						$arNewIBlocksList[$intIBlockID]['NEED_LINK'] = 'Y';
					}
				}
			}
		}
		// create iblock
		$arNewOffers = array();
		if ($boolFlag)
		{
			$DB->StartTransaction();
			foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
			{
				if (CATALOG_NEW_OFFERS_IBLOCK_NEED == $arIBlockInfo['OFFERS_IBLOCK_ID'])
				{
					// need new offers
					$arResultNewCatalogItem = array();
					if ('Y' == $arIBlockInfo['CREATE_OFFERS_NEW_TYPE'])
					{
						$rsIBlockTypes = CIBlockType::GetByID($arIBlockInfo['OFFERS_NEW_TYPE']);
						if ($arIBlockType = $rsIBlockTypes->Fetch())
						{
							$arIBlockInfo['OFFERS_TYPE'] = $arIBlockInfo['OFFERS_NEW_TYPE'];
						}
						else
						{
							$arFields = array(
								'ID' => $arIBlockInfo['OFFERS_NEW_TYPE'],
								'SECTIONS' => 'N',
								'IN_RSS' => 'N',
								'SORT' => 500,
							);
							$rsLanguages = CLanguage::GetList($by="sort", $order="desc",array('ACTIVE' => 'Y'));
							while ($arLanguage = $rsLanguages->Fetch())
							{
								$arFields['LANG'][$arLanguage['LID']]['NAME'] = $arIBlockInfo['OFFERS_NEW_TYPE'];
							}
							$obIBlockType = new CIBlockType();
							$mxOffersType = $obIBlockType->Add($arFields);
							if (!$mxOffersType)
							{
								$boolFlag = false;
								$strWarning .= str_replace(array('#PRODUCT#','#ERROR#'),array($arCurrentIBlocks[$intIBlockID]['INFO'],$obIBlockType->LAST_ERROR),GetMessage('CAT_IBLOCK_OFFERS_ERR_NEW_IBLOCK_TYPE_NOT_ADD')).'<br />';
							}
							else
							{
								$arIBlockInfo['OFFERS_TYPE'] = $arIBlockInfo['OFFERS_NEW_TYPE'];
							}
						}
					}
					if ($boolFlag)
					{
						$arParentRights = CIBlock::GetGroupPermissions($intIBlockID);
						foreach ($arParentRights as $keyRight => $valueRight)
						{
							if ('U' == $valueRight)
							{
								$arParentRights[$keyRight] = 'W';
							}
						}
						$arFields = array(
							'SITE_ID' => $arCurrentIBlocks[$intIBlockID]['SITE_ID'],
							'IBLOCK_TYPE_ID' => $arIBlockInfo['OFFERS_TYPE'],
							'NAME' => $arIBlockInfo['OFFERS_NAME'],
							'ACTIVE' => 'Y',
							'GROUP_ID' => $arParentRights,
							'WORKFLOW' => 'N',
							'BIZPROC' => 'N',
							"LIST_PAGE_URL" => '',
							"SECTION_PAGE_URL" => '',
							"DETAIL_PAGE_URL" => '#PRODUCT_URL#',
							"INDEX_SECTION" => "N",
						);
						$obIBlock = new CIBlock();
						$mxOffersID = $obIBlock->Add($arFields);
						if (false === $mxOffersID)
						{
							$boolFlag = false;
							$strWarning .= str_replace(array('#PRODUCT#','#ERR#'),array($arCurrentIBlocks[$intIBlockID]['INFO'],$obIBlock->LAST_ERROR),GetMessage('CAT_IBLOCK_OFFERS_ERR_IBLOCK_ADD')).'<br />';
						}
						else
						{
							$arResultNewCatalogItem = array(
								'INFO' => '['.$arFields['IBLOCK_TYPE_ID'].'] '.htmlspecialcharsbx($arFields['NAME']).' ('.implode(' ',$arCurrentIBlocks[$intIBlockID]['SITE_ID']).')',
								'SITE_ID' => $arCurrentIBlocks[$intIBlockID]['SITE_ID'],
								'IBLOCK_TYPE_ID' => $arFields['IBLOCK_TYPE_ID'],
								'ID' => $mxOffersID,
								'NAME' => $arFields['NAME'],
								'CATALOG' => 'Y',
								'IS_CONTENT' => 'N',
								'YANDEX_EXPORT' => 'N',
								'VAT_ID' => 0,
								'PRODUCT_IBLOCK_ID' => $intIBlockID,
								'SKU_PROPERTY_ID' => 0,
								'NEED_IS_REQUIRED' => 'N',
								'NEED_UPDATE' => 'Y',
								'LINK_PROP' => false,
								'NEED_LINK' => 'Y',
							);
							$arFields = array(
								'IBLOCK_ID' => $mxOffersID,
								'NAME' => GetMessage('CAT_IBLOCK_OFFERS_TITLE_LINK_NAME'),
								'ACTIVE' => 'Y',
								'PROPERTY_TYPE' => 'E',
								'MULTIPLE' => 'N',
								'LINK_IBLOCK_ID' => $intIBlockID,
								'CODE' => 'CML2_LINK',
								'XML_ID' => 'CML2_LINK',
								"FILTRABLE" => "Y",
								'USER_TYPE' => 'SKU',
							);
							$obProp = new CIBlockProperty();
							$mxPropID = $obProp->Add($arFields);
							if (!$mxPropID)
							{
								$boolFlag = false;
								$strWarning .= str_replace(array('#OFFERS#','#ERR#'),array($arResultNewCatalogItem['INFO'],$obProp->LAST_ERROR),GetMessage('CAT_IBLOCK_OFFERS_ERR_CANNOT_CREATE_LINK')).'<br />';
							}
							else
							{
								$arResultNewCatalogItem['SKU_PROPERTY_ID'] = $mxPropID;
								$arResultNewCatalogItem['NEED_IS_REQUIRED'] = 'N';
								$arResultNewCatalogItem['NEED_UPDATE'] = 'N';
								$arResultNewCatalogItem['NEED_LINK'] = 'N';
							}
						}
					}
					if ($boolFlag)
					{
						$arNewOffers[$mxOffersID] = $arResultNewCatalogItem;
					}
					else
					{
						break;
					}
				}
			}
			if (!$boolFlag)
			{
				$DB->Rollback();
			}
			else
			{
				$DB->Commit();
			}
		}
		// create properties
		if ($boolFlag)
		{
			$DB->StartTransaction();
			foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
			{
				if (0 < $arIBlockInfo['OFFERS_IBLOCK_ID'])
				{
					if ('Y' == $arIBlockInfo['NEED_LINK'])
					{
						$arFields = array(
							'IBLOCK_ID' => $arIBlockInfo['OFFERS_IBLOCK_ID'],
							'NAME' => GetMessage('CAT_IBLOCK_OFFERS_TITLE_LINK_NAME'),
							'ACTIVE' => 'Y',
							'PROPERTY_TYPE' => 'E',
							'MULTIPLE' => 'N',
							'LINK_IBLOCK_ID' => $intIBlockID,
							'CODE' => 'CML2_LINK',
							'XML_ID' => 'CML2_LINK',
							"FILTRABLE" => "Y",
							'USER_TYPE' => 'SKU',
						);
						$obProp = new CIBlockProperty();
						$mxPropID = $obProp->Add($arFields);
						if (!$mxPropID)
						{
							$boolFlag = false;
							$strWarning .= str_replace(array('#OFFERS#','#ERR#'),array($arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['INFO'],$obProp->LAST_ERROR),GetMessage('CAT_IBLOCK_OFFERS_ERR_CANNOT_CREATE_LINK')).'<br />';
						}
						else
						{
							$arNewIBlocksList[$intIBlockID]['OFFERS_PROP'] = $mxPropID;
							$arNewIBlocksList[$intIBlockID]['NEED_IS_REQUIRED'] = 'N';
							$arNewIBlocksList[$intIBlockID]['NEED_UPDATE'] = 'N';
							$arNewIBlocksList[$intIBlockID]['NEED_LINK'] = 'N';
						}
					}
					elseif (0 < $arIBlockInfo['OFFERS_PROP'])
					{
						if ('Y' == $arIBlockInfo['NEED_UPDATE'])
						{
							$arPropFields = array(
								'USER_TYPE' => 'SKU',
								'XML_ID' => 'CML2_LINK',
							);
							$obProp = new CIBlockProperty();
							$mxPropID = $obProp->Update($arIBlockInfo['OFFERS_PROP'],$arPropFields);
							if (!$mxPropID)
							{
								$boolFlag = false;
								$strWarning .= $strWarning .= str_replace(array('#OFFERS#','#ERR#'),array($arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['INFO'],$obProp->LAST_ERROR),GetMessage('CAT_IBLOCK_OFFERS_ERR_MODIFY_PROP_IS_REQ')).'<br />';
								break;
							}
						}
					}
				}
			}
			if (!$boolFlag)
			{
				$DB->Rollback();
			}
			else
			{
				$DB->Commit();
			}
		}
		// reverse array
		if ($boolFlag)
		{
			foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
			{
				$arCurrentIBlocks[$intIBlockID]['CATALOG'] = $arIBlockInfo['CATALOG'];
				$arCurrentIBlocks[$intIBlockID]['SUBSCRIPTION'] = $arIBlockInfo['SUBSCRIPTION'];
				$arCurrentIBlocks[$intIBlockID]['YANDEX_EXPORT'] = $arIBlockInfo['YANDEX_EXPORT'];
				$arCurrentIBlocks[$intIBlockID]['VAT_ID'] = $arIBlockInfo['VAT_ID'];
			}
			foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
			{
				if (0 < $arIBlockInfo['OFFERS_IBLOCK_ID'])
				{
					$arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['CATALOG'] = 'Y';
					$arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['PRODUCT_IBLOCK_ID'] = $intIBlockID;
					$arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['SKU_PROPERTY_ID'] = $arIBlockInfo['OFFERS_PROP'];
				}
			}
		}
		// check old offers
		if ($boolFlag)
		{
			foreach ($arCurrentIBlocks as $intIBlockID => $arIBlockInfo)
			{
				if (0 < $arIBlockInfo['PRODUCT_IBLOCK_ID'])
				{
					if ($intIBlockID != $arNewIBlocksList[$arIBlockInfo['PRODUCT_IBLOCK_ID']]['OFFERS_IBLOCK_ID'])
					{
						$arCurrentIBlocks[$intIBlockID]['UNLINK'] = 'Y';
					}
				}
			}
		}
		// go exist iblock
		$boolCatalogUpdate = false;
		if ($boolFlag)
		{
			$DB->StartTransaction();
			$obCatalog = new CCatalog();
			foreach ($arCurrentIBlocks as $intIBlockID => $arIBlockInfo)
			{
				$boolAttr = true;
				if (isset($arIBlockInfo['UNLINK']) && 'Y' == $arIBlockInfo['UNLINK'])
				{
					$boolFlag = $obCatalog->UnLinkSKUIBlock($arIBlockInfo['PRODUCT_IBLOCK_ID']);
					if ($boolFlag)
					{
						$arIBlockInfo['PRODUCT_IBLOCK_ID'] = 0;
						$arIBlockInfo['SKU_PROPERTY_ID'] = 0;
						$boolCatalogUpdate = true;
					}
					else
					{
						$boolFlag = false;
						$ex = $APPLICATION->GetException();
						$strError = $ex->GetString();
						$strWarning .= str_replace(array('#PRODUCT#','#ERROR#'),array($arIBlockInfo['INFO'],$strError),GetMessage('CAT_IBLOCK_OFFERS_ERR_UNLINK_SKU')).'<br />';
					}
				}
				if ($boolFlag)
				{
					$boolExists = isset($arCatalogList[$intIBlockID]);
					$arCurValues = ($boolExists ? $arCatalogList[$intIBlockID] : array());

					if ($boolExists && ('Y' == $arIBlockInfo['CATALOG'] || 'Y' == $arIBlockInfo['SUBSCRIPTION'] || 0 < $arIBlockInfo['PRODUCT_IBLOCK_ID']))
					{
						$boolAttr = $obCatalog->Update(
							$intIBlockID,
							array(
								'IBLOCK_ID' => $arIBlockInfo['ID'],
								'YANDEX_EXPORT' => $arIBlockInfo['YANDEX_EXPORT'],
								'SUBSCRIPTION' => $arIBlockInfo['SUBSCRIPTION'],
								'VAT_ID' => $arIBlockInfo['VAT_ID'],
								'PRODUCT_IBLOCK_ID' => $arIBlockInfo['PRODUCT_IBLOCK_ID'],
								'SKU_PROPERTY_ID' => $arIBlockInfo['SKU_PROPERTY_ID']
							)
						);
						if (!$boolAttr)
						{
							$ex = $APPLICATION->GetException();
							$strError = $ex->GetString();
							$strWarning .= str_replace(
								array('#PRODUCT#', '#ERROR#'),
								array($arIBlockInfo['INFO'], $strError),
								GetMessage('CAT_IBLOCK_OFFERS_ERR_CAT_UPDATE')
							).'<br />';
							$boolFlag = false;
						}
						else
						{
							if (
								$arCurValues['SUBSCRIPTION'] != $arIBlockInfo['SUBSCRIPTION']
								|| $arCurValues['PRODUCT_IBLOCK_ID'] != $arIBlockInfo['PRODUCT_IBLOCK_ID']
								|| $arCurValues['YANDEX_EXPORT'] != $arIBlockInfo['YANDEX_EXPORT']
								|| $arCurValues['VAT_ID'] != $arIBlockInfo['VAT_ID']
							)
							{
								$boolCatalogUpdate = true;
							}
							if ($arIBlockInfo['YANDEX_EXPORT']=="Y")
								$bNeedAgent = true;
						}
					}
					elseif ($boolExists && $arIBlockInfo['CATALOG']!="Y" && $arIBlockInfo['SUBSCRIPTION']!="Y" && 0 == $arIBlockInfo['PRODUCT_IBLOCK_ID'])
					{
						if (!CCatalog::Delete($arIBlockInfo['ID']))
						{
							$boolFlag = false;
							$strWarning .= GetMessage("CAT_DEL_CATALOG1").' '.$arIBlockInfo['INFO'].' '.GetMessage("CAT_DEL_CATALOG2").".<br />";
						}
						else
						{
							$boolCatalogUpdate = true;
						}
					}
					elseif ($arIBlockInfo['CATALOG']=="Y" || $arIBlockInfo['SUBSCRIPTION']=="Y" || 0 < $arIBlockInfo['PRODUCT_IBLOCK_ID'])
					{
						$boolAttr = $obCatalog->Add(array(
							'IBLOCK_ID' => $arIBlockInfo['ID'],
							'YANDEX_EXPORT' => $arIBlockInfo['YANDEX_EXPORT'],
							'SUBSCRIPTION' => $arIBlockInfo['SUBSCRIPTION'],
							'VAT_ID' => $arIBlockInfo['VAT_ID'],
							'PRODUCT_IBLOCK_ID' => $arIBlockInfo['PRODUCT_IBLOCK_ID'],
							'SKU_PROPERTY_ID' => $arIBlockInfo['SKU_PROPERTY_ID']
						));
						if (!$boolAttr)
						{
							$ex = $APPLICATION->GetException();
							$strError = $ex->GetString();
							$strWarning .= str_replace(
								array('#PRODUCT#', '#ERROR#'),
								array($arIBlockInfo['INFO'], $strError),
								GetMessage('CAT_IBLOCK_OFFERS_ERR_CAT_ADD')
							).'<br />';
							$boolFlag = false;
						}
						else
						{
							if ($arIBlockInfo['YANDEX_EXPORT']=="Y") $bNeedAgent = true;
							$boolCatalogUpdate = true;
						}
					}
				}
				if (!$boolFlag)
					break;
			}
			if (!$boolFlag)
			{
				$DB->Rollback();
			}
			else
			{
				$DB->Commit();
			}
		}
		if ($boolFlag)
		{
			if (!empty($arNewOffers))
			{
				$DB->StartTransaction();
				foreach ($arNewOffers as $IntIBlockID => $arIBlockInfo)
				{
					$boolAttr = $obCatalog->Add(array('IBLOCK_ID' => $arIBlockInfo['ID'], "YANDEX_EXPORT" => $arIBlockInfo['YANDEX_EXPORT'], "SUBSCRIPTION" => $arIBlockInfo['SUBSCRIPTION'], "VAT_ID" => $arIBlockInfo['VAT_ID'], "PRODUCT_IBLOCK_ID" => $arIBlockInfo['PRODUCT_IBLOCK_ID'], 'SKU_PROPERTY_ID' => $arIBlockInfo['SKU_PROPERTY_ID']));
					if (!$boolAttr)
					{
						$ex = $APPLICATION->GetException();
						$strError = $ex->GetString();
						$strWarning .= str_replace(array('#PRODUCT#','#ERROR#'),array($arIBlockInfo['INFO'],$strError),GetMessage('CAT_IBLOCK_OFFERS_ERR_CAT_ADD')).'<br />';
						$boolFlag = false;
						break;
					}
					else
					{
						if ($arIBlockInfo['YANDEX_EXPORT']=="Y") $bNeedAgent = true;
						$boolCatalogUpdate = true;
					}
				}
				if (!$boolFlag)
				{
					$DB->Rollback();
				}
				else
				{
					$DB->Commit();
				}
			}
		}

		if ($boolFlag && $boolCatalogUpdate)
		{
			$strOK .= GetMessage('CAT_IBLOCK_CATALOG_SUCCESSFULLY_UPDATE').'<br />';
		}

		CAgent::RemoveAgent('CCatalog::PreGenerateXML("yandex");', 'catalog');
		if ($bNeedAgent)
		{
			CAgent::AddAgent('CCatalog::PreGenerateXML("yandex");', 'catalog', "N", intval(COption::GetOptionString("catalog", "yandex_xml_period", "24"))*60*60, "", "Y");
		}
	}

	if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['agent_start']) && strlen($_POST['agent_start']) > 0 && !$bReadOnly && check_bitrix_sessid())
	{
		CAgent::RemoveAgent('CCatalog::PreGenerateXML("yandex");', 'catalog');
		$intCount = CCatalog::GetList(array(),array('YANDEX_EXPORT' => 'Y'),array());
		if ($intCount > 0)
		{
			CAgent::AddAgent('CCatalog::PreGenerateXML("yandex");', 'catalog', "N", intval(COption::GetOptionString("catalog", "yandex_xml_period", "24"))*60*60, "", "Y");
			$strOK .= GetMessage('CAT_AGENT_ADD_SUCCESS').'. ';
		}
		else
		{
			$strWarning .= GetMessage('CAT_AGENT_ADD_NO_EXPORT').'. ';
		}
	}

	if(!empty($strWarning))
		CAdminMessage::ShowMessage($strWarning);

	if(!empty($strOK))
		CAdminMessage::ShowNote($strOK);

	$aTabs = array(
		array("DIV" => "edit5", "TAB" => GetMessage("CO_TAB_5"), "ICON" => "catalog_settings", "TITLE" => GetMessage("CO_TAB_5_TITLE")),
		array("DIV" => "edit1", "TAB" => GetMessage("CO_TAB_1"), "ICON" => "catalog_settings", "TITLE" => GetMessage("CO_TAB_1_TITLE")),
		array("DIV" => "edit2", "TAB" => GetMessage("CO_TAB_2"), "ICON" => "catalog_settings", "TITLE" => GetMessage("CO_TAB_2_TITLE"))
	);

	if ($USER->IsAdmin())
	{
		if (CBXFeatures::IsFeatureEnabled('SaleRecurring'))
		{
			$aTabs[] = array("DIV" => "edit3", "TAB" => GetMessage("CO_TAB_3"), "ICON" => "catalog_settings", "TITLE" => GetMessage("CO_SALE_GROUPS"));
		}
		$aTabs[] = array("DIV" => "edit4", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "catalog_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS"));
	}

	$tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);

	$strSaveProductWithoutPrice = COption::GetOptionString('catalog', 'save_product_without_price');
	$strAllowNegativeAmount = COption::GetOptionString('catalog', 'allow_negative_amount');
	$strEnableReservation = COption::GetOptionString('catalog', 'enable_reservation');
	$strAllowCanBuyZero = COption::GetOptionString('catalog', 'default_can_buy_zero');
	$strQuantityTrace = COption::GetOptionString('catalog', 'default_quantity_trace');
	$strUseStoreControl = COption::GetOptionString('catalog', 'default_use_store_control');
	$strSubscribe = COption::GetOptionString('catalog', 'default_subscribe');
	$strShowCatalogTab = COption::GetOptionString('catalog', 'show_catalog_tab_with_offers');

	$clearQuantityUser = COption::GetOptionString('catalog','clear_quantity_user',0);
	$clearQuantityReservedUser = COption::GetOptionString('catalog','clear_reserved_quantity_user',0);
	$clearStoreUser = COption::GetOptionString('catalog','clear_store_user',0);
	$strClearQuantityDate = COption::GetOptionString('catalog','clear_quantity_date','');
	$strClearQuantityReservedDate = COption::GetOptionString('catalog','clear_reserved_quantity_date','');
	$strClearStoreDate = COption::GetOptionString('catalog','clear_store_date','');

	$arUserList = array();
	$strNameFormat = CSite::GetNameFormat(true);
	$rsUsers = CUser::GetList(($by2 = 'ID'), ($order2 = 'ASC'), array('ID' => implode(' || ', array($clearQuantityUser, $clearQuantityReservedUser, $clearStoreUser))), array('FIELDS' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME')));
	while ($arOneUser = $rsUsers->Fetch())
	{
		$arOneUser['ID'] = intval($arOneUser['ID']);
		$arUserList[$arOneUser['ID']] = CUser::FormatName($strNameFormat, $arOneUser);
	}
	$strQuantityUser = '<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='.$clearQuantityUser.'">'.$arUserList[$clearQuantityUser].'</a>';
	$strQuantityReservedUser = '<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='.$clearQuantityUser.'">'.$arUserList[$clearQuantityReservedUser].'</a>';
	$strStoreUser = '<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='.$clearQuantityUser.'">'.$arUserList[$clearStoreUser].'</a>';

	$boolStoreExists = false;
	$arStores = array();
	$arStores[] = array("ID" => -1, "ADDRESS" => GetMessage("CAT_ALL_STORES"));
	$rsStores = CCatalogStore::GetList(
		array('SORT' => 'ASC', 'ID' => 'ASC'),
		array('ACTIVE' => 'Y'),
		false,
		false,
		array('ID', 'TITLE', 'ADDRESS')
		);
	while ($arStore = $rsStores->GetNext())
	{
		$boolStoreExists = true;
		$arStores[] = $arStore;
	}

	$tabControl->Begin();
	?><script type="text/javascript">
	function catClearQuantity(el, action)
	{
		var waiter_parent = BX.findParent(el, BX.is_relative),
			pos = BX.pos(el, !!waiter_parent);
		var iblockId = BX("catalogs_id").value;
		if(action == 'clearStore')
		{
			iblockId = BX("catalogs_store_id").value;
		}
		var storeId = BX("stores_id").value;
		var dateURL = '<?=bitrix_sessid_get()?>&iblockId='+iblockId+'&action='+action+'&storeId='+storeId+'&elId='+el.id+'&lang=<? echo LANGUAGE_ID; ?>';
		el.disabled = true;
		el.bxwaiter = (waiter_parent || document.body).appendChild(BX.create('DIV', {
			props: {className: 'adm-btn-load-img'},
			style: {
				top: parseInt((pos.bottom + pos.top)/2 - 5) + 'px',
				left: parseInt((pos.right + pos.left)/2 - 9) + 'px'
			}
		}));
		BX.addClass(el, 'adm-btn-load');
		BX.ajax.post('/bitrix/admin/cat_quantity_control.php', dateURL, catClearQuantityResult);
	}

	function catClearQuantityResult(result)
	{
		if (result.length > 0)
		{
			var res = eval( '('+result+')' );
			var el = BX(res);
			BX(res).setAttribute('class', 'adm-btn');
			if (el.bxwaiter && el.bxwaiter.parentNode)
			{
				el.bxwaiter.parentNode.removeChild(el.bxwaiter);
				el.bxwaiter = null;
			}
			el.disabled = false;
		}
	}
	function catDisableReservationCheckbox(el)
	{
		if (el.checked)
		{
			BX('enable_reservation_y').checked = true;
			BX('enable_reservation_y').disabled = true;
		}
		else
		{
			BX('enable_reservation_y').disabled = false;
		}
	}
	</script>
	<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?lang=<?=LANGUAGE_ID; ?>&mid=<?=htmlspecialcharsbx($mid); ?>&mid_menu=1" name="ara">
	<?echo bitrix_sessid_post()?><?
	$tabControl->BeginNextTab();
	?>
<tr class="heading">
	<td colspan="2"><? echo GetMessage("CAT_PRODUCT_CARD") ?></td>
</tr>
<tr>
	<td width="40%"><label for="default_subscribe"><? echo GetMessage("CAT_PRODUCT_SUBSCRIBE"); ?></label></td>
	<td width="60%"><input type="hidden" name="default_subscribe" id="default_subscribe_n" value="N"><input type="checkbox" name="default_subscribe" id="default_subscribe_y" value="Y"<?if ('Y' == $strSubscribe) echo " checked";?>></td>
</tr>
<tr>
	<td width="40%"><label for="show_catalog_tab_with_offers"><? echo GetMessage("CAT_SHOW_CATALOG_TAB"); ?></label></td>
	<td width="60%"><input type="hidden" name="show_catalog_tab_with_offers" id="show_catalog_tab_with_offers_n" value="N"><input type="checkbox" name="show_catalog_tab_with_offers" id="show_catalog_tab_with_offers_y" value="Y"<?if ('Y' == $strShowCatalogTab) echo " checked";?>></td>
</tr>
<tr>
	<td width="40%"><label for="save_product_without_price_y"><? echo GetMessage("CAT_SAVE_PRODUCTS_WITHOUT_PRICE"); ?></label></td>
	<td width="60%"><input type="hidden" name="save_product_without_price" id="save_product_without_price_n" value="N"><input type="checkbox" name="save_product_without_price" id="save_product_without_price_y" value="Y"<?if ('Y' == $strSaveProductWithoutPrice) echo " checked";?>></td>
</tr>
<tr class="heading">
	<td colspan="2" valign="top" align="center"><? echo GetMessage("CAT_STORE") ?></td>
</tr>
<tr id='cat_store_tr'>
	<td width="40%"><label for="use_store_control_y"><? echo GetMessage("CAT_USE_STORE_CONTROL"); ?></label></td>
	<td width="60%"><input type="hidden" name="use_store_control" id="use_store_control_n" value="N"><input type="checkbox" onclick="catDisableReservationCheckbox(this);" name="use_store_control" id="use_store_control_y" value="Y"<?if($strUseStoreControl == "Y")echo " checked";?>></td>
</tr>
<tr>
	<td width="40%"><label for="quantity_trace_y"><? echo GetMessage("CAT_ENABLE_QUANTITY_TRACE"); ?></label></td>
	<td width="60%"><input type="hidden" name="quantity_trace" id="quantity_trace_n" value="N"><input type="checkbox" name="quantity_trace" id="quantity_trace_y" value="Y"<?if($strQuantityTrace == "Y")echo " checked";?>></td>
</tr>
<tr>
	<td width="40%"><label for="allow_can_buy_zero_y"><? echo GetMessage("CAT_ALLOW_CAN_BUY_ZERO"); ?></label></td>
	<td width="60%"><input type="hidden" name="allow_can_buy_zero" id="allow_can_buy_zero_n" value="N"><input type="checkbox" name="allow_can_buy_zero" id="allow_can_buy_zero_y" value="Y"<?if($strAllowCanBuyZero == "Y")echo " checked";?>></td>
</tr>
<tr>
	<td width="40%"><label for="allow_negative_amount_y"><? echo GetMessage("CAT_ALLOW_NEGATIVE_AMOUNT"); ?></label></td>
	<td width="60%"><input type="hidden" name="allow_negative_amount" id="allow_negative_amount_n" value="N"><input type="checkbox" name="allow_negative_amount" id="allow_negative_amount_y" value="Y"<?if($strAllowNegativeAmount == "Y")echo " checked";?>></td>
</tr>
</tr>
<tr>
	<td width="40%"><label for="enable_reservation"><? echo GetMessage("CAT_ENABLE_RESERVATION"); ?></label></td>
	<td width="60%"><input type="hidden" name="enable_reservation" id="enable_reservation_n" value="N"><input type="checkbox" name="enable_reservation" id="enable_reservation_y" value="Y" <?if($strEnableReservation == "Y")echo " checked";?> <?if($strUseStoreControl == "Y")echo " disabled";?>></td>
</tr>
<?
	if (CBXFeatures::IsFeatureEnabled('CatDiscountSave'))
	{
		$strDiscSaveApply = COption::GetOptionString('catalog', 'discsave_apply', 'R');
	?>
<tr class="heading">
	<td colspan="2"><? echo GetMessage("CAT_DISCOUNT"); ?></td>
</tr>
<tr>
	<td width="40%"><label for="discsave_apply"><? echo GetMessage("CAT_DISCSAVE_APPLY"); ?></label></td>
	<td width="60%">
		<select name="discsave_apply" id="discsave_apply">
			<option value="R" <? echo ('R' == $strDiscSaveApply ? 'selected' : ''); ?>><? echo GetMessage('CAT_DISCSAVE_APPLY_MODE_R'); ?></option>
			<option value="A" <? echo ('A' == $strDiscSaveApply ? 'selected' : ''); ?>><? echo GetMessage('CAT_DISCSAVE_APPLY_MODE_A'); ?></option>
			<option value="D" <? echo ('D' == $strDiscSaveApply ? 'selected' : ''); ?>><? echo GetMessage('CAT_DISCSAVE_APPLY_MODE_D'); ?></option>
		</select>
	</td>
</tr>
<?
	}
/*
$strDiscountVat = COption::GetOptionString('catalog', 'discount_vat', 'Y');
?>
<tr>
	<td width="40%"><label for="discount_vat_y"><? echo GetMessage("CAT_DISCOUNT_VAT"); ?></label></td>
	<td width="60%"><input type="hidden" name="discount_vat" id="discount_vat_n" value="N"><input type="checkbox" name="discount_vat" id="discount_vat_y" value="Y"<?if ('Y' == $strDiscountVat) echo " checked";?>></td>
</tr>
<?
*/
	$tabControl->BeginNextTab();
?>
<tr class="heading">
	<td colspan="2"><? echo GetMessage("CAT_COMMON_EXPIMP_SETTINGS"); ?></td>
</tr><?
	for ($i = 0, $intCount = count($arAllOptions); $i < $intCount; $i++)
	{
		$Option = $arAllOptions[$i];
		$val = COption::GetOptionString("catalog", $Option[0], $Option[2]);
		$type = $Option[3];
		?>
		<tr>
			<td width="40%"><? echo ($type[0]=="checkbox" ? '<label for="'.htmlspecialcharsbx($Option[0]).'">'.$Option[1].'</label>' : $Option[1]); ?></td>
			<td width="60%">
				<?
				if ($Option[0] == 'export_default_path')
				{
					CAdminFileDialog::ShowScript
					(
						array(
							"event" => "BtnClickExpPath",
							"arResultDest" => array("FORM_NAME" => "ara", "FORM_ELEMENT_NAME" => $Option[0]),
							"arPath" => array("PATH" => GetDirPath($val)),
							"select" => 'D',// F - file only, D - folder only
							"operation" => 'O',// O - open, S - save
							"showUploadTab" => false,
							"showAddToMenuTab" => false,
							"fileFilter" => '',
							"allowAllFiles" => true,
							"SaveConfig" => true,
						)
					);
					?><input type="text" name="<? echo htmlspecialcharsbx($Option[0]); ?>" size="50" maxlength="255" value="<?echo htmlspecialcharsbx($val); ?>">&nbsp;<input type="button" name="browseExpPath" value="..." onClick="BtnClickExpPath()"><?
				}
				else
				{
					if($type[0]=="checkbox"):?>
						<input type="checkbox" name="<?echo htmlspecialcharsbx($Option[0])?>" id="<?echo htmlspecialcharsbx($Option[0])?>" value="Y"<?if($val=="Y")echo" checked";?>>
					<?elseif($type[0]=="text"):?>
						<input type="text" size="<?echo $type[1]?>" maxlength="255" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0])?>">
					<?elseif($type[0]=="textarea"):?>
						<textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0])?>"><?echo htmlspecialcharsbx($val)?></textarea>
					<?endif;
				}
				?>
			</td>
		</tr>
	<?
	}
	?>
	<tr>
		<td width="40%"><?=GetMessage("CAT_DEF_OUTFILE")?></td>
		<td width="60%">
			<?$default_outfile_action = COption::GetOptionString("catalog", "default_outfile_action", "D");?>
			<select name="default_outfile_action">
				<option value="D" <?if ($default_outfile_action=="D" || strlen($default_outfile_action)<=0) echo "selected" ?>><?echo GetMessage("CAT_DEF_OUTFILE_D") ?></option>
				<option value="H" <?if ($default_outfile_action=="H") echo "selected" ?>><?=GetMessage("CAT_DEF_OUTFILE_H")?></option>
				<option value="F" <?if ($default_outfile_action=="F") echo "selected" ?>><?=GetMessage("CAT_DEF_OUTFILE_F")?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td width="40%">
		<?
		$yandex_agent_file = COption::GetOptionString('catalog','yandex_agent_file','');
		CAdminFileDialog::ShowScript
		(
			Array(
				"event" => "BtnClick",
				"arResultDest" => array("FORM_NAME" => "ara", "FORM_ELEMENT_NAME" => "yandex_agent_file"),
				"arPath" => array("PATH" => GetDirPath($yandex_agent_file)),
				"select" => 'F',// F - file only, D - folder only
				"operation" => 'O',// O - open, S - save
				"showUploadTab" => true,
				"showAddToMenuTab" => false,
				"fileFilter" => 'php',
				"allowAllFiles" => true,
				"SaveConfig" => true,
			)
		);
		?>
		<?echo GetMessage("CAT_AGENT_FILE")?></td>
		<td width="60%"><input type="text" name="yandex_agent_file" size="50" maxlength="255" value="<?echo $yandex_agent_file?>">&nbsp;<input type="button" name="browse" value="..." onClick="BtnClick()"></td>
	</tr>
	<tr class="heading">
		<td colspan="2"><?echo GetMessage("CO_PAR_IE_CSV") ?></td>
	</tr>
	<tr>
		<td width="40%" valign="top"><?echo GetMessage("CO_PAR_DPP_CSV") ?></td>
		<td width="60%" valign="top">
			<?
			$strVal = COption::GetOptionString("catalog", "allowed_product_fields", $defCatalogAvailProdFields.",".$defCatalogAvailPriceFields);
			$arVal = explode(",", $strVal);
			$arCatalogAvailProdFields_tmp = array_merge($arCatalogAvailProdFields, $arCatalogAvailPriceFields);
			?>
			<select name="allowed_product_fields[]" multiple size="8">
				<?for ($i = 0, $intCount = count($arCatalogAvailProdFields_tmp); $i < $intCount; $i++):?>
					<option value="<?echo $arCatalogAvailProdFields_tmp[$i]["value"] ?>"<?if (in_array($arCatalogAvailProdFields_tmp[$i]["value"], $arVal)) echo " selected";?>><?echo $arCatalogAvailProdFields_tmp[$i]["name"]; ?></option>
				<?endfor;?>
			</select>
		</td>
	</tr>
	<tr>
		<td width="40%" valign="top"><? echo GetMessage("CO_AVAIL_PRICE_FIELDS"); ?></td>
		<td width="60%" valign="top">
			<?
			$strVal = COption::GetOptionString("catalog", "allowed_price_fields", $defCatalogAvailValueFields);
			$arVal = explode(",", $strVal);
			?>
			<select name="allowed_price_fields[]" multiple size="3">
				<?for ($i = 0, $intCount = count($arCatalogAvailValueFields); $i < $intCount; $i++):?>
					<option value="<?echo $arCatalogAvailValueFields[$i]["value"] ?>"<?if (in_array($arCatalogAvailValueFields[$i]["value"], $arVal)) echo " selected";?>><?echo $arCatalogAvailValueFields[$i]["name"]; ?></option>
				<?endfor;?>
			</select>
		</td>
	</tr>
	<tr>
		<td width="40%"><?echo GetMessage("CAT_NUM_CATALOG_LEVELS");?></td>
		<td width="60%"><?
			$strVal = COption::GetOptionString("catalog", "num_catalog_levels", "3");
			?><input type="text" size="5" maxlength="5" value="<?echo htmlspecialcharsbx($strVal)?>" name="num_catalog_levels"></td>
	</tr>
	<tr>
		<td width="40%" valign="top"><?echo GetMessage("CO_PAR_DPG_CSV") ?></td>
		<td width="60%" valign="top"><?
			$strVal = COption::GetOptionString("catalog", "allowed_group_fields", $defCatalogAvailGroupFields);
			$arVal = explode(",", $strVal);
			?>
			<select name="allowed_group_fields[]" multiple size="9">
				<?for ($i = 0, $intCount = count($arCatalogAvailGroupFields); $i < $intCount; $i++):?>
					<option value="<?echo $arCatalogAvailGroupFields[$i]["value"] ?>"<?if (in_array($arCatalogAvailGroupFields[$i]["value"], $arVal)) echo " selected";?>><?echo $arCatalogAvailGroupFields[$i]["name"]; ?></option>
				<?endfor;?>
			</select></td>
	</tr>
	<tr>
		<td width="40%" valign="top"><?echo GetMessage("CO_PAR_DV1_CSV")?></td>
		<td width="60%" valign="top">
			<?
			$strVal = COption::GetOptionString("catalog", "allowed_currencies", $defCatalogAvailCurrencies);
			$arVal = explode(",", $strVal);

			$lcur = CCurrency::GetList(($by1="sort"), ($order1="asc"));
			?>
			<select name="allowed_currencies[]" multiple size="5">
				<?while ($lcur_res = $lcur->Fetch()):?>
					<option value="<?echo htmlspecialcharsbx($lcur_res["CURRENCY"]) ?>"<?if (in_array($lcur_res["CURRENCY"], $arVal)) echo " selected";?>><?echo htmlspecialcharsex($lcur_res["CURRENCY"].(!empty($lcur_res['FULL_NAME']) ? ' ('.$lcur_res['FULL_NAME'].')' : '')); ?></option>
				<?endwhile;?>
			</select>
		</td>
	</tr>

<?
	$tabControl->BeginNextTab();
	$arVATRef = CatalogGetVATArray(array(), true);

	$arCatalogList = array();
	$arIBlockSitesList = array();

	$arIBlockFullInfo = array();

	$arRecurring = array();
	$arRecurringKey = array();

	$rsIBlocks = CIBlock::GetList(array('IBLOCK_TYPE' => 'ASC','ID' => 'ASC'));
	while ($arIBlock = $rsIBlocks->Fetch())
	{
		$arIBlock['ID'] = intval($arIBlock['ID']);
		if (!isset($arIBlockSitesList[$arIBlock['ID']]))
		{
			$arLIDList = array();
			$arWithLinks = array();
			$arWithoutLinks = array();
			$rsIBlockSites = CIBlock::GetSite($arIBlock['ID']);
			while ($arIBlockSite = $rsIBlockSites->Fetch())
			{
				$arLIDList[] = $arIBlockSite['LID'];
				$arWithLinks[] = '<a href="/bitrix/admin/site_edit.php?LID='.urlencode($arIBlockSite['LID']).'&lang='.LANGUAGE_ID.'" title="'.GetMessage("CO_SITE_ALT").'">'.htmlspecialcharsbx($arIBlockSite["LID"]).'</a>';
				$arWithoutLinks[] = htmlspecialcharsbx($arIBlockSite['LID']);
			}
			$arIBlockSitesList[$arIBlock['ID']] = array(
				'SITE_ID' => $arLIDList,
				'WITH_LINKS' => implode('&nbsp;',$arWithLinks),
				'WITHOUT_LINKS' => implode(' ',$arWithoutLinks),
			);
		}
		$arIBlockItem = array(
			'ID' => $arIBlock['ID'],
			'IBLOCK_TYPE_ID' => $arIBlock['IBLOCK_TYPE_ID'],
			'SITE_ID' => $arIBlockSitesList[$arIBlock['ID']]['SITE_ID'],
			'NAME' => htmlspecialcharsbx($arIBlock['NAME']),
			'ACTIVE' => $arIBlock['ACTIVE'],
			'FULL_NAME' => '['.$arIBlock['IBLOCK_TYPE_ID'].'] '.htmlspecialcharsbx($arIBlock['NAME']).' ('.$arIBlockSitesList[$arIBlock['ID']]['WITHOUT_LINKS'].')',
			'IS_CATALOG' => 'N',
			'IS_CONTENT' => 'N',
			'YANDEX_EXPORT' => 'N',
			'VAT_ID' => 0,
			'PRODUCT_IBLOCK_ID' => 0,
			'SKU_PROPERTY_ID' => 0,
			'OFFERS_IBLOCK_ID' => 0,
			'IS_OFFERS' => 'N',
			'OFFERS_PROPERTY_ID' => 0
		);
		$arIBlockFullInfo[$arIBlock['ID']] = $arIBlockItem;
	}

	$rsCatalogs = CCatalog::GetList(
		array(),
		array(),
		false,
		false,
		array('IBLOCK_ID', 'SUBSCRIPTION', 'YANDEX_EXPORT', 'VAT_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID')
	);
	while ($arOneCatalog = $rsCatalogs->Fetch())
	{
		$arOneCatalog['IBLOCK_ID'] = intval($arOneCatalog['IBLOCK_ID']);
		$arOneCatalog['VAT_ID'] = intval($arOneCatalog['VAT_ID']);
		$arOneCatalog['PRODUCT_IBLOCK_ID'] = intval($arOneCatalog['PRODUCT_IBLOCK_ID']);
		$arOneCatalog['SKU_PROPERTY_ID'] = intval($arOneCatalog['SKU_PROPERTY_ID']);

		if (!CBXFeatures::IsFeatureEnabled('SaleRecurring') && 'Y' == $arOneCatalog['SUBSCRIPTION'])
		{
			$arRecurring[] = '['.$arIBlockItem['ID'].'] '.$arIBlockItem['NAME'];
			$arRecurringKey[$arIBlockItem['ID']] = true;
		}

		$arIBlock = $arIBlockFullInfo[$arOneCatalog['IBLOCK_ID']];
		$arIBlock['IS_CATALOG'] = 'Y';
		$arIBlock['IS_CONTENT'] = (CBXFeatures::IsFeatureEnabled('SaleRecurring') ? $arOneCatalog['SUBSCRIPTION'] : 'N');
		$arIBlock['YANDEX_EXPORT'] = $arOneCatalog['YANDEX_EXPORT'];
		$arIBlock['VAT_ID'] = $arOneCatalog['VAT_ID'];
		$arIBlock['PRODUCT_IBLOCK_ID'] = $arOneCatalog['PRODUCT_IBLOCK_ID'];
		$arIBlock['SKU_PROPERTY_ID'] = $arOneCatalog['SKU_PROPERTY_ID'];
		if (0 < $arOneCatalog['PRODUCT_IBLOCK_ID'])
		{
			$arIBlock['IS_OFFERS'] = 'Y';
			$arOwnBlock = $arIBlockFullInfo[$arOneCatalog['PRODUCT_IBLOCK_ID']];
			$arOwnBlock['OFFERS_IBLOCK_ID'] = $arOneCatalog['IBLOCK_ID'];
			$arOwnBlock['OFFERS_PROPERTY_ID'] = $arOneCatalog['SKU_PROPERTY_ID'];
			$arIBlockFullInfo[$arOneCatalog['PRODUCT_IBLOCK_ID']] = $arOwnBlock;
			unset($arOwnBlock);
		}
		$arIBlockFullInfo[$arOneCatalog['IBLOCK_ID']] = $arIBlock;
		if ('Y' == $arIBlock['IS_CATALOG'])
			$arCatalogList[$arOneCatalog['IBLOCK_ID']] = $arIBlock;
		unset($arIBlock);
	}

	$arIBlockTypeIDList = array();
	$arIBlockTypeNameList = array();
	$rsIBlockTypes = CIBlockType::GetList(array("sort"=>"asc"), array("ACTIVE"=>"Y"));
	while ($arIBlockType = $rsIBlockTypes->Fetch())
	{
		if ($ar = CIBlockType::GetByIDLang($arIBlockType["ID"], LANGUAGE_ID, true))
		{
			$arIBlockTypeIDList[] = htmlspecialcharsbx($arIBlockType["ID"]);
			$arIBlockTypeNameList[] = htmlspecialcharsbx('['.$arIBlockType["ID"].'] '.$ar["~NAME"]);
		}
	}

	$arDoubleIBlockFullInfo = $arIBlockFullInfo;

?>
<tr><td><?
	if (!empty($arRecurring))
	{
		$strRecurring = GetMessage('SMALL_BUSINESS_RECURRING_ERR_LIST').'<ul><li>'.implode('</li><li>', $arRecurring).'</li></ul>'.GetMessage('SMALL_BUSINESS_RECURRING_ERR_LIST_CLEAR');
		CAdminMessage::ShowMessage(array(
			"MESSAGE" => GetMessage("SMALL_BUSINESS_RECURRING_ERR"),
			"DETAILS" => $strRecurring,
			"HTML" => true,
			"TYPE" => "ERROR",
		));
	}
?>
<script type="text/javascript">
function ib_checkFldActivity(id, flag)
{
	var Cat = BX('IS_CATALOG_' + id + '_Y');
	var Cont = BX('IS_CONTENT_' + id + '_Y');
	var Yand = BX('YANDEX_EXPORT_' + id + '_Y');
	var Vat = BX('VAT_ID_' + id);

	if (flag == 0)
	{
		if (!!Cat && !!Cont)
		{
			if (!Cat.checked)
				Cont.checked = false;
		}
	}

	if (flag == 1)
	{
		if (!!Cat && !!Cont)
		{
			if (Cont.checked)
				Cat.checked = true;
		}
	}

	var bActive = Cat.checked;
	if (!!Yand)
		Yand.disabled = !bActive;
	if (!!Vat)
		Vat.disabled = !bActive;
}

function show_add_offers(id, obj)
{
	var value = obj.options[obj.selectedIndex].value;
	var add_form = document.getElementById('offers_add_info_'+id);
	if (undefined !== add_form)
	{
		if (<? echo CATALOG_NEW_OFFERS_IBLOCK_NEED; ?> == value)
		{
			add_form.style.display = 'block';
		}
		else
		{
			add_form.style.display = 'none';
		}
	}
}
function change_offers_ibtype(obj,ID)
{
	var value = obj.value;
	if ('Y' == value)
	{
		document.forms.ara['OFFERS_TYPE_' + ID].disabled = true;
		document.forms.ara['OFFERS_NEWTYPE_' + ID].disabled = false;
	}
	else if ('N' == value)
	{
		document.forms.ara['OFFERS_TYPE_' + ID].disabled = false;
		document.forms.ara['OFFERS_NEWTYPE_' + ID].disabled = true;
	}
}
</script>
<table width="100%" cellspacing="0" cellpadding="0" border="0" class="internal">
	<tr class="heading">
		<td><?=GetMessage("CAT_IBLOCK_SELECT_NAME")?></td>
		<td><?=GetMessage("CAT_IBLOCK_SELECT_CAT")?></td>
		<td><?=GetMessage("CAT_IBLOCK_SELECT_OFFERS")?></td><?
		if (CBXFeatures::IsFeatureEnabled('SaleRecurring'))
		{
			?><td><?=GetMessage("CO_SALE_CONTENT") ?></td><?
		}
		?><td><?=GetMessage("CAT_IBLOCK_SELECT_YAND")?></td>
		<td><?=GetMessage("CAT_IBLOCK_SELECT_VAT")?></td>
	</tr>
	<?
	foreach ($arIBlockFullInfo as &$res)
	{
		?>
		<tr>
			<td>[<a title="<? echo GetMessage("CO_IB_TYPE_ALT"); ?>" href="/bitrix/admin/iblock_admin.php?type=<? echo urlencode($res["IBLOCK_TYPE_ID"]); ?>&lang=<? echo LANGUAGE_ID; ?>&admin=Y"><? echo $res["IBLOCK_TYPE_ID"]; ?></a>]
				&nbsp;[<? echo $res['ID']; ?>] <a title="<? echo GetMessage("CO_IB_ELEM_ALT"); ?>" href="<? echo CIBlock::GetAdminElementListLink($res["ID"], array('find_section_section' => '0', 'admin' => 'Y')); ?>"><? echo $res["NAME"]; ?></a> (<? echo $arIBlockSitesList[$res['ID']]['WITH_LINKS']; ?>)
				<input type="hidden" name="IS_OFFERS_<? echo $res["ID"]; ?>" value="<? echo $res['IS_OFFERS']; ?>" />
			</td>
			<td align="center" style="text-align: center;"><input type="hidden" name="IS_CATALOG_<?echo $res["ID"] ?>" id="IS_CATALOG_<?echo $res["ID"] ?>_N" value="N"><input type="checkbox" name="IS_CATALOG_<?echo $res["ID"] ?>" id="IS_CATALOG_<?echo $res["ID"] ?>_Y" onclick="ib_checkFldActivity('<?=$res['ID']?>', 0)" <?if ('Y' == $res['IS_CATALOG']) echo 'checked="checked"'?> <? if ('Y' == $res['IS_OFFERS']) echo 'disabled="disabled"'; ?>value="Y" /></td>
			<td align="center"><select id="OFFERS_IBLOCK_ID_<? echo $res["ID"]; ?>" name="OFFERS_IBLOCK_ID_<? echo $res["ID"]; ?>" class="typeselect" <? echo ('Y' == $res['IS_OFFERS'] ? 'disabled="disabled"' : 'onchange="show_add_offers('.$res["ID"].',this);"'); ?> style="width: 100%;">
			<option value="0" <? echo (0 == $res['OFFERS_IBLOCK_ID'] ? 'selected' : '');?>><? echo GetMessage('CAT_IBLOCK_OFFERS_EMPTY')?></option>
			<?
			if ('Y' != $res['IS_OFFERS'])
			{
				if ($USER->IsAdmin())
				{
					?><option value="<? echo CATALOG_NEW_OFFERS_IBLOCK_NEED; ?>"><? echo GetMessage('CAT_IBLOCK_OFFERS_NEW')?></option><?
				}
				foreach ($arDoubleIBlockFullInfo as &$value)
				{
					if ($value['ID'] != $res['OFFERS_IBLOCK_ID'])
					{
						if (
							('Y' != $value['IS_CATALOG'])
							|| ('N' == $value['ACTIVE'])
							|| ('Y' == $value['IS_OFFERS'])
							|| (0 < $value['OFFERS_IBLOCK_ID'])
							|| ($res['ID'] == $value['ID'])
							|| (0 < $value['PRODUCT_IBLOCK_ID'])
						)
						{
							continue;
						}
						else
						{
							$arDiffParent = array();
							$arDiffParent = array_diff($value['SITE_ID'],$res['SITE_ID']);
							$arDiffOffer = array();
							$arDiffOffer = array_diff($res['SITE_ID'],$value['SITE_ID']);
							if (!empty($arDiffParent) || !empty($arDiffOffer))
							{
								continue;
							}
						}
					}
					?><option value="<? echo intval($value['ID']); ?>"<? echo ($value['ID'] == $res['OFFERS_IBLOCK_ID'] ? ' selected' : ''); ?>><? echo $value['FULL_NAME']; ?></option><?
				}
				if (isset($value))
					unset($value);
			}
			?>
			</select>
			<div id="offers_add_info_<? echo $res["ID"]; ?>" style="display: none; width: 98%; maring: 0 1%;"><table class="internal" style="width: 100%;"><tbody>
				<tr><td style="text-align: right; width: 25%;"><? echo GetMessage('CAT_IBLOCK_OFFERS_TITLE'); ?>:</td><td style="text-align: left; width: 75%;"><input type="text" name="OFFERS_NAME_<? echo $res["ID"]; ?>" value="" style="width: 98%; margin: 0 1%;" /></td></tr>
				<tr><td style="text-alogn: left; width: 100%;" colspan="2"><input type="radio" value="N" id="CREATE_OFFERS_TYPE_N_<? echo $res['ID']; ?>" name="CREATE_OFFERS_TYPE_<? echo $res['ID']; ?>" checked="checked" onclick="change_offers_ibtype(this,<? echo $res['ID']?>);"><label for="CREATE_OFFERS_TYPE_N_<? echo $res['ID']; ?>"><? echo GetMessage('CAT_IBLOCK_OFFERS_OLD_IBTYPE');?></label></td></tr>
				<tr><td style="text-align: right; width: 25%;"><? echo GetMessage('CAT_IBLOCK_OFFERS_TYPE'); ?>:</td><td style="text-align: left; width: 75%;"><? echo SelectBoxFromArray('OFFERS_TYPE_'.$res["ID"],array('REFERENCE' => $arIBlockTypeNameList,'REFERENCE_ID' => $arIBlockTypeIDList),'','','style="width: 98%;  margin: 0 1%;"'); ?></td></tr>
				<tr><td style="text-alogn: left; width: 100%;" colspan="2"><input type="radio" value="Y" id="CREATE_OFFERS_TYPE_Y_<? echo $res['ID']; ?>" name="CREATE_OFFERS_TYPE_<? echo $res['ID']; ?>" onclick="change_offers_ibtype(this,<? echo $res['ID']?>);"><label for="CREATE_OFFERS_TYPE_Y_<? echo $res['ID']; ?>"><? echo GetMessage('CAT_IBLOCK_OFFERS_NEW_IBTYPE');?></label></td></tr>
				<tr><td style="text-align: right; width: 25%;"><? echo GetMessage('CAT_IBLOCK_OFFERS_NEWTYPE'); ?>:</td><td style="text-align: left; width: 75%;"><input type="text" name="OFFERS_NEWTYPE_<? echo $res["ID"]; ?>" value="" style="width: 98%; margin: 0 1%;" disabled="disabled" /></td></tr>
			</tbody></table></div></td><?
			if (CBXFeatures::IsFeatureEnabled('SaleRecurring'))
			{
				?><td align="center" style="text-align: center;"><input type="hidden" name="IS_CONTENT_<?echo $res["ID"] ?>" id="IS_CONTENT_<?echo $res["ID"] ?>_N" value="N"><input type="checkbox" name="IS_CONTENT_<?echo $res["ID"] ?>" id="IS_CONTENT_<?echo $res["ID"] ?>_Y" onclick="ib_checkFldActivity('<?=$res['ID']?>', 1)" <?if ('Y' == $res["IS_CONTENT"]) echo "checked"?> value="Y" /></td><?
			}
			else
			{
				?><input type="hidden" name="IS_CONTENT_<?echo $res["ID"] ?>" value="N" id="IS_CONTENT_<?echo $res["ID"] ?>_N"><?
			}
			?><td align="center" style="text-align: center;"><input type="hidden" name="YANDEX_EXPORT_<?echo $res["ID"] ?>" id="YANDEX_EXPORT_<?echo $res["ID"] ?>_N"><input type="checkbox" name="YANDEX_EXPORT_<?echo $res["ID"] ?>" id="YANDEX_EXPORT_<?echo $res["ID"] ?>_Y" <?if ('N' == $res['IS_CATALOG']) echo 'disabled="disabled"';?> <?if ('Y' == $res["YANDEX_EXPORT"]) echo "checked"?> value="Y" /></td>
			<td align="center"><?=SelectBoxFromArray('VAT_ID_'.$res['ID'], $arVATRef, $res['VAT_ID'], '', ('N' == $res['IS_CATALOG'] ? 'disabled="disabled"' : ''))?></td>
		</tr>
		<?
	}
	if (isset($res))
		unset($res);
	?>
</table>
</td></tr>
<?
	if ($USER->IsAdmin())
	{
		if (CBXFeatures::IsFeatureEnabled('SaleRecurring'))
		{
			$tabControl->BeginNextTab();

			$strVal = COption::GetOptionString("catalog", "avail_content_groups");
			$arVal = explode(",", $strVal);

			$dbUserGroups = CGroup::GetList(($b="c_sort"), ($o="asc"), array("ANONYMOUS" => "N"));
			while ($arUserGroups = $dbUserGroups->Fetch())
			{
				$arUserGroups["ID"] = intval($arUserGroups["ID"]);
				if ($arUserGroups["ID"] == 2)
					continue;
			?>
			<tr>
				<td width="40%"><label for="user_group_<?=$arUserGroups["ID"]?>"><?= htmlspecialcharsEx($arUserGroups["NAME"])?></label> [<a href="group_edit.php?ID=<?=$arUserGroups["ID"]?>&lang=<?=LANGUAGE_ID?>" title="<?=GetMessage("CO_USER_GROUP_ALT")?>"><?=$arUserGroups["ID"]?></a>]:</td>
				<td width="60%"><input type="checkbox" id="user_group_<?=$arUserGroups["ID"]?>" name="AVAIL_CONTENT_GROUPS[]"<?if (in_array($arUserGroups["ID"], $arVal)) echo " checked"?> value="<?= $arUserGroups["ID"] ?>"></td>
			</tr>
			<?
			}
		}

		$tabControl->BeginNextTab();

		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights2.php");

	}

	$tabControl->Buttons();
	?>
<script type="text/javascript">
function RestoreDefaults()
{
	if (confirm('<?echo GetMessageJS("MAIN_HINT_RESTORE_DEFAULTS_WARNING");?>'))
		window.location = "<? echo $APPLICATION->GetCurPage(); ?>?RestoreDefaults=Y&lang=<? echo LANGUAGE_ID; ?>&mid=<? echo urlencode($mid); ?>&<? echo bitrix_sessid_get(); ?>";
}
</script>
<input type="submit" class="adm-btn-save" <? if ($bReadOnly) echo "disabled" ?> name="Update" value="<? echo GetMessage("MAIN_SAVE"); ?>">
<input type="hidden" name="Update" value="Y">
<input type="reset" name="reset" value="<?echo GetMessage("MAIN_RESET")?>">
<input type="button" <?if ($bReadOnly) echo "disabled" ?> title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" onclick="RestoreDefaults();" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
</form>
<?
	$tabControl->End();
?><h2><?= GetMessage("COP_SYS_ROU") ?></h2>
<?
	$aTabs = array(
		array("DIV" => "fedit2", "TAB" => GetMessage("COP_TAB2_AGENT"), "ICON" => "catalog_settings", "TITLE" => GetMessage("COP_TAB2_AGENT_TITLE")),
	);
	if ($strUseStoreControl === 'N' && !empty($arCatalogList))
	{
		$aTabs[] = array("DIV" => "fedit3", "TAB" => GetMessage("CAT_QUANTITY_CONTROL_TAB"), "ICON" => "catalog_settings", "TITLE" => GetMessage("CAT_QUANTITY_CONTROL"));
	}

	$tabControl = new CAdminTabControl("tabControl2", $aTabs, true, true);

	$tabControl->Begin();
	$tabControl->BeginNextTab();
?><tr><td align="left"><?
	$arAgentInfo = false;
	$rsAgents = CAgent::GetList(array(),array('MODULE_ID' => 'catalog','NAME' => "CCatalog::PreGenerateXML(\"yandex\");"));
	if ($arAgent = $rsAgents->Fetch())
	{
		$arAgentInfo = $arAgent;
	}
	if (!is_array($arAgentInfo) || empty($arAgentInfo))
	{
		?><form name="agent_form" method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?=LANGUAGE_ID?>">
		<?echo bitrix_sessid_post()?>
		<input type="submit" class="adm-btn-save" name="agent_start" value="<? echo GetMessage('CAT_AGENT_START') ?>" <?if ($bReadOnly) echo "disabled" ?>>
		</form><?
	}
	else
	{
		echo GetMessage('CAT_AGENT_ACTIVE').':&nbsp;'.($arAgentInfo['ACTIVE'] == 'Y' ? GetMessage("MAIN_YES") : GetMessage("MAIN_NO")).'<br>';
		if ($arAgentInfo['LAST_EXEC'])
		{
			echo GetMessage('CAT_AGENT_LAST_EXEC').':&nbsp;'.($arAgentInfo['LAST_EXEC'] ? $arAgentInfo['LAST_EXEC'] : '').'<br>';
			echo GetMessage('CAT_AGENT_NEXT_EXEC').':&nbsp;'.($arAgentInfo['NEXT_EXEC'] ? $arAgentInfo['NEXT_EXEC'] : '').'<br>';
		}
		else
		{
			echo GetMessage('CAT_AGENT_WAIT_START').'<br>';
		}
	}
?><br><?
	$strYandexFile = str_replace('//','/',COption::GetOptionString("catalog", "export_default_path", "/upload/").'/yandex.php');
	if (file_exists($_SERVER['DOCUMENT_ROOT'].$strYandexFile))
	{
		echo str_replace('#FILE#', '<a href="'.$strYandexFile.'">'.$strYandexFile.'</a>',GetMessage('CAT_AGENT_FILEPATH')).'<br>';
	}
	else
	{
		echo GetMessage('CAT_AGENT_FILE_ABSENT').'<br>';
	}
?><br><?
	echo GetMessage('CAT_AGENT_EVENT_LOG').':&nbsp;';

?><a href="/bitrix/admin/event_log.php?lang=<? echo LANGUAGE_ID; ?>&set_filter=Y<? echo CCatalogEvent::GetYandexAgentFilter(); ?>"><? echo GetMessage('CAT_AGENT_EVENT_LOG_SHOW_ERROR')?></a>
</td></tr>
<?
	if($strUseStoreControl === 'N' && !empty($arCatalogList))
	{
		$tabControl->BeginNextTab();
	?>
	<tr>
		<td><?= GetMessage("CAT_SELECT_CATALOG") ?>:</td>
		<td>
			<select style='max-width:300px' id="catalogs_id" name="catalogs_id" <?=($bReadOnly) ? " disabled" : ""?>>
				<?foreach($arCatalogList as &$arOneCatalog)
				{
					echo '<option value="'.$arOneCatalog['ID'].'">'.htmlspecialcharsex($arOneCatalog["NAME"]).' ('.$arIBlockSitesList[$arOneCatalog['ID']]['WITHOUT_LINKS'].')</option>';
				}
				unset($arOneCatalog);
				?>
			</select>
		</td>
	</tr>

	<tr>
		<td width="40%"><? echo GetMessage("CAT_CLEAR_QUANTITY"); ?>:</td>
		<td width="60%">
			<input type="button" value="<? echo GetMessage("CAT_CLEAR_ACTION"); ?>" id="cat_clear_quantity_btn" onclick="catClearQuantity(this, 'clearQuantity')" title="">
			<span style="font-size: smaller;"><?=$strQuantityUser;?>&nbsp;<?=$strClearQuantityDate;?></span>
		</td>
	</tr>

	<tr>
		<td width="40%"><? echo GetMessage("CAT_CLEAR_RESERVED_QUANTITY"); ?></td>
		<td>
			<input type="button" value="<? echo GetMessage("CAT_CLEAR_ACTION"); ?>" id="cat_clear_reserved_quantity_btn" onclick="catClearQuantity(this, 'clearReservedQuantity')" title="">
			<span style="font-size: smaller;"><?=$strQuantityReservedUser;?>&nbsp;<?=$strClearQuantityReservedDate;?></span>
		</td>
	</tr>
	<tr class="heading">
		<td colspan="2"><? echo GetMessage("CAT_CLEAR_STORE") ?></td>
	</tr>
<?
		if ($boolStoreExists)
		{
?>
	<tr>
		<td><?= GetMessage("CAT_SELECT_CATALOG") ?>:</td>
		<td>
			<select style='max-width:300px' id="catalogs_store_id" name="catalogs_store_id" <?=($bReadOnly) ? " disabled" : ""?>>
				<?foreach($arCatalogList as &$arOneCatalog)
				{
					echo '<option value="'.$arOneCatalog['ID'].'">'.htmlspecialcharsex($arOneCatalog["NAME"]).' ('.$arIBlockSitesList[$arOneCatalog['ID']]['WITHOUT_LINKS'].')</option>';
				}
				unset($arOneCatalog);
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td><?= GetMessage("CAT_SELECT_STORE") ?>:</td>
		<td>
			<select style='max-width:300px' id="stores_id" name="stores_id" <?=($bReadOnly) ? " disabled" : ""?>>
				<?
				foreach($arStores as $key => $val)
				{
					$store = ($val["TITLE"] != '') ? $val["TITLE"]." (".$val["ADDRESS"].")" : $val["ADDRESS"];
					echo '<option value="'.$val['ID'].'">'.$store.'</option>';
				}
				?>
			</select>

		</td>
	</tr>
	<tr>
		<td><?= GetMessage("CAT_CLEAR_STORE") ?>:</td>
		<td>
			<input type="button" value="<? echo GetMessage("CAT_CLEAR_ACTION"); ?>" id="cat_clear_store_btn" onclick="catClearQuantity(this, 'clearStore')" title="">
			<span style="font-size: smaller;"><?=$strStoreUser;?>&nbsp;<?=$strClearStoreDate;?></span>
		</td>
	</tr>
<?
		}
		else
		{
?>
	<tr>
		<td colspan="2"><?= GetMessage("CAT_STORE_LIST_IS_EMPTY"); ?></td>
	</tr>
<?
		}
	}
	$tabControl->End();
}
?>