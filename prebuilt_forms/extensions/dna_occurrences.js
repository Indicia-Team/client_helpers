/**
 * @file
 * DNA Occurrences extension JavaScript for Indicia prebuilt forms.
 */

(function ($) {
  $(document).ready(function () {

    // Show/hide the advanced fields when the toggle button is clicked.
    $('.toggle-advanced-dna-fields').on('click', function() {
      $(this).siblings('.advanced-dna-fields').toggle();
      $(this).text($(this).siblings('.advanced-dna-fields:visible').length ? indiciaData.lang.hideAdvancedFields : indiciaData.lang.showAdvancedFields);
    });

  });
})(jQuery);