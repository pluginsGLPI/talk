<?php
define('GLPI_ROOT', '../../..');
include ('../../../inc/includes.php');
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

//Plugin::load('talk', true);

Session::checkLoginUser();

if (!isset($_POST['type']) || !isset($_POST['parenttype'])) {
   exit();
}

if ($_POST['type'] != "Solution" &&
      ($item = getItemForItemtype($_POST['type']))
    && ($parent = getItemForItemtype($_POST['parenttype']))) {
   if (isset($_POST[$parent->getForeignKeyField()])
       && isset($_POST["id"])
       && $parent->getFromDB($_POST[$parent->getForeignKeyField()])) {
         PluginTalkTicket::showSubForm($item, $_POST["id"], array('parent' => $parent, 
                                                                  'tickets_id' => $_POST["tickets_id"]));
   } else {
      echo $LANG['plugin_talk']["error"][1];
   }
} else if ($_POST['type'] == "Solution") {
   PluginTalkTicket::showSubFormSolution($_POST["tickets_id"]);
}
Html::ajaxFooter();
