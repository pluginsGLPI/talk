split_button = function() {
   var splitBtn = Ext.get('x-split-button');

   Ext.select('.x-button-drop').on('click', function(event, target, options) {
      splitBtn.toggleClass('open');
   });

   Ext.select('.x-split-button').on('click', function(event, target, options) {
      event.stopPropagation();
   });

   Ext.select('.x-button-drop-menu li').on('click', function(event, target, options) {
      if (target.children.length) {
         //clean old status class
         current_class = Ext.select('.x-button-drop').elements[0].className;
         current_class = current_class.replace('x-button x-button-drop', ''); // don't remove native classes
         current_class_arr = current_class.split(" ");
         Ext.select('.x-button-drop').removeClass(current_class_arr);

         //find status
         match = target.children[0].src.match(/.*\/(.*)\.png/);
         status = match[1];

         //add status to dropdown button
         Ext.select('.x-button-drop').addClass(status);

         //fold status list
         splitBtn.removeClass('open');
      }
   });

   Ext.select('html').on('click', function(event, target, options) {
      if (splitBtn.hasClass('open')) {
         //fold status list
         splitBtn.removeClass('open');
      }
   });
}