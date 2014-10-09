split_button = function() {
   var splitBtn = Ext.get('x-split-button');

   Ext.select('.x-button-drop').on('click', function(event, target, options) {
      splitBtn.toggleClass('open');
   });

   Ext.select('.x-split-button').on('click', function(event, target, options) {
      event.stopPropagation();
   });

   Ext.select('.x-button-drop-menu li, html').on('click', function(event, target, options) {
      if (splitBtn.hasClass('open')) {
         splitBtn.removeClass('open');
      }
   });
}