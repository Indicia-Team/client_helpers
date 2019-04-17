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

 /* eslint no-underscore-dangle: ["error", { "allow": ["_id", "_source", "_latlng"] }] */
 /* eslint no-extend-native: ["error", { "exceptions": ["String"] }] */

(function enclose() {
  'use strict';
  var $ = jQuery;

  /**
   * Extend the String class to simplify a column fieldname string.
   *
   * For special column handlers, a fieldname can be given as the following
   * format:
   * #fieldname:param1:param2:...#
   * This function will return just fieldname.
   */
  String.prototype.simpleFieldName = function simpleFieldName() {
    return this.replace(/#/g, '').split(':')[0];
  };

  /**
   * Keep track of a list of all the plugin instances that output something.
   */
  indiciaData.esOutputPluginClasses = [];

  /**
   * Font Awesome icon and other classes for record statuses and flags.
   */
  indiciaData.statusClasses = {
    V: 'far fa-check-circle status-V',
    V1: 'fas fa-check-double status-V1',
    V2: 'fas fa-check status-V2',
    C: 'fas fa-clock status-C',
    C3: 'fas fa-check-square status-C3',
    R: 'far fa-times-circle status-R',
    R4: 'fas fa-times status-R4',
    R5: 'fas fa-times status-R5',
    // Additional flags
    Q: 'fas fa-question-circle',
    A: 'fas fa-reply',
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
   * Initially populate the data sources.
   */
  indiciaFns.populateDataSources = function populateDataSources() {
    // Build the source objects and run initial population.
    indiciaData.esSourceObjects = {};
    $.each(indiciaData.esSources, function eachSource() {
      var sourceObject = new EsDataSource(this);
      indiciaData.esSourceObjects[this.id] = sourceObject;
      sourceObject.populate();
    });
  };

  /**
   * Keep track of a unique list of output plugin classes active on the page.
   */
  indiciaFns.registerOutputPluginClass = function registerOutputPluginClasses(name) {
    if ($.inArray(name, indiciaData.esOutputPluginClasses) === -1) {
      indiciaData.esOutputPluginClasses.push(name);
    }
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
   * Convert an ES (ISO) date to local display format.
   *
   * @param string dateString
   *   Date as returned from ES date field.
   *
   * @return string
   *   Date formatted.
   */
  indiciaFns.formatDate = function formatDate(dateString) {
    var date;
    var month;
    var day;
    if (dateString.trim() === '') {
      return '';
    }
    date = new Date(dateString);
    month = (1 + date.getMonth()).toString();
    month = month.length > 1 ? month : '0' + month;
    day = date.getDate().toString();
    day = day.length > 1 ? day : '0' + day;
    return indiciaData.dateFormat
      .replace('d', day)
      .replace('m', month)
      .replace('Y', date.getFullYear());
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

  /**
   * Searches an object for a nested property.
   *
   * Useful for finding the buckets property of an aggregation for example.
   *
   * @return mixed
   *   Property value.
   */
  indiciaFns.findValue = function findValue(object, key) {
    var value;
    Object.keys(object).some(function eachKey(k) {
      if (k === key) {
        value = object[k];
        return true;
      }
      if (object[k] && typeof object[k] === 'object') {
        value = indiciaFns.findValue(object[k], key);
        return value !== undefined;
      }
      return false;
    });
    return value;
  };

  /**
   * Searches an object for a nested property and sets its value.
   *
   * @return mixed
   *   Property value.
   */
  indiciaFns.findAndSetValue = function findAndSetValue(object, key, updateValue) {
    var value;
    Object.keys(object).some(function eachKey(k) {
      if (k === key) {
        object[k] = updateValue;
        return true;
      }
      if (object[k] && typeof object[k] === 'object') {
        value = indiciaFns.findAndSetValue(object[k], key, updateValue);
        return value !== undefined;
      }
      return false;
    });
    return value;
  };

  /**
   * A list of functions which provide HTML generation for special fields.
   *
   * These are field values in HTML that can be extracted from an Elasticsearch
   * doc which are not simple values.
   */
  indiciaFns.fieldConvertors = {
    /**
     * Record status and other flag icons.
     */
    status_icons: function statusIcons(doc) {
      return indiciaFns.getEsStatusIcons({
        status: doc.identification.verification_status,
        substatus: doc.identification.verification_substatus,
        query: doc.identification.query ? doc.identification.query : '',
        sensitive: doc.metadata.sensitive,
        confidential: doc.metadata.confidential
      });
    },

    /**
     * Data cleaner automatic rule check result icons.
     */
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
            var icon = Object.prototype.hasOwnProperty.call(indiciaData.ruleClasses, this.rule_type)
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

    /**
     * Output the event date or date range.
     */
    event_date: function eventDate(doc) {
      if (doc.event.date_start !== doc.event.date_end) {
        return indiciaFns.formatDate(doc.event.date_start) + ' - ' + indiciaFns.formatDate(doc.event.date_end);
      }
      return indiciaFns.formatDate(doc.event.date_start);
    },

    /**
     * Output a higher geography value.
     *
     * The column should be configured with two parameters, the first is the
     * type (e.g. Vice county) and the second the field to return (e.g. name,
     * code). For example:
     * {"caption":"VC code","field":"#higher_geography:Vice County:code#"}
     */
    higher_geography: function higherGeography(doc, params) {
      var output = '';
      if (doc.location.higher_geography) {
        $.each(doc.location.higher_geography, function eachGeography() {
          if (this.type === params[0]) {
            output = this[params[1]];
          }
        });
      }
      return output;
    },

    /**
     * A summary of location information.
     *
     * Includes the given location name (verbatim locality) as well as list of
     * higher geography.
     */
    locality: function locality(doc) {
      var info = '';
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
    },

    /**
     * A simple output of website and survey ID.
     *
     * Has a hint to show the underlying titles.
     */
    datasource_code: function datasourceCode(doc) {
      return '<abbr title="' + doc.metadata.website.title + ' | ' + doc.metadata.survey.title + '">' +
        doc.metadata.website.id + '|' + doc.metadata.survey.id +
        '</abbr>';
    }
  };

  /**
   * Special fields provided by field convertors are not searchable unless a
   * dedicated function is provided to build an appropriate query string for
   * the user input.
   *
   * This list could also potentially override the search behaviour for normal
   * mapped fields.
   *
   * Builders should return:
   * * false if the input text is not a valid filter.
   * * a string suitable for use as a query_string.
   * * an object that defines any filter suitable for adding to the bool
   *   queries array.
   * The builder can assume that the input text value is already trimmed.
   */
  indiciaFns.fieldConvertorQueryBuilders = {
    /**
     * Handle datasource_code filtering in format website_id [| survey ID].
     */
    datasource_code: function datasourceCode(text) {
      var parts;
      var query;
      if (text.match(/^\d+(\s*\|\s*\d*)?$/)) {
        parts = text.split('|');
        // Search always includes the website ID.
        query = 'metadata.website.id:' + parts[0].trim();
        // Search can optionally include the survey ID.
        if (parts.length > 1 && parts[1].trim() !== '') {
          query += 'AND metadata.survey.id:' + parts[1].trim();
        }
        return query;
      }
      return false;
    },

    /**
     * Event date filtering.
     *
     * Supports yyyy, mm/dd/yyyy or yyyy-mm-dd formats.
     */
    event_date: function eventDate(text) {
      // A series of possible date patterns, with the info required to build
      // a query string.
      var tests = [
        {
          // yyyy format.
          pattern: '(\\d{4})',
          field: 'event.year',
          format: '{1}'
        },
        {
          // dd/mm/yyyy format.
          pattern: '(\\d{2})\\/(\\d{2})\\/(\\d{4})',
          field: 'event.date_start',
          format: '{3}-{2}-{1}'
        },
        {
          // yyyy-mm-dd format.
          pattern: '(\\d{4})\\-(\\d{2})\\-(\\d{2})',
          field: 'event.date_start',
          format: '{1}-{2}-{3}'
        }
      ];
      var filter = false;
      // Loop the patterns to find a match.
      $.each(tests, function eachTest() {
        var regex = new RegExp('^' + this.pattern + '$');
        var match = text.match(regex);
        var value = this.format;
        var i;
        if (match) {
          // Got a match, so reformat and build the filter string.
          for (i = 1; i < match.length; i++) {
            value = value.replace('{' + i + '}', match[i]);
          }
          filter = this.field + ':' + value;
          // Abort the search.
          return false;
        }
        return true;
      });
      return filter;
    },

    /**
     * Builds a nested query for higher geography columns.
     */
    higher_geography: function higherGeography(text, params) {
      var filter = {};
      var query;
      filter['location.higher_geography.' + params[1]] = text;
      query = {
        nested: {
          path: 'location.higher_geography',
          query: {
            bool: {
              must: [
                { match: { 'location.higher_geography.type': params[0] } },
                { match: filter }
              ]
            }
          }
        }
      };
      return {
        bool_clause: 'must',
        value: '',
        query: JSON.stringify(query)
      };
    }
  };

  /**
   * Field convertors which allow sort on underlying fields are listed here.
   */
  indiciaData.fieldConvertorSortFields = {
    // Unsupported possibilities are commented out.
    // status_icons: []
    // data_cleaner_icons: [],
    event_date: ['event.date_start'],
    // higher_geography: [],
    // locality: [],
    datasource_code: ['metadata.website.id', 'metadata.survey.id']
  };

  /**
   * Retrieves a field value from the document.
   *
   * @param object doc
   *   Document read from Elasticsearch.
   * @param string field
   *   Name of the field. Either a path to the field in the document (such as
   *   taxon.accepted_name) or a special field name surrounded by # characters,
   *   e.g. #locality.
   */
  indiciaFns.getValueForField = function getValueForField(doc, field) {
    var i;
    var valuePath = doc;
    var fieldPath = field.split('.');
    var convertor;
    // Special field handlers are in the list of convertors.
    if (field.match(/^#/)) {
      // Find the convertor definition between the hashes. If there are
      // colons, stuff that follows the first colon are parameters.
      convertor = field.replace(/^#(.+)#$/, '$1').split(':');
      if (typeof indiciaFns.fieldConvertors[convertor[0]] !== 'undefined') {
        return indiciaFns.fieldConvertors[convertor[0]](doc, convertor.slice(1));
      }
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

  /**
   * Build query data to send to ES proxy.
   *
   * Builds the data to post to the Elasticsearch search proxy to represent
   * the current state of the form inputs on the page.
   */
  indiciaFns.getEsFormQueryData = function getEsFormQueryData(source) {
    var data = {
      warehouse_url: indiciaData.warehouseUrl,
      filters: {},
      bool_queries: [],
      user_filters: []
    };
    var mapToFilterTo;
    var bounds;
    var filterSourceGrid;
    var filterSourceRow;
    var thisDoc;
    if (source.settings.size) {
      data.size = source.settings.size;
    }
    if (source.settings.from) {
      data.from = source.settings.from;
    }
    if (source.settings.sort) {
      data.sort = source.settings.sort;
    }
    if (source.settings.filterBoolClauses) {
      // Using filter paremeter controls.
      $.each(source.settings.filterBoolClauses, function eachBoolClause(type, filters) {
        $.each(filters, function eachFilter() {
          data.bool_queries.push({
            bool_clause: type,
            query_type: this.query_type,
            field: this.field ? this.field : null,
            query: this.query ? this.query : null,
            value: this.value ? this.value : null
          });
        });
      });
    }
    if (source.settings.filterSourceGrid && source.settings.filterField) {
      // Using a grid row as a filter.
      filterSourceGrid = $('#' + source.settings.filterSourceGrid);
      if (filterSourceGrid.length === 0) {
        alert('Invalid @filterSourceGrid setting for source. Grid with id="' +
          source.settings.filterSourceGrid + '" does not exist.');
      }
      filterSourceRow = $(filterSourceGrid).find('tbody tr.selected');
      if (filterSourceRow.length === 0) {
        // Don't populate until a row selected.
        data.bool_queries.push({
          bool_clause: 'must',
          query_type: 'match_none'
        });
      } else {
        thisDoc = JSON.parse($(filterSourceRow).attr('data-doc-source'));
        data.bool_queries.push({
          bool_clause: 'must',
          field: source.settings.filterField,
          query_type: 'term',
          value: indiciaFns.getValueForField(thisDoc, source.settings.filterField)
        });
      }
    } else {
      // Using filter paremeter controls.
      $.each($('.es-filter-param'), function eachParam() {
        if ($(this).val().trim() !== '') {
          data.bool_queries.push({
            bool_clause: $(this).attr('data-es-bool-clause'),
            field: $(this).attr('data-es-field') ? $(this).attr('data-es-field') : null,
            query_type: $(this).attr('data-es-query-type'),
            query: $(this).attr('data-es-query') ? $(this).attr('data-es-query') : null,
            value: $(this).val().trim()
          });
        }
      });
      if (typeof source.outputs.dataGrid !== 'undefined') {
        $.each(source.outputs.dataGrid, function eachGrid() {
          var filterRow = $(this).find('.es-filter-row');
          // Remove search text format errors.
          $(filterRow).find('.fa-exclamation-circle').remove();
          // Build the filter required for values in each filter row input.
          $.each($(filterRow).find('input'), function eachInput() {
            var el = $(this).closest('.es-output');
            var cell = $(this).closest('td');
            var col = $(el)[0].settings.columns[$(cell).attr('data-col')];
            var fnQueryBuilder;
            var query;
            var fieldNameParts;
            var fn;
            if ($(this).val().trim() !== '') {
              // If there is a special field name, break it into the name
              // + parameters.
              fieldNameParts = col.field.replace(/#/g, '').split(':');
              // Remove the convertor name from the start of the array,
              // leaving the parameters.
              fn = fieldNameParts.shift();
              if (typeof indiciaFns.fieldConvertorQueryBuilders[fn] !== 'undefined') {
                // A special field with a convertor function.
                fnQueryBuilder = indiciaFns.fieldConvertorQueryBuilders[fn];
                query = fnQueryBuilder($(this).val().trim(), fieldNameParts);
                if (query === false) {
                  // Flag input as invalid.
                  $(this).after('<span title="Invalid search text" class="fas fa-exclamation-circle"></span>');
                } else if (typeof query === 'object') {
                  // Query is an object, so use it as is.
                  data.bool_queries.push(query);
                } else {
                  // Query is a string, so treat as a query_string.
                  data.bool_queries.push({
                    bool_clause: 'must',
                    query_type: 'query_string',
                    value: query
                  });
                }
              } else {
                // A normal mapped field with no special handling.
                data.filters[col.field] = $(this).val().trim();
              }
            }
          });
        });
      }
      if ($('.user-filter').length > 0) {
        $.each($('.user-filter'), function eachUserFilter() {
          if ($(this).val()) {
            data.user_filters.push($(this).val());
          }
        });
      }
      if ($('.permissions-filter').length > 0) {
        data.permissions_filter = $('.permissions-filter').val();
      }
    }
    if (source.settings.aggregation) {
      // Find the map bounds if limited to the viewport of a map.
      if (source.settings.filterBoundsUsingMap) {
        mapToFilterTo = $('#' + source.settings.filterBoundsUsingMap);
        if (mapToFilterTo.length === 0 || !mapToFilterTo[0].map) {
          alert('Data source incorrectly configured. @filterBoundsUsingMap does not point to a valid map.');
        } else {
          bounds = mapToFilterTo[0].map.getBounds();
          indiciaFns.findAndSetValue(source.settings.aggregation, 'geo_bounding_box', {
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
          indiciaFns.findAndSetValue(source.settings.aggregation, 'geohash_grid', {
            field: 'location.point',
            precision: Math.min(Math.max(mapToFilterTo[0].map.getZoom() - 3, 4), 10)
          });
        }
      }
      data.aggs = source.settings.aggregation;
    }
    return data;
  };

  /**
   * Constructor for an EsDataSource.
   *
   * @param object settings
   *   Datasource settings.
   */
  function EsDataSource(settings) {
    var ds = this;
    ds.settings = settings;
    // Prepare a structure to store the output plugins linked to this source.
    ds.outputs = {};
    $.each(indiciaData.esOutputPluginClasses, function eachPluginClass() {
      ds.outputs[this] = [];
    });
    $.each($('.es-output'), function eachOutput() {
      var el = this;
      var source = JSON.parse($(el).attr('data-es-source'));
      if (Object.prototype.hasOwnProperty.call(source, ds.settings.id)) {
        $.each(indiciaData.esOutputPluginClasses, function eachPluginClass(i, pluginClass) {
          if ($(el).hasClass('es-output-' + pluginClass)) {
            ds.outputs[pluginClass].push(el);
          }
        });
      }
    });
    if (ds.settings.filterSourceGrid && ds.settings.filterField) {
      $('#' + ds.settings.filterSourceGrid).esDataGrid('on', 'rowSelect', function onRowSelect(tr) {
        if (tr) {
          ds.populate();
        }
      });
    }
    // If limited to a map's bounds, redraw when the map is zoomed or panned.
    if (ds.settings.filterBoundsUsingMap) {
      $('#' + ds.settings.filterBoundsUsingMap).esMap('on', 'moveend', function onMoveEnd() {
        ds.populate();
      });
    }
  }

  EsDataSource.prototype.lastRequestStr = '';

  /**
   * Request a datasource to repopulate from current parameters.
   */
  EsDataSource.prototype.populate = function datasourcePopulate() {
    var source = this;
    var needsPopulation = false;
    var request;
    // Check we have an output other than the download plugin, which only
    // outputs when you click Download.
    $.each(this.outputs, function eachOutput(name) {
      needsPopulation = needsPopulation || name !== 'download';
    });
    if (needsPopulation) {
      request = indiciaFns.getEsFormQueryData(source);
      // Don't repopulate if exactly the same request as already loaded.
      if (JSON.stringify(request) !== this.lastRequestStr) {
        this.lastRequestStr = JSON.stringify(request);
        $.ajax({
          url: indiciaData.ajaxUrl + '/esproxy_searchbyparams/' + indiciaData.nid,
          type: 'post',
          data: request,
          success: function success(response) {
            if (response.error || (response.code && response.code !== 200)) {
              alert('Elasticsearch query failed');
            } else {
              // Build any configured output tables.
              source.buildTableXY(response);
              $.each(indiciaData.esOutputPluginClasses, function eachPluginClass(i, pluginClass) {
                var fn = 'es' + pluginClass.charAt(0).toUpperCase() + pluginClass.slice(1);
                $.each(source.outputs[pluginClass], function eachOutput() {
                  $(this)[fn]('populate', source.settings, response, request);
                });
              });
            }
          },
          error: function error(jqXHR, textStatus, errorThrown) {
            console.log(errorThrown);
            alert('Elasticsearch query failed');
          },
          dataType: 'json'
        });
      }
    }
  };

  /**
   * ESDataSource function to tablify 2 tier aggregation responses.
   *
   * Use this method if there is an outer aggregation which corresponds to the
   * table columns (X) and an inner aggregation which corresponds to the table
   * rows (Y).
   *
   * @param object response
   *   Response from an ES aggregation search request.
   */
  EsDataSource.prototype.buildTableXY = function buildTableXY(response) {
    var source = this;
    if (source.settings.buildTableXY) {
      $.each(source.settings.buildTableXY, function eachTable(name, aggs) {
        var data = {};
        var colsTemplate = {
          key: ''
        };
        // Collect the list of columns
        $.each(response.aggregations[aggs[0]].buckets, function eachOuterBucket() {
          colsTemplate[this.key] = 0;
        });
        // Now for each column, collect the rows.
        $.each(response.aggregations[aggs[0]].buckets, function eachOuterBucket() {
          var thisCol = this.key;
          var aggsPath = aggs[1].split(',');
          var obj = this;
          // Drill down the required level of nesting.
          $.each(aggsPath, function eachPathLevel() {
            obj = obj[this];
          });
          $.each(obj.buckets, function eachInnerBucket() {
            if (typeof data[this.key] === 'undefined') {
              data[this.key] = $.extend({}, colsTemplate);
              data[this.key].key = this.key;
            }
            data[this.key][thisCol] = this.doc_count;
          });
        });
        // Attach the data table to the response.
        response[name] = data;
      });
    }
  };
}());

/**
 * Output plugin for data downloads.
 */
(function esDownloadPlugin() {
  'use strict';
  var $ = jQuery;

  /**
   * Place to store public methods.
   */
  var methods;

  /**
   * Flag to track when file generation completed.
   */
  var done;

  /**
   * Declare default settings.
   */
  var defaults = {
  };

  /**
   * Wind the progress spinner forward to a certain percentage.
   *
   * @param element el
   *   The plugin instance's element.
   * @param int progress
   *   Progress percentage.
   */
  function animateTo(el, progress) {
    var target = done ? 1006 : 503 + (progress * 503);
    // Stop previous animations if we are making better progress through the
    // download than 1 chunk per 0.5s. This allows the spinner to speed up.
    $(el).find('.circle').stop(true);
    $(el).find('.circle').animate({
      'stroke-dasharray': target
    }, {
      duration: 500
    });
  }

  /**
   * Updates the progress text and spinner after receiving a response.
   *
   * @param element el
   *   The plugin instance's element.
   * @param obj response
   *   Response body from the ES proxy containing progress data.
   */
  function updateProgress(el, response) {
    $(el).find('.progress-text').text(response.done + ' of ' + response.total);
    animateTo(el, response.done / response.total);
  }

  /**
   * Recurse until all the pages of a chunked download are received.
   *
   * @param obj data
   *   Response body from the ES proxy containing progress data.
   */
  function doPages(el, data) {
    var date;
    var hours;
    var minutes;
    if (data.done < data.total) {
      // Post to the ES proxy. Pass scroll_id parameter to request the next
      // chunk of the dataset.
      $.ajax({
        url: indiciaData.ajaxUrl + '/esproxy_download/' + indiciaData.nid,
        type: 'post',
        data: {
          warehouse_url: indiciaData.warehouseUrl,
          scroll_id: data.scroll_id
        },
        success: function success(response) {
          updateProgress(el, response);
          doPages(el, response);
        },
        dataType: 'json'
      });
    } else {
      date = new Date();
      date.setTime(date.getTime() + (45 * 60 * 1000));
      hours = '0' + date.getHours();
      hours = hours.substr(hours.length - 2);
      minutes = '0' + date.getMinutes();
      minutes = minutes.substr(minutes.length - 2);
      $(el).find('.progress-container').addClass('download-done');
      $(el).find('.files').append('<div><a href="' + data.filename + '">' +
        '<span class="fas fa-file-archive fa-2x"></span>' +
        'Download .zip file</a><br/>' +
        'File containing ' + data.total + ' occurrences. Available until ' + hours + ':' + minutes + '</div>');
      $(el).find('.files').fadeIn('med');
    }
  }

  function initHandlers(el) {
    /**
     * Download button click handler.
     */
    $(el).find('.do-download').click(function doDownload() {
      var data;
      var sources = JSON.parse($(el).attr('data-es-source'));
      $.each(sources, function eachSource(sourceId) {
        var source = indiciaData.esSourceObjects[sourceId];
        if (typeof source === 'undefined') {
          indiciaFns.controlFail(el, 'Download source not found.');
        }
        $(el).find('.progress-container').removeClass('download-done');
        $(el).find('.progress-container').show();
        done = false;
        $(el).find('.circle').attr('style', 'stroke-dashoffset: 503px');
        $(el).find('.progress-text').text('Loading...');
        data = indiciaFns.getEsFormQueryData(source);
        // Post to the ES proxy.
        $.ajax({
          url: indiciaData.ajaxUrl + '/esproxy_download/' + indiciaData.nid,
          type: 'post',
          data: data,
          success: function success(response) {
            if (typeof response.code !== 'undefined' && response.code === 401) {
              alert('Elasticsearch alias configuration user or secret incorrect in the form configuration.');
              $('.progress-container').hide();
            } else {
              updateProgress(el, response);
              doPages(el, response);
            }
          }
        });
      });
    });
  }

  /**
   * Declare public methods.
   */
  methods = {

    /**
     * Initialise the esMap  plugin.
     *
     * @param array options
     */
    init: function init(options) {
      var el = this;

      indiciaFns.registerOutputPluginClass('download');
      el.settings = $.extend({}, defaults);
      // Apply settings passed in the HTML data-* attribute.
      if (typeof $(el).attr('data-es-output-config') !== 'undefined') {
        $.extend(el.settings, JSON.parse($(el).attr('data-es-output-config')));
      }
      // Apply settings passed to the constructor.
      if (typeof options !== 'undefined') {
        $.extend(el.settings, options);
      }
      initHandlers(el);
    },

    /*
     * The download plugin doesn't do anything until requested.
     */
    populate: function populate() {
      // Nothing to do.
    }
  };

  /**
   * Extend jQuery to declare esDownload method.
   */
  $.fn.esDownload = function buildEsDownload(methodOrOptions) {
    var passedArgs = arguments;
    $.each(this, function callOnEachDiv() {
      if (methods[methodOrOptions]) {
        // Call a declared method.
        return methods[methodOrOptions].apply(this, Array.prototype.slice.call(passedArgs, 1));
      } else if (typeof methodOrOptions === 'object' || !methodOrOptions) {
        // Default to "init".
        return methods.init.apply(this, passedArgs);
      }
      // If we get here, the wrong method was called.
      $.error('Method ' + methodOrOptions + ' does not exist on jQuery.esDownload');
      return true;
    });
    return this;
  };
}());

/**
 * Output plugin for data grids.
 */
(function esMapPlugin() {
  'use strict';
  var $ = jQuery;

  /**
   * Place to store public methods.
   */
  var methods;

  /**
   * Declare default settings.
   */
  var defaults = {
    initialBoundsSet: false,
    initialLat: 54.093409,
    initialLng: -2.89479,
    initialZoom: 5,
    baseLayer: 'OpenStreetMap',
    cookies: true
  };

  var callbacks = {
    moveend: []
  };

  /**
   * Variable to hold the marker used to highlight the currently selected row
   * in a linked dataGrid.
   */
  var selectedRowMarker = null;

   /**
   * Variable to hold the polygon used to highlight the currently selected
   * location boundary when relevant.
   */
  var selectedFeature = null;


  function addFeature(el, sourceId, location, metric) {
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
  }

  /**
   * Thicken the borders of selected features when zoomed out to aid visibility.
   */
  function ensureFeatureClear(el, feature) {
    var weight = Math.min(20, Math.max(1, 20 - (el.map.getZoom())));
    var opacity = Math.min(1, Math.max(0.6, el.map.getZoom() / 18));
    if (typeof feature.setStyle !== 'undefined') {
      feature.setStyle({
        weight: weight,
        opacity: opacity
      });
    }
  }

  /**
   *
   */
  function showFeatureWkt(el, geom, zoom, style) {
    var centre;
    var wkt;
    var obj;
    wkt = new Wkt.Wkt();
    wkt.read(geom);
    var objStyle = {
      color: '#0000FF',
      opacity: 1.0,
      fillColor: '#0000FF',
      fillOpacity: 0.2
    };
    if (style) {
      $.extend(objStyle, style);
    }
    obj = wkt.toObject(objStyle);
    obj.addTo(el.map);
    centre = typeof obj.getCenter === 'undefined' ? obj.getLatLng() : obj.getCenter();
    // Pan and zoom the map. Method differs for points vs polygons.
    if (!zoom) {
      el.map.panTo(centre);
    } else if (wkt.type === 'polygon') {
      el.map.fitBounds(obj.getBounds(), { maxZoom: 11 });
    } else {
      el.map.setView(centre, 11);
    }
    return obj;
  }

  /**
   * Select a grid row pans, optionally zooms and adds a marker.
   */
  function rowSelected(el, tr, zoom) {
    var doc;
    var obj;
    if (selectedRowMarker) {
      selectedRowMarker.removeFrom(el.map);
    }
    selectedRowMarker = null;
    if (tr) {
      doc = JSON.parse($(tr).attr('data-doc-source'));
      obj = showFeatureWkt(el, doc.location.geom, zoom);
      ensureFeatureClear(el, obj);
      selectedRowMarker = obj;
    }
  }

  function loadSettingsFromCookies(cookieNames) {
    var val;
    var settings = {};
    if (typeof $.cookie !== 'undefined') {
      $.each(cookieNames, function eachCookie() {
        val = $.cookie(this);
        if (val !== null && val !== 'undefined') {
          settings[this] = val;
        }
      });
    }
    return settings;
  }

  /**
   * Declare public methods.
   */
  methods = {
    /**
     * Initialise the esMap  plugin.
     *
     * @param array options
     */
    init: function init(options) {
      var el = this;
      var source = JSON.parse($(el).attr('data-es-source'));
      var baseMaps;
      var overlays = {};
      var layersControl;
      el.outputLayers = {};

      indiciaFns.registerOutputPluginClass('map');
      el.settings = $.extend({}, defaults);
      // Apply settings passed in the HTML data-* attribute.
      if (typeof $(el).attr('data-es-output-config') !== 'undefined') {
        $.extend(el.settings, JSON.parse($(el).attr('data-es-output-config')));
      }
      // Apply settings passed to the constructor.
      if (typeof options !== 'undefined') {
        $.extend(el.settings, options);
      }
      // Apply settings stored in cookies.
      if (el.settings.cookies) {
        $.extend(el.settings, loadSettingsFromCookies(['initialLat', 'initialLong', 'initialZoom', 'baseLayer']));
      }
      el.map = L.map(el.id).setView([el.settings.initialLat, el.settings.initialLng], el.settings.initialZoom);
      baseMaps = {
        OpenStreetMap: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }),
        OpenTopoMap: L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
          maxZoom: 17,
          attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, ' +
            '<a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> ' +
            '(<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)'
        })
      };
      // Add the active base layer to the map.
      baseMaps[el.settings.baseLayer].addTo(el.map);
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
      layersControl = L.control.layers(baseMaps, overlays);
      layersControl.addTo(el.map);
      el.map.on('zoomend', function zoomEnd() {
        if (selectedRowMarker !== null) {
          ensureFeatureClear(el, selectedRowMarker);
        }
      });
      el.map.on('moveend', function moveEnd() {
        $.each(callbacks.moveend, function eachCallback() {
          this(el);
        });
        if (typeof $.cookie !== 'undefined' && el.settings.cookies) {
          $.cookie('initialLat', el.map.getCenter().lat);
          $.cookie('initialLng', el.map.getCenter().lng);
          $.cookie('initialZoom', el.map.getZoom());
        }
      });
      if (typeof $.cookie !== 'undefined' && el.settings.cookies) {
        el.map.on('baselayerchange', function baselayerchange(layer) {
          $.cookie('baseLayer', layer.name);
        });
      }
    },

    /*
     * Populate the map with Elasticsearch response data.
     *
     * @param obj sourceSettings
     *   Settings for the data source used to generate the response.
     * @param obj response
     *   Elasticsearch response data.
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
        buckets = indiciaFns.findValue(response.aggregations, 'buckets');
        if (typeof buckets !== 'undefined') {
          $.each(buckets, function eachBucket() {
            var count = indiciaFns.findValue(this, 'count');
            maxMetric = Math.max(Math.sqrt(count), maxMetric);
          });
          $.each(buckets, function eachBucket() {
            var location = indiciaFns.findValue(this, 'location');
            var count = indiciaFns.findValue(this, 'count');
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
        $('#' + settings.showSelectedRow).esDataGrid('on', 'rowSelect', function onRowSelect(tr) {
          rowSelected(el, tr, false);
        });
        $('#' + settings.showSelectedRow).esDataGrid('on', 'rowDblClick', function onRowDblClick(tr) {
          rowSelected(el, tr, true);
        });
      }
    },

    /**
     * Clears the selected feature boundary (e.g. a selected location).
     */
    clearFeature: function clearFeature() {
      if (selectedFeature) {
        selectedFeature.removeFrom($(this)[0].map);
        selectedFeature = null;
      }
    },

    /**
     * Shows a selected feature boundary (e.g. a selected location).
     * */
    showFeature: function showFeature(geom, zoom) {
      if (selectedFeature) {
        selectedFeature.removeFrom($(this)[0].map);
        selectedFeature = null;
      }
      selectedFeature = showFeatureWkt(this, geom, zoom, {
        color: '#7700CC',
        fillColor: '#7700CC',
        fillOpacity: 0.1
      });
    },

    /**
     * Hook up event handlers.
     */
    on: function on(event, handler) {
      if (typeof callbacks[event] === 'undefined') {
        indiciaFns.controlFail(this, 'Invalid event handler requested for ' + event);
      }
      callbacks[event].push(handler);
    }
  };

  /**
   * Extend jQuery to declare esMap method.
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
}());

/**
 * Output plugin for data grids.
 */
(function esDataGridPlugin() {
  'use strict';
  var $ = jQuery;

  /**
   * Place to store public methods.
   */
  var methods;

  /**
   * Declare default settings.
   */
  var defaults = {
    actions: [],
    includeColumnHeadings: true,
    includeFilterRow: true,
    includePager: true,
    sortable: true
  };

  var callbacks = {
    rowSelect: [],
    rowDblClick: [],
    populate: []
  };

  function initHandlers(el) {
    indiciaFns.on('click', '#' + el.id + ' .es-data-grid tbody tr', {}, function onEsDataGridRowClick() {
      var tr = this;
      $(tr).closest('tbody').find('tr.selected').removeClass('selected');
      $(tr).addClass('selected');
      $.each(callbacks.rowSelect, function eachCallback() {
        this(tr);
      });
    });

    indiciaFns.on('dblclick', '#' + el.id + ' .es-data-grid tbody tr', {}, function onEsDataGridRowDblClick() {
      var tr = this;
      if (!$(tr).hasClass('selected')) {
        $(tr).closest('tbody').find('tr.selected').removeClass('selected');
        $(tr).addClass('selected');
      }
      $.each(callbacks.rowDblClick, function eachCallback() {
        this(tr);
      });
    });

    $(el).find('.pager .next').click(function clickNext() {
      var sources = JSON.parse($(el).attr('data-es-source'));
      $.each(sources, function eachSource(sourceId) {
        var source = indiciaData.esSourceObjects[sourceId];
        if (typeof source.settings.from === 'undefined') {
          source.settings.from = 0;
        }
        // Move to next page based on currently visible row count, in case some
        // have been removed.
        source.settings.from += $(el).find('tbody tr.data-row').length;
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
        var fields;
        var fieldName = col.field.simpleFieldName();
        $(row).find('.sort.fas').removeClass('fa-sort-down');
        $(row).find('.sort.fas').removeClass('fa-sort-up');
        $(row).find('.sort.fas').addClass('fa-sort');
        $(sortButton).removeClass('fa-sort');
        $(sortButton).addClass('fa-sort-' + (sortDesc ? 'down' : 'up'));
        source.settings.sort = {};
        if (indiciaData.esMappings[fieldName]) {
          source.settings.sort[indiciaData.esMappings[fieldName].sort_field] = {
            order: sortDesc ? 'desc' : 'asc'
          };
        } else if (indiciaData.fieldConvertorSortFields[fieldName]) {
          fields = indiciaData.fieldConvertorSortFields[fieldName];
          $.each(fields, function eachField() {
            source.settings.sort[this] = {
              order: sortDesc ? 'desc' : 'asc'
            };
          });
        }
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

    indiciaFns.on('click', '.multiselect-all', {}, function onClick(e) {
      var table = $(e.currentTarget).closest('table');
      if ($(e.currentTarget).is(':checked')) {
        table.find('.multiselect').prop('checked', true);
      } else {
        $(table).find('.multiselect').prop('checked', false);
      }
    });
  }

  /**
   * Retrieve any action links to attach to a dataGrid row.
   *
   * @param array actions
   *   List of actions from configuration.
   * @param object doc
   *   The ES document for the row.
   *
   * @return string
   *   Action link HTML.
   */
  function getActionsForRow(actions, doc) {
    var html = '';
    $.each(actions, function eachActions() {
      var item;
      var link;
      if (typeof this.title === 'undefined') {
        html += '<span class="fas fa-times-circle error" title="Invalid action definition - missing title"></span>';
      } else {
        if (this.iconClass) {
          item = '<span class="' + this.iconClass + '" title="' + this.title + '"></span>';
        } else {
          item = this.title;
        }
        if (this.path) {
          link = this.path.replace('{rootFolder}', indiciaData.rootFolder);
          if (this.urlParams) {
            link += link.indexOf('?') === -1 ? '?' : '&';
            $.each(this.urlParams, function eachParam(name, value) {
              // Find any field name replacements.
              var fieldMatches = value.match(/\[(.*?)\]/g);
              var updatedVal = value;
              $.each(fieldMatches, function eachMatch(i, fieldToken) {
                var dataVal;
                // Cleanup the square brackets which are not part of the field name.
                var field = fieldToken.replace(/\[/, '').replace(/\]/, '');
                dataVal = indiciaFns.getValueForField(doc, field);
                updatedVal = value.replace(fieldToken, dataVal);
              });
              link += name + '=' + updatedVal;
            });
          }
          item = '<a href="' + link + '" title="' + this.title + '">' + item + '</a>';
        }
        html += item;
      }
    });
    return html;
  }

  /**
   * Declare public methods.
   */
  methods = {
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
      var totalCols;
      var showingAggregation;
      var footableSort;
      indiciaFns.registerOutputPluginClass('dataGrid');
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
      showingAggregation = el.settings.simpleAggregation || el.settings.sourceTable;
      footableSort = showingAggregation && el.settings.sortable ? 'true' : 'false';
      // Build the elements required for the table.
      table = $('<table class="table es-data-grid" data-sort="' + footableSort + '" />').appendTo(el);
      // If we need any sort of header, add <thead>.
      if (el.settings.includeColumnHeadings !== false || el.settings.includeFilterRow !== false) {
        header = $('<thead/>').appendTo(table);
        // Output header row for column titles.
        if (el.settings.includeColumnHeadings !== false) {
          headerRow = $('<tr/>').appendTo(header);
          $.each(el.settings.columns, function eachColumn(idx) {
            var heading = this.caption;
            var footableExtras = '';
            var sortableField = typeof indiciaData.esMappings[this.field] !== 'undefined'
              && indiciaData.esMappings[this.field].sort_field;
            sortableField = sortableField
              || indiciaData.fieldConvertorSortFields[this.field.simpleFieldName()];
            if (el.settings.sortable !== false && sortableField) {
              heading += '<span class="sort fas fa-sort"></span>';
            }
            if (this.multiselect) {
              heading += '<span title="Enable multiple selection mode" class="fas fa-list multiselect-switch"></span>';
            }
            // Extra data attrs to support footable.
            if (this['data-hide']) {
              footableExtras = ' data-hide="' + this['data-hide'] + '"';
            }
            if (this['data-type']) {
              footableExtras += ' data-type="' + this['data-type'] + '"';
            }
            $('<th class="col-' + idx + '" data-col="' + idx + '"' + footableExtras + '>' + heading + '</th>').appendTo(headerRow);
          });
          if (el.settings.actions.length) {
            $('<th class="col-actions">Actions</th>').appendTo(headerRow);
          }
        }
        // Disable filter row for aggregations.
        el.settings.includeFilterRow = el.settings.includeFilterRow && !showingAggregation;
        // Output header row for filtering.
        if (el.settings.includeFilterRow !== false) {
          filterRow = $('<tr class="es-filter-row" />').appendTo(header);
          $.each(el.settings.columns, function eachColumn(idx) {
            var td = $('<td class="col-' + idx + '" data-col="' + idx + '"></td>').appendTo(filterRow);
            // No filter input if this column has no mapping unless there is a
            // special field function that can work out the query.
            if (typeof indiciaData.esMappings[this.field] !== 'undefined'
              || typeof indiciaFns.fieldConvertorQueryBuilders[this.field.simpleFieldName()] !== 'undefined') {
              $('<input type="text">').appendTo(td);
            }
          });
        }
      }
      // We always want a table body for the data.
      $('<tbody />').appendTo(table);
      // Output a footer if we want a pager.
      if (el.settings.includePager && !(el.settings.sourceTable || el.settings.simpleAggregation)) {
        totalCols = el.settings.columns.length + (el.settings.actions.length > 0 ? 1 : 0);
        $('<tfoot><tr class="pager"><td colspan="' + totalCols + '"><span class="showing"></span>' +
          '<span class="buttons"><button class="prev">Previous</button><button class="next">Next</button></span>' +
          '</td></tr></tfoot>').appendTo(table);
      }
      initHandlers(el);
      // Make grid responsive.
      $(table).indiciaFootableReport();
    },

    /**
     * Populate the data grid with Elasticsearch response data.
     *
     * @param obj sourceSettings
     *   Settings for the data source used to generate the response.
     * @param obj response
     *   Elasticsearch response data.
     * @param obj data
     *   Data sent in request.
     */
    populate: function populate(sourceSettings, response, data) {
      var el = this;
      var fromRowIndex = typeof data.from === 'undefined' ? 1 : (data.from + 1);
      var dataList;
      $(el).find('tbody tr').remove();
      $(el).find('.multiselect-all').prop('checked', false);
      if ($(el)[0].settings.sourceTable) {
        dataList = response[$(el)[0].settings.sourceTable];
      } else if ($(el)[0].settings.simpleAggregation === true && typeof response.aggregations !== 'undefined') {
        dataList = indiciaFns.findValue(response.aggregations, 'buckets');
      } else {
        dataList = response.hits.hits;
      }
      $.each(dataList, function eachHit() {
        var hit = this;
        var cells = [];
        var row;
        var media;
        var selectedClass;
        var doc = hit._source ? hit._source : hit;
        if (el.settings.blockIdsOnNextLoad && $.inArray(hit._id, el.settings.blockIdsOnNextLoad) !== -1) {
          // Skip the row if blocked. This is required because ES is only
          // near-instantaneous, so if we take an action on a record then
          // reload the grid, it is quite likely to re-appear.
          return true;
        }
        if ($(el).find('table.multiselect-mode').length) {
          cells.push('<td class="multiselect-cell"><input type="checkbox" class="multiselect" /></td>');
        }
        $.each(el.settings.columns, function eachColumn(idx) {
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
          if (value && this.handler && this.handler === 'media') {
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
        if (el.settings.actions.length) {
          cells.push('<td class="col-actions">' + getActionsForRow(el.settings.actions, doc) + '</td>');
        }
        selectedClass = (el.settings.selectIdsOnNextLoad && $.inArray(hit._id, el.settings.selectIdsOnNextLoad) !== -1)
          ? ' selected' : '';
        row = $('<tr class="data-row' + selectedClass + '" data-row-id="' + hit._id + '">'
           + cells.join('') +
           '</tr>').appendTo($(el).find('tbody'));
        $(row).attr('data-doc-source', JSON.stringify(hit._source));
        return true;
      });
      // Discard the list of IDs to block during this population as now done.
      el.settings.blockIdsOnNextLoad = false;
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
      // Fire callbacks for selected row if any.
      $.each(callbacks.rowSelect, function eachCallback() {
        this($(el).find('tr.selected').length === 0 ? null : $(el).find('tr.selected')[0]);
      });
    },
    on: function on(event, handler) {
      if (typeof callbacks[event] === 'undefined') {
        indiciaFns.controlFail(this, 'Invalid event handler requested for ' + event);
      }
      callbacks[event].push(handler);
    },
    hideRowAndMoveNext: function hideRowAndMoveNext() {
      var grid = this;
      var oldSelected = $(grid).find('tr.selected');
      var newSelectedId;
      var sources;
      var showingLabel = $(grid).find('.showing');
      var blocked = [];

      if ($(grid).find('table.multiselect-mode').length > 0) {
        $.each($(grid).find('input.multiselect:checked'), function eachRow() {
          var tr = $(this).closest('tr');
          blocked.push($(tr).attr('data-row-id'));
          tr.remove();
        });
      } else {
        if ($(oldSelected).next('tr').length > 0) {
          newSelectedId = $(oldSelected).next('tr').attr('data-row-id');
        } else if ($(oldSelected).prev('tr').length > 0) {
          newSelectedId = $(oldSelected).prev('tr').attr('data-row-id');
        }
        blocked.push($(oldSelected).attr('data-row-id'));
        $(oldSelected).remove();
      }
      sources = JSON.parse($(grid).attr('data-es-source'));
      $.each(sources, function eachSource(sourceId) {
        var source = indiciaData.esSourceObjects[sourceId];
        if ($(grid).find('table tbody tr.data-row').length < source.settings.size * 0.75) {
          $(grid)[0].settings.blockIdsOnNextLoad = blocked;
          $(grid)[0].settings.selectIdsOnNextLoad = [newSelectedId];
          source.populate();
        } else {
          // Update the paging info if some rows left.
          showingLabel.html(showingLabel.html().replace(/\d+ of /, $(grid).find('tbody tr.data-row').length + ' of '));
          // Immediately select the next row.
          if (typeof newSelectedId !== 'undefined') {
            $(grid).find('table tbody tr.data-row[data-row-id="' + newSelectedId + '"]').addClass('selected');
          }
          // Fire callbacks for selected row.
          $.each(callbacks.rowSelect, function eachCallback() {
            this($(grid).find('tr.selected').length === 0 ? null : $(grid).find('tr.selected')[0]);
          });
        }
      });
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
}());

/**
* Output plugin for data grids.
*/
(function esDetailsPane() {
  'use strict';
  var $ = jQuery;

  /**
   * Place to store public methods.
   */
  var methods;

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

  function getExperienceCells(buckets, userId, el, filter, yr) {
    var total = buckets.C + buckets.V + buckets.R;
    var indicatorSize;
    var datedUrl;
    var links;
    var urls;
    var settings = $(el)[0].settings;
    var html = '';

    if (settings.exploreUrl) {
      datedUrl = settings.exploreUrl.replace('-userId-', userId);
      if (yr) {
        datedUrl = datedUrl
          .replace('-df-', yr + '-01-01')
          .replace('-dt-', yr + '-12-31');
      } else {
        datedUrl = datedUrl
        .replace('-df-', '')
        .replace('-dt-', '');
      }
      urls = {
        V: datedUrl.replace('-q-', 'V'),
        C: datedUrl.replace('-q-', 'P'),
        R: datedUrl.replace('-q-', 'R')
      };
      links = {
        V: buckets.V ? '<a target="_top" href="' + urls.V + '&' + filter + '">' + buckets.V + '</a>' : '0',
        C: buckets.C ? '<a target="_top" href="' + urls.C + '&' + filter + '">' + buckets.C + '</a>' : '0',
        R: buckets.R ? '<a target="_top" href="' + urls.R + '&' + filter + '">' + buckets.R + '</a>' : '0'
      };
    } else {
      // No explore URL, so just output the numbers.
      links = buckets;
    }
    indicatorSize = Math.min(80, total * 2);
    html += '<td>' + links.V + '<span class="exp-V" style="width: ' + (indicatorSize * (buckets.V / total)) + 'px;"></span></td>';
    html += '<td>' + links.C + '<span class="exp-C" style="width: ' + (indicatorSize * (buckets.C / total)) + 'px;"></span></td>';
    html += '<td>' + links.R + '<span class="exp-R" style="width: ' + (indicatorSize * (buckets.R / total)) + 'px;"></span></td>';
    return html;
  }

  function getExperienceAggregation(data, type, userId, filter, el) {
    var html = '';
    var minYear = 9999;
    var maxYear = 0;
    var yr;
    var matrix = { C: {}, V: {}, R: {} };
    var buckets;

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
        html += getExperienceCells(buckets, userId, el, filter, yr);
        html += '</tr>';
      }
      buckets = {
        V: 0,
        C: 0,
        R: 0
      };
      for (yr = minYear; yr <= maxYear; yr++) {
        buckets.V += typeof matrix.V[yr] !== 'undefined' ? matrix.V[yr] : 0;
        buckets.C += typeof matrix.C[yr] !== 'undefined' ? matrix.C[yr] : 0;
        buckets.R += typeof matrix.R[yr] !== 'undefined' ? matrix.R[yr] : 0;
      }
      html += '<tr>';
      html += '<th scope="row">Total</th>';
      html += getExperienceCells(buckets, userId, el, filter);
      html += '</tr>';
      html += '<tbody>';
      html += '</tbody></table>';
    }
    return html;
  }

  function loadComments(el, occurrenceId) {
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
  }

  function loadAttributes(el, occurrenceId) {
    // Check not already loaded.
    if (loadedAttrsOcurrenceId === occurrenceId) {
      return;
    }
    loadedAttrsOcurrenceId = occurrenceId;
    $.ajax({
      url: indiciaData.ajaxUrl + '/attrs/' + indiciaData.nid,
      data: { occurrence_id: occurrenceId },
      success: function success(response) {
        var attrsDiv = $(el).find('.record-details .attrs');
        $(attrsDiv).html('');
        $.each(response, function eachHeading(title, attrs) {
          var table;
          var tbody;
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
  }

  function loadExperience(el, doc) {
    var data;
    // Check not already loaded.
    if (loadedExperienceOcurrenceId === doc.id) {
      return;
    }
    if (doc.metadata.created_by_id === '1') {
      $(el).find('.recorder-experience').html(
        '<div class="alert alert-info"><span class="fas fa-info-circle"></span>' +
          'Recorder was not logged in so experience cannot be loaded.</div>'
      );
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
        if (typeof response.error !== 'undefined' || (response.code && response.code !== 200)) {
          console.log(response);
          alert('Elasticsearch query failed');
          $(el).find('.recorder-experience').html('<div class="alert alert-warning">Experience could not be loaded.</div>');
          $(el).find('.loading-spinner').hide();
        } else {
          html += '<h3>Experience for <span class="field-taxon--accepted-name">' + doc.taxon.accepted_name + '</span></h3>';
          html += getExperienceAggregation(response.aggregations, 'species', doc.metadata.created_by_id,
            'filter-taxa_taxon_list_external_key_list=' + doc.taxon.accepted_taxon_id, el);
          html += '<h3>Experience for ' + doc.taxon.group + '</h3>';
          html += getExperienceAggregation(response.aggregations, 'group', doc.metadata.created_by_id,
            'filter-taxon_group_list=' + doc.taxon.group_id, el);
          $(el).find('.recorder-experience').html(html);
          $(el).find('.loading-spinner').hide();
        }
      },
      error: function error(jqXHR, textStatus, errorThrown) {
        console.log(errorThrown);
        alert('Elasticsearch query failed');
      },
      dataType: 'json'
    });
  }

  function loadCurrentTabAjax(el) {
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
  }

  function tabActivate(event, ui) {
    loadCurrentTabAjax($(ui.newPanel).closest('.details-container'));
  }

  function addRow(rows, doc, caption, fields, separator) {
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
  }

  /**
   * Declare public methods.
   */
  methods = {
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
      $(dataGrid).esDataGrid('on', 'rowSelect', function rowSelect(tr) {
        var doc;
        var rows = [];
        var acceptedNameAnnotation;
        var vernaculardNameAnnotation;
        if (tr) {
          doc = JSON.parse($(tr).attr('data-doc-source'));
          acceptedNameAnnotation = doc.taxon.taxon_name === doc.taxon.accepted_name ? ' (as recorded)' : '';
          vernaculardNameAnnotation = doc.taxon.taxon_name === doc.taxon.vernacular_name ? ' (as recorded)' : '';
          addRow(rows, doc, 'ID', 'id');
          if (doc.taxon.taxon_name !== doc.taxon.accepted_name && doc.taxon.taxon_name !== doc.taxon.vernacular_name) {
            addRow(rows, doc, 'Given name', ['taxon.taxon_name', 'taxon.taxon_name_authorship'], ' ');
          }
          addRow(rows, doc, 'Accepted name' + acceptedNameAnnotation,
            ['taxon.accepted_name', 'taxon.accepted_name_authorship'], ' ');
          addRow(rows, doc, 'Common name' + vernaculardNameAnnotation, 'taxon.vernacular_name');
          addRow(rows, doc, 'Taxonomy', ['taxon.phylum', 'taxon.order', 'taxon.family'], ' :: ');
          addRow(rows, doc, 'Licence', 'metadata.licence_code');
          addRow(rows, doc, 'Status', '#status_icons#');
          addRow(rows, doc, 'Checks', '#data_cleaner_icons#');
          addRow(rows, doc, 'Date', '#event_date#');
          addRow(rows, doc, 'Output map ref', 'location.output_sref');
          if (el.settings.locationTypes) {
            addRow(rows, doc, 'Location', 'location.verbatim_locality');
            $.each(el.settings.locationTypes, function eachType() {
              addRow(rows, doc, this, '#higher_geography:' + this + ':name#');
            });
          } else {
            addRow(rows, doc, 'Location', '#locality#');
          }
          addRow(rows, doc, 'Sample comments', 'event.event_remarks');
          addRow(rows, doc, 'Occurrence comments', 'occurrence.occurrence_remarks');
          addRow(rows, doc, 'Submitted on', 'metadata.created_on');
          addRow(rows, doc, 'Last updated on', 'metadata.updated_on');
          addRow(rows, doc, 'Dataset',
            ['metadata.website.title', 'metadata.survey.title', 'metadata.group.title'], ' :: ');
          $(recordDetails).html('<table><tbody>' + rows.join('') + '</tbody></table>');
          $(recordDetails).append('<div class="attrs"></div>');
          $(el).find('.empty-message').hide();
          $(el).find('.tabs').show();
          // Load Ajax content depending on the tab.
          loadCurrentTabAjax($(el));
        } else {
          // If no row selected, hide the details tabs.
          $(el).find('.empty-message').show();
          $(el).find('.tabs').hide();
        }
      });
      $(dataGrid).esDataGrid('on', 'populate', function rowSelect() {
        $(el).find('.empty-message').show();
        $(el).find('.tabs').hide();
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
}());

/**
* Output plugin for verification buttons.
*/
(function esVerificationButtons() {
  'use strict';
  var $ = jQuery;

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

    // Now post update to Elasticsearch.
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
        if (typeof response.error !== 'undefined' || (response.code && response.code !== 200)) {
          console.log(response);
          alert('Elasticsearch update failed');
        } else {
          if (response.updated !== occurrenceIds.length) {
            alert('An error occurred whilst updating the reporting index. It may not reflect your changes temporarily but will be updated automatically later.');
          }
          $(dataGrid).esDataGrid('hideRowAndMoveNext');
          $(dataGrid).find('.multiselect-all').prop('checked', false);
        }
      },
      error: function error(jqXHR, textStatus, errorThrown) {
        console.log('Error thrown');
        alert('Elasticsearch update failed');
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
    $('<legend><span class="' + indiciaData.statusClasses[overallStatus] + ' fa-2x"></span>' + heading + '</legend>')
      .appendTo(fs);
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
      $(dataGrid).esDataGrid('on', 'rowSelect', function rowSelect(tr) {
        var sep;
        var doc;
        if (tr) {
          // Update the view and edit button hrefs. This allows the user to
          // right click and open in a new tab, rather than have an active
          // button.
          doc = JSON.parse($(tr).attr('data-doc-source'));
          $('.verification-buttons-wrap').show();
          sep = el.settings.viewPath.indexOf('?') === -1 ? '?' : '&';
          $(el).find('.view').attr('href', el.settings.viewPath + sep + 'occurrence_id=' + doc.id);
          $(el).find('.edit').attr('href', el.settings.editPath + sep + 'occurrence_id=' + doc.id);
        } else {
          $('.verification-buttons-wrap').hide();
        }
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
}());

jQuery(document).ready(function docReady() {
  'use strict';
  var $ = jQuery;

  // Hook up higher geography controls.
  $('.es-higher-geography-select').addClass('es-filter-param');
  $('.es-higher-geography-select').attr('data-es-bool-clause', 'must');
  $('.es-higher-geography-select').attr('data-es-query', JSON.stringify({
    nested: {
      path: 'location.higher_geography',
      query: {
        bool: {
          must: [
            { match: { 'location.higher_geography.id': '#value#' } }
          ]
        }
      }
    }
  }));
  $('.es-higher-geography-select').change(function higherGeoSelectChange() {
    if ($(this).val()) {
      $.getJSON(indiciaData.warehouseUrl + 'index.php/services/report/requestReport?' +
          'report=library/locations/location_boundary_projected.xml' +
          '&reportSource=local&srid=4326&location_id=' + $(this).val() +
          '&nonce=' + indiciaData.read.nonce + '&auth_token=' + indiciaData.read.auth_token +
          '&mode=json&callback=?', function getLoc(data) {
        $.each($('.es-output-map'), function eachMap() {
          $(this).esMap('showFeature', data[0].boundary_geom, true);
        });
      });
    } else {
      $(this).esMap('clearFeature');
    }
  });

  $('.es-output-download').esDownload({});
  $('.es-output-dataGrid').esDataGrid({});
  $('.es-output-map').esMap({});
  $('.details-container').esDetailsPane({});
  $('.verification-buttons').esVerificationButtons({});
  $('.es-output-map').esMap('bindGrids');

  $('.es-filter-param, .user-filter, .permissions-filter').change(function eachFilter() {
    // Force map to update viewport for new data.
    $.each($('.es-output-map'), function eachMap() {
      this.settings.initialBoundsSet = false;
    });
    // Reload all sources.
    $.each(indiciaData.esSourceObjects, function eachSource() {
      // Reset to first page.
      this.settings.from = 0;
      this.populate();
    });
  });

});
