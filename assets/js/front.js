(function($){
  $(document).ready(function(){
    var $form = $('#loxo-jobs-filter-form'),
        selectedCategory = 'any',
        selectedState = 'any';

    $form.on('submit', function(){
      return false;
    });

    var onChangeFilter = function(){
      var matches = loxo.jobs.filter(function(job){
        matched = false;
        if ('any' === selectedCategory) {
          matched = true;
        } else if ('others' === selectedCategory) {
          matched = job.categories.length < 1;
        } else {
          matched = job.categories.length > 0 && job.categories.find(c => c.id == selectedCategory) !== undefined;
        }

        if (matched) {
          if ('any' === selectedState) {
            matched = true;
          } else if ('others' === selectedState) {
            matched = ! job.state_code;
          } else {
            matched = job.state_code && job.state_code == selectedState;
          }
        }

        return matched;
      });

      visibleJobs = [];
      matches.forEach(function(job){
        visibleJobs.push(job.id);
      });

      hiddenJobs = [];
      loxo.jobs.forEach(function(job){
        if (! visibleJobs.find(id => id === job.id)) {
          $('#job-' + job.id).slideUp();
        } else {
          $('#job-' + job.id).slideDown();
        }
      });

      if (visibleJobs.length < 1){
        setTimeout(function(){
          $('.loxo-jobs .no-jobs').html(loxo.notMatch).show();
        }, 500);
      } else {
        $('.loxo-jobs .no-jobs').empty().hide();
      }
    };

    $form.on('click', '.reset-button', function(){
      selectedCategory = 'any',
      selectedState = 'any';

      $('#loxo-job-category').val(selectedCategory);
      $('#loxo-job-state').val(selectedState);

      onChangeFilter();

      return false;
    });

    $(document.body).on('change', '#loxo-job-category', function(){
      selectedCategory = $(this).val();
      onChangeFilter();
    });

    $form.on('change', '#loxo-job-state', function(){
      selectedState = $(this).val();
      onChangeFilter();
    });

    $('#loxo-job-category').trigger('change');
  });
})(jQuery);
