filter_timeline = function() {
   Ext.select('.filter_timeline li a').on('click', function(ev, current_el) {
      //hide all elements in timeline
      Ext.select('.h_item').addClass('h_hidden');

      //reset all elements
      if (this.className == 'reset') {
         Ext.select('.filter_timeline li a img').each(function(el2) {
            el2.dom.src = el2.dom.src.replace('_active', '');
         })
         Ext.select('.h_item').removeClass('h_hidden');
         return;
      }

      //activate clicked element
      Ext.get(this.id).toggleClass('h_active');
      if (current_el.src.indexOf('active') > 0) {
         current_el.src = current_el.src.replace('_active', '');
      } else {
         current_el.src = current_el.src.replace(/\.(png)$/, '_active.$1');
      }

      //find active classname
      active_classnames = [];
      Ext.select('.filter_timeline .h_active').each(function(el) {
         active_classnames.push(".h_content."+el.dom.className.replace(' h_active', ''));
      })

      Ext.select(active_classnames.join(', ')).each(function(el){
         el.parent().removeClass('h_hidden');
      })

      //show all items when no active filter 
      if (active_classnames.length == 0) {
         Ext.select('.h_item').removeClass('h_hidden');
      }
   });
}