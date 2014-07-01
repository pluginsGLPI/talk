<?php
include ('../../../inc/includes.php');
Session::checkLoginUser();


//add followup
if (isset($_REQUEST['fup'])) {
   $fup = new TicketFollowup();
   if (isset($_POST["add"])) {

      $fup->check(-1,'w',$_POST);
      $fup->add($_POST);

      Event::log($fup->getField('tickets_id'), "ticket", 4, "tracking",
                 //TRANS: %s is the user login
                 sprintf(__('%s adds a followup'), $_SESSION["glpiname"]));

   }
}

//add document
if (isset($_REQUEST['filename']) && !empty($_REQUEST['filename'])) {
   $doc = new Document();
   if (isset($_POST["add"])) {
      $doc->check(-1,'w',$_POST);

      if ($newID = $doc->add($_POST)) {
         Event::log($newID, "documents", 4, "login",
                    sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $doc->fields["name"]));
      }
   }
}

Html::back();