jQuery(document).ready(function($) {
  "use strict";

  /**
   * Custom script handler for loading ES data into a pie chart.
   */
  indiciaFns.outputGroupsPie = function(el, sourceSettings, response) {
    var dataList = [];
    $.each(response.aggregations.taxon_group.buckets, function() {
      dataList.push([this.key, this.doc_count]);
    });
    $(el).parent().find('.jqplot-target').each(function() {
      var jqp = $(this).data('jqplot');
      jqp.series[0].data = dataList;
      jqp.replot();
    });
  }

  /**
   * Custom script handler for loading ES data into a totals block.
   */
  indiciaFns.outputTotals = function(el, sourceSettings, response) {
    var occs;
    var occsString;
    var speciesString;
    var photosString;
    if (response.hits && typeof response.hits.total !== 'undefined') {
      occs = response.hits.total;
      // ES version agnostic.
      occs = occs.value ? occs.value : occs;
      occsString = occs === 1 ? indiciaData.lang.esTotalsBlock.occurrencesSingle : indiciaData.lang.esTotalsBlock.occurrencesMulti;
      speciesString = response.aggregations.species_count.value === 1 ? indiciaData.lang.esTotalsBlock.speciesSingle : indiciaData.lang.esTotalsBlock.speciesMulti;
      photosString = response.aggregations.photo_count.doc_count === 1 ? indiciaData.lang.esTotalsBlock.photosSingle : indiciaData.lang.esTotalsBlock.photosMulti;
      $(el).find('.occurrences').append(occsString.replace('{1}', occs));
      $(el).find('.species').append(speciesString.replace('{1}', response.aggregations.species_count.value));
      $(el).find('.photos').append(photosString.replace('{1}', response.aggregations.photo_count.doc_count));
    }

  }

  
});