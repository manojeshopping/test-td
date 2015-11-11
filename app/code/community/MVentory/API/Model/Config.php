<?php

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License BY-NC-ND.
 * By Attribution (BY) - You can share this file unchanged, including
 * this copyright statement.
 * Non-Commercial (NC) - You can use this file for non-commercial activities.
 * A commercial license can be purchased separately from mventory.com.
 * No Derivatives (ND) - You can make changes to this file for your own use,
 * but you cannot share or redistribute the changes.  
 *
 * See the full license at http://creativecommons.org/licenses/by-nc-nd/4.0/
 *
 * @package MVentory/API
 * @copyright Copyright (c) 2014 mVentory Ltd. (http://mventory.com)
 * @license http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

/**
 * Different constants and values
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Model_Config
{
  //Version of API. Returned in every API call under '_apiversion_' key
  const API_VERSION = '20140815';

  //Config paths
  const _FETCH_LIMIT = 'mventory/api/products-number-to-fetch';
  const _TAX_CLASS = 'mventory/api/tax_class';
  const _ITEM_LIFETIME = 'mventory/api/cart-item-lifetime';
  const _LINK_LIFETIME = 'mventory/api/app_profile_link_lifetime';
  const _LOST_CATEGORY = 'mventory/api/lost_category';
  const _API_VISIBILITY = 'mventory/api/product_visiblity';
  const _ROOT_WEBSITE = 'mventory/api/root_website';
  const _DEFAULT_ROLE = 'mventory/api/default_role';
  const _APPLY_RULES = 'mventory/api/apply_rules';
  const _QR_ROWS = 'mventory/qr/rows';
  const _QR_COLUMNS = 'mventory/qr/columns';
  const _QR_SIZE = 'mventory/qr/size';
  const _QR_PAGES = 'mventory/qr/pages';
  const _QR_CSS = 'mventory/qr/css';
  const _QR_URL = 'mventory/qr/base_url';
  const _QR_COPIES = 'mventory/qr/copies';

  //BackGround Genie config paths
  const _BGG_ENABLED = 'bg_genie/settings/enabled';
  const _BGG_BACKUP_DIR = 'bg_genie/settings/backup_dir';
  const _BGG_EXCL_NEW = 'bg_genie/settings/exclude_new';
  const _BGG_AUTO_REPL = 'bg_genie/settings/auto_replace';
  const _BGG_DBX_TKN = 'bg_genie/settings/dropbox_token';
  const _BGG_DBX_PATH = 'bg_genie/settings/dropbox_path';

  //Attribute metadata values
  const MT_INPUT_KBD = 0;
  const MT_INPUT_NUMKBD = 1;
  const MT_INPUT_SCANNER = 2;
  const MT_INPUT_GESTURES = 3;
  const MT_INPUT_INTERNETSEARCH = 4;
  const MT_INPUT_ANOTHERPROD = 5;
}
