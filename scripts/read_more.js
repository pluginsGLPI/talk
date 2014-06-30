read_more = function() {
   Ext.select(".long_text .read_more a").on('click', function(event, target, options) {
      Ext.get(this.id).parent('.long_text').removeClass('long_text');
      Ext.get(this.id).parent('.read_more').remove();
      return false;
   });
}
