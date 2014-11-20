<?php

// Init the hooks of the plugins -Needed
function plugin_init_talk() {
   global $PLUGIN_HOOKS,$CFG_GLPI,$LANG;
    
   $PLUGIN_HOOKS['csrf_compliant']['talk'] = true;
   
   $plugin = new Plugin();
   if ($plugin->isInstalled('talk') && $plugin->isActivated('talk')) {
      $PLUGIN_HOOKS['change_profile']['talk'] = array('PluginTalkProfile','changeProfile');
       
      //if glpi is loaded
      if (Session::getLoginUserID()) {

         Plugin::registerClass('PluginTalkProfile',
                               array('addtabon' => 'Profile'));

         Plugin::registerClass('PluginTalkUserpref',
                               array('addtabon' => array('User', 'Preference')));

         $PLUGIN_HOOKS['use_massive_action']['talk'] = 1;

         if (Session::haveRight('plugin_accounts_open_ticket', 1)) {
            if (strpos($_SERVER['REQUEST_URI'], "/ticket.form.php") !== false
               && isset($_GET['id'])) {

               if (PluginTalkUserpref::isFunctionEnabled("talk_tab"))  {
                  Plugin::registerClass('PluginTalkTicket',
                                  array('addtabon' => array('Ticket')));

                  $PLUGIN_HOOKS['add_css']['talk'][] = 'css/talk.css';
                  $PLUGIN_HOOKS['add_css']['talk'][] = 'css/hide_ticket_tabs.css';

                  $PLUGIN_HOOKS['add_javascript']['talk'][] = 'scripts/move_talktab.js';
                  $PLUGIN_HOOKS['add_javascript']['talk'][] = 'scripts/filter_timeline.js';
                  $PLUGIN_HOOKS['add_javascript']['talk'][] = 'scripts/read_more.js';
                  $PLUGIN_HOOKS['add_javascript']['talk'][] = 'scripts/split_button.js';
               }

               // if (PluginTalkUserpref::isFunctionEnabled("split_view"))  {
               //    $PLUGIN_HOOKS['add_css']['talk'][] = 'css/split_ticket_view.css';
               // }
            

            }
         }
      }
   }
}

// Get the name and the version of the plugin - Needed
function plugin_version_talk() {
   global $LANG;

   $author = "<a href='www.teclib.com'>TECLIB'</a>";
   return array ('name' => __("Talks", "talk"),
                 'version' => '0.84-1.0',
                 'author' => $author,
                 'homepage' => 'www.teclib.com',
                 'minGlpiVersion' => '0.85');
}

// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_talk_check_prerequisites() {
   if (version_compare(GLPI_VERSION,'0.85','lt') || version_compare(GLPI_VERSION,'0.86','ge')) {
      echo "This plugin requires GLPI 0.85+";
      return false;
   }
   return true;
}

// Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
function plugin_talk_check_config() {
   return true;
}

function plugin_talk_haveRight($module,$right) {
   $matches=array(
            ""  => array("","r","w"), // ne doit pas arriver normalement
            "r" => array("r","w"),
            "w" => array("w"),
            "1" => array("1"),
            "0" => array("0","1"), // ne doit pas arriver non plus
   );
   if (isset($_SESSION["glpi_plugin_talk_profile"][$module])
         && in_array($_SESSION["glpi_plugin_talk_profile"][$module], $matches[$right]))
      return true;
   else return false;
}