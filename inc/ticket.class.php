<?php

class PluginTalkTicket {
   static function getTypeName($nb=0) {
      return __("Processing ticket", "talk");
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      if ($item instanceOf Ticket) {
         return self::getTypeName();
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      if ($item instanceof Ticket) {
         return self::showForTicket($item, $withtemplate);
      }
      return true;
   }

   static function showForTicket(Ticket $ticket, $withtemplate = array()) {
      $rand = mt_rand();

      echo "<div class='talk_box'>";
      self::showForm($ticket, $rand);
      self::showHistory($ticket, $rand);
      echo "</div>";
   }

   static function showForm(Ticket $ticket, $rand) {
      global $CFG_GLPI;

      //check global rights
      if (!Session::haveRight("observe_ticket", "1")
          && !Session::haveRight("show_full_ticket", "1")) {
         return false;
      }

      // javascript function for add and edit items
      echo "<script type='text/javascript' >\n";
      echo "function viewAddSubitem" . $ticket->fields['id'] . "$rand(itemtype) {\n";
      $params = array('type'       => 'itemtype',
                      'parenttype' => 'Ticket',
                      'tickets_id' => $ticket->fields['id'],
                      'id'         => -1);
      $out = Ajax::updateItemJsCode("viewitem" . $ticket->fields['id'] . "$rand",
                             $CFG_GLPI["root_doc"]."/plugins/talk/ajax/viewsubitem.php", $params, "", false);
      echo str_replace("itemtype", "'+itemtype+'", $out);
      echo "};";
      echo "function viewEditSubitem" . $ticket->fields['id'] . "$rand(itemtype, items_id, o) {\n";
      $params = array('type'       => 'itemtype',
                      'parenttype' => 'Ticket',
                      'tickets_id' => $ticket->fields['id'],
                      'id'         => 'items_id');
      $out = Ajax::updateItemJsCode("viewitem" . $ticket->fields['id'] . "$rand",
                             $CFG_GLPI["root_doc"]."/plugins/talk/ajax/viewsubitem.php", $params, "", false);
      $out = str_replace("itemtype", "'+itemtype+'", $out);
      $out = str_replace("items_id", "'+items_id+'", $out);
      echo $out;

      //scroll to edit form
      echo "window.scrollTo(0,500);";

      // add a mark to currently edited element
      echo "var found_active = document.getElementsByClassName('talk_active');
            i = found_active.length;
            while(i--) {
               var classes = found_active[i].className.replace( /(?:^|\s)talk_active(?!\S)/ , '' );
               found_active[i].className = classes;
            }
            o.className = o.className + ' talk_active';
      };";
      echo "</script>\n";
      
      //check sub-items rights
      $tmp = array('tickets_id' => $ticket->getID());
      $fup             = new TicketFollowup;
      $ttask           = new TicketTask;

      $canadd_fup      = TicketFollowup::canCreate() && $fup->can(-1, 'w', $tmp);
      $canadd_task     = TicketTask::canCreate() && $ttask->can(-1, 'w', $tmp);
      $canadd_document = Document::canCreate();
      $canadd_solution = Ticket::canUpdate() && $ticket->canSolve();

      if (!$canadd_fup && !$canadd_task && !$canadd_document && !$canadd_solution ) {
         return false;
      }

      //show choices
      if ($ticket->fields["status"] != CommonITILObject::SOLVED
         && $ticket->fields["status"] != CommonITILObject::CLOSED) {
         echo "<h2>"._sx('button', 'Add')." : </h2>";
         echo "<div class='talk_form'>";
         echo "<ul class='talk_choices'>";
         if ($canadd_fup) {   
            echo "<li class='followup' onclick='".
                 "javascript:viewAddSubitem".$ticket->fields['id']."$rand(\"TicketFollowup\");'>"
                 .__("Followup")."</li>";
         }
         if ($canadd_task) {   
            echo "<li class='task' onclick='".
                 "javascript:viewAddSubitem".$ticket->fields['id']."$rand(\"TicketTask\");'>"
                 .__("Task")."</li>";
         }
         if ($canadd_document) { 
            echo "<li class='document' onclick='".
                 "javascript:viewAddSubitem".$ticket->fields['id']."$rand(\"Document_Item\");'>"
                 .__("Document")."</li>";
         }
         if ($canadd_solution) { 
            echo "<li class='solution' onclick='".
                 "javascript:viewAddSubitem".$ticket->fields['id']."$rand(\"Solution\");'>"
                 .__("Solution")."</li>";
         }
         echo "</ul>"; // talk_choices
         echo "<div class='clear'>&nbsp;</div>";
         echo "</div>"; //end talk_form      
      } 

      echo "<div class='ajax_box' id='viewitem" . $ticket->fields['id'] . "$rand'></div>\n";

   }

   static function showHistory(Ticket $ticket, $rand) {
      global $CFG_GLPI, $DB;

      $pics_url = "../plugins/talk/pics";
      $user = new User;
      $timeline = array();

      $followup_obj = new TicketFollowup;
      $task_obj = new TicketTask;
      $document_item_obj = new Document_Item;
      $ticket_valitation_obj = new TicketValidation;

      //checks rights
      $showpublic = Session::haveRight("observe_ticket", "1");
      $showprivate = Session::haveRight("show_full_ticket", "1");
      $restrict = "";
      if (!$showprivate) {
         $restrict = " AND (`is_private` = '0'
                            OR `users_id` ='" . Session::getLoginUserID() . "') ";
      }
      if (!$showpublic) {
         $restrict = " AND 1 = 0";
      }

      //add ticket followups to timeline
      $followups = $followup_obj->find("tickets_id = ".$ticket->getID()." $restrict", 'date DESC');
      foreach ($followups as $followups_id => $followup) {
         $followup_obj->getFromDB($followups_id);
         $can_edit = $followup_obj->canUpdateItem();
         $followup['can_edit'] = $can_edit;
         $timeline[$followup['date']."_followup_".$followups_id] = array('type' => 'TicketFollowup', 'item' => $followup);
      }


      //add ticket taks to timeline
      $tasks = $task_obj->find("tickets_id = ".$ticket->getID()." $restrict", 'date DESC');
      foreach ($tasks as $tasks_id => $task) {
         $task_obj->getFromDB($tasks_id);
         $can_edit = $task_obj->canUpdateItem();
         $task['can_edit'] = $can_edit;
         $timeline[$task['date']."_task_".$tasks_id] = array('type' => 'TicketTask', 'item' => $task);
      }


      //add ticket documents to timeline
      $document_obj = new Document;
      $document_items = $document_item_obj->find("itemtype = 'Ticket' AND items_id = ".$ticket->getID());
      foreach ($document_items as $document_item) {
         $document_obj->getFromDB($document_item['documents_id']);
         $timeline[$document_obj->fields['date_mod']."_document_".$document_item['documents_id']] 
            = array('type' => 'Document_Item', 'item' => $document_obj->fields);
      }

      //add existing solution
      if (!empty($ticket->fields['solution'])) {
         $users_id = 0;
         $solution_date = $ticket->fields['solvedate'];

         //search date and user of last solution in glpi_logs
         if ($res_solution = $DB->query("SELECT date_mod AS solution_date, user_name FROM glpi_logs
                                     WHERE itemtype = 'Ticket' 
                                     AND items_id = ".$ticket->getID()."
                                     AND id_search_option = 24
                                     ORDER BY id DESC
                                     LIMIT 1")) {
            $data_solution = $DB->fetch_assoc($res_solution);
            if (!empty($data_solution['solution_date'])) $solution_date = $data_solution['solution_date'];
            
            // find user
            if (!empty($data_solution['user_name'])) {
               $users_id = addslashes(trim(preg_replace("/.*\(([0-9]+)\)/", "$1", $data_solution['user_name'])));
            }
         }
      
         $timeline[$solution_date."_solution"] 
            = array('type' => 'Solution', 'item' => array('id'               => 0,
                                                          'content'          => $ticket->fields['solution'],
                                                          'date'             => $solution_date, 
                                                          'users_id'         => $users_id, 
                                                          'solutiontypes_id' => $ticket->fields['solutiontypes_id'],
                                                          'can_edit'         => Ticket::canUpdate() && $ticket->canSolve()));
      }

      // add ticket validation to timeline
       if ($ticket->fields['type'] == Ticket::DEMAND_TYPE && 
            (Session::haveRight('validate_request',1) || Session::haveRight('create_request_validation',1))
       || $ticket->fields['type'] == Ticket::INCIDENT_TYPE &&
            (Session::haveRight('validate_incident',1)|| Session::haveRight('create_incident_validation',1)))
          {
        
         $ticket_validations = $ticket_valitation_obj->find('tickets_id = '.$ticket->getID());
         foreach ($ticket_validations as $validations_id => $validation) {
            $canedit = $ticket_valitation_obj->can($validations_id,'w');
            $user->getFromDB($validation['users_id_validate']);
            $timeline[$validation['submission_date']."_validation_".$validations_id] 
               = array('type' => 'TicketValidation', 'item' => array(
                  'id'        => $validations_id,
                  'date'      => $validation['submission_date'],
                  'content'   => __('Validation request')." => ".$user->getlink().
                                 "<br>".$validation['comment_submission'],
                  'users_id'  => $validation['users_id'], 
                  'can_edit'  => $canedit
               ));

            if (!empty($validation['validation_date'])) {
               $timeline[$validation['validation_date']."_validation_".$validations_id] 
               = array('type' => 'TicketValidation', 'item' => array(
                  'id'        => $validations_id,
                  'date'      => $validation['validation_date'],
                  'content'   => __('Validation request answer')." : ".
                                 _sx('status', ucfirst($validation['status']))."<br>".
                                 $validation['comment_validation'],
                  'users_id'  => $validation['users_id_validate'], 
                  'status'    => $validation['status'], 
                  'can_edit'  => $canedit
               ));
            }
         }
      }

      //reverse sort timeline items by key (date)
      krsort($timeline);

      if (count($timeline) == 0) {
         return;
      }

      //display timeline
      echo "<div class='talk_history'>";
      echo "<h2>".__("Historical")."</h2>";
      $timeline_index = 0;
      foreach ($timeline as $item) {
         $item_i = $item['item'];

         $date = "";
         if (isset($item_i['date'])) $date = $item_i['date'];
         if (isset($item_i['date_mod'])) $date = $item_i['date_mod'];
         
         echo "<div class='h_item'>";

         echo "<div class='h_left'>";
         echo "<div class='h_date'>".Html::convDateTime($date)."</div>";
         if (isset($item_i['users_id'])) {
            $user->getFromDB($item_i['users_id']);
            echo "<div class='h_user'>".$user->getLink()."</div>";
         }
         echo "</div>";
      
         echo "<div class='h_right ".$item['type'].
              ((isset($item_i['is_private']) && $item_i['is_private']) ? " private" : "").
              ((isset($item_i['status'])) ? " ".$item_i['status'] : "").
              "'";
         if ($item['type'] != "Document_Item" && $item_i['can_edit']) {     
            echo " onclick='javascript:viewEditSubitem".$ticket->fields['id']."$rand(\"".$item['type']."\", ".$item_i['id'].", this)'";
         }
         echo ">";
         if (isset($item_i['requesttypes_id']) 
               && file_exists("$pics_url/".$item_i['requesttypes_id'].".png")) {
            echo "<img src='$pics_url/".$item_i['requesttypes_id'].".png' title='' class='h_requesttype' />";
         }

         if (isset($item_i['content'])) {
            echo "<div class='item_content'>";
            echo html_entity_decode(nl2br($item_i['content']));
            echo "</div>";
         }

         echo "<div class='b_right'>";
         if (isset($item_i['solutiontypes_id']) && !empty($item_i['solutiontypes_id'])) {
            echo Dropdown::getDropdownName("glpi_solutiontypes", $item_i['solutiontypes_id'])."<br>";
         }
         if (isset($item_i['taskcategories_id']) && !empty($item_i['taskcategories_id'])) {
            echo Dropdown::getDropdownName("glpi_taskcategories", $item_i['taskcategories_id'])."<br>";
         }
         if (isset($item_i['actiontime']) && !empty($item_i['actiontime'])) {
            echo "<span class='actiontime'>";
            echo Html::timestampToString($item_i['actiontime'], false);
            echo "</span>";
         }
         if (isset($item_i['state'])) {
            echo "<span class='state state_".$item_i['state']."'>";
            echo Planning::getState($item_i['state']);
            echo "</span>";
         }
         if (isset($item_i['begin'])) {
            echo "<span class='planification'>";
            echo Html::convDateTime($item_i["begin"]);
            echo " => ";
            echo Html::convDateTime($item_i["end"]);
            echo "</span>";
         }
         echo "</div>";

         if ($item['type'] == 'Document_Item') {
            $filename = $item_i['filename'];
            if (empty($filename)) {
               $filename = $item_i['name'];
            }
            echo "<img src='$pics_url/file.png' title='file' />&nbsp;";
            echo "<a href='".$CFG_GLPI['root_doc']."/front/document.send.php?docid=".$item_i['id']
                ."&tickets_id=".$ticket->getID()
                ."' target='_blank'>$filename</a>";
         }
         echo "</div>"; //end h_right

         echo "</div>"; //end  h_item

         if ($timeline_index == 0 && $item['type'] == "Solution" 
            && $ticket->fields["status"] == CommonITILObject::SOLVED) {
            $followup_obj->showApprobationForm($ticket);
         }
         $timeline_index++;
      } // end foreach timeline
      echo "<div class='break'></div>";
      echo "</div>";
   }

   static function showSubForm(CommonDBTM $item, $id, $params) {
      if (method_exists($item, "showForm")) {
         $item->showForm($id, $params);
      } else {
         if ($item instanceof Document_Item) {
            self::showSubFormDocument_Item($params['tickets_id'], $params);
         } 
      }
   }

   static function showSubFormDocument_Item($ID, $params) {
      global $DB, $CFG_GLPI;

      $item = new Ticket;
      $item->getFromDB($ID);

      if (empty($withtemplate)) {
         $withtemplate = 0;
      }
      $linkparam = '';

      if (get_class($item) == 'Ticket') {
         $linkparam = "&amp;tickets_id=".$item->fields['id'];
      }

      $canedit       =  $item->canAddItem('Document') && Document::canView();
      $rand          = mt_rand();
      $is_recursive  = $item->isRecursive();
      $order = "DESC";
      $sort = "`assocdate`";
      
      $query = "SELECT `glpi_documents_items`.`id` AS assocID,
                       `glpi_documents_items`.`date_mod` AS assocdate,
                       `glpi_entities`.`id` AS entityID,
                       `glpi_entities`.`completename` AS entity,
                       `glpi_documentcategories`.`completename` AS headings,
                       `glpi_documents`.*
                FROM `glpi_documents_items`
                LEFT JOIN `glpi_documents`
                          ON (`glpi_documents_items`.`documents_id`=`glpi_documents`.`id`)
                LEFT JOIN `glpi_entities` ON (`glpi_documents`.`entities_id`=`glpi_entities`.`id`)
                LEFT JOIN `glpi_documentcategories`
                        ON (`glpi_documents`.`documentcategories_id`=`glpi_documentcategories`.`id`)
                WHERE `glpi_documents_items`.`items_id` = '$ID'
                      AND `glpi_documents_items`.`itemtype` = '".$item->getType()."' ";

      if (Session::getLoginUserID()) {
         $query .= getEntitiesRestrictRequest(" AND","glpi_documents",'','',true);
      } else {
         // Anonymous access from FAQ
         $query .= " AND `glpi_documents`.`entities_id`= '0' ";
      }

      // Document : search links in both order using union
      if ($item->getType() == 'Document') {
         $query .= "UNION
                    SELECT `glpi_documents_items`.`id` AS assocID,
                           `glpi_documents_items`.`date_mod` AS assocdate,
                           `glpi_entities`.`id` AS entityID,
                           `glpi_entities`.`completename` AS entity,
                           `glpi_documentcategories`.`completename` AS headings,
                           `glpi_documents`.*
                    FROM `glpi_documents_items`
                    LEFT JOIN `glpi_documents`
                              ON (`glpi_documents_items`.`items_id`=`glpi_documents`.`id`)
                    LEFT JOIN `glpi_entities`
                              ON (`glpi_documents`.`entities_id`=`glpi_entities`.`id`)
                    LEFT JOIN `glpi_documentcategories`
                              ON (`glpi_documents`.`documentcategories_id`=`glpi_documentcategories`.`id`)
                    WHERE `glpi_documents_items`.`documents_id` = '$ID'
                          AND `glpi_documents_items`.`itemtype` = '".$item->getType()."' ";

         if (Session::getLoginUserID()) {
            $query .= getEntitiesRestrictRequest(" AND","glpi_documents",'','',true);
         } else {
            // Anonymous access from FAQ
            $query .= " AND `glpi_documents`.`entities_id`='0' ";
         }
      }
      $query .= " ORDER BY $sort $order ";

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $i      = 0;

      $documents = array();
      $used      = array();
      if ($numrows = $DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $documents[$data['assocID']] = $data;
            $used[$data['id']]           = $data['id'];
         }
      }

      if ($item->canAddItem('Document') && $withtemplate < 2) {
         // Restrict entity for knowbase
         $entities = "";
         $entity   = $_SESSION["glpiactive_entity"];

         if ($item->isEntityAssign()) {
            /// Case of personal items : entity = -1 : create on active entity (Reminder case))
            if ($item->getEntityID() >=0 ) {
               $entity = $item->getEntityID();
            }

            if ($item->isRecursive()) {
               $entities = getSonsOf('glpi_entities',$entity);
            } else {
               $entities = $entity;
            }
         }
         $limit = getEntitiesRestrictRequest(" AND ","glpi_documents",'',$entities,true);
         $q = "SELECT COUNT(*)
               FROM `glpi_documents`
               WHERE `is_deleted` = '0'
               $limit";

         $result = $DB->query($q);
         $nb     = $DB->result($result,0,0);


         if ($item->getType() == 'Document') {
            $used[$ID] = $ID;
         }

         echo "<div class='firstbloc'>";
         echo "<form name='documentitem_form$rand' id='documentitem_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL('Document')."'  enctype=\"multipart/form-data\">";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='5'>".__('Add a document')."</th></tr>";
         echo "<tr class='tab_bg_1'>";

         echo "<td class='center'>";
         _e('Heading');
         echo '</td><td>';
         DocumentCategory::dropdown(array('entity' => $entities));
         echo "</td>";
         echo "<td class='right'>";
         echo "<input type='hidden' name='entities_id' value='$entity'>";
         echo "<input type='hidden' name='is_recursive' value='$is_recursive'>";

         echo "<input type='hidden' name='itemtype' value='".$item->getType()."'>";
         echo "<input type='hidden' name='items_id' value='$ID'>";
         if ($item->getType() == 'Ticket') {
            echo "<input type='hidden' name='tickets_id' value='$ID'>";
         }
         echo "<input type='file' name='filename' size='25'>";
         echo "</td><td class='left'>";
         echo "(".Document::getMaxUploadSize().")&nbsp;";
         echo "</td>";
         echo "<td class='center' width='20%'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add a new file')."\"
                class='submit'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();

         if (Session::haveRight('document','r')
             && ($nb > count($used))) {
            echo "<form name='document_form$rand' id='document_form$rand' method='post'
                   action='".Toolbox::getItemTypeFormURL('Document')."'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='4' class='center'>";
            echo "<input type='hidden' name='itemtype' value='".$item->getType()."'>";
            echo "<input type='hidden' name='items_id' value='$ID'>";
            if ($item->getType() == 'Ticket') {
               echo "<input type='hidden' name='tickets_id' value='$ID'>";
               echo "<input type='hidden' name='documentcategories_id' value='".
                      $CFG_GLPI["documentcategories_id_forticket"]."'>";
            }

            Document::dropdown(array('entity' => $entities ,
                                     'used'   => $used));
            echo "</td><td class='center' width='20%'>";
            echo "<input type='submit' name='add' value=\"".
                     _sx('button', 'Associate an existing document')."\" class='submit'>";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
            Html::closeForm();
         }

         echo "</div>";
      }
      

   }

   static function showSubFormSolution($ID) {
      $ticket = new Ticket;
      $ticket->getFromDB($ID);
      $ticket->showSolutionForm();
   }
   
}