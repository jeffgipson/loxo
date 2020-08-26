(function($){
  $(document).ready(function(){
    $('.loxo-expiration-datepicker').each(function(){
      var $input = $(this);

      $input.datepicker({
        showOn: 'button',
        buttonText: "Change",
        dateFormat: 'yy-mm-dd',
        minDate: $input.data( 'min' ),
        onSelect: function(date, el) {
          $.post( ajaxurl, {
            action: 'loxo_set_job_expiration', 
            date_expires: date, 
            id: $input.data( 'id' )
          })
          .done(function(){

          });
        }
      });
    })
  });
})(jQuery);
