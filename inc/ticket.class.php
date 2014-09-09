<?php
class PluginTalkTicket extends CommonGLPI {
   static function getTypeName($nb=0) {
      global $LANG;
      return $LANG['plugin_talk']["title"][2];
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      if ($item instanceOf Ticket) {
         $timeline = self::geTimelineItems($item, '');
         $nb_elements = count($timeline);
         return self::createTabEntry(self::getTypeName(2), $nb_elements);
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
      global $LANG, $CFG_GLPI;

      //check global rights
      if (!Session::haveRight("observe_ticket", "1")
          && !Session::haveRight("show_full_ticket", "1")) {
         return false;
      }

      // javascript function for add and edit items
      echo "<script type='text/javascript'>\n";
      echo "function viewAddSubitem" . $ticket->fields['id'] . "$rand(itemtype) {\n";
      $params = array('type'       => 'itemtype',
                      'parenttype' => 'Ticket',
                      'tickets_id' => $ticket->fields['id'],
                      'id'         => -1);
      ob_start();
      Ajax::updateItemJsCode("viewitem" . $ticket->fields['id'] . "$rand",
                             $CFG_GLPI["root_doc"]."/plugins/talk/ajax/viewsubitem.php", $params, "", false);
      $out = ob_get_contents();
      ob_get_clean();
      echo str_replace("itemtype", "'+itemtype+'", $out);
      echo "};";
      echo "function viewEditSubitem" . $ticket->fields['id'] . "$rand(e, itemtype, items_id, o) {\n";
      echo "var e = window.event || e; console.log(e);";
      echo "if (e.target.localName == 'a') return;";
      echo "if (e.target.className == 'read_more_button') return;";
      $params = array('type'       => 'itemtype',
                      'parenttype' => 'Ticket',
                      'tickets_id' => $ticket->fields['id'],
                      'id'         => 'items_id');
      ob_start();
      Ajax::updateItemJsCode("viewitem" . $ticket->fields['id'] . "$rand",
            $CFG_GLPI["root_doc"]."/plugins/talk/ajax/viewsubitem.php", $params, "");
      $out = ob_get_contents();
      ob_get_clean();
      
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
      $fup             = new TicketFollowup();
      $ttask           = new TicketTask();
      $doc             = new Document();
      
      $canadd_fup      = $fup->canCreate() && $fup->can(-1, 'w', $tmp);
      $canadd_task     = $ttask->canCreate() && $ttask->can(-1, 'w', $tmp);
      $canadd_document = $doc->canCreate();
      $canadd_solution = $ticket->canUpdate() && $ticket->canSolve();

      if (!$canadd_fup && !$canadd_task && !$canadd_document && !$canadd_solution ) {
         return false;
      }

      //show choices
      if ($ticket->fields["status"] != 'solved'
         && $ticket->fields["status"] != 'closed') {
         echo "<h2>" . $LANG['buttons'][8] . "</h2>";
         echo "<div class='talk_form'>";
         echo "<ul class='talk_choices'>";
         if ($canadd_fup) {   
            echo "<li class='followup' onclick='".
                 "javascript:viewAddSubitem".$ticket->fields['id']."$rand(\"TicketFollowup\");'>"
                 .$LANG['mailing'][141]."</li>";
         }
         if ($canadd_task) {   
            echo "<li class='task' onclick='".
                 "javascript:viewAddSubitem".$ticket->fields['id']."$rand(\"TicketTask\");'>"
                 .$LANG['job'][7]."</li>";
         }
         if ($canadd_document) { 
            echo "<li class='document' onclick='".
                 "javascript:viewAddSubitem".$ticket->fields['id']."$rand(\"Document_Item\");'>"
                 .$LANG['document'][18]."</li>";
         }
         if ($canadd_solution) { 
            echo "<li class='solution' onclick='".
                 "javascript:viewAddSubitem".$ticket->fields['id']."$rand(\"Solution\");'>"
                 .$LANG['jobresolution'][1]."</li>";
         }
         echo "</ul>"; // talk_choices
         echo "<div class='clear'>&nbsp;</div>";
         echo "</div>"; //end talk_form      
      }

      echo "<div class='ajax_box' id='viewitem" . $ticket->fields['id'] . "$rand'></div>\n";
   }

   static function geTimelineItems(Ticket $ticket, $rand) {
      global $DB, $LANG;

      $timeline = array();

      $user                  = new User();
      $followup_obj          = new TicketFollowup();
      $task_obj              = new TicketTask();
      $document_item_obj     = new Document_Item();
      $ticket_valitation_obj = new TicketValidation();

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
      $document_obj = new Document();
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
                                                          'content'          => Html::clean(html_entity_decode($ticket->fields['solution'])),
                                                          'date'             => $solution_date, 
                                                          'users_id'         => $users_id, 
                                                          'solutiontypes_id' => $ticket->fields['solutiontypes_id'],
                                                          'can_edit'         => $ticket->canUpdate() && $ticket->canSolve()));
      }

      // add ticket validation to timeline
       if (Session::haveRight('validate_ticket',1)) {
        
         $ticket_validations = $ticket_valitation_obj->find('tickets_id = '.$ticket->getID());
         foreach ($ticket_validations as $validations_id => $validation) {
            $canedit = $ticket_valitation_obj->can($validations_id,'w');
            $user->getFromDB($validation['users_id_validate']);
            $timeline[$validation['submission_date']."_validation_".$validations_id] 
               = array('type' => 'TicketValidation', 'item' => array(
                  'id'        => $validations_id,
                  'date'      => $validation['submission_date'],
                  'content'   => $LANG['plugin_talk']['old'][1]." => ".$user->getlink().
                                 "<br>".$validation['comment_submission'],
                  'users_id'  => $validation['users_id'], 
                  'can_edit'  => $canedit
               ));

            if (!empty($validation['validation_date'])) {
               //TODO
               $tab_status = Ticket::getAllStatusArray();
               $tab_status = TicketValidation::getAllStatusArray();
               $name = $validation['status'];
               $str = ucfirst($tab_status[$name]);
               
               $timeline[$validation['validation_date']."_validation_".$validations_id] 
               = array('type' => 'TicketValidation', 'item' => array(
                  'id'        => $validations_id,
                  'date'      => $validation['validation_date'],
                  'content'   => $LANG['plugin_talk']['old'][2]." : ".$str."<br>".
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
      
      return $timeline;
   }

   static function showHistory(Ticket $ticket, $rand) {
      global $LANG, $DB, $CFG_GLPI;

      //get ticket actors
      $ticket_users_keys = self::prepareTicketUser($ticket);

      $user = new User();
      $followup_obj = new TicketFollowup();
      $pics_url = "../plugins/talk/pics";
      
      $timeline = self::geTimelineItems($ticket, $rand);
      if (count($timeline) == 0) {
         return;
      }

      //include lib for parsing url 
      require GLPI_ROOT."/plugins/talk/lib/urllinker.php";

      //display timeline
      echo "<div class='talk_history'>";

      $tmp = array_values($timeline);
      $first_item = array_shift($tmp);

      //don't display title on solution approbation
      if ($first_item['type'] != 'Solution' 
         || $ticket->fields["status"] != 'solved') {
         self::showHistoryHeader();
      }

      $timeline_index = 0;
      foreach ($timeline as $item) {
         $item_i = $item['item'];

         // don't display empty followup (ex : solution approbation)
         if ($item['type'] == 'TicketFollowup' && empty($item_i['content'])) {
            continue;
         }

         $date = "";
         if (isset($item_i['date']))     $date = $item_i['date'];
         if (isset($item_i['date_mod'])) $date = $item_i['date_mod'];
         
         // check if curent item user is assignee or requester
         $user_position = 'left';
         if (isset($ticket_users_keys[$item_i['users_id']]) 
            && $ticket_users_keys[$item_i['users_id']] == Ticket::ASSIGN) {
            $user_position = 'right';
         }

         //display solution in middle
         if ($timeline_index == 0 && $item['type'] == "Solution" 
            && $ticket->fields["status"] == 'solved') {
            $user_position.= ' middle';
         }
         
         echo "<div class='h_item $user_position'>";

         echo "<div class='h_info'>";
         echo "<div class='h_date'>".Html::convDateTime($date)."</div>";
         echo "<div class='h_user'>";
         if (isset($item_i['users_id']) && $item_i['users_id'] != 0) {
            $user->getFromDB($item_i['users_id']);
            echo $user->getLink();
         } else echo $LANG['job'][4];
         echo "</div>";
         echo "</div>";

         echo "<div class='h_content ".$item['type'].
              ((isset($item_i['status'])) ? " ".$item_i['status'] : "").
              "'";
         if ($item['type'] != "Document_Item" && $item_i['can_edit']) {     
            echo " onclick='javascript:viewEditSubitem".$ticket->fields['id']."$rand(event, \"".$item['type']."\", ".$item_i['id'].", this)'";
         }
         echo ">";
         if (isset($item_i['requesttypes_id']) 
               && file_exists("$pics_url/".$item_i['requesttypes_id'].".png")) {
            echo "<img src='$pics_url/".$item_i['requesttypes_id'].".png' title='' class='h_requesttype' />";
         }

         if (isset($item_i['content'])) {
            $content = nl2br(linkUrlsInTrustedHtml($item_i['content']));
            $content = html_entity_decode($content);

            $long_text = "";
            if(substr_count($content, "<br") > 30 || strlen($content) > 2000) {
               $long_text = "long_text";
            }

            echo "<div class='item_content $long_text'>";
            echo "<p>";
            echo $content;
            echo "</p>";
            if (!empty($long_text)) {
               echo "<p class='read_more'>";
               echo "<a class='read_more_button'>.....</a>";
               echo "</p>";
            }
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

         // show "is_private" icon
         if (isset($item_i['is_private']) && $item_i['is_private']) {
            echo "<div class='private'>";
            echo $LANG['common'][77];
            echo "</div>";
         }
      
         echo "</div>";

         if ($item['type'] == 'Document_Item') {
            $filename = $item_i['filename'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            echo "<img src='";
            if (empty($filename)) {
               $filename = $item_i['name'];
            }
            if (file_exists(GLPI_ROOT."/pics/icones/$ext-dist.png")) {
               echo $CFG_GLPI['root_doc']."/pics/icones/$ext-dist.png";
            } else {
               echo "$pics_url/file.png";
            }
            echo "' title='file' />&nbsp;";
            echo "<a href='".$CFG_GLPI['root_doc']."/front/document.send.php?docid=".$item_i['id']
                ."&tickets_id=".$ticket->getID()
                ."' target='_blank'>$filename";
            if (in_array($ext, array('jpg', 'jpeg', 'png', 'bmp'))) {
               echo "<div class='talk_img_preview'>";
               echo "<img src='".$CFG_GLPI['root_doc']."/front/document.send.php?docid=".$item_i['id']
                ."&tickets_id=".$ticket->getID()
                ."'/>";
               echo "</div>";
            }

            echo "</a>";
            if (!empty($item_i['mime'])) echo "&nbsp;(".$item_i['mime'].")";
            echo "<a href='".$CFG_GLPI['root_doc'].
                 "/plugins/talk/front/item.form.php?delete_document&documents_id=".$item_i['id'].
                 "&tickets_id=".$ticket->getID()."' class='delete_document'>";
            echo "<img src='../plugins/talk/pics/delete.png' /></a>";
            
         }
         echo "</div>"; //end h_content

         echo "</div>"; //end  h_item

         if ($timeline_index == 0 && $item['type'] == "Solution" 
            && $ticket->fields["status"] == 'solved') {
            echo "<div class='break'></div>";
            echo "<div class='approbation_form'>";
            $followup_obj->showApprobationForm($ticket);
            echo "</div>";
            echo "<hr class='approbation_separator' />";
            self::showHistoryHeader();
         }
         $timeline_index++;
      } // end foreach timeline
      echo "<div class='break'></div>";
      echo "</div>";

      echo "<script type='text/javascript'>read_more();</script>";
   }

   static function showHistoryHeader() {
      global $LANG;
      echo "<h2>" . $LANG['plugin_talk']["title"][3] . " : </h2>";
      self::filterTimeline();
   }

   static function filterTimeline() {
      global $LANG, $CFG_GLPI;

      $pics_url = $CFG_GLPI['root_doc']."/plugins/talk/pics";
      echo "<div class='filter_timeline'>";
      echo "<label>" . $LANG['plugin_talk']["title"][4] . " : </label>";
      echo "<ul>";
      
      echo "<li><a class='reset' title=\"".$LANG['plugin_talk']["oldglpi"][1].
      "\"><img src='$pics_url/reset.png' /></a></li>";
      echo "<li><a class='Solution' title='".$LANG['stats'][9].
      "'><img src='$pics_url/solution_min.png' /></a></li>";
      echo "<li><a class='TicketValidation' title='".$LANG['rulesengine'][41].
      "'><img src='$pics_url/validation_min.png' /></a></li>";
      echo "<li><a class='Document_Item' title='".$LANG['document'][18].
      "'><img src='$pics_url/document_min.png' /></a></li>";
      echo "<li><a class='TicketTask' title='".$LANG['job'][7].
      "'><img src='$pics_url/task_min.png' /></a></li>";
      echo "<li><a class='TicketFollowup' title='".$LANG['mailing'][141].
      "'><img src='$pics_url/followup_min.png' /></a></li>";
      echo "</ul>";
      echo "</div>";

      echo "<script type='text/javascript'>filter_timeline();</script>";
   }

   /**
    * 
    * @param Ticket $ticket
    * @return array
    */
   static function prepareTicketUser(Ticket $ticket) {
      global $DB;

      $query = "SELECT
            DISTINCT users_id, type
         FROM (
            SELECT usr.id as users_id, tu.type as type
            FROM `glpi_tickets_users` tu
            LEFT JOIN glpi_users usr
               ON tu.users_id = usr.id
            WHERE tu.`tickets_id` = ".$ticket->getId()."
            
            UNION 
            
            SELECT usr.id as users_id, gt.type as type
            FROM glpi_groups_tickets gt
            LEFT JOIN glpi_groups_users gu
               ON gu.groups_id = gt.groups_id
            LEFT JOIN glpi_users usr
               ON gu.users_id = usr.id
            WHERE gt.tickets_id = ".$ticket->getId()."
            
            UNION 
            
            SELECT usr.id as users_id, '2' as type
            FROM glpi_profiles prof
            LEFT JOIN glpi_profiles_users pu
               ON pu.profiles_id = prof.id
            LEFT JOIN glpi_users usr
               ON usr.id = pu.users_id
            WHERE prof.own_ticket = 1
         ) AS allactors
         WHERE type != ".Ticket::OBSERVER."
         GROUP BY users_id
         ORDER BY type DESC";
      $res = $DB->query($query);
      $ticket_users_keys = array();
      while ($current_tu = $DB->fetch_assoc($res)) {
         $ticket_users_keys[$current_tu['users_id']] = $current_tu['type'];
      }

      return $ticket_users_keys;
   }

   static function showSubForm(CommonDBTM $item, $id, $params) {
      global $DB;
      
      if ($item instanceof Document_Item) {
         self::showSubFormDocument_Item($params['tickets_id'], $params);

      } else if ($item instanceof TicketFollowup) {
         self::showSubFormTicketFollowup($item, $id, $params);

      } else if (method_exists($item, "showForm")) {
         $item->showForm($id, $params);

      }
   }

   static function showSubFormTicketFollowup($item, $id, $params) {
      global $DB;
      ob_start();
      
      //get html of followup form
      $item->showForm($id, $params);
      $fup_form_html = ob_get_contents();
      ob_clean();

      //get html of document form
      $params['no_form'] = true;
      self::showSubFormDocument_Item($params['tickets_id'], $params);
      $doc_form_html = ob_get_contents();
      ob_end_clean();

      //replace action param to redirect to talk controller (only for add)
      if (strpos($fup_form_html, "<input type='submit' name='update'") === false) {
         $fup_form_html = str_replace("front/ticketfollowup.form.php", 
                                      "plugins/talk/front/item.form.php?fup=1", 
                                      $fup_form_html);

         //add multipart attribute to permit doc upload
         $fup_form_html = str_replace("<form ", 
                                      "<form enctype='multipart/form-data'", 
                                      $fup_form_html);            
      }

      //don't display document form on update
      if (strpos($fup_form_html, "<input type='submit' name='update'") === false) {
         //echo str_replace("class='submit'></td></tr>", "class='submit'></td></tr><tr><td>".$doc_form_html."</td></tr>", $fup_form_html);
         echo str_replace("<tr><td class='tab_bg_2 center' colspan='4'><input type='submit' name='add'", "<tr><td>".$doc_form_html."</td></tr><tr><td class='tab_bg_2 center' colspan='4'><input type='submit' name='add'", $fup_form_html);
      } else {
         echo $fup_form_html;
      }

   }

   static function showSubFormDocument_Item($ID, $params) {
      global $DB, $CFG_GLPI, $LANG;

      $item = new Ticket();
      $item->getFromDB($ID);

      if (empty($withtemplate)) {
         $withtemplate = 0;
      }
      $linkparam = '';

      if (get_class($item) == 'Ticket') {
         $linkparam = "&amp;tickets_id=".$item->fields['id'];
      }

      $document      = new Document();
      $canedit       = $item->canAddItem('Document') && $document->canView();
      $rand          = mt_rand();
      $is_recursive  = $item->isRecursive();
      $order = "DESC";
      //$sort = "`assocdate`";
      
      $query = "SELECT `glpi_documents_items`.`id` AS assocID,".
                       //`glpi_documents_items`.`date_mod` AS assocdate,
                       "`glpi_entities`.`id` AS entityID,
                       `glpi_entities`.`completename` AS entity,".
                       //`glpi_documentcategories`.`completename` AS headings,
                       "`glpi_documents`.*
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
                    SELECT `glpi_documents_items`.`id` AS assocID,".
                           //`glpi_documents_items`.`date_mod` AS assocdate,
                           "`glpi_entities`.`id` AS entityID,
                           `glpi_entities`.`completename` AS entity,".
                           //`glpi_documentcategories`.`completename` AS headings,
                           "`glpi_documents`.*
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
      //$query .= " ORDER BY $sort $order ";

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
            if ($item->getEntityID() >= 0) {
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

         if (!isset($params['no_form']) || $params['no_form'] == false) {
            echo "<div class='firstbloc'>";
            echo "<form name='documentitem_form$rand' id='documentitem_form$rand' method='post'
                   action='".Toolbox::getItemTypeFormURL('Document')."'  enctype=\"multipart/form-data\">";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th colspan='5'>". $LANG['document'][16] ."</th></tr>";
         }
         echo "<tr class='tab_bg_1'>";

         if (!isset($params['no_form']) || $params['no_form'] == false) {
            echo "<td class='center'>";
            echo $LANG['log'][44];
            echo "</td><td>";
            Dropdown::show('DocumentCategory', array('entity' => $entities));
            echo "</td>";
            echo "<td class='right'>";
         } else {
            echo "<td class='center'>". $LANG['document'][16] ."</td>";
            echo "<td style='padding-left:50px'>";
         }
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

         if (!isset($params['no_form']) || $params['no_form'] == false) {
            echo "<td class='center' width='20%'>";
            echo "<input type='submit' name='add' value=\"". $LANG['document'][6] . "\"
                   class='submit'></td>";
         }
         echo "</tr>";

         if (!isset($params['no_form']) || $params['no_form'] == false) {
            echo "</table>";
            Html::closeForm();
         }

         if (Session::haveRight('document','r') && $nb > count($used) &&
            (!isset($params['no_form']) || $params['no_form'] == false)) {
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

            Document::dropdown(array('entity' => $entities,
                                     'used'   => $used));
            echo "</td>";
            echo "<td class='center' width='20%'>";
            echo "<input type='submit' name='add' value=\"".
                  $LANG['document'][5]."\" class='submit'>";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
            Html::closeForm();
         }

         echo "</div>";
      }
      

   }

   static function showSubFormSolution($ID) {
      $ticket = new Ticket();
      $ticket->getFromDB($ID);
      $ticket->showSolutionForm();
   }
   
}