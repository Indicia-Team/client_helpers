
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
    // Remove Drupal tabs
    $('div#tasks').remove();
    // Clean up jQuery UI styling
    $('.ui-widget').removeClass('ui-widget');
    $('.ui-widget-header').removeClass('ui-widget-header');
    $('.ui-widget-content').removeClass('ui-widget-content');
    // Remove active stuff on grids
    $('tr.filter-row').remove();
    $('.col-actions').remove();
    $('.col-picker').remove();
    $('tfoot .pager').remove();
    $('div.report-download-link').closest('tr').remove();
    // Tidy other table related styling for print.
    $('table.report-grid').css('width', '100%');
    $('table.report-grid').css('width', '100%');
    $('tr.odd').removeClass('odd');
    $('tr').css('padding', '0');
    $('td').css('padding', '0 4px');
    $('th').css('border-bottom-color', '');
    $('tr').css('border-bottom', 'solid silver 1px');
    $('table.report-grid audio').remove();
    // Move the page title into the report
    $(indiciaData.printSettings.includeSelector).prepend($(indiciaData.printSettings.titleSelector));
    // Transcribe tab headings into header elements.
    $.each($('ul.ui-tabs-nav li a'), function() {
      $($(this).attr('href')).prepend('<h2>' + $(this).text() + '</h2>');
    });
    // Show all tabs
    $('ul.ui-tabs-nav').remove();
    $('ul.ui-tabs-panel').css('padding', 0);
    $('#controls > div[aria-hidden="true"]').show();
    // Pad the outer content
    $('div.node-content').css('padding', '10px');
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

  function doConversion() {
    if (!indiciaData.htmlPreparedForPdf) {
      prepareHtmlForPdf();
      indiciaData.htmlPreparedForPdf = true;
    }
    shrinkReportsIfNeeded();
    if (indiciaData.printSettings.excludeSelector !== '') {
      $(indiciaData.printSettings.excludeSelector).remove();
    }

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
    $('#show-pdf-options').show();
    $('div.loading').remove();
  }

  window.reportLoaded = function checkIfAllReportsLoaded() {
    // Count down until there are zero reports left to load, when we will be ready to prepare the PDF.
    indiciaData.reportsToLoad--;
    if (indiciaData.reportsToLoad === 0) {
      doConversion();
    }
  };

  /**
   * Initiates the process of converting the page HTML to a PDF document.
   */
  function convertToPdf() {
    $('#show-pdf-options').hide();
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
