Ext.onReady(function () {
/*   Ext.select(".long_text .button").on('click', function() {

   });
*/
   Ext.getBody().on('click', function(event, target){
      console.log(this);
      alert('test');
    }, null, {
        delegate: '.long_text .read_more a'
    });
});