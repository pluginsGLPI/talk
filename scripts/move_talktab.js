// Ext.onReady(function() {

//    //need a timeout for execute code after tabpanel initialization
//    window.setTimeout(function() {

//       //function for move tab
//       this.moveTab = function() {
//          talktab = tabpanel.getComponent('PluginTalkTicket$1');
//          tabpanel.remove(talktab, false);
//          tabpanel.insert(0, talktab);

//          // active talk tab when followup/task/solution tabs was selected
//          var activeTab = tabpanel.getActiveTab().id;
//          if (activeTab == "TicketFollowup$1" || activeTab == "TicketTask$1" || activeTab == "Ticket$2") {
//             tabpanel.setActiveTab('PluginTalkTicket$1');
//          }
//       };
      
//       // check if tabpanel is already loaded, else wait a little more
//       if (typeof tabpanel !== "undefined") {
//          this.moveTab();
//       } else {
//          window.setTimeout(function() {
//             this.moveTab();
//          }, 250)
//       }
//    }, 250)

// });