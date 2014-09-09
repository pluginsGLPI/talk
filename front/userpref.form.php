<?php
define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

if (isset ($_POST['update'])) {
   $userpref = new PluginTalkUserpref();
   $userpref->update($_POST);
   
   if ($_POST['users_id'] === Session::getLoginUserID()) {
      PluginTalkUserpref::loadInSession();
   }
}

Html::back();
