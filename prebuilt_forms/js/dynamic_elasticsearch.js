/*eslint no-underscore-dangle: ["error", { "allow": ["_source", "_latlng"] }]*/

(function enclose($) {
  'use strict';

  indiciaData.esOutputPlugins = [];

  indiciaData.statusIcons = {
    V: 'far fa-check-circle',
    V1: 'fas fa-check-double',
    V2: 'fas fa-check',
    C: 'far fa-square',
    C3: 'fas fa-question',
    R: 'far fa-times-circle',
    R4: 'fas fa-times',
    R5: 'fas fa-times'
  };

  indiciaData.statusTooltips = {
    V: 'Accepted',
    V1: 'Accepted :: correct',
    V2: 'Accepted :: considered correct',
    C: 'Pending review',
    C3: 'Plausible',
    R: 'Not accepted',
    R4: 'Not accepted :: unable to verify',
    R5: 'Not accepted :: incorrect'
  };

  /**
   * Function to flag an output plugin as failed.
   */
  indiciaFns.controlFail = function controlFail(el, msg) {
    $(el).before('<p class="alert alert-danger"><span class="fas fa-exclamation-triangle fa-2x"></span>Error loading control</p>');
    throw new Error(msg);
  };

  /**
   * Utility function to retrieve status icon HTML from a status code.
   *
   * @param object status
   *   Primary character status code
   * @param object substatus
   *   Secondary character status code
   */
  indiciaFns.getEsStatusIconFromStatus = function getEsStatusIconFromStatus(status, substatus) {
    var combined = status + (substatus === null || substatus === '0' ? '' : substatus);
    if (typeof indiciaData.statusIcons[combined] !== 'undefined') {
      return '<span title="' + indiciaData.statusTooltips[combined] + '" class="' + indiciaData.statusIcons[combined] + ' status-' + combined + '"></span>';
    }
    return '';
  };

  /**
   * Utility function to retrieve status icon HTML from a doc.
   *
   * @param object doc
   *   Document source from ElasticSearch.
   */
  indiciaFns.getEsStatusIcon = function getEsStatusIcon(doc) {
    return indiciaFns.getEsStatusIconFromStatus(doc.identification.verification_status, doc.identification.verification_substatus);
  };

  indiciaFns.findVal = function i(object, key) {
    var value;
    Object.keys(object).some(function eachKey(k) {
      if (k === key) {
        value = object[k];
        return true;
      }
      if (object[k] && typeof object[k] === 'object') {
        value = indiciaFns.findVal(object[k], key);
        return value !== undefined;
      }
      return false;
    });
    return value;
  };

  indiciaFns.getValueForField = function getValueForField(doc, field) {
    var i;
    var valuePath = doc;
    var fieldPath = field.split('.');
    var values = [];
    var info = '';
    if (field === '#status_icon#') {
      return indiciaFns.getEsStatusIcon(doc);
    }
    if (field === '#date#') {
      if (doc.event.date_start !== doc.event.date_end) {
        return doc.event.date_start + ' - ' + doc.event.date_end;
      }
      return doc.event.date_start;
    }
    if (field === '#locality#') {
      if (doc.location.verbatim_locality) {
        info += '<div>' + doc.location.verbatim_locality + '</div>';
        if (doc.location.higher_geography) {
          info += '<ul>';
          $.each(doc.location.higher_geography, function eachPlace() {
            info += '<li>' + this.type + ': ' + this.name + '</li>';
          });
          info += '</ul>';
        }
      }
      return info;
    }
    if (field === '#metadata_icons#') {
      if (typeof doc.metadata.licence_code !== 'undefined') {
        values.push('<span class="alert alert-warning">doc.metadata.licence_code</span>');
      }
      if (doc.metadata.sensitive === 'true') {
        values.push('<span class="alert alert-danger">Sensitive</span>');
      }
      if (doc.metadata.confidential === 'true') {
        values.push('<span class="alert alert-danger">Confidential</span>');
      }
      return values.join('');
    }
    for (i = 0; i < fieldPath.length; i++) {
      if (typeof valuePath[fieldPath[i]] === 'undefined') {
        valuePath = '';
        break;
      }
      valuePath = valuePath[fieldPath[i]];
    }
    return valuePath;
  };

  indiciaFns.setValIfEmpty = function setValIfEmpty(object, key, updateValue) {
    var value;
    Object.keys(object).some(function eachKey(k) {
      if (k === key) {
        value = object[k];
        if (value.length === 0) {
          object[k] = updateValue;
        }
        return true;
      }
      if (object[k] && typeof object[k] === 'object') {
        value = indiciaFns.setValIfEmpty(object[k], key, updateValue);
        return value !== undefined;
      }
      return false;
    });
    return value;
  };
}(jQuery));


/**
 * Output plugin for data grids.
 */
(function esMapPlugin($) {
  'use strict';

  /**
   * Declare default settings.
   */
  var defaults = {
    initialBoundsSet: false,
    initialLat: 54.093409,
    initialLng: -2.89479,
    initialZoom: 5
  };

  var selectedRowMarker;

  var addFeature = function addFeature(el, sourceId, location, metric) {
    var config = { type: 'marker', options: {} };
    if (typeof $(el)[0].settings.styles[sourceId] !== 'undefined') {
      $.extend(config, $(el)[0].settings.styles[sourceId]);
    }
    if (config.type === 'circle' && typeof metric !== 'undefined') {
      config.options.radius = metric;
      config.options.fillOpacity = 0.5;
      config.options.stroke = false;
    }
    switch (config.type) {
      case 'circle':
        el.outputLayers[sourceId].addLayer(L.circle(location, config.options));
        break;

      case 'heat':
        el.outputLayers[sourceId].addLatLng([location.lat, location.lon, metric]);
        break;

      default:
        el.outputLayers[sourceId].addLayer(L.marker(location, config.options));
    }
  };

  /**
   * Declare public methods.
   */
  var methods = {
    /**
     * Initialise the esMap  plugin.
     *
     * @param array options
     */
    init: function init(options) {
      var el = this;
      var source = JSON.parse($(el).attr('data-es-source'));
      var base;
      var baseMaps;
      var overlays = {};
      el.outputLayers = {};


      indiciaData.esOutputPlugins.push('map');
      el.settings = $.extend({}, defaults);
      // Apply settings passed in the HTML data-* attribute.
      if (typeof $(el).attr('data-es-output-config') !== 'undefined') {
        $.extend(el.settings, JSON.parse($(el).attr('data-es-output-config')));
      }
      // Apply settings passed to the constructor.
      if (typeof options !== 'undefined') {
        $.extend(el.settings, options);
      }

      el.map = L.map(el.id).setView([el.settings.initialLat, el.settings.initialLng], el.settings.initialZoom);
      base = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      });
      baseMaps = { 'Base map': base };
      base.addTo(el.map);
      $.each(source, function eachSource(id, title) {
        var group;
        if (el.settings.styles[id].type !== 'undefined' && el.settings.styles[id].type === 'heat') {
          group = L.heatLayer([], { radius: 10 });
        } else {
          group = L.featureGroup();
        }
        // Leaflet wants layers keyed by title.
        overlays[title] = group;
        // Plugin wants them keyed by source ID.
        el.outputLayers[id] = group;
        // Add the group to the map
        group.addTo(el.map);
      });
      L.control.layers(baseMaps, overlays).addTo(el.map);
    },
    /*
     * Populate the map with ElasticSearch response data.
     *
     * @param obj sourceSettings
     *   Settings for the data source used to generate the response.
     * @param obj response
     *   ElasticSearch response data.
     * @param obj data
     *   Data sent in request.
     */
    populate: function populate(sourceSettings, response) {
      var el = this;
      var buckets;
      var maxMetric = 10;
      if (typeof el.outputLayers[sourceSettings.id].clearLayers !== 'undefined') {
        el.outputLayers[sourceSettings.id].clearLayers();
      } else {
        el.outputLayers[sourceSettings.id].setLatLngs([]);
      }
      // Are there document hits to map?
      $.each(response.hits.hits, function eachHit() {
        var latlon = this._source.location.point.split(',');
        addFeature(el, sourceSettings.id, latlon);
      });
      // Are there aggregations to map?
      if (typeof response.aggregations !== 'undefined') {
        buckets = indiciaFns.findVal(response.aggregations, 'buckets');
        if (typeof buckets !== 'undefined') {
          $.each(buckets, function eachBucket() {
            var count = indiciaFns.findVal(this, 'count');
            maxMetric = Math.max(Math.sqrt(count), maxMetric);
          });
          $.each(buckets, function eachBucket() {
            var location = indiciaFns.findVal(this, 'location');
            var count = indiciaFns.findVal(this, 'count');
            var metric = Math.round((Math.sqrt(count) / maxMetric) * 20000);
            if (typeof location !== 'undefined') {
              addFeature(el, sourceSettings.id, location, metric);
            }
          });
        }
      }
      if (sourceSettings.initialMapBounds && !$(el)[0].settings.initialBoundsSet) {
        if (typeof el.outputLayers[sourceSettings.id].getLayers !== 'undefined' &&
            el.outputLayers[sourceSettings.id].getLayers().length > 0) {
          el.map.fitBounds(el.outputLayers[sourceSettings.id].getBounds());
          $(el)[0].settings.initialBoundsSet = true;
        }
      }
    },

    bindGrids: function bindGrids() {
      var el = this;
      var settings = $(el)[0].settings;
      if (typeof settings.showSelectedRow !== 'undefined') {
        if ($('#' + settings.showSelectedRow).length === 0) {
          indiciaFns.controlFail(el, 'Invalid grid ID in @showSelectedRow parameter');
        }
        $('#' + settings.showSelectedRow).esDataGrid('on', 'rowSelect', function onRowSelect(gridEl, tr) {
          var doc = JSON.parse($(tr).attr('data-doc-source'));
          var wkt = new Wkt.Wkt();
          var obj;
          wkt.read(doc.location.geom);
          obj = wkt.toObject({
            color: '#AA0000',
            weight: 3,
            opacity: 1.0,
            fillColor: '#AA0000',
            fillOpacity: 0.2
          });
          obj.addTo(el.map);
          if (typeof selectedRowMarker !== 'undefined') {
            selectedRowMarker.removeFrom(el.map);
          }
          selectedRowMarker = obj;
          // Pan and zoom the map. Method differs for points vs polygons.
          if (wkt.type === 'polygon') {
            el.map.fitBounds(obj.getBounds(), { maxZoom: 11 });
          } else {
            el.map.setView(obj._latlng, 11);
          }
        });
      }
    }
  };

  /**
   * Extend jQuery to declare esDataGrid method.
   */
  $.fn.esMap = function buildEsMap(methodOrOptions) {
    var passedArgs = arguments;
    $.each(this, function callOnEachOutput() {
      if (methods[methodOrOptions]) {
        // Call a declared method.
        return methods[methodOrOptions].apply(this, Array.prototype.slice.call(passedArgs, 1));
      } else if (typeof methodOrOptions === 'object' || !methodOrOptions) {
        // Default to "init".
        return methods.init.apply(this, passedArgs);
      }
      // If we get here, the wrong method was called.
      $.error('Method ' + methodOrOptions + ' does not exist on jQuery.esMap');
      return true;
    });
    return this;
  };
}(jQuery));

/**
 * Output plugin for data grids.
 */
(function esDataGridPlugin($) {
  'use strict';

  /**
   * Declare default settings.
   */
  var defaults = {
    columnTitles: true,
    filterRow: true,
    pager: true,
    sortable: true
  };

  var callbacks = {
    rowSelect: [],
    populate: []
  };

  var initHandlers = function initHandlers(el) {
    indiciaFns.on('click', '#' + el.id + ' .es-data-grid tbody tr', {}, function onEsDataGridRowClick() {
      var tr = this;
      $(tr).closest('tbody').find('tr.selected').removeClass('selected');
      $(tr).addClass('selected');
      $.each(callbacks.rowSelect, function eachCallback() {
        this(el, tr);
      });
    });

    $(el).find('.pager .next').click(function clickNext() {
      var sources = JSON.parse($(el).attr('data-es-source'));
      $.each(sources, function eachSource(sourceId) {
        var source = indiciaData.esSourceObjects[sourceId];
        if (typeof source.settings.from === 'undefined') {
          source.settings.from = 0;
        }
        source.settings.from += source.settings.size;
        source.populate();
      });
    });

    $(el).find('.pager .prev').click(function clickPrev() {
      var sources = JSON.parse($(el).attr('data-es-source'));
      $.each(sources, function eachSource(sourceId) {
        var source = indiciaData.esSourceObjects[sourceId];
        if (typeof source.settings.from === 'undefined') {
          source.settings.from = 0;
        }
        source.settings.from -= source.settings.size;
        source.settings.from = Math.max(0, source.settings.from);
        source.populate();
      });
    });

    $(el).find('.sort').click(function clickSort() {
      var sortButton = this;
      var row = $(sortButton).closest('tr');
      var sources = JSON.parse($(el).attr('data-es-source'));
      $.each(sources, function eachSource(sourceId) {
        var source = indiciaData.esSourceObjects[sourceId];
        var idx = $(sortButton).closest('th').attr('data-col');
        var col = $(el)[0].settings.columns[idx];
        var sortDesc = $(sortButton).hasClass('fa-sort-up');
        $(row).find('.sort.fas').removeClass('fa-sort-down');
        $(row).find('.sort.fas').removeClass('fa-sort-up');
        $(row).find('.sort.fas').addClass('fa-sort');
        $(sortButton).removeClass('fa-sort');
        $(sortButton).addClass('fa-sort-' + (sortDesc ? 'down' : 'up'));
        source.settings.sort = {};
        source.settings.sort[indiciaData.esMappings[col.field].sort_field] = { order: sortDesc ? 'desc' : 'asc' };
        source.populate();
      });
    });

    $(el).find('.es-filter-row input').change(function changeFilterInput() {
      var sources = JSON.parse($(el).attr('data-es-source'));
      $.each(sources, function eachSource(sourceId) {
        var source = indiciaData.esSourceObjects[sourceId];
        // Reset to first page.
        source.settings.from = 0;
        source.populate();
      });
    });
  };

  /**
   * Declare public methods.
   */
  var methods = {
    /**
     * Initialise the esDataGrid plugin.
     *
     * @param array options
     */
    init: function init(options) {
      var table;
      var header;
      var headerRow;
      var filterRow;
      var sortButton;
      var el = this;
      indiciaData.esOutputPlugins.push('dataGrid');
      el.settings = $.extend({}, defaults);
      // Apply settings passed in the HTML data-* attribute.
      if (typeof $(el).attr('data-es-output-config') !== 'undefined') {
        $.extend(el.settings, JSON.parse($(el).attr('data-es-output-config')));
      }
      // Apply settings passed to the constructor.
      if (typeof options !== 'undefined') {
        $.extend(el.settings, options);
      }
      // Validate settings.
      if (typeof el.settings.columns === 'undefined') {
        indiciaFns.controlFail(el, 'Missing columns config for table.');
      }

      // Build the elements required for the table.
      table = $('<table class="table es-data-grid" />').appendTo(el);
      // If we need any sort of header, add <thead>.
      if (el.settings.columnTitles !== false || el.settings.filterRow !== false) {
        header = $('<thead/>').appendTo(table);
        // Output header row for column titles.
        if (el.settings.columnTitles !== false) {
          headerRow = $('<tr/>').appendTo(header);
          $.each(el.settings.columns, function eachColumn(idx) {
            sortButton = el.settings.sortable === false ||
              typeof indiciaData.esMappings[this.field] === 'undefined' ||
              !indiciaData.esMappings[this.field].sort_field
              ? '' : '<span class="sort fas fa-sort"></span>';
            $('<th class="col-' + idx + '" data-col="' + idx + '">' + this.caption + sortButton + '</th>').appendTo(headerRow);
          });
        }
        // Output header row for filtering.
        if (el.settings.filterRow !== false) {
          filterRow = $('<tr class="es-filter-row" />').appendTo(header);
          $.each(el.settings.columns, function eachColumn(idx) {
            var td = $('<td class="col-' + idx + '" data-col="' + idx + '"></td>').appendTo(filterRow);
            // No filter input if this column has no mapping.
            if (typeof indiciaData.esMappings[this.field] !== 'undefined') {
              $('<input type="text">').appendTo(td);
            }
          });
        }
      }
      // We always want a table body for the data.
      $('<tbody />').appendTo(table);
      // Output a footer if we want a pager.
      if (el.settings.pager) {
        $('<tfoot><tr class="pager"><td colspan="' + el.settings.columns.length + '"><span class="showing"></span>' +
          '<span class="buttons"><button class="prev">Previous</button><button class="next">Next</button></span>' +
          '</td></tr></tfoot>').appendTo(table);
      }
      initHandlers(el);
    },
    /**
     * Populate the data grid with ElasticSearch response data.
     *
     * @param obj sourceSettings
     *   Settings for the data source used to generate the response.
     * @param obj response
     *   ElasticSearch response data.
     * @param obj data
     *   Data sent in request.
     */
    populate: function populate(sourceSettings, response, data) {
      var el = this;
      var fromRowIndex = typeof data.from === 'undefined' ? 1 : (data.from + 1);
      $(el).find('tbody tr').remove();
      $.each(response.hits.hits, function eachHit() {
        var hit = this;
        var cells = [];
        var row;
        var media;
        $.each(el.settings.columns, function eachColumn(idx) {
          var doc = hit._source;
          var value;
          var rangeValue;
          var match;
          value = indiciaFns.getValueForField(doc, this.field);
          if (this.range_field) {
            rangeValue = indiciaFns.getValueForField(doc, this.range_field);
            if (value !== rangeValue) {
              value = value + ' to ' + rangeValue;
            }
          }
          if (value && this.media) {
            media = '';
            $.each(value, function eachFile(i, file) {
              // Check if an extenral URL.
              match = file.match(/^http(s)?:\/\/(www\.)?([a-z(\.kr)]+)/);
              if (match !== null) {
                // If so, is it iNat? We can work out the image file names if so.
                if (file.match(/^https:\/\/static\.inaturalist\.org/)) {
                  media += '<a ' +
                    'href="' + file.replace('/square.', '/large.') + '" ' +
                    'class="inaturalist fancybox"><img src="' + file + '" /></a>';
                } else {
                  media += '<a ' +
                    'href="' + file + '" class="social-icon ' + match[3].replace('.', '') + '"></a>';
                }
              } else if ($.inArray(file.split('.').pop(), ['mp3', 'wav']) > -1) {
                // Audio files can have a player control.
                media += '<audio controls src="' + indiciaData.warehouseUrl + 'upload/' + file + '" type="audio/mpeg"/>';
              } else {
                // Standard link to Indicia image.
                media += '<a ' +
                  'href="' + indiciaData.warehouseUrl + 'upload/' + file + '" ' +
                  'class="fancybox"><img src="' +
                indiciaData.warehouseUrl + 'upload/thumb-' + file + '" /></a>';
              }
            });
            value = media;
          }
          cells.push('<td class="col-' + idx + '">' + value + '</td>');
        });
        row = $('<tr>' + cells.join('') + '</tr>').appendTo($(el).find('tbody'));
        $(row).attr('data-doc-source', JSON.stringify(hit._source));
      });
      $(el).find('tfoot .showing').html('Showing ' + fromRowIndex +
        ' to ' + (fromRowIndex + (response.hits.hits.length - 1)) + ' of ' + response.hits.total);
      if (fromRowIndex > 1) {
        $(el).find('.pager .prev').removeAttr('disabled');
      } else {
        $(el).find('.pager .prev').attr('disabled', 'disabled');
      }
      if (fromRowIndex + response.hits.hits.length < response.hits.total) {
        $(el).find('.pager .next').removeAttr('disabled');
      } else {
        $(el).find('.pager .next').attr('disabled', 'disabled');
      }
      $.each(callbacks['populate'], function() {
        this(el);
      });
    },
    on: function on(event, handler) {
      if (typeof callbacks[event] === 'undefined') {
        indiciaFns.controlFail(this, 'Invalid event handler requested for ' + event);
      }
      callbacks[event].push(handler);
    },
    hideRowAndMoveNext: function hideRowAndMoveNext() {
      var oldSelected = $(this).find('tr.selected');
      var newSelected;
      if ($(oldSelected).next('tr').length > 0) {
        newSelected = $(oldSelected).next('tr');
      } else if ($(oldSelected).prev('tr').length > 0) {
        newSelected = $(oldSelected).prev('tr');
      }
      $(oldSelected).removeClass('selected');
      $(oldSelected).hide();
      if (typeof newSelected !== 'undefined') {
        newSelected.addClass('.selected');
        $(newSelected).click();
      }
    }
  };

  /**
   * Extend jQuery to declare esDataGrid method.
   */
  $.fn.esDataGrid = function buildEsDataGrid(methodOrOptions) {
    var passedArgs = arguments;
    $.each(this, function callOnEachGrid() {
      if (methods[methodOrOptions]) {
        // Call a declared method.
        return methods[methodOrOptions].apply(this, Array.prototype.slice.call(passedArgs, 1));
      } else if (typeof methodOrOptions === 'object' || !methodOrOptions) {
        // Default to "init".
        return methods.init.apply(this, passedArgs);
      }
      // If we get here, the wrong method was called.
      $.error('Method ' + methodOrOptions + ' does not exist on jQuery.esDataGrid');
      return true;
    });
    return this;
  };
}(jQuery));

/**
* Output plugin for data grids.
*/
(function esDetailsPane($) {
  'use strict';

  /**
   * Declare default settings.
   */
  var defaults = {
  };
  var callbacks = {
  };

  var dataGrid;

  var loadedCommentsOcurrenceId = 0;
  var loadedAttrsOcurrenceId = 0;

  var loadComments = function loadComments(el, occurrenceId) {
    // Check not already loaded.
    if (loadedCommentsOcurrenceId === occurrenceId) {
      return;
    }
    loadedCommentsOcurrenceId = occurrenceId;
    // Load the comments
    $.ajax({
      url: indiciaData.ajaxUrl + '/comments/' + indiciaData.nid,
      data: { occurrence_id: occurrenceId },
      success: function success(response) {
        $(el).find('.comments').html('');
        $.each(response, function eachComment() {
          var statusIcon = indiciaFns.getEsStatusIconFromStatus(this.record_status, this.record_substatus);
          $('<div class="panel panel-info">' +
            '<div class="panel-heading">' + statusIcon + this.person_name + ' ' + this.updated_on + '</div>' +
            '<div class="panel-body">' + this.comment + '</div>' +
            '</div').appendTo($(el).find('.comments'));
        });
      },
      dataType: 'json'
    });
  };

  var loadAttributes = function loadAttributes(el, occurrenceId) {
    // Check not already loaded.
    if (loadedAttrsOcurrenceId === occurrenceId) {
      return;
    }
    loadedAttrsOcurrenceId = occurrenceId;
    $.ajax({
      url: indiciaData.ajaxUrl + '/attrs/' + indiciaData.nid,
      data: { occurrence_id: occurrenceId },
      success: function success(response) {
        $.each(response, function eachHeading(title, attrs) {
          var table;
          var tbody;
          var attrsDiv = $(el).find('.record-details .attrs');
          $(attrsDiv).html('');
          $(attrsDiv).append('<h3>' + title + '</h3>');
          table = $('<table>').appendTo(attrsDiv);
          tbody = $('<tbody>').appendTo($(table));
          $.each(attrs, function eachAttr() {
            $('<tr><th>' + this.caption + '</th><td>' + this.value + '</td></tr>').appendTo(tbody);
          });
        });
      },
      dataType: 'json'
    });
  };

  var loadCurrentTabAjax = function loadCurrentTabAjax(el) {
    var selectedTr = $(dataGrid).find('tr.selected');
    var doc;
    if (selectedTr.length > 0) {
      doc = JSON.parse(selectedTr.attr('data-doc-source'));
      loadComments(el, doc.id);
      loadAttributes(el, doc.id);
    }
  };

  var tabActivate = function tabActivate(event, ui) {
    loadCurrentTabAjax($(ui.newPanel).closest('.details-container'));
  };

  var addRow = function addRow(rows, doc, caption, fields, separator) {
    var values = [];
    var value;
    var item;
    // Always treat fields as array so code can be consistent.
    var fieldArr = Array.isArray(fields) ? fields : [fields];
    $.each(fieldArr, function eachField(i, field) {
      item = indiciaFns.getValueForField(doc, field);
      if (item !== '') {
        values.push(item);
      }
    });
    value = values.join(separator);
    if (typeof value !== 'undefined' && value !== '') {
      rows.push('<tr><th scope="row">' + caption + '</th><td>' + value + '</td></tr>');
    }
  };

  /**
   * Declare public methods.
   */
  var methods = {
    /**
     * Initialise the esDetailsPane plugin.
     *
     * @param array options
     */
    init: function init(options) {
      var el = this;
      var recordDetails = $(el).find('.record-details');
      el.settings = $.extend({}, defaults);
      // Apply settings passed in the HTML data-* attribute.
      if (typeof $(el).attr('data-es-output-config') !== 'undefined') {
        $.extend(el.settings, JSON.parse($(el).attr('data-es-output-config')));
      }
      // Apply settings passed to the constructor.
      if (typeof options !== 'undefined') {
        $.extend(el.settings, options);
      }
      // Validate settings.
      if (typeof el.settings.showSelectedRow === 'undefined') {
        indiciaFns.controlFail(el, 'Missing showSelectedRow config for esDetailsPane.');
      }
      dataGrid = $('#' + el.settings.showSelectedRow);
      if (dataGrid.length === 0) {
        indiciaFns.controlFail(el, 'Missing dataGrid ' + el.settings.showSelectedRow + ' for esDetailsPane @showSelectedRow setting.');
      }
      // Tabify
      $(el).find('.tabs').tabs({
        activate: tabActivate
      });
      $(dataGrid).esDataGrid('on', 'rowSelect', function rowSelect(dataGrid, tr) {
        var doc = JSON.parse($(tr).attr('data-doc-source'));
        var rows = [];
        addRow(rows, doc, 'ID', 'id');
        addRow(rows, doc, 'Metadata', '#metadata_icons#');
        addRow(rows, doc, 'Accepted name', 'taxon.accepted_name');
        addRow(rows, doc, 'Status', '#status_icon#');
        addRow(rows, doc, 'Date', '#date#');
        addRow(rows, doc, 'Output map ref', 'location.output_sref');
        addRow(rows, doc, 'Location', '#locality#');
        addRow(rows, doc, 'Taxonomy', ['taxon.phylum', 'taxon.order', 'taxon.family'], ' :: ');
        addRow(rows, doc, 'Submitted on', 'metadata.created_on');
        addRow(rows, doc, 'Last updated on', 'metadata.updated_on');
        addRow(rows, doc, 'Dataset', ['metadata.website.title', 'metadata.survey.title', 'metadata.group.title'], ' :: ');
        $(recordDetails).html('<table><tbody>' + rows.join('') + '</tbody></table>');
        $(recordDetails).append('<div class="attrs"></div>');
        $(el).find('.empty-message').hide();
        $(el).find('.tabs').show();
        // Load Ajax content depending on the tab.
        loadCurrentTabAjax(el);
      });
      $(dataGrid).esDataGrid('on', 'populate', function rowSelect() {
        $(el).find('.empty-message').show();
        $(el).find('.tabs').hide();
      });
    },
    otherPublicFunction: function otherPublicFunction() {

    },
    on: function on(event, handler) {
      if (typeof callbacks[event] === 'undefined') {
        indiciaFns.controlFail(this, 'Invalid event handler requested for ' + event);
      }
      callbacks[event].push(handler);
    }
  };

    /**
   * Extend jQuery to declare esDataGrid method.
   */
  $.fn.esDetailsPane = function buildEsDetailsPane(methodOrOptions) {
    var passedArgs = arguments;
    $.each(this, function callOnEachGrid() {
      if (methods[methodOrOptions]) {
        // Call a declared method.
        return methods[methodOrOptions].apply(this, Array.prototype.slice.call(passedArgs, 1));
      } else if (typeof methodOrOptions === 'object' || !methodOrOptions) {
        // Default to "init".
        return methods.init.apply(this, passedArgs);
      }
      // If we get here, the wrong method was called.
      $.error('Method ' + methodOrOptions + ' does not exist on jQuery.esDetailsPane');
      return true;
    });
    return this;
  };
}(jQuery));

/**
* Output plugin for verification buttons.
*/
(function esVerificationButtons($) {
  'use strict';

  /**
   * Declare default settings.
   */
  var defaults = {
  };

  /**
   * Registered callbacks for events.
   */
  var callbacks = {
  };

  var dataGrid;

  var saveVerifyComment = function saveVerifyComment(occurrenceId, status, substatus, comment) {
    var data = {
      website_id: indiciaData.website_id,
      'occurrence:id': occurrenceId,
      user_id: indiciaData.userId,
      'occurrence:record_status': status,
      'occurrence_comment:comment': comment,
      'occurrence:record_decision_source': 'H'
    };
    if (substatus) {
      data['occurrence:record_substatus'] = substatus;
    }
    $.post(
      indiciaData.ajaxFormPostSingleVerify,
      data,
      function success() {

      }
    );
    data = {
      id: occurrenceId,
      warehouse_url: indiciaData.warehouseUrl,
      doc: {
        identification: {
          verification_status: status
        }
      }
    };
    if (substatus) {
      data.doc.identification.verification_substatus = substatus;
    }
    $.ajax({
      url: indiciaData.ajaxUrl + '/esproxyupdate/' + indiciaData.nid,
      type: 'post',
      data: data,
      success: function success(response) {
        if (typeof response.error !== 'undefined') {
          console.log(response);
          alert('ElasticSearch update failed');
        } else {
          $(dataGrid).esDataGrid('hideRowAndMoveNext');
        }
      },
      dataType: 'json'
    });
  };

  var setStatus = function setStatus(status) {
    var selectedTr = $(dataGrid).find('tr.selected');
    var doc;
    var fs;
    if (selectedTr.length > 0) {
      doc = JSON.parse(selectedTr.attr('data-doc-source'));
      fs = $('<fieldset class="comment-popup" data-id="' + doc.id + '" data-status="' + status + '">');
      $('<legend><span class="' + indiciaData.statusIcons[status] + '"></span>Set status to ' + indiciaData.statusTooltips[status] + '</legend>').appendTo(fs);
      $('<label for="comment-textarea">Provide any comments here:</label>').appendTo(fs);
      $('<textarea id="comment-textarea">').appendTo(fs);
      $('<button class="btn btn-primary">Save</button>').appendTo(fs);
      $.fancybox(fs);
    }
  };

  /**
   * Declare public methods.
   */
  var methods = {
    /**
     * Initialise the myPlugin plugin.
     *
     * @param array options
     */
    init: function init(options) {
      var el = this;

      el.settings = $.extend({}, defaults);
      // Apply settings passed in the HTML data-* attribute.
      if (typeof $(el).attr('data-es-output-config') !== 'undefined') {
        $.extend(el.settings, JSON.parse($(el).attr('data-es-output-config')));
      }
      // Apply settings passed to the constructor.
      if (typeof options !== 'undefined') {
        $.extend(el.settings, options);
      }
      // Validate settings.
      if (typeof el.settings.showSelectedRow === 'undefined') {
        indiciaFns.controlFail(el, 'Missing showSelectedRow config for table.');
      }
      dataGrid = $('#' + el.settings.showSelectedRow);
      $(dataGrid).esDataGrid('on', 'rowSelect', function rowSelect() {
        $(el).show();
      });
      $(dataGrid).esDataGrid('on', 'populate', function rowSelect() {
        $(el).hide();
      });
      $(el).find('button.verify').click(function buttonClick(e) {
        var status = $(e.currentTarget).attr('data-status');
        setStatus(status);
      });
      indiciaFns.on('click', '.comment-popup button', {}, function onClickSave(e) {
        var popup = $(e.currentTarget).closest('.comment-popup');
        var id = $(popup).attr('data-id');
        var status = $(popup).attr('data-status');
        saveVerifyComment(id, status[0], status[1], $(popup).find('textarea').val());
        $.fancybox.close();
      });
      $(el).find('.l1').hide();
      $(el).find('.toggle').click(function toggleClick(e) {
        if ($(e.currentTarget).hasClass('fa-toggle-on')) {
          $(e.currentTarget).removeClass('fa-toggle-on');
          $(e.currentTarget).addClass('fa-toggle-off');
          $(el).find('.l2').hide();
          $(el).find('.l1').show();
        } else {
          $(e.currentTarget).removeClass('fa-toggle-off');
          $(e.currentTarget).addClass('fa-toggle-on');
          $(el).find('.l1').hide();
          $(el).find('.l2').show();
        }
      });
    },
    on: function on(event, handler) {
      if (typeof callbacks[event] === 'undefined') {
        indiciaFns.controlFail(this, 'Invalid event handler requested for ' + event);
      }
      callbacks[event].push(handler);
    }
  };

    /**
   * Extend jQuery to declare esDataGrid method.
   */
  $.fn.esVerificationButtons = function buildEsVerificationButtons(methodOrOptions) {
    var passedArgs = arguments;
    $.each(this, function callOnEachGrid() {
      if (methods[methodOrOptions]) {
        // Call a declared method.
        return methods[methodOrOptions].apply(this, Array.prototype.slice.call(passedArgs, 1));
      } else if (typeof methodOrOptions === 'object' || !methodOrOptions) {
        // Default to "init".
        return methods.init.apply(this, passedArgs);
      }
      // If we get here, the wrong method was called.
      $.error('Method ' + methodOrOptions + ' does not exist on jQuery.esVerificationButtons');
      return true;
    });
    return this;
  };
}(jQuery));

jQuery(document).ready(function docReady($) {
  function EsDataSource(settings) {
    var ds = this;
    ds.settings = settings;
    // Prepare a structure to store the output plugins linked to this source.
    ds.outputs = {};
    $.each(indiciaData.esOutputPlugins, function eachPlugin() {
      ds.outputs[this] = [];
    });
    $.each($('.es-output'), function eachOutput() {
      var el = this;
      var source = JSON.parse($(el).attr('data-es-source'));
      if (source.hasOwnProperty(ds.settings.id)) {
        $.each(indiciaData.esOutputPlugins, function eachPlugin(i, plugin) {
          if ($(el).hasClass('es-output-' + plugin)) {
            ds.outputs[plugin].push(el);
          }
        });
      }
    });
  }

  EsDataSource.prototype.populate = function datasourcePopulate() {
    var source = this;
    var data = {
      warehouse_url: indiciaData.warehouseUrl,
      filters: {},
      bool_queries: [],
      user_filters: []
    };
    var bounds;
    if (source.settings.size) {
      data.size = source.settings.size;
    }
    if (source.settings.from) {
      data.from = source.settings.from;
    }
    if (source.settings.sort) {
      data.sort = source.settings.sort;
    }
    $.each($('.es-filter-param'), function eachParam() {
      if ($(this).val().trim() !== '') {
        data.bool_queries.push({
          bool_clause: $(this).attr('data-es-bool-clause'),
          field: $(this).attr('data-es-field'),
          query_type: $(this).attr('data-es-query-type'),
          value: $(this).val().trim()
        });
      }
    });
    $.each(source.outputs.dataGrid, function eachGrid() {
      $.each($(this).find('.es-filter-row input'), function eachInput() {
        var el = $(this).closest('.es-output');
        var cell = $(this).closest('td');
        var col = $(el)[0].settings.columns[$(cell).attr('data-col')];
        if ($(this).val().trim() !== '') {
          data.filters[col.field] = $(this).val().trim();
        }
      });
    });
    if (source.settings.aggregation) {
      // Find the map bounds.
      $.each($('.es-output-map'), function() {
        if ($(this)[0].settings.applyBoundsTo === source.settings.id) {
          bounds = $(this)[0].map.getBounds();
        }
      });
      indiciaFns.setValIfEmpty(source.settings.aggregation, 'geo_bounding_box', {
        ignore_unmapped: true,
        'location.point': {
          top_left: {
            lat: bounds.getNorth(),
            lon: bounds.getWest()
          },
          bottom_right: {
            lat: bounds.getSouth(),
            lon: bounds.getEast()
          }
        }
      });
      data.aggs = source.settings.aggregation;
    }
    if ($('.user-filter').length > 0) {
      $.each($('.user-filter'), function eachUserFilter() {
        if ($(this).val() !== '') {
          data.user_filters.push($(this).val());
        }
      });
    }
    $.ajax({
      url: indiciaData.ajaxUrl + '/esproxy/' + indiciaData.nid,
      type: 'post',
      data: data,
      success: function success(response) {
        if (typeof response.error !== 'undefined') {
          alert('ElasticSearch query failed');
        } else {
          $.each(indiciaData.esOutputPlugins, function(i, plugin) {
            var fn = 'es' + plugin.charAt(0).toUpperCase() + plugin.slice(1);
            $.each(source.outputs[plugin], function() {
              $(this)[fn]('populate', source.settings, response, data);
            });
          });
        }
      },
      dataType: 'json'
    });
  };

  $('.es-output-dataGrid').esDataGrid({});
  $('.es-output-map').esMap({});
  $('.details-container').esDetailsPane({});
  $('.verification-buttons').esVerificationButtons({});
  $('.es-output-map').esMap('bindGrids');

  // Build the source objects and run initial population.
  indiciaData.esSourceObjects = {};
  $.each(indiciaData.esSources, function eachSource() {
    var sourceObject = new EsDataSource(this);
    indiciaData.esSourceObjects[this.id] = sourceObject;
    sourceObject.populate();
  });

  $('.es-filter-param, .user-filter').change(function eachFilter() {
    // Force map to updatea viewport for new data.
    $('.es-output-map')[0].settings.initialBoundsSet = false;
    // Reload all sources.
    $.each(indiciaData.esSourceObjects, function eachSource() {
      this.populate();
    });
  });

});
