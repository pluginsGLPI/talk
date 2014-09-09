<?php
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginTalkUserpref extends CommonDBTM {

   static function getTypeName($nb=0) {
      global $LANG;
      return $LANG['plugin_talk']["title"][7];
   }
    
   function getIndexName() {
      return "users_id";
   }

   function canCreate() {
      return true;
   }

   function canView() {
      return true;
   }
    
   static function install(Migration $migration) {
      global $DB;

      if (!$DB->query("CREATE TABLE IF NOT EXISTS `glpi_plugin_talk_userprefs` (
            `id`                INT(11) NOT NULL auto_increment,
            `users_id`          INT(11) NOT NULL default '0',
            `talk_tab`   TINYINT(1) NOT NULL default '1',
            `split_view` TINYINT(1) NOT NULL default '0',
            PRIMARY KEY  (`id`),
            UNIQUE KEY (`users_id`),
            KEY `talk_tab` (`talk_tab`),
            KEY `split_view` (`split_view`)
      ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci")) {
         return false;
      }   
   }

   static function uninstall() {
      global $DB;
      return $DB->query("DROP TABLE IF EXISTS `glpi_plugin_talk_userprefs`");
   }

   static function loadInSession() {
      unset($_SESSION['talk_userprefs']);
      $self = new self();
      if (! $self->getFromDB(Session::getLoginUserID())) {
         $self->add(array('users_id' => Session::getLoginUserID()));
         $self->getFromDB(Session::getLoginUserID());
      }
      $_SESSION['talk_userprefs'] = $self->fields;
   }

   /**
    * 
    * @param unknown $function
    * @return boolean
    */
   static function isFunctionEnabled($function) {
      return (isset($_SESSION['talk_userprefs'][$function])
         && $_SESSION['talk_userprefs'][$function] == 1);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      if (in_array($item->getType(), array('User', 'Preference'))) {
         return self::getTypeName(2);
      }
      return '';
   }

   /**
    * 
    * @param CommonGLPI $item
    * @param number $tabnum
    * @param number $withtemplate
    * @return boolean true
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      if ($item->getType()=='User') {
         $ID = $item->getField('id');
      } else if ($item->getType()=='Preference') {
         $ID = Session::getLoginUserID();
      }

      $self = new self();
      $self->showForm($ID);
      
      return true;
   }

   function showForm($ID, $options=array()) {
      global $LANG;
      
      if (!$this->getFromDB($ID)) {
         $this->add(array('users_id' => $ID));
         $this->getFromDB($ID);
      }

      //$this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_2'>";
      echo "<td width='10%'>".$LANG['plugin_talk']["title"][5]."</td>";
      echo "<td style='text-align:left;'>";
      Dropdown::showYesNo("talk_tab", $this->fields["talk_tab"]);
      echo "</td>";

      echo "<td width='10%'>".$LANG['plugin_talk']["title"][6]."</td>";
      echo "<td style='text-align:left;'>";
      Dropdown::showYesNo("split_view", $this->fields["split_view"]);
      echo "</td>";
      echo "</tr>";

      echo "<input type='hidden' name='id' value=".$this->fields["id"].">";
      echo "<input type='hidden' name='users_id' value=".$this->fields["users_id"].">";

      $options['candel'] = false;
      $this->showFormButtons($options);
   }
}
