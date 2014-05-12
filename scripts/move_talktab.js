//need a timeout for execute code after tabpanel initialization
window.setTimeout(function() {
   // move talk tab in first position
   talktab = tabpanel.getComponent('PluginTalkTicket$1');
   tabpanel.remove(talktab, false);
   tabpanel.insert(0, talktab);

   // active talk tab when followup tabs was selected
   if (tabpanel.getActiveTab().id == "TicketFollowup$1") {
      tabpanel.setActiveTab('PluginTalkTicket$1');
   }

}, 250)