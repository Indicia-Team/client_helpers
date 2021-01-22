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
    // Add other bucket.
    if (response.aggregations.taxon_group.sum_other_doc_count) {
      dataList.push([indiciaData.lang.esGroupsPie.other, response.aggregations.taxon_group.sum_other_doc_count]);
    }
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
    var recordersString;
    if (response.hits && typeof response.hits.total !== 'undefined') {
      occs = response.hits.total;
      // ES version agnostic.
      occs = typeof occs.value === 'undefined' ? occs : occs.value;
      occsString = occs === 1 ? indiciaData.lang.esTotalsBlock.occurrencesSingle : indiciaData.lang.esTotalsBlock.occurrencesMulti;
      speciesString = response.aggregations.species_count.value === 1 ? indiciaData.lang.esTotalsBlock.speciesSingle : indiciaData.lang.esTotalsBlock.speciesMulti;
      photosString = response.aggregations.photo_count.doc_count === 1 ? indiciaData.lang.esTotalsBlock.photosSingle : indiciaData.lang.esTotalsBlock.photosMulti;
      recordersString = response.aggregations.recorder_count.value === 1 ? indiciaData.lang.esTotalsBlock.recordersSingle : indiciaData.lang.esTotalsBlock.recordersMulti;
      $(el).find('.occurrences').append(occsString.replace('{1}', occs));
      $(el).find('.species').append(speciesString.replace('{1}', response.aggregations.species_count.value));
      $(el).find('.photos').append(photosString.replace('{1}', response.aggregations.photo_count.doc_count));
      $(el).find('.recorders').append(recordersString.replace('{1}', response.aggregations.recorder_count.value));
    }
  };

  /**
   * Handler for the request which finds the 1000th most recent record.
   *
   * Uses this to filter and load a trending taxa cloud.
   */
  indiciaFns.rangeLimitAndPopulateSources = function(el, sourceSettings, response) {
    $.each(indiciaData.applyRangeLimitTo, function() {
      var src = indiciaData.esSourceObjects[this];
      src.settings.disabled = false;
      // If we find a 1000th record, only load data added after.
      if (response.hits.hits.length > 0) {
        src.settings.filterBoolClauses.must = [
          {
            query_type: 'query_string',
            value: 'id:[' + response.hits.hits[0]._source.id + ' TO *]',
          }
        ]
      }
      src.populate();
    });
  };

  /**
   * Custom script handler for loading ES data into a trending recorders cloud.
   */
  indiciaFns.outputTrendingRecordersCloud = function(el, sourceSettings, response) {
    var names = [];
    var min = 100000;
    var max = 0;
    $.each(response.aggregations.recorders.buckets, function() {
      var info = {
        name: this.key,
        count: this.doc_count
      };
      min = Math.min(info.count, min);
      max = Math.max(info.count, max);
      names.push(info);
    });
    names.sort(function(a, b) {
      return a.name.localeCompare(b.name);
    });
    $.each(names, function() {
      var size = 90 + Math.round(110 * (this.count - min) / (max - min));
      var weight = 100 + 100 * Math.round(8 * (this.count - min) / (max - min));
      $(el).append('<span class="cloud-term" style="font-size: ' + size + '%; weight: ' + weight + '">' + this.name + '</span>');
    })
  };


  /**
   * Custom script handler for loading ES data into a trending taxa cloud.
   */
  indiciaFns.outputTrendingTaxaCloud = function(el, sourceSettings, response) {
    var names = [];
    var min = 100000;
    var max = 0;
    $.each(response.aggregations.species.buckets, function() {
      var info = {
        name: this.key,
        italic: true,
        count: this.doc_count
      };
      if (this.vernacular.buckets.length) {
        info.name = this.vernacular.buckets[0].key;
        info.italic = false;
      }
      min = Math.min(info.count, min);
      max = Math.max(info.count, max);
      names.push(info);
    });
    names.sort(function(a, b) {
      return a.name.localeCompare(b.name);
    });
    $.each(names, function() {
      var size = 90 + Math.round(110 * (this.count - min) / (max - min));
      var weight = 100 + 100 * Math.round(8 * (this.count - min) / (max - min));
      var name = this.italic ? '<em>' + this.name + '</em>' : this.name;
      $(el).append('<span class="cloud-term" style="font-size: ' + size + '%; weight: ' + weight + '">' + name + '</span>');
    })
  };

  /**
   * Creates thumbnails in response to an Elasticsearch photos query.
   */
  indiciaFns.outputPhotos = function(el, sourceSettings, response) {
    var count = 0;
    if (response.hits.hits) {
      $.each(response.hits.hits, function() {
        var id = this._source.id;
        var taxon = this._source.taxon;
        var event = this._source.event;
        $.each(this._source.occurrence.media, function() {
          var label = '<p><em>' + taxon.accepted_name + '</em></p>';
          if (taxon.vernacular_name) {
            label += '<p>' + taxon.vernacular_name + '</p>';
          }
          this.caption = this.caption ? this.caption + ' | ' : '';
          this.caption += taxon.accepted_name;
          if (taxon.vernacular_name) {
            this.caption += ' | ' + taxon.vernacular_name;
          }
          if (event && event.recorded_by) {
            this.caption += ' | ' + event.recorded_by;
          }
          $(el).append('<div class="thumbnail">' +
            indiciaFns.drawMediaFile(id, this, 'med', taxon) +
            '<div class="caption">' + label + '</div>' +
            '</div>');
          count++;
          // Abort if enough photos output (because can be multiple per record).
          if (count >= sourceSettings.size) {
            return false;
          }
        });
        if (count >= sourceSettings.size) {
          return false;
        }
      });
      $(el).find('.fancybox').fancybox();
    }
  };

});