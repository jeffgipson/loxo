(function($){
  $(document).ready(function(){
    var $form = $('#loxo-jobs-filter-form'),
        selectedCategory = 'any',
        selectedType = 'any',
        selectedCity = 'any';

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
          if ('any' === selectedType) {
            matched = true;
          } else if ('others' === selectedType) {
            matched = ! job.job_type;
          } else {
            matched = job.job_type && job.job_type.id == selectedType;
          }
        }

        if (matched) {
          if ('any' === selectedCity) {
            matched = true;
          } else if ('others' === selectedCity) {
            matched = ! job.city;
          } else {
            matched = job.city && job.city == selectedCity;
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
      selectedType = 'any',
      selectedCity = 'any';

      $('#loxo-job-category').val(selectedCategory);
      $('#loxo-job-type').val(selectedType);
      $('#loxo-job-city').val(selectedCity);

      onChangeFilter();

      return false;
    });

    $(document.body).on('change', '#loxo-job-category', function(){
      selectedCategory = $(this).val();
      onChangeFilter();
    });

    $form.on('change', '#loxo-job-type', function(){
      selectedType = $(this).val();
      onChangeFilter();
    });

    $form.on('change', '#loxo-job-city', function(){
      selectedCity = $(this).val();
      onChangeFilter();
    });
  });
})(jQuery);
