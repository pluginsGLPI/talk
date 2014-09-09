<?php
define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("profile", "r");

$profile = new PluginTalkProfile();
if (isset ($_POST['update'])) {
   $profile->update($_POST);
   PluginTalkProfile::changeProfile();
   Html::back();
}
