/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/indicia-team/client_helpers
 */

 /* eslint no-underscore-dangle: ["error", { "allow": ["_source", "_latlng"] }]*/

(function enclose($) {
  'use strict';

  /**
   * Keep track of a list of all the plugin instances that output something.
   */
  indiciaData.esOutputPlugins = [];

  /**
   * Font Awesome icon and other classes for record statuses and flags.
   */
  indiciaData.statusClasses = {
    V: 'far fa-check-circle status-V',
    V1: 'fas fa-check-double status-V1',
    V2: 'fas fa-check status-V2',
    C: 'fas fa-clock status-C',
    C3: 'fas fa-question status-C3',
    R: 'far fa-times-circle status-R',
    R4: 'fas fa-times status-R4',
    R5: 'fas fa-times status-R5',
    // Additional flags
    Q: 'far fa-comment',
    A: 'far fa-comments',
    Sensitive: 'fas fa-exclamation-circle',
    Confidential: 'fas fa-exclamation-triangle'
  };

  /**
   * Messages for record statuses and other flags.
   */
  indiciaData.statusMsgs = {
    V: 'Accepted',
    V1: 'Accepted as correct',
    V2: 'Accepted as considered correct',
    C: 'Pending review',
    C3: 'Plausible',
    R: 'Not accepted',
    R4: 'Not accepted as unable to verify',
    R5: 'Not accepted as incorrect',
    // Additional flags
    Q: 'Queried',
    A: 'Answered',
    Sensitive: 'Sensitive',
    Confidential: 'Confidential'
  };

  /**
   * Font Awesome icon classes for verification automatic check rules.
   */
  indiciaData.ruleClasses = {
    WithoutPolygon: 'fas fa-globe',
    PeriodWithinYear: 'far fa-calendar-times',
    IdentificationDifficulty: 'fas fa-microscope',
    default: 'fas fa-ruler',
    pass: 'fas fa-thumbs-up',
    fail: 'fas fa-thumbs-down',
    pending: 'fas fa-cog',
    checksDisabled: 'fas fa-eye-slash'
  };

  /**
   * Function to flag an output plugin as failed.
   *
   * Places an error message before the plugin instance then throws a message.
   *
   * @param object el
   *   Plugin element.
   * @param string msg
   *   Failure message.
   */
  indiciaFns.controlFail = function controlFail(el, msg) {
    $(el).before('<p class="alert alert-danger">' +
      '<span class="fas fa-exclamation-triangle fa-2x"></span>Error loading control' +
      '</p>');
    throw new Error(msg);
  };

  /**
   * Utility function to retrieve status icon HTML from a status code.
   *
   * @param object flags
   *   Array of flags, including any of:
   *   * status
   *   * substatus
   *   * query
   *   * sensitive
   *   * confidential
   * @param string iconClass
   *   Additional class to add to the icons, e.g. fa-2x.
   *
   * @return string
   *   HTML for the icons.
   */
  indiciaFns.getEsStatusIcons = function getEsStatusIcons(flags, iconClass) {
    var html = '';
    var fullStatus;

    var addIcon = function addIcon(flag) {
      var classes = [];
      if (typeof indiciaData.statusClasses[flag] !== 'undefined') {
        classes = [indiciaData.statusClasses[flag]];
        if (iconClass) {
          classes.push(iconClass);
        }
        html += '<span title="' + indiciaData.statusMsgs[flag] + '" class="' + classes.join(' ') + '"></span>';
      }
    };
    // Add the record status icon.
    if (flags.status) {
      fullStatus = flags.status + (!flags.substatus || flags.substatus === '0' ? '' : flags.substatus);
      addIcon(fullStatus);
    }
    // Add other metadata icons as required.
    if (flags.query) {
      addIcon(flags.query);
    }
    if (flags.sensitive && flags.sensitive !== 'false') {
      addIcon('Sensitive');
    }
    if (flags.confidential && flags.confidential !== 'false') {
      addIcon('Confidential');
    }
    return html;
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

  /**
   * A list of functions which provide special handling for special fields that
   * can be extracted from an ElasticSearch doc.
   */
  indiciaFns.fieldConvertors = {
    // Record status and other flag icons.
    status_icons: function statusIcons(doc) {
      return indiciaFns.getEsStatusIcons({
        status: doc.identification.verification_status,
        substatus: doc.identification.verification_substatus,
        query: doc.identification.query ? doc.identification.query : '',
        sensitive: doc.metadata.sensitive,
        confidential: doc.metadata.confidential
      });
    },
    // Data cleaner automatic rule check result icons.
    data_cleaner_icons: function dataCleanerIcons(doc) {
      var autoChecks = doc.identification.auto_checks;
      var icons = [];
      if (autoChecks.enabled === 'false') {
        icons.push('<span title="Automatic rule checks will not be applied to records in this dataset." class="' + indiciaData.ruleClasses.checksDisabled + '"></span>');
      } else if (autoChecks.result === 'true') {
        icons.push('<span title="All automatic rule checks passed." class="' + indiciaData.ruleClasses.pass + '"></span>');
      } else if (autoChecks.result === 'false') {
        if (autoChecks.output.length > 0) {
          icons = ['<span title="The following automatic rule checks were triggered for this record." class="' + indiciaData.ruleClasses.fail + '"></span>'];
          // Add an icon for each rule violation.
          $.each(autoChecks.output, function eachViolation() {
            // Set a default for any other rules.
            var icon = indiciaData.ruleClasses.hasOwnProperty(this.rule_type)
              ? indiciaData.ruleClasses[this.rule_type] : indiciaData.ruleClasses.default;
            icons.push('<span title="' + this.message + '" class="' + icon + '"></span>');
          });
        }
      } else {
        // Not yet checked.
        icons.push('<span title="Record not yet checked against rules." class="' + indiciaData.ruleClasses.pending + '"></span>');
      }
      return icons.join('');
    },
    // Format dates, with handling of range dates.
    date: function date(doc) {
      if (doc.event.date_start !== doc.event.date_end) {
        return doc.event.date_start + ' - ' + doc.event.date_end;
      }
      return doc.event.date_start;
    },
    // A list of higher geographic areas in the doc.
    locality: function locality(doc) {
      var info;
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
  };

  /**
   * Retrieves a field value from the document.
   *
   * @param object doc
   *   Document read from ElasticSearch.
   * @param string field
   *   Name of the field. Either a path to the field in the document (such as
   *   taxon.accepted_name) or a special field name surrounded by # characters,
   *   e.g. #locality.
   */
  indiciaFns.getValueForField = function getValueForField(doc, field) {
    var i;
    var valuePath = doc;
    var fieldPath = field.split('.');
    // Special field handlers are in the list of convertors.
    if (typeof indiciaFns.fieldConvertors[field.replace(/^#(.+)#$/, '$1')] !== 'undefined') {
      return indiciaFns.fieldConvertors[field.replace(/^#(.+)#$/, '$1')](doc);
    }
    // If not a special field, work down the document hierarchy according to
    // the field's path components.
    for (i = 0; i < fieldPath.length; i++) {
      if (typeof valuePath[fieldPath[i]] === 'undefined') {
        valuePath = '';
        break;
      }
      valuePath = valuePath[fieldPath[i]];
    }
    // Reformat date fields to user-friendly format.
    // @todo Localisation for non-UK dates.
    if (field.match(/_on$/)) {
      valuePath = valuePath.replace(/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}).*/, '$3/$2/$1 $4:$5');
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

  /**
   * Variable to hold the marker used to highlight the currently selected row
   * in a linked dataGrid.
   */
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
      // Circle markers on layer.
      case 'circle':
        el.outputLayers[sourceId].addLayer(L.circle(location, config.options));
        break;
      // Leaflet.heat powered heat maps.
      case 'heat':
        el.outputLayers[sourceId].addLatLng([location.lat, location.lon, metric]);
        break;
      // Default layer type is markers.
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

    $(el).find('.multiselect-switch').click(function clickMultiselectSwitch() {
      var table = $(this).closest('table');
      if ($(table).hasClass('multiselect-mode')) {
        $(table).removeClass('multiselect-mode');
        $(table).find('.multiselect-cell').remove();
        $('.verification-buttons-wrap').append($('.verification-buttons'));
        $('.verification-buttons .single-only').show();
      } else {
        $(table).addClass('multiselect-mode');
        $(table).find('thead tr').prepend(
          '<th class="multiselect-cell" />'
        );
        $(table).find('thead tr:first-child th:first-child').append(
          '<input type="checkbox" class="multiselect-all" />'
        );
        $(table).find('tbody tr').prepend(
          '<td class="multiselect-cell"><input type="checkbox" class="multiselect" /></td>'
        );
        $(table).closest('div').prepend(
          $('.verification-buttons')
        );
        $('.verification-buttons .single-only').hide();
      }
    });

    indiciaFns.on('click', '.multiselect-all', {}, function(e) {
      var table = $(e.currentTarget).closest('table');
      if ($(e.currentTarget).is(':checked')) {
        table.find('.multiselect').prop('checked', true);
      } else {
        $(table).find('.multiselect').prop('checked', false);
      }
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
            var heading = this.caption;

            if (el.settings.sortable !== false &&
                typeof indiciaData.esMappings[this.field] !== 'undefined' &&
                indiciaData.esMappings[this.field].sort_field) {
              heading += '<span class="sort fas fa-sort"></span>';
            }
            if (this.multiselect) {
              heading += '<span title="Enable multiple selection mode" class="fas fa-list multiselect-switch"></span>';
            }
            $('<th class="col-' + idx + '" data-col="' + idx + '">' + heading + '</th>').appendTo(headerRow);
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
      $(el).find('.multiselect-all').prop('checked', false);
      $.each(response.hits.hits, function eachHit() {
        var hit = this;
        var cells = [];
        var row;
        var media;
        if ($(el).find('table.multiselect-mode').length) {
          cells.push('<td class="multiselect-cell"><input type="checkbox" class="multiselect" /></td>');
        }
        $.each(el.settings.columns, function eachColumn(idx) {
          var doc = hit._source;
          var value;
          var rangeValue;
          var match;
          var sizeClass;
          var fieldClass = 'field-' + this.field.replace('.', '--').replace('_', '-');
          value = indiciaFns.getValueForField(doc, this.field);
          if (this.range_field) {
            rangeValue = indiciaFns.getValueForField(doc, this.range_field);
            if (value !== rangeValue) {
              value = value + ' to ' + rangeValue;
            }
          }
          if (value && this.media) {
            media = '';
            // Tweak image sizes if more than 1.
            sizeClass = value.length === 1 ? 'single' : 'multi';
            $.each(value, function eachFile(i, file) {
              // Check if an extenral URL.
              match = file.match(/^http(s)?:\/\/(www\.)?([a-z(\.kr)]+)/);
              if (match !== null) {
                // If so, is it iNat? We can work out the image file names if so.
                if (file.match(/^https:\/\/static\.inaturalist\.org/)) {
                  media += '<a ' +
                    'href="' + file.replace('/square.', '/large.') + '" ' +
                    'class="inaturalist fancybox" rel="group-' + doc.id + '">' +
                    '<img class="' + sizeClass + '" src="' + file + '" /></a>';
                } else {
                  media += '<a ' +
                    'href="' + file + '" class="social-icon ' + match[3].replace('.', '') + '"></a>';
                }
              } else if ($.inArray(file.split('.').pop(), ['mp3', 'wav']) > -1) {
                // Audio files can have a player control.
                media += '<audio controls ' +
                  'src="' + indiciaData.warehouseUrl + 'upload/' + file + '" type="audio/mpeg"/>';
              } else {
                // Standard link to Indicia image.
                media += '<a ' +
                  'href="' + indiciaData.warehouseUrl + 'upload/' + file + '" ' +
                  'class="fancybox" rel="group-' + doc.id + '">' +
                  '<img class="' + sizeClass + '" src="' + indiciaData.warehouseUrl + 'upload/thumb-' + file + '" />' +
                  '</a>';
              }
            });
            value = media;
          }
          cells.push('<td class="col-' + idx + ' ' + fieldClass + '">' + value + '</td>');
        });
        row = $('<tr class="data-row">' + cells.join('') + '</tr>').appendTo($(el).find('tbody'));
        $(row).attr('data-doc-source', JSON.stringify(hit._source));
      });
      // Set up the count info in the footer.
      if (response.hits.hits.length > 0) {
        $(el).find('tfoot .showing').html('Showing ' + fromRowIndex +
          ' to ' + (fromRowIndex + (response.hits.hits.length - 1)) + ' of ' + response.hits.total);
      } else {
        $(el).find('tfoot .showing').html('No hits');
      }
      // Enable or disable the paging buttons.
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
      // Fire any population callbacks.
      $.each(callbacks.populate, function eachCallback() {
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
      var sources;
      var showingLabel = $(this).find('.showing');
      if ($(this).find('table.multiselect-mode').length > 0) {
        $.each($(this).find('input.multiselect:checked'), function eachRow() {
          $(this).closest('tr').remove();
        });
      } else {
        if ($(oldSelected).next('tr').length > 0) {
          newSelected = $(oldSelected).next('tr');
        } else if ($(oldSelected).prev('tr').length > 0) {
          newSelected = $(oldSelected).prev('tr');
        }
        $(oldSelected).remove();
        if (typeof newSelected !== 'undefined') {
          newSelected.addClass('.selected');
          $(newSelected).click();
        }
      }
      // Repopulate the grid if now empty.
      if ($(this).find('table tbody tr').length === 0) {
        sources = JSON.parse($(this).attr('data-es-source'));
        $.each(sources, function eachSource(sourceId) {
          var source = indiciaData.esSourceObjects[sourceId];
          source.populate();
        });
      } else {
        // Update the paging info if some rows left.
        showingLabel.html(showingLabel.html().replace(/\d+ of /, $(this).find('tbody tr.data-row').length + ' of '));
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

  // Info for tracking loaded tabs.
  var loadedCommentsOcurrenceId = 0;
  var loadedAttrsOcurrenceId = 0;
  var loadedExperienceOcurrenceId = 0;

  var getExperienceAggregation = function getExperienceAggregation(data, type) {
    var html = '';
    var minYear = 9999;
    var maxYear = 0;
    var yr;
    var matrix = { C: {}, V: {}, R: {} };
    var buckets;
    var indicatorSize;
    var total;
    $.each(data[type + '_status'][type + '_status_filtered'].buckets, function eachStatus() {
      var status = this.key;
      $.each(this[type + '_status_filtered_age'].buckets, function eachYear() {
        minYear = Math.min(minYear, this.key);
        maxYear = Math.max(maxYear, this.key);
        if (typeof matrix[status] !== 'undefined') {
          matrix[status][this.key] = this.doc_count;
        }
      });
    });
    html += '<strong>Total records:</strong> ' + data[type + '_status'].doc_count;
    if (minYear < 9999) {
      html += '<table><thead><tr><th>Year</th>'
        + '<th>Verified</th>' +
        '<th>Other</th>' +
        '<th>Rejected</th>' +
        '</tr></thead>';
      for (yr = maxYear; yr >= Math.max(minYear, maxYear - 2); yr--) {
        html += '<tr>';
        html += '<th scope="row">' + yr + '</th>';
        buckets = {
          V: typeof matrix.V[yr] !== 'undefined' ? matrix.V[yr] : 0,
          C: typeof matrix.C[yr] !== 'undefined' ? matrix.C[yr] : 0,
          R: typeof matrix.R[yr] !== 'undefined' ? matrix.R[yr] : 0
        };
        total = buckets.C + buckets.V + buckets.R;
        indicatorSize = Math.min(80, total * 2);
        html += '<td>' + buckets.V + '<span class="exp-V" style="width: ' + (indicatorSize * (buckets.V / total)) + 'px;"></span></td>';
        html += '<td>' + buckets.C + '<span class="exp-C" style="width: ' + (indicatorSize * (buckets.C / total)) + 'px;"></span></td>';
        html += '<td>' + buckets.R + '<span class="exp-V" style="width: ' + (indicatorSize * (buckets.R / total)) + 'px;"></span></td>';
        html += '</tr>';
      }
      if (maxYear - minYear > 3) {
        buckets = {
          V: 0,
          C: 0,
          R: 0
        };
        for (yr = minYear; yr <= maxYear - 3; yr++) {
          buckets.V += typeof matrix.V[yr] !== 'undefined' ? matrix.V[yr] : 0;
          buckets.C += typeof matrix.C[yr] !== 'undefined' ? matrix.C[yr] : 0;
          buckets.R += typeof matrix.R[yr] !== 'undefined' ? matrix.R[yr] : 0;
        }
        html += '<tr>';
        html += '<th scope="row">Other years</th>';
        total = buckets.C + buckets.V + buckets.R;
        indicatorSize = Math.min(80, total * 2);
        html += '<td>' + buckets.V + '<span class="exp-V" style="width: ' + (indicatorSize * (buckets.V / total)) + 'px;"></span></td>';
        html += '<td>' + buckets.C + '<span class="exp-C" style="width: ' + (indicatorSize * (buckets.C / total)) + 'px;"></span></td>';
        html += '<td>' + buckets.R + '<span class="exp-V" style="width: ' + (indicatorSize * (buckets.R / total)) + 'px;"></span></td>';
        html += '</tr>';
      }
      html += '<tbody>';
      html += '</tbody></table>';
    }
    return html;
  };

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
        if (response.length === 0) {
          $('<div class="alert alert-info">There are no comments for this record.</div>')
            .appendTo($(el).find('.comments'));
        } else {
          $.each(response, function eachComment() {
            var statusIcon = indiciaFns.getEsStatusIcons({
              status: this.record_status,
              substatus: this.record_substatus,
              query: this.query === 't' ? 'Q' : null
            }, 'fa-2x');
            $('<div class="panel panel-info">' +
              '<div class="panel-heading">' + statusIcon + this.person_name + ' ' + this.updated_on + '</div>' +
              '<div class="panel-body">' + this.comment + '</div>' +
              '</div').appendTo($(el).find('.comments'));
          });
        }
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

  var loadExperience = function loadExperience(el, doc) {
    var data;
    // Check not already loaded.
    if (loadedExperienceOcurrenceId === doc.id) {
      return;
    }
    loadedExperienceOcurrenceId = doc.id;
    data = {
      warehouse_url: indiciaData.warehouseUrl,
      size: 0,
      query: {
        term: { 'metadata.created_by_id': doc.metadata.created_by_id }
      },
      aggs: {
        group_status: {
          filter: {
            term: { 'taxon.group.keyword': doc.taxon.group }
          },
          aggs: {
            group_status_filtered: {
              terms: {
                field: 'identification.verification_status',
                size: 10,
                order: {
                  _count: 'desc'
                }
              },
              aggs: {
                group_status_filtered_age: {
                  terms: {
                    field: 'event.year',
                    size: 5,
                    order: {
                      _key: 'desc'
                    }
                  }
                }
              }
            }
          }
        },
        species_status: {
          filter: {
            term: { 'taxon.accepted_taxon_id': doc.taxon.accepted_taxon_id }
          },
          aggs: {
            species_status_filtered: {
              terms: {
                field: 'identification.verification_status',
                size: 10,
                order: {
                  _count: 'desc'
                }
              },
              aggs: {
                species_status_filtered_age: {
                  terms: {
                    field: 'event.year',
                    size: 5,
                    order: {
                      _key: 'desc'
                    }
                  }
                }
              }
            }
          }
        }
      }
    };
    $(el).find('.loading-spinner').show();
    $.ajax({
      url: indiciaData.ajaxUrl + '/esproxy_rawsearch/' + indiciaData.nid,
      type: 'post',
      data: data,
      success: function success(response) {
        var html = '';
        if (typeof response.error !== 'undefined') {
          alert('ElasticSearch query failed');
        } else {
          html += '<h3>Experience for <span class="field-taxon--accepted-name">' + doc.taxon.accepted_name + '</span></h3>';
          html += getExperienceAggregation(response.aggregations, 'species');
          html += '<h3>Experience for ' + doc.taxon.group + '</h3>';
          html += getExperienceAggregation(response.aggregations, 'group');
          $(el).find('.recorder-experience').html(html);
          $(el).find('.loading-spinner').hide();
        }
      },
      dataType: 'json'
    });
  };

  var loadCurrentTabAjax = function loadCurrentTabAjax(el) {
    var selectedTr = $(dataGrid).find('tr.selected');
    var doc;
    var activeTab = indiciaFns.activeTab($(el).find('.tabs'));
    if (selectedTr.length > 0) {
      doc = JSON.parse(selectedTr.attr('data-doc-source'));
      switch (activeTab) {
        case 0:
          loadAttributes(el, doc.id);
          break;

        case 1:
          loadComments(el, doc.id);
          break;

        case 2:
          loadExperience(el, doc);
          break;

        default:
          throw new Error('Invalid tab index');
      }
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
      var fieldClass = 'field-' + field.replace('.', '--').replace('_', '-').replace('#', '-');
      item = indiciaFns.getValueForField(doc, field);
      if (item !== '') {
        values.push('<span class="' + fieldClass + '">' + item + '</span>');
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
      // Clean tabs
      $('.ui-tabs-nav').removeClass('ui-widget-header');
      $('.ui-tabs-nav').removeClass('ui-corner-all');
      $(dataGrid).esDataGrid('on', 'rowSelect', function rowSelect(dataGrid, tr) {
        var doc = JSON.parse($(tr).attr('data-doc-source'));
        var rows = [];
        var acceptedNameAnnotation = doc.taxon.taxon_name === doc.taxon.accepted_name
          ? ' (as recorded)' : '';
        var vernaculardNameAnnotation = doc.taxon.taxon_name === doc.taxon.vernacular_name
          ? ' (as recorded)' : '';
        addRow(rows, doc, 'ID', 'id');
        if (doc.taxon.taxon_name !== doc.taxon.accepted_name && doc.taxon.taxon_name !== doc.taxon.vernacular_name) {
          addRow(rows, doc, 'Given name', ['taxon.taxon_name', 'taxon.taxon_name_authorship'], ' ');
        }
        addRow(rows, doc, 'Accepted name' + acceptedNameAnnotation, ['taxon.accepted_name', 'taxon.accepted_name_authorship'], ' ');
        addRow(rows, doc, 'Common name' + vernaculardNameAnnotation, 'taxon.vernacular_name');
        addRow(rows, doc, 'Taxonomy', ['taxon.phylum', 'taxon.order', 'taxon.family'], ' :: ');
        addRow(rows, doc, 'Licence', 'metadata.licence_code');
        addRow(rows, doc, 'Status', '#status_icons#');
        addRow(rows, doc, 'Checks', '#data_cleaner_icons#');
        addRow(rows, doc, 'Date', '#date#');
        addRow(rows, doc, 'Output map ref', 'location.output_sref');
        addRow(rows, doc, 'Location', '#locality#');
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

  var saveVerifyComment = function saveVerifyComment(occurrenceIds, status, comment) {
    var commentToSave;
    var data = {
      website_id: indiciaData.website_id,
      user_id: indiciaData.userId
    };
    var doc = {
      identification: {}
    };
    var indiciaPostUrl;
    if (status.status) {
      indiciaPostUrl = indiciaData.ajaxFormPostSingleVerify;
      commentToSave = comment.trim() === ''
        ? indiciaData.statusMsgs[status.status]
        : comment.trim();
      $.extend(data, {
        'occurrence:ids': occurrenceIds.join(','),
        'occurrence:record_decision_source': 'H',
        'occurrence:record_status': status.status[0],
        'occurrence_comment:comment': commentToSave
      });
      doc.identification.verification_status = status.status[0];
      if (status.status.length > 1) {
        data['occurrence:record_substatus'] = status.status[1];
        doc.identification.verification_substatus = status.status[1];
      }
      // Post update to Indicia.
      $.post(
        indiciaPostUrl,
        data,
        function success() {

        }
      );
    } else if (status.query) {
      // No bulk API for query updates at the moment, so process one at a time.
      indiciaPostUrl = indiciaData.ajaxFormPostComment;
      doc.identification.query = status.query;
      commentToSave = comment.trim() === ''
        ? 'This record has been queried.'
        : comment.trim();
      $.each(occurrenceIds, function eachOccurrence() {
        $.extend(data, {
          'occurrence_comment:query': 't',
          'occurrence_comment:occurrence_id': this,
          'occurrence_comment:comment': commentToSave
        });
        // Post update to Indicia.
        $.post(
          indiciaPostUrl,
          data,
          function success() {

          }
        );
      });
    }

    // Now post update to ElasticSearch.
    data = {
      ids: occurrenceIds,
      warehouse_url: indiciaData.warehouseUrl,
      doc: doc
    };
    $.ajax({
      url: indiciaData.ajaxUrl + '/esproxy_updateids/' + indiciaData.nid,
      type: 'post',
      data: data,
      success: function success(response) {
        if (typeof response.error !== 'undefined') {
          console.log(response);
          alert('ElasticSearch update failed');
        } else {
          if (response.updated !== occurrenceIds.length) {
            alert('An error occurred whilst updating the reporting index. It may not reflect your changes temporarily but will be updated automatically later.');
          }
          $(dataGrid).esDataGrid('hideRowAndMoveNext');
          $(dataGrid).find('.multiselect-all').prop('checked', false);
        }
      },
      dataType: 'json'
    });
  };

  var commentPopup = function commentPopup(status) {
    var doc;
    var fs;
    var heading;
    var statusData = [];
    var overallStatus = status.status ? status.status : status.query;
    var selectedTrs = $(dataGrid).find('table').hasClass('multiselect-mode')
      ? $(dataGrid).find('.multiselect:checked').closest('tr')
      : $(dataGrid).find('tr.selected');
    var ids = [];
    if (selectedTrs.length === 0) {
      alert('There are no selected records');
      return;
    }
    $.each(selectedTrs, function eachRow() {
      doc = JSON.parse($(this).attr('data-doc-source'));
      ids.push(parseInt(doc.id, 10));
    });
    if (status.status) {
      statusData.push('data-status="' + status.status + '"');
    }
    if (status.query) {
      statusData.push('data-query="' + status.query + '"');
    }
    fs = $('<fieldset class="comment-popup" data-ids="' + JSON.stringify(ids) + '" ' + statusData.join('') + '>');
    if (selectedTrs.length > 1) {
      heading = status.status
        ? 'Set status to ' + indiciaData.statusMsgs[overallStatus] + ' for ' + selectedTrs.length + ' records'
        : 'Query ' + selectedTrs.length + ' records';
      $('<div class="alert alert-info">You are updating multiple records!</alert>').appendTo(fs);
    } else {
      heading = status.status
        ? 'Set status to ' + indiciaData.statusMsgs[overallStatus]
        : 'Query this record';
    }
    $('<legend><span class="' + indiciaData.statusClasses[overallStatus] + ' fa-2x"></span>' + heading + '</legend>').appendTo(fs);
    $('<label for="comment-textarea">Add the following comment:</label>').appendTo(fs);
    $('<textarea id="comment-textarea">').appendTo(fs);
    $('<button class="btn btn-primary">Save</button>').appendTo(fs);
    $.fancybox(fs);
  };

  /**
   * Declare public methods.
   */
  var methods = {
    /**
     * Initialise the esVerificationButtons plugin.
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
        $('.verification-buttons-wrap').show();
      });
      $(dataGrid).esDataGrid('on', 'populate', function rowSelect() {
        $('.verification-buttons-wrap').hide();
      });
      $(el).find('button.verify').click(function buttonClick(e) {
        var status = $(e.currentTarget).attr('data-status');
        commentPopup({ status: status });
      });
      $(el).find('button.query').click(function buttonClick(e) {
        var query = $(e.currentTarget).attr('data-query');
        commentPopup({ query: query });
      });
      $(el).find('button.edit').click(function buttonClick() {
        var selectedTr = $(dataGrid).find('tr.selected');
        var doc;
        var sep = indiciaData.editPath.indexOf('?') === -1 ? '?' : '&';
        if (selectedTr.length > 0) {
          doc = JSON.parse(selectedTr.attr('data-doc-source'));
          window.location = indiciaData.editPath + sep + 'occurrence_id=' + doc.id;
        }
      });
      indiciaFns.on('click', '.comment-popup button', {}, function onClickSave(e) {
        var popup = $(e.currentTarget).closest('.comment-popup');
        var ids = JSON.parse($(popup).attr('data-ids'));
        var statusData = {};
        if ($(popup).attr('data-status')) {
          statusData.status = $(popup).attr('data-status');
        }
        if ($(popup).attr('data-query')) {
          statusData.query = $(popup).attr('data-query');
        }
        saveVerifyComment(ids, statusData, $(popup).find('textarea').val());
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
      url: indiciaData.ajaxUrl + '/esproxy_searchbyparams/' + indiciaData.nid,
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
