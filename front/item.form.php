<?php
define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkLoginUser();
global $LANG;

//add followup
if (isset($_REQUEST['fup'])) {
   $fup = new TicketFollowup();
   if (isset($_POST["add"])) {

      $fup->check(-1,'w',$_POST);
      $fup->add($_POST);

      Event::log($fup->getField('tickets_id'), "ticket", 4, "tracking",
                 //TRANS: %s is the user login
                 sprintf('%s', $_SESSION["glpiname"])." ".$LANG['plugin_talk']["special"][1]);

   }
}

//add document
if (isset($_REQUEST['filename']) && !empty($_REQUEST['filename'])) {
   $doc = new Document();
   if (isset($_POST["add"])) {
      $doc->check(-1,'w',$_POST);

      if ($newID = $doc->add($_POST)) {
         $str  = sprintf('%1$s', $_SESSION["glpiname"])." ";
         $str .= $LANG['plugin_talk']["special"][2]." ";
         $str .= sprintf('%2$s', $doc->fields["name"]);
         Event::log($newID, "documents", 4, "login", $str);
      }
   }
}

//delete document
if (isset($_REQUEST['delete_document'])) {
   $document_item = new Document_Item();
   $found_document_items = $document_item->find("itemtype = 'Ticket' ".
                                                " AND items_id = ".intval($_REQUEST['tickets_id']).
                                                " AND documents_id = ".intval($_REQUEST['documents_id']));
   foreach ($found_document_items as $item) {
      $document_item->delete($item, true);
   }
}

Html::back();