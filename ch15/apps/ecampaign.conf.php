<?php

   error_reporting(E_ALL);
   // If you have installed PEAR packages in a different
   // directory than %DocumentRoot%/pear change the setting below.
   $PEAR_DIR           = $_SERVER['DOCUMENT_ROOT'] . '/pear' ;

   // If you have installed PHPLIB in a different
   // directory than %DocumentRoot%/phplib, change the setting below.
   $PHPLIB_DIR         = $_SERVER['DOCUMENT_ROOT'] . '/phplib';

   // If you have installed framewirk directory in a different
   // directory than %DocumentRoot%/framework, change the setting below.
   $APP_FRAMEWORK_DIR  = $_SERVER['DOCUMENT_ROOT'] . '/framework';

   $PATH = $PEAR_DIR . ':' . $PHPLIB_DIR . ':' . $APP_FRAMEWORK_DIR;

   ini_set( 'include_path', ':' . $PATH . ':' . ini_get('include_path'));

   $AUTHENTICATION_URL = '/login/login.php';
   $LOGOUT_URL         = '/logout/logout.php';
   $HOME_URL           =  $_SERVER['HTTP_HOST'];


   $APPLICATION_NAME   = 'ECAMPAIGN';
   $XMAILER_ID         = 'EVOKNOW Survey System Version 1.0';
   $DEFAULT_LANGUAGE   = 'US';
   $ROOT_PATH          = $_SERVER['DOCUMENT_ROOT'];
   $REL_ROOT_PATH      = '/ecampaign';
   $REL_APP_PATH       =  $REL_ROOT_PATH . '/apps';

   $ECAMPAIGN_MENU_URL =  $REL_APP_PATH .'/ecampaign_mngr.php';
   $REL_MSGS_DIR       = $REL_ROOT_PATH  . '/messagetemplates';

   $TEMPLATE_DIR       = $ROOT_PATH     . $REL_APP_PATH . '/templates';
   $CLASS_DIR          = $ROOT_PATH     . $REL_APP_PATH . '/classes';

   require_once 'ecampaign.errors';
   require_once 'ecampaign.messages';
   require_once 'DB.php';
   require_once $APP_FRAMEWORK_DIR . '/' . 'constants.php';
   require_once $APP_FRAMEWORK_DIR . '/' . $APPLICATION_CLASS;
   require_once $APP_FRAMEWORK_DIR . '/' . $ERROR_HANDLER_CLASS;
   require_once $APP_FRAMEWORK_DIR . '/' . $AUTHENTICATION_CLASS;
   require_once $APP_FRAMEWORK_DIR . '/' . $DBI_CLASS;
   require_once $TEMPLATE_CLASS;

   //Classes
   $ECAMPAIGN_LIST_CLASS       = $CLASS_DIR . '/' . 'class.EcampaignList.php';
   $ECAMPAIGN_URL_CLASS        = $CLASS_DIR . '/' . 'class.EcampaignURL.php';
   $ECAMPAIGN_TRACK_CLASS      = $CLASS_DIR . '/' . 'class.EcampaignTrack.php';
   $ECAMPAIGN_UNSUB_CLASS      = $CLASS_DIR . '/' . 'class.EcampaignUnsub.php';
   $ECAMPAIGN_CLASS            = $CLASS_DIR . '/' . 'class.Ecampaign.php';
   $ECAMPAIGN_CAMPAIGN_CLASS   = $CLASS_DIR . '/' . 'class.EcampaignCampaign.php';
   $ECAMPAIGN_MESSAGE_CLASS    = $CLASS_DIR . '/' . 'class.EcampaignMessage.php';
   $ECAMPAIGN_REPORT_CLASS     = $CLASS_DIR . '/' . 'class.EcampaignReport.php';

   // Application names

   $ECAMPAIGN_MNGR           = 'ecampaign_mngr.php';
   $ECAMPAIGN_URL_MNGR       = 'ecampaign_url_mngr.php';
   $ECAMPAIGN_CAMPAIGN_MNGR  = 'ecampaign_campaign_mngr.php';
   $ECAMPAIGN_LIST_MNGR      = 'ecampaign_list_mngr.php';
   $ECAMPAIGN_MESSAGE_MNGR   = 'ecampaign_message_mngr.php';
   $ECAMPAIGN_EXEC_MNGR      = 'ecampaign_execution_mngr.php';
   $ECAMPAIGN_REPORT_MNGR    = 'ecampaign_rpt_mngr.php';
   $ECAMPAIGN_REDIR_MNGR     = 'redir.php';
   $ECAMPAIGN_UNSUB_MNGR     = 'unsub.php';

   $REL_TEMPLATE_DIR  = $REL_APP_PATH . '/templates/';

   $ECAMPAIGN_DB_URL      = 'mysql://root:foobar@localhost/ECAMPAIGN';



   $MAX_DELIVERY_AT_A_TIME  = 2;
   $MAX_WAIT_PER_DELIVERY   = 5;


  /* Tracking */

  $SECRET = 666;


   /*  --------------START TABLE NAMES ---------------------- */


   $ECAMPAIGN_LIST_TBL              = 'ECAMPAIGN.LIST';
   $ECAMPAIGN_URL_TBL               = 'ECAMPAIGN.URL';
   $LIST_FIELD_MAP_TBL              = 'ECAMPAIGN.LIST_FIELD_MAP';
   $ECAMPAIGN_TBL                   = 'ECAMPAIGN.CAMPAIGN';
   $ECAMPAIGN_MESSAGE_TBL           = 'ECAMPAIGN.MESSAGE';
   $MESSAGE_HDRS_TBL                = 'ECAMPAIGN.MESSAGE_HDRS';
   $ECAMPAIGN_EXECUTION_TBL         = 'ECAMPAIGN.ECAMPAIGN_EXECUTION';
   $ECAMPAIGN_ASSEMBLY_TBL          = 'ECAMPAIGN.ASSEMBLY';
   $ECAMPAIGN_TRACK_TBL             = 'ECAMPAIGN.TRACK';
   $ECAMPAIGN_UNSUB_TBL             = 'ECAMPAIGN.UNSUB';
   $ECAMPAIGN_BOUNCED_TBL           = 'ECAMPAIGN.BOUNCED';


   /*  --------------END TABLE NAMES ---------------------- */

   $STATUS_TEMPLATE                 = 'ecampaign_status.ihtml';
   $ECAMPAIGN_MENU_TEMPLATE         = 'ecampaign_menu.ihtml';

   $ECAMPAIGN_ADD_LIST_TEMPLATE     = 'ecampaign_add_list.ihtml';
   $ECAMPAIGN_MOD_LIST_TEMPLATE     = 'ecampaign_mod_list.ihtml';
   $ECAMPAIGN_ADD_URL_TEMPLATE      = 'ecampaign_add_url.ihtml';
   $ECAMPAIGN_MOD_URL_TEMPLATE      = 'ecampaign_modify_url.ihtml';
   $ECAMPAIGN_DEL_URL_TEMPLATE      = 'ecampaign_del_url.ihtml';
   $ECAMPAIGN_MOD_CAMPAIGN_TEMPLATE = 'ecampaign_mod_campaign.ihtml';

   $ECAMPAIGN_MAPPING_TEMPLATE      = 'ecampaign_take_map.ihtml';
   $ECAMPAIGN_ADD_CAMPAIGN_TEMPLATE = 'ecampaign_add_campaign.ihtml';
   $ECAMPAIGN_ADD_MESSAGE_TEMPLATE  = 'ecampaign_add_message.ihtml';
   $ECAMPAIGN_ADD_TEMPLATE          = 'ecampaign_add.ihtml';

   $ECAMPAIGN_PREVIEW_MESSAGE_INPUT_TEMPLATE = 'ecampaign_preview_message_input.ihtml';
   $ECAMPAIGN_PREVIEW_MESSAGE_SHOW_TEMPLATE  = 'ecampaign_preview_message_show.ihtml';
   $ECAMPAIGN_PREVIEW_MESSAGE_TEMPLATE       = 'ecampaign_preview_message.ihtml';
   $ECAMPAIGN_EXECUTION_TEMPLATE             = 'ecampaign_execute.ihtml';
   $ECAMPAIGN_UNSUB_TEMPLATE                 = 'ecampaign_unsub.ihtml';
   $ECAMPAIGN_UNSUB_CONFIRM_TEMPLATE         = 'ecampaign_unsub_confirmation.ihtml';

   $ECAMPAIGN_REPORT_TEMPLATE     =  'ecampaign_report.ihtml';

   $MAIL_TEMPLATE = 'ecampaign_mail.ihtml';

   /* --------------------- REPORT --------------------*/
   $REPORT_EVEN_ROW_COLOR = '#ffccff';
   $REPORT_ODD_ROW_COLOR  = '#ccccff';


    $FROM_HEADER     = 1;
    $REPLY_HEADER    = 2;
    $PRIORITY_HEADER = 3;
    $SUBJECT_HEADER  = 4;

?>
