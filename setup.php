<?php

// Init the hooks of the plugins -Needed
function plugin_init_talk() {
   global $PLUGIN_HOOKS,$CFG_GLPI,$LANG;
    
   $PLUGIN_HOOKS['csrf_compliant']['talk'] = true;
   
   $plugin = new Plugin();
   if ($plugin->isInstalled('talk') && $plugin->isActivated('talk')) {
       
      //if glpi is loaded
      if (Session::getLoginUserID()) {
         Plugin::registerClass('PluginTalkTicket',
                               array('addtabon' => array('Ticket')));

         if (strpos($_SERVER['REQUEST_URI'], "ticket.form.php") !== false
            && isset($_GET['id'])) {

            $PLUGIN_HOOKS['add_css']['talk'][] = 'css/talk.css';
            //$PLUGIN_HOOKS['add_css']['talk'][] = 'css/hide_ticket_tabs.css';
         
            $PLUGIN_HOOKS['add_javascript']['talk'][] = 'scripts/move_talktab.js';
         }
     
      }
   }
}

// Get the name and the version of the plugin - Needed
function plugin_version_talk() {
   global $LANG;

   $author = "<a href='www.teclib.com'>TECLIB'</a>";
   return array ('name' => "Talk",
                 'version' => '0.84-1.0',
                 'author' => $author,
                 'homepage' => 'www.teclib.com',
                 'minGlpiVersion' => '0.84');
}

// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_talk_check_prerequisites() {
   if (version_compare(GLPI_VERSION,'0.84','lt') || version_compare(GLPI_VERSION,'0.85','ge')) {
      echo "This plugin requires GLPI 0.84+";
      return false;
   }
   return true;
}

// Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
function plugin_talk_check_config() {
   return true;
}
