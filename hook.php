<?php

function plugin_talk_install() {
   $version = plugin_version_talk();
   $migration = new Migration($version['version']);

   // Parse inc directory
   foreach(glob(dirname(__FILE__).'/inc/*') as $filepath) {
      // Load *.class.php files and get the class name
      if(preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
         $classname = 'PluginTalk' . ucfirst($matches[1]);
         include_once($filepath);
         // If the install method exists, load it
         if(method_exists($classname, 'install')) {
            $classname::install($migration);
         }
      }
   }
   return true ;
}   

function plugin_talk_uninstall() {
   // Parse inc directory
   foreach(glob(dirname(__FILE__).'/inc/*') as $filepath) {
      // Load *.class.php files and get the class name
      if(preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
         $classname = 'PluginTalk' . ucfirst($matches[1]);
         include_once($filepath);
         // If the install method exists, load it
         if(method_exists($classname, 'uninstall')) {
            $classname::uninstall();
         }
      }
   }
   return true ;
}