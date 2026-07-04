(function(){
  function init() {
    var temporaryDates = document.getElementById('temporary-dates');
    var startInput = document.getElementById('start_date');
    var endInput = document.getElementById('end_date');

    function showDates() {
      if (temporaryDates) temporaryDates.hidden = false;
      if (startInput) startInput.required = true;
      if (endInput) endInput.required = true;
    }

    function hideDates() {
      if (temporaryDates) temporaryDates.hidden = true;
      if (startInput) startInput.required = false;
      if (endInput) endInput.required = false;
      if (startInput) startInput.value = '';
      if (endInput) endInput.value = '';
    }

    function updateDateVisibility() {
      try {
        var selected = document.querySelector('input[name="access_duration"]:checked');
        if (selected && selected.value === 'temporary') {
          showDates();
        } else {
          hideDates();
        }
      } catch (err) {
        console && console.error && console.error('updateDateVisibility error', err);
      }
    }

    var radios = document.querySelectorAll('input[name="access_duration"]');
    if (radios && radios.length) {
      Array.prototype.forEach.call(radios, function(r){
        r.addEventListener('change', updateDateVisibility);
        r.addEventListener('click', updateDateVisibility);
      });
    } else {
      document.addEventListener('change', updateDateVisibility);
    }

    updateDateVisibility();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
