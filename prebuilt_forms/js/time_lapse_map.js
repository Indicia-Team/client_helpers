var iTLMOpts = {};
var iTLMData = {
  myData: {},
  mySpecies: {},
  last_displayed: -1,
  global_timer_function: false,
  maxDayIndex: 365, // Dec 31 on a leap year
  minDayIndex: 0, // first January
  species: ''
};

// for loops: Use of these is meant to prevent bugs.
// i = records
// j = days in year
// k = events
// m = species
// n = map controls
// p = features

// functions
var rgbvalue, applyJitter, setToDate, loadYear;

(function ($) {
  // See colorbrewer2.org
  var colourSequence = ['#e66101', '#5e3c99', '#fdb863', '#b2abd2'];
  var currentYear = function() {
    return $(iTLMOpts.yearControlSelector).val();
  };

  var currentSpecies = function() {
    var r;
    var doingSpeciesColours;
    if ($(iTLMOpts.speciesControlSelector).is('select')) {
      return $(iTLMOpts.speciesControlSelector).val();
    } else {
      // Clear and reset colors in correct sequence
      $(iTLMOpts.speciesControlSelector).find('.color-icon').css('background', 'none');
      r = [];
      // switch to colour by species when showing more than 1.
      doingSpeciesColours = $(iTLMOpts.speciesControlSelector + ' :checked').length > 1;
      $.each($(iTLMOpts.speciesControlSelector + ' :checked'), function(idx) {
        if (idx >= 4) {
          if (idx === 4) {
            alert('Up to 4 species can be shown at a time.');
          }
          $(this).removeAttr('checked');
        }
        r.push($(this).val());
        if (doingSpeciesColours) {
          $(this).next('.color-icon').css('background-color', colourSequence[idx]);
        }
      });
      return r;
    }
  }

  var stopAnimation = function () {
    if (iTLMData.global_timer_function)
      clearInterval(iTLMData.global_timer_function);
    iTLMData.global_timer_function = false;
  };

  var enableSpeciesControlOptions = function(year) {
    var existingSelectedSpecies;
    var colorIdx = 0;
    if ($(iTLMOpts.speciesControlSelector).is('select')) {
      existingSelectedSpecies = $(iTLMOpts.speciesControlSelector).val();
      // Single species mode. Clear existing species
      $(iTLMOpts.speciesControlSelector).find('option[value!=""]').remove();
      // Add options for available species
      $.each(iTLMData.mySpecies['year:' + year], function() {
        $(iTLMOpts.speciesControlSelector).append('<option value="' + this.id + '" >' + this.taxon + '</option>');
      });
      if (existingSelectedSpecies) {
        $(iTLMOpts.speciesControlSelector).find('option[value="' + existingSelectedSpecies + '"]').attr('selected', 'selected');
      }
    } else {
      // Single species mode. Clear existing species
      $(iTLMOpts.speciesControlSelector).find('li').remove();
      $(iTLMOpts.speciesControlSelector).find('.color-icon').css('background', 'none');
      // Add options for available species
      $.each(iTLMData.mySpecies['year:' + year], function() {
        $(iTLMOpts.speciesControlSelector).append(
            '<li><label><input type="checkbox" value="' + this.id + '" />' +
            '<span class="color-icon"></span>' +
            this.taxon + '</label></li>'
        );
        colorIdx++;
      });
    }
  };

  var loadDataOnDemand = function(species) {
    var year = $(iTLMOpts.yearControlSelector).val();
    if (iTLMOpts.preloadData === false) {
      // Always treat species as an array, so we can handle multiple or single with same code
      if (species !== null && species !== '' && !Array.isArray(species)) {
        species = [species];
      }
      $.each(species, function() {
        if (typeof iTLMData.myData['year:' + year]['species:' + this] === 'undefined') {
          loadYear(year, 'lh', species);
        }
      });
    }
  };

  var calculateMinAndMax = function() {
    var year = $(iTLMOpts.yearControlSelector).val();
    var species = currentSpecies();
    var date;
    // Always treat species as an array, so we can handle multiple or single with same code
    if (species !== null && species !== '' && !Array.isArray(species)) {
      species = [species];
    }
    if (!species) {
      return;
    }
    iTLMData.species = species;
    // Indexes will be days since Unix Epoch.
    iTLMData.minDayIndex = Math.floor((new Date()).getTime() / (24 * 60 * 60 * 1000));
    iTLMData.maxDayIndex = 0;
    // Loop to find first and last day, as we can't rely on grabbing first and last property in correct order
    $.each(species, function() {
      // Skip any species not yet loaded if loading on demand.
      if (typeof iTLMData.myData['year:' + year]['species:' + this] !== 'undefined') {
        $.each(iTLMData.myData['year:' + year]['species:' + this], function(idx) {
          var day = idx.replace('day:', '');
          iTLMData.minDayIndex = Math.min(day, iTLMData.minDayIndex);
          iTLMData.maxDayIndex = Math.max(day, iTLMData.maxDayIndex);
        });
      }
    });

    if (iTLMData.minDayIndex > 0) {
      iTLMData.minDayIndex--; // allow for day before data actually starts
    }
    var slider = $(iTLMOpts.timeControlSelector);
    $(iTLMOpts.timeControlSelector).slider('option', 'min', iTLMData.minDayIndex);
    $(iTLMOpts.timeControlSelector).slider('option', 'max', iTLMData.maxDayIndex);
    var diff = iTLMData.maxDayIndex - iTLMData.minDayIndex;
    var spacing = 100 / diff;
    slider.find('.ui-slider-tick-mark').remove();
    slider.find('.ui-slider-label').remove();
    var maxLabels = iTLMOpts.numberOfDateLabels;
    var maxTicks = 100;
    var daySpacing = diff === 0 ? 1 : Math.ceil(diff / maxTicks);
    var provisionalLabelSpacing = Math.max(7, Math.ceil(diff / maxLabels));
    var actualLabelSpacing = daySpacing * Math.ceil(provisionalLabelSpacing / daySpacing);

    for (var j = iTLMData.minDayIndex; j <= iTLMData.maxDayIndex; j += daySpacing) {
      if (j > iTLMData.minDayIndex && j < iTLMData.maxDayIndex)
        $('<span class=\"ui-slider-tick-mark' + (!((j - iTLMData.minDayIndex) % actualLabelSpacing) ? ' long' : '') + '\"></span>').css(
              'left', Math.round(spacing * (j - iTLMData.minDayIndex) * 10) / 10 + '%').appendTo(slider);

      if (!((j - iTLMData.minDayIndex) % actualLabelSpacing) && spacing * (j - iTLMData.minDayIndex) < 95) {
        date = new Date(j * 60 * 60 * 24 * 1000);
        $('<span class=\"ui-slider-label\"><span>' +
            date.getDate() + ' ' + iTLMOpts.monthNames[date.getMonth()] + ' ' + date.getFullYear() + '</span></span>').css(
            'left', Math.round(spacing * (j - iTLMData.minDayIndex) * 10) / 10 + '%').appendTo(slider);
      }
    }
    // if playing, leave playing, otherwise jump to end.
    if (!iTLMData.global_timer_function) {
      setToDate(-1);
      setToDate(iTLMData.maxDayIndex);
    } else {
      setLastDisplayed(getLastDisplayed());
    }
  }

  var getLastDisplayed = function () {
    if (iTLMData.last_displayed === -1) {
      iTLMData.last_displayed = iTLMData.minDayIndex-1;
    }
    return iTLMData.last_displayed;
  };

  var setLastDisplayed = function (idx) {
    iTLMData.last_displayed = idx;
    $(iTLMOpts.timeControlSelector).slider('option', 'value', idx);
  };

  var resetMap = function () {
    var last = getLastDisplayed();
    setToDate(-1);
    setToDate(last);
  };

  // init must be called before the maps are initialised, as it sets up a
  indiciaFns.initTimeLapseMap = function (options) {
    var defaults = {
      firstDateRGB: {r: 0, g: 0, b: 255}, // colour of first date displayed.
      lastDateRGB: {r: 255, g: 0, b: 0}, // colour of last date displayed.
      jitterRadius: 15000,
      timerDelay: 250, // milliseconds
      yearControlSelector: '#yearControl',
      speciesControlSelector: '#speciesControl',
      mapSelector: '#map',
      firstButtonSelector: '#beginning',
      lastButtonSelector: '#end',
      playButtonSelector: '#playMap',
      playButtonPlayLabel: 'play',
      playButtonPlayIcon: 'ui-icon-play',
      playButtonPauseLabel: 'pause',
      playButtonPauseIcon: 'ui-icon-pause',
      timeControlSelector: '#timeSlider',
      dotControlSelector: '#dotControl',
      dotControlMin: 2,
      dotControlMax: 5,
      dotSize: 3,
      errorDiv: '#errorMsg',
      pleaseSelectPrompt: 'Please select a Year / Species combination before playing',
      waitDialogText: 'Loading, please wait...',
      waitDialogTitle: 'Loading data',
      // waitDialogOK: 'OK',
      noMappableDataError: 'The report does not output any mappable data.',
      noDateError: 'The report does not output a date.',
      monthNames: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
      imgPath: '/'
    };
    iTLMOpts = $.extend({}, defaults, options);
    iTLMData.dotSize = iTLMOpts.dotSize;

    // Field change events:

    $(iTLMOpts.yearControlSelector).on('change', function (evt) {
      var year = $(evt.target).val();
      stopAnimation();
      loadYear(year, 'lh');
    });

    $(iTLMOpts.speciesControlSelector).on('change', function (evt) {
      stopAnimation();
      // Either a select (single species) or a checked checkbox triggers load.
      if ($(evt.target).is('select') || $(evt.target).is(':checked')) {
        loadDataOnDemand($(evt.target).val());
      }
      calculateMinAndMax();
      resetMap();
    });

    $('#acceptedOnlyControl').on('change', function acceptedChange() {
      calculateMinAndMax();
      resetMap();
    });

    $(iTLMOpts.playButtonSelector).on('click', function () {
      if (currentYear() === '' || iTLMData.species === '') {
        alert(iTLMOpts.pleaseSelectPrompt);
        return;
      }

      var caller = function () {
        var value = getLastDisplayed();
        if (value < iTLMData.maxDayIndex) {
          setToDate(value + 1);
        } else {
          stopAnimation();
          $(iTLMOpts.playButtonSelector).button('option', {label: iTLMOpts.playButtonPlayLabel, icons: {primary: iTLMOpts.playButtonPlayIcon}});
        }
      };

      var options;
      if (!iTLMData.global_timer_function) {
        var value = getLastDisplayed();
        if (value >= iTLMData.maxDayIndex)
          setToDate(iTLMData.minDayIndex);
        options = {label: iTLMOpts.playButtonPauseLabel, icons: {primary: iTLMOpts.playButtonPauseIcon}};
        iTLMData.global_timer_function = setInterval(caller, iTLMOpts.timerDelay);
      } else {
        stopAnimation();
        options = {label: iTLMOpts.playButtonPlayLabel, icons: {primary: iTLMOpts.playButtonPlayIcon}};
      }
      $(this).button('option', options);
    });

    $(iTLMOpts.firstButtonSelector).on('click', function () {
      stopAnimation();
      $(iTLMOpts.playButtonSelector).button('option', {label: iTLMOpts.playButtonPlayLabel, icons: {primary: iTLMOpts.playButtonPlayIcon}});
      setToDate(iTLMData.minDayIndex);
    });

    $(iTLMOpts.lastButtonSelector).on('click', function () {
      stopAnimation();
      $(iTLMOpts.playButtonSelector).button('option', {label: iTLMOpts.playButtonPlayLabel, icons: {primary: iTLMOpts.playButtonPlayIcon}});
      setToDate(iTLMData.maxDayIndex);
    });

    // Time control
    $(iTLMOpts.timeControlSelector).slider();
    $(iTLMOpts.timeControlSelector).slider({change: function (event, ui) {
        setToDate($(iTLMOpts.timeControlSelector).slider('value'));
      }});
    $(iTLMOpts.firstButtonSelector).button({text: false, icons: {primary: 'ui-icon-seek-start'}});
    $(iTLMOpts.playButtonSelector).button({text: false, icons: {primary: 'ui-icon-play'}});
    $(iTLMOpts.lastButtonSelector).button({text: false, icons: {primary: 'ui-icon-seek-end'}});

    // Dot size control
    $(iTLMOpts.dotControlSelector).slider();
    $(iTLMOpts.dotControlSelector).slider('option', 'min', iTLMOpts.dotControlMin);
    $(iTLMOpts.dotControlSelector).slider('option', 'max', iTLMOpts.dotControlMax);
    $(iTLMOpts.dotControlSelector).slider('option', 'value', iTLMData.dotSize);
    $(iTLMOpts.dotControlSelector).slider({change: function (event, ui) {
        iTLMData.dotSize = $(iTLMOpts.dotControlSelector).slider('value');
        if ($(iTLMOpts.mapSelector)[0].map.sitesLayer.features.length > 0) {
          var features = $(iTLMOpts.mapSelector)[0].map.sitesLayer.features;
          $(iTLMOpts.mapSelector)[0].map.sitesLayer.removeFeatures(features);
          for (p = 0; p < features.length; p++) {
            features[p].style.pointRadius = iTLMData.dotSize + 2;
          }
          $(iTLMOpts.mapSelector)[0].map.sitesLayer.addFeatures(features);
        }
        resetMap();
    }});

    mapInitialisationHooks.push(function (mapdiv) {
      var year = $(iTLMOpts.yearControlSelector).val();
      var eventsLayer = new OpenLayers.Layer.Vector('Events layer', {displayInLayerSwitcher: false});
      mapdiv.map.eventsLayer = eventsLayer;
      mapdiv.map.addLayer(eventsLayer);

      // switch off the mouse drag pan.
      for (var n = 0; n < mapdiv.map.controls.length; n++) {
        if (mapdiv.map.controls[n].CLASS_NAME === 'OpenLayers.Control.Navigation')
          mapdiv.map.controls[n].deactivate();
      }
      if ('#' + mapdiv.id === iTLMOpts.mapSelector) {
        if (iTLMOpts.preloadData) {
          loadYear(year, 'lh');
        } else {
          loadSpeciesOpts();
        }
      }
    });
  };

  loadYear = function (year, side, speciesId) {
    var loadingAll = typeof speciesId === 'undefined';
    if ((iTLMOpts.preloadData && typeof iTLMData.myData['year:' + year] !== 'undefined' && loadingAll)
      || (!loadingAll && typeof iTLMData.myData['year:' + year]['species:' + speciesId] !== 'undefined')) {
      if (loadingAll) {
        enableSpeciesControlOptions(year);
      }
      calculateMinAndMax();
      resetMap();
      return; // already loaded.
    }
    $(iTLMOpts.errorDiv).empty();
    $.fancyDialog({ title: iTLMOpts.waitDialogTitle, message: iTLMOpts.waitDialogText, cancelButton: null });
    var params = {
      report: iTLMOpts.report_name + '.xml',
      reportSource: 'local',
      mode: 'json',
      reset_timeout: true,
      quality: '!R',
      date_from: year === 'all' ? iTLMOpts.firstYear + '-01-01' : year + '-01-01',
      date_to: year === 'all' ? iTLMOpts.lastYear + '-12-31' : year + '-12-31',
      auth_token: indiciaData.read.auth_token,
      nonce: indiciaData.read.nonce,
      ...iTLMOpts.reportExtraParams
    };
    if (typeof speciesId === 'undefined') {
      iTLMData.myData['year:' + year] = {};
      iTLMData.mySpecies['year:' + year] = {};
    } else {
      params.species_id = speciesId;
    }
    // Report record should have geom, date, recordDayIndex (days since unix epoch), species ttl_id, attributes.
    $.ajax({
      url: indiciaData.warehouseUrl + 'index.php/services/report/requestReport',
      data: params,
      dataType: 'jsonp',
      crossDomain: true
    })
    .done(function (data) {
      var hasDate = false;
      var wktCol = false;
      var parser = new OpenLayers.Format.WKT();
      var donePoints = [];
      var wkt;
      var found;

      if (typeof data.records !== 'undefined') {
        if (data.records.length > 0) {
          // first isolate geometry column
          $.each(data.columns, function (column, properties) {
            if (column === 'created_by_id')
              canIDuser = true;
            if (column === 'date')
              hasDate = true;
            if (typeof properties.mappable !== 'undefined' && properties.mappable === 'true' && !wktCol)
              wktCol = column;
          });
          if (!wktCol)
            return $(iTLMOpts.errorDiv).append('<p>' + iTLMOpts.noMappableDataError + '</p>');
          if (!hasDate)
            return $(iTLMOpts.errorDiv).append('<p>' + iTLMOpts.noDateError + '</p>');
          $.each(data.records, function() {
            // remove point stuff: don't need to convert to numbers, as that was only to save space in php.
            wkt = this[wktCol].replace(/POINT\(/, '').replace(/\)/, '');

            if (typeof iTLMData.mySpecies['year:' + year]['species:' + this.species_id] === 'undefined') {
              iTLMData.mySpecies['year:' + year]['species:' + this.species_id] = {
                id: this.species_id,
                taxon: this.taxon
              };
            }
            if (typeof iTLMData.myData['year:' + year]['species:' + this.species_id] === 'undefined') {
              iTLMData.myData['year:' + year]['species:' + this.species_id] = {};
            }
            // Need to check that geom + species ID combination not already done at this point
            found = $.inArray(this.species_id + ':' + wkt, donePoints) !== -1;
            if (found) {
              return true; // continue to next iteration of records
            }
            donePoints.push(this.species_id + ':' + wkt);
            if (typeof iTLMData.myData['year:' + year]['species:' + this.species_id]['day:' + this.recordDayIndex] === "undefined") {
              iTLMData.myData['year:' + year]['species:' + this.species_id]['day:' + this.recordDayIndex] = {
                records: [],
                coords: [],
                acceptedCoords: [],
              };
            }
            iTLMData.myData['year:' + year]['species:' + this.species_id]['day:' + this.recordDayIndex].records.push(this);
            iTLMData.myData['year:' + year]['species:' + this.species_id]['day:' + this.recordDayIndex].coords.push(wkt);
            if (this.record_status === 'V') {
              iTLMData.myData['year:' + year]['species:' + this.species_id]['day:' + this.recordDayIndex].acceptedCoords.push(wkt);
            }
          });
          // Now, loop the data, for each species/day combination, build 1 big feature.
          $.each(iTLMData.myData['year:' + year], function(speciesTag, dayList) {
            $.each(dayList, function(idx) {
              var shapeType = this.coords.length === 1 ? 'POINT' : 'MULTIPOINT';
              this.feature = parser.read(shapeType + '(' + this.coords.join(',') + ')');
              this.feature.style = { fillOpacity: 0.8, strokeWidth: 0 };
              this.feature.attributes.dayIndex = idx.replace('day:', '');
              this.feature.attributes.acceptedOnly = 0;
              this.acceptedFeature = parser.read(shapeType + '(' + this.acceptedCoords.join(',') + ')');
              this.acceptedFeature.style = { fillOpacity: 0.8, strokeWidth: 0 };
              this.acceptedFeature.attributes.dayIndex = idx.replace('day:', '');
              this.acceptedFeature.attributes.acceptedOnly = 1;
            });
          });
        }
      } else if (typeof data.error !== 'undefined') {
        $(iTLMOpts.errorDiv).html('<p>Error Returned from warehouse report:<br>' + data.error + '<br/>' +
                (typeof data.code !== 'undefined' ? 'Code: ' + data.code + '<br/>' : '') +
                (typeof data.file !== 'undefined' ? 'File: ' + data.file + '<br/>' : '') +
                (typeof data.line !== 'undefined' ? 'Line: ' + data.line + '<br/>' : '') +
                // not doing trace
                '</p>');
      } else {
        $(iTLMOpts.errorDiv).html('<p>Internal Error: Format from report request not recognised.</p>');
      }
      if (loadingAll) {
        enableSpeciesControlOptions(year);
      }
      calculateMinAndMax();
      resetMap();
      $.fancybox.close();
    });
  };

  /**
   * If not preloading data, we still need to at least load the list of species to choose from.
   */
  loadSpeciesOpts = function() {
    iTLMData.myData['year:all'] = {};
    iTLMData.mySpecies['year:all'] = {};
    $.ajax({
      url: indiciaData.warehouseUrl + 'index.php/services/report/requestReport',
      data: {
        report: iTLMOpts.species_report_name + '.xml',
        reportSource: 'local',
        mode: 'json',
        reset_timeout: true,
        auth_token: indiciaData.read.auth_token,
        nonce: indiciaData.read.nonce,
        ...iTLMOpts.reportExtraParams
      },
      dataType: 'jsonp',
      crossDomain: true
    })
    .done(function (data) {
      if (typeof data.records !== 'undefined') {
        $.each(data.records, function() {
          if (typeof iTLMData.mySpecies['year:all']['species:' + this.species_id] === 'undefined') {
            iTLMData.mySpecies['year:all']['species:' + this.species_id] = {
              id: this.species_id,
              taxon: this.taxon
            };
          }
        });
      } else if (typeof data.error !== 'undefined') {
        $(iTLMOpts.errorDiv).html('<p>Error Returned from warehouse report:<br>' + data.error + '<br/>' +
                (typeof data.code !== 'undefined' ? 'Code: ' + data.code + '<br/>' : '') +
                (typeof data.file !== 'undefined' ? 'File: ' + data.file + '<br/>' : '') +
                (typeof data.line !== 'undefined' ? 'Line: ' + data.line + '<br/>' : '') +
                // not doing trace
                '</p>');
      } else {
        $(iTLMOpts.errorDiv).html('<p>Internal Error: Format from report request not recognised.</p>');
      }
      enableSpeciesControlOptions('all');
    });
  }

  rgbvalue = function (dateidx) {
    var r = parseInt(iTLMOpts.lastDateRGB.r * (dateidx - iTLMData.minDayIndex) / (iTLMData.maxDayIndex - iTLMData.minDayIndex) + iTLMOpts.firstDateRGB.r * (iTLMData.maxDayIndex - dateidx) / (iTLMData.maxDayIndex - iTLMData.minDayIndex));
    r = (r < 16 ? '0' : '') + r.toString(16);
    var g = parseInt(iTLMOpts.lastDateRGB.g * (dateidx - iTLMData.minDayIndex) / (iTLMData.maxDayIndex - iTLMData.minDayIndex) + iTLMOpts.firstDateRGB.g * (iTLMData.maxDayIndex - dateidx) / (iTLMData.maxDayIndex - iTLMData.minDayIndex));
    g = (g < 16 ? '0' : '') + g.toString(16);
    var b = parseInt(iTLMOpts.lastDateRGB.b * (dateidx - iTLMData.minDayIndex) / (iTLMData.maxDayIndex - iTLMData.minDayIndex) + iTLMOpts.firstDateRGB.b * (iTLMData.maxDayIndex - dateidx) / (iTLMData.maxDayIndex - iTLMData.minDayIndex));
    b = (b < 16 ? '0' : '') + b.toString(16);
    return '#' + r + g + b;
  };

  setToDate = function (idx) {
    var displayDay = function (idx) {
      var applyDay = function (day, layer, speciesIdx) {
        var featureName;
        if (typeof day !== 'undefined' && day !== false) {
          featureName = $('#acceptedOnlyControl').is(':checked') ? 'acceptedFeature' : 'feature';
          if (day[featureName]) {
            day[featureName].style.pointRadius = iTLMData.dotSize;
            if (iTLMData.species.length === 1) {
              day[featureName].style.fillColor = rgbvalue(idx);
            } else {
              day[featureName].style.fillColor = colourSequence[speciesIdx];
            }
            layer.addFeatures([day[featureName]]);
          }
        }
      };

      if (currentYear() !== '' && iTLMData.species !== '') {
        $.each(iTLMData.species, function(speciesIdx) {
          // Skip species not yet loaded.
          if (typeof iTLMData.myData['year:' + currentYear()]['species:' + this] !== 'undefined' &&
              typeof iTLMData.myData['year:' + currentYear()]['species:' + this]['day:' + idx] !== 'undefined') {
            applyDay(
              iTLMData.myData['year:' + currentYear()]['species:' + this]['day:' + idx],
              $(iTLMOpts.mapSelector)[0].map.eventsLayer,
              speciesIdx
            );
          }
        });
      }
    };

    var rmFeatures = function (layer, dayIdx) {
      var toRemove = [];
      for (var p = 0; p < layer.features.length; p++)
        if (layer.features[p].attributes.dayIndex > dayIdx)
          toRemove.push(layer.features[p]);
      if (toRemove.length > 0)
        layer.removeFeatures(toRemove);
    };

    var myDate = new Date(idx * 60 * 60 * 24 * 1000);
    $('#displayDate').html(myDate.getDate() + '/' + (myDate.getMonth() + 1) + '/' + myDate.getFullYear());

    if (currentYear() === '' || iTLMData.species === '')
      rmFeatures($(iTLMOpts.mapSelector)[0].map.eventsLayer, -1);
    if (idx !== getLastDisplayed()) {
      if (getLastDisplayed() > idx) {
        rmFeatures($(iTLMOpts.mapSelector)[0].map.eventsLayer, idx);
      } else {
        for (var j = getLastDisplayed() + 1; j <= idx; j++)
          displayDay(j);
      }
      setLastDisplayed(idx);
    }
  };
}(jQuery));