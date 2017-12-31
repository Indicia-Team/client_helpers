
jQuery(document).ready(function enablePdf($) {
  indiciaData.reportsToLoad = 0;
  indiciaData.htmlPreparedForPdf = false;

  function shrinkReportsIfNeeded() {
    var maxWidthLandscape = 1100;
    // Calculate max width value, based on A4 aspect ratio for portrait.
    var maxWidth = $('#pdf-format').val() === 'landscape' ? maxWidthLandscape : (maxWidthLandscape * 210) / 297;
    // A rough attempt at shrinking the font for tables that are too wide.
    $.each($('table.report-grid'), function correctTableFontSize() {
      $(this).css('font-size', '');
      if ($(this).width() > maxWidth) {
        $(this).css('font-size', ((maxWidth * 100) / $(this).width()) + '%');
      }
    });
    $('.jqplot-target').css('max-width', maxWidth * 0.8);
    if (typeof indiciaFns.reflowAllCharts !== 'undefined') {
      indiciaFns.reflowAllCharts();
    }
  }

  /**
   * Tidies up various aspects of the HTML output to make it suitable for printing/PDF.
   */
  function prepareHtmlForPdf() {
    // Move the page title into the report
    $(indiciaData.printSettings.includeSelector).prepend($(indiciaData.printSettings.titleSelector));
    // Transcribe tab headings into header elements.
    $.each($('ul.ui-tabs-nav li a'), function addHeader() {
      $($(this).attr('href')).prepend('<h2 class="print-header">' + $(this).text() + '</h2>');
    });
    // Clean up broken images
    $('img').each(function checkImage() {
      if (!this.complete || typeof this.naturalWidth === 'undefined' || this.naturalWidth === 0) {
        $(this).remove();
      }
    });
    // Split tabs onto different pages.
    $('.ui-tabs-panel').after('<div class="html2pdf__page-break"></div>');
    // Not the last page
    $('.html2pdf__page-break:last').remove();
  }

  /**
   * Once the page has all data loaded, trigger conversion to PDF.
   */
  function doConversion() {
    // Apply required HTML changes.
    if (!indiciaData.htmlPreparedForPdf) {
      prepareHtmlForPdf();
      indiciaData.htmlPreparedForPdf = true;
    }
    // Use a CSS class to clean up page style.
    $(indiciaData.printSettings.includeSelector).addClass('printing');
    shrinkReportsIfNeeded();

    // Create the PDF
    html2pdf($(indiciaData.printSettings.includeSelector)[0], {
      filename: indiciaData.printSettings.fileName,
      margin: [0.5, 0.5],
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { dpi: 192, letterRendering: true },
      jsPDF: {
        orientation: $('#pdf-format').val(),
        unit: 'cm',
        format: 'a4'
      }
    });
    //
    $(indiciaData.printSettings.includeSelector).removeClass('printing');
    $('div.loading').remove();
  }

  window.reportLoaded = function checkIfAllReportsLoaded(div) {
    // Count down until there are zero reports left to load, when we will be ready to prepare the PDF.
    indiciaData.reportsToLoad--;
    // Ensure any existing report grid callbacks that we replaced are still called.
    if (typeof div.settings.originalCallback !== 'undefined') {
      window[div.settings.originalCallback](div);
    }
    if (indiciaData.reportsToLoad === 0) {
      doConversion();
    }
  };

  /**
   * Initiates the process of converting the page HTML to a PDF document.
   */
  function convertToPdf() {
    $('body').append('<div class="loading">Loading&#8230;</div>');
    $.fancybox.close();
    if (typeof indiciaData.reports !== 'undefined') {
      // Count the report grids so we know when they are all done
      $.each(indiciaData.reports, function handleReportGroup() {
        indiciaData.reportsToLoad += Object.keys(this).length;
      });
      // Reloaad report grids without pagination. The callback means when the last is loaded, the PDF will be rendered.
      $.each(indiciaData.reports, function handleReportGroup() {
        $.each(this, function handleReportGrid() {
          // Reload the report grid if not showing all data or never loaded.
          if (typeof this[0].settings.recordCount === 'undefined'
              || this[0].settings.recordCount > this[0].settings.itemsPerPage) {
            if (typeof this[0].settings.callback !== 'undefined') {
              this[0].settings.originalCallback = this[0].settings.callback;
            }
            this[0].settings.callback = 'reportLoaded';
            this[0].settings.itemsPerPage = indiciaData.printSettings.maxRecords;
            this.reload(false);
          } else {
            indiciaData.reportsToLoad--;
          }
        });
      });
    }
    if (indiciaData.reportsToLoad === 0) {
      doConversion();
    }
  }

  /**
   * Show the options popup.
   */
  function showOptions() {
    $.fancybox($('#pdf-options'));
  }

  // Button handlers
  $('#show-pdf-options').click(showOptions);
  $('#convert-to-pdf').click(convertToPdf);
  $('#pdf-options-cancel').click(function cancel() {
    $.fancybox.close();
  });
});
