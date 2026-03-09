/**
 * @file
 * DNA Occurrences extension JavaScript for Indicia prebuilt forms.
 */

(function ($) {
  $(document).ready(function () {

    // Show/hide the optional fields when the toggle button is clicked.
    $('.toggle-optional-dna-fields').on('click', function() {
      $(this).siblings('.optional-dna-fields').toggle();
      $(this).text($(this).siblings('.optional-dna-fields:visible').length ? indiciaData.lang.hideOptionalFields : indiciaData.lang.showOptionalFields);
    });

  });
})(jQuery);