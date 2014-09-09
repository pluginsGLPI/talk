<?php
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginTalkProfile extends CommonDBTM {

   static function install(Migration $migration) {
      global $DB;

      if (!$DB->query("CREATE TABLE IF NOT EXISTS `glpi_plugin_talk_profiles` (
            `id` int(11) NOT NULL auto_increment,
            `profiles_id` int(11) NOT NULL default '0',
            `is_active` char(1) collate utf8_unicode_ci default NULL,
            PRIMARY KEY (`id`),
            KEY `profiles_id` (`profiles_id`)
      ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci")) {
         return false;
      }

      self::createFirstAccess($_SESSION['glpiactiveprofile']['id']);     
   }

   static function uninstall() {
      global $DB;
      return $DB->query("DROP TABLE IF EXISTS `glpi_plugin_talk_profiles`");
   }
    
   static function getTypeName($nb=0) {
      global $LANG;
      return $LANG['plugin_talk']["title"][7];
   }
    
   function canCreate() {
      return Session::haveRight('profile', 'w');
   }

   function canView() {
      return Session::haveRight('profile', 'r');
   }
    
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      if ($item->getType() == 'Profile') {
         return self::getTypeName(2);
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      if ($item->getType() == 'Profile') {
         $ID = $item->getField('id');
         $prof = new self();
          
         if (!$prof->getFromDBByProfile($item->getField('id'))) {
            $prof->createAccess($item->getField('id'));
         }
         $prof->showForm($item->getField('id'), array('target' =>
                  $CFG_GLPI["root_doc"]."/plugins/talk/front/profile.form.php"));
      }
      return true;
   }
    
   static function purgeProfiles(Profile $prof) {
      $plugprof = new self();
      $plugprof->deleteByCriteria(array('profiles_id' => $prof->getField("id")));
   }
    
   private function getFromDBByProfile($profiles_id) {
      global $DB;

      $query = "SELECT * FROM `".$this->getTable()."`
               WHERE `profiles_id` = '" . $profiles_id . "' ";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) != 1) {
            return false;
         }
         $this->fields = $DB->fetch_assoc($result);
         if (is_array($this->fields) && count($this->fields)) {
            return true;
         } else {
            return false;
         }
      }
      return false;
   }

   static function createFirstAccess($ID) {

      $myProf = new self();
      if (!$myProf->getFromDBByProfile($ID)) {
         $myProf->add(array(
                  'profiles_id' => $ID,
                  'is_active' => '1'));
      }
   }

   function createAccess($ID) {
      $this->add(array(
               'profiles_id' => $ID));
   }

   static function changeProfile() {
      $prof = new self();
      if ($prof->getFromDBByProfile($_SESSION['glpiactiveprofile']['id'])) {
         $_SESSION["glpi_plugin_talk_profile"] = $prof->fields;

         //get User preferences
         PluginTalkUserpref::loadInSession();
      } else {
         unset($_SESSION["glpi_plugin_talk_profile"]);
      }
   }
    
   function showForm ($ID, $options=array()) {
      global $LANG;
      if (!Session::haveRight("profile","r")) return false;

      $prof = new Profile();
      if ($ID) {
         $this->getFromDBByProfile($ID);
         $prof->getFromDB($ID);
      }

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_2'>";
      echo "<td width='10%'>" . $LANG['buttons'][41] . "</td>";
      echo "<td style='text-align:left;'>";
      Dropdown::showYesNo("is_active",$this->fields["is_active"]);
      echo "</td>";
      echo "</tr>";

      echo "<input type='hidden' name='id' value=".$this->fields["id"].">";

      $options['candel'] = false;
      $this->showFormButtons($options);
   }
}
