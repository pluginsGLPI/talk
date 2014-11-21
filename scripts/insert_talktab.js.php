<?php
include ('../../../inc/includes.php');

//change mimetype
header("Content-type: application/javascript");

if (!$plugin->isInstalled("talk") 
   || !$plugin->isActivated("talk")
   || !isset($_SESSION['plugin_talk_lasttickets_id'])) {
   exit;
}

$ticket     = new Ticket;
$ticket->getFromDB(intval($_SESSION['plugin_talk_lasttickets_id']));
$talkticket = new PluginTalkTicket;
$tab_title  = $talkticket->getTabNameForItem($ticket);
$tab_url    = "/glpi/0.85-git/ajax/common.tabs.php?_target=/glpi/0.85-git/front/ticket.form.php&_itemtype=Ticket&_glpi_tab=PluginTalkTicket$1&id=1";

$JS = <<<JAVASCRIPT
$(document).ready(function() {
   //need a timeout for execute code after tabpanel initialization
   window.setTimeout(function() {
      //function for move tab
      this.inserTab = function() {
         tabpanel = $('#tabspanel + div.ui-tabs');
         tabpanel.children("ul").append(
            "<li title='$tab_title'><a href='$tab_url'>$tab_title</a></li>"
         );
         tabpanel.tabs("refresh");
      }

      this.inserTab();;
   }, 250)
});

JAVASCRIPT;
echo $JS;