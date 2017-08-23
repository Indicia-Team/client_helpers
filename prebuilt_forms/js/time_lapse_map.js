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
  var currentYear = function() {
    return $(iTLMOpts.yearControlSelector).val();
  };
  
  var stopAnimation = function () {
    if (iTLMData.global_timer_function)
      clearInterval(iTLMData.global_timer_function);
    iTLMData.global_timer_function = false;
  };
  
  var enableSpeciesControlOptions = function(year) {
    var species = $(iTLMOpts.speciesControlSelector).val();
    // clear existing species
    $(iTLMOpts.speciesControlSelector).find('option[value!=""]').remove();
    // Add options for available species
    $.each(iTLMData.mySpecies['year:' + year], function() {
      $(iTLMOpts.speciesControlSelector).append('<option value="' + this.id + '" >' + this.taxon + '</option>');
    });
    if (species) {
      $(iTLMOpts.speciesControlSelector).find('option[value="' + species + '"]').attr('selected', 'selected');
    }
  };
  
  var calculateMinAndMax = function() {
    var year = $(iTLMOpts.yearControlSelector).val();
    var species = $(iTLMOpts.speciesControlSelector).val();
    var date;
    if (!species) {
      return;
    }
    iTLMData.species = species;
    // Indexes will be days since Unix Epoch.
    iTLMData.minDayIndex = Math.floor((new Date()).getTime() / (24 * 60 * 60 * 1000));
    iTLMData.maxDayIndex = 0;
    // Loop to find first and last day, as we can't rely on grabbing first and last property in correct order
    $.each(iTLMData.myData['year:' + year]['species:' + species], function(idx) {
      var day = idx.replace('day:', '');
      iTLMData.minDayIndex = Math.min(day, iTLMData.minDayIndex);
      iTLMData.maxDayIndex = Math.max(day, iTLMData.maxDayIndex);
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
    var maxLabels = 11; // TODO ".(isset($args['numberOfDates']) && $args['numberOfDates'] > 1 ? $args['numberOfDates'] : 11).";
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
      mapContainerClass: 'mapContainers',
      indicia_user_id: false,
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
      waitDialogText: 'Please wait whilst the data for {year} is loaded.',
      waitDialogTitle: 'Loading Data...',
      // waitDialogOK: 'OK',
      noMappableDataError: 'The report does not output any mappable data.',
      noDateError: 'The report does not output a date.',
      monthNames: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
      imgPath: '/'
    };
    iTLMOpts = $.extend({}, defaults, options);
    iTLMData.dotSize = iTLMOpts.dotSize;

    // Field change events:

    $(iTLMOpts.yearControlSelector).change(function (evt) {
      var year = $(evt.target).val();
      stopAnimation();
      loadYear(year, 'lh');
    });

    $(iTLMOpts.speciesControlSelector).change(function (evt) {
      stopAnimation();
      calculateMinAndMax();
      resetMap();
    });

    $(iTLMOpts.playButtonSelector).click(function () {
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

    $(iTLMOpts.firstButtonSelector).click(function () {
      stopAnimation();
      $(iTLMOpts.playButtonSelector).button('option', {label: iTLMOpts.playButtonPlayLabel, icons: {primary: iTLMOpts.playButtonPlayIcon}});
      setToDate(iTLMData.minDayIndex);
    });

    $(iTLMOpts.lastButtonSelector).click(function () {
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
          for (p = 0; p < features.length; p++)
            features[p].style.pointRadius = iTLMData.dotSize + 2;
          $(iTLMOpts.mapSelector)[0].map.sitesLayer.addFeatures(features);
        }
        resetMap();
    }});

    mapInitialisationHooks.push(function (mapdiv) {
      var year = $(iTLMOpts.yearControlSelector).val();
      mapdiv.map.eventsLayer = new OpenLayers.Layer.Vector('Events Layer', {displayInLayerSwitcher: false});
      mapdiv.map.addLayer(mapdiv.map.eventsLayer);
      // switch off the mouse drag pan.
      for (var n = 0; n < mapdiv.map.controls.length; n++) {
        if (mapdiv.map.controls[n].CLASS_NAME === 'OpenLayers.Control.Navigation')
          mapdiv.map.controls[n].deactivate();
      }
      if ('#' + mapdiv.id === iTLMOpts.mapSelector) {
        loadYear(year, 'lh');
      }
    });
  };

  loadYear = function (year, side) {
    var dateFilter = (year === 'all' ? '&date_from=' + iTLMOpts.firstYear + '-01-01' : '&date_from=' + year + '-01-01&date_to=' + year + '-12-31');
    if (typeof iTLMData.myData['year:' + year] !== 'undefined') {
      enableSpeciesControlOptions(year);
      calculateMinAndMax();
      resetMap();
      return; // already loaded.
    }
    iTLMData.myData['year:' + year] = {};
    iTLMData.mySpecies['year:' + year] = {};
    $(iTLMOpts.errorDiv).empty();
    dialog = $('<p>' + iTLMOpts.waitDialogText.replace('{year}', year) + '</p>').dialog({
      title: iTLMOpts.waitDialogTitle, 
      buttons: {OK: function () {
        dialog.dialog('close');
      }}
    });
    // Report record should have geom, date, recordDayIndex (days since unix epoch), species ttl_id, attributes.
    jQuery.getJSON(iTLMOpts.base_url + '/index.php/services/report/requestReport?report=' + iTLMOpts.report_name + '.xml&reportSource=local&mode=json' +
        '&auth_token=' + iTLMOpts.auth_token + '&reset_timeout=true&nonce=' + iTLMOpts.nonce + iTLMOpts.reportExtraParams +
        '&callback=?' + dateFilter,
      function (data) {
        var hasDate = false;
        var wktCol = false;
        var parser = new OpenLayers.Format.WKT();
        var donePoints = [];
        var wkt;

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
                iTLMData.myData['year:' + year]['species:' + this.species_id] = {};
                iTLMData.mySpecies['year:' + year]['species:' + this.species_id] = {
                  id: this.species_id,
                  taxon: this.taxon
                };
              }
              // Need to check that geom + species ID combination not already done at this point
              found = $.inArray(this.species_id + ':' + wkt, donePoints) !== -1;
              if (found) {
                return true; // continue to next iteration of records
              } else {
                donePoints.push(this.species_id + ':' + wkt);
              }
              if (typeof iTLMData.myData['year:' + year]['species:' + this.species_id]['day:' + this.recordDayIndex] === "undefined") {
                iTLMData.myData['year:' + year]['species:' + this.species_id]['day:' + this.recordDayIndex] = {records: [], coords: []};
              }
              iTLMData.myData['year:' + year]['species:' + this.species_id]['day:' + this.recordDayIndex].records.push(this);
              iTLMData.myData['year:' + year]['species:' + this.species_id]['day:' + this.recordDayIndex].coords.push(wkt);
            });
            // Now, loop the data, for each species/day combination, build 1 big feature.
            $.each(iTLMData.myData['year:' + year], function(speciesTag, dayList) {
              $.each(dayList, function(idx) {
                var shapeType = this.coords.length === 1 ? 'POINT' : 'MULTIPOINT';
                this.feature = parser.read(shapeType + '(' + this.coords.join(',') + ')');
                this.feature.style = {fillOpacity: 0.8, strokeWidth: 0};
                this.feature.attributes.dayIndex = idx.replace('day:', '');
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
        enableSpeciesControlOptions(year);
        calculateMinAndMax();
        resetMap();
        dialog.dialog('close');
      });
  };

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
      var applyJitter = function (layer, feature) {
        var X = iTLMOpts.jitterRadius + 1;
        for (var p = 0; p < layer.features.length; p++)
          X = Math.min(X, feature.geometry.distanceTo(layer.features[p].geometry));
        if (!feature.attributes.jittered && X < iTLMOpts.jitterRadius) {
          feature.attributes.jittered = true;
          var angle = Math.random() * Math.PI * 2;
          feature.geometry.move(iTLMOpts.jitterRadius * Math.cos(angle), iTLMOpts.jitterRadius * Math.sin(angle));
        }
      };

      var applyDay = function (day, layer) {
        if (typeof day !== 'undefined' && day !== false) {
          if (day.feature) {
            applyJitter(layer, day.feature);
            day.feature.style.pointRadius = iTLMData.dotSize;
            day.feature.style.fillColor = rgbvalue(idx);
            layer.addFeatures([day.feature]);
          }
        }
      };

      if (currentYear() !== '' && iTLMData.species !== '' && 
          typeof iTLMData.myData['year:' + currentYear()]['species:' + iTLMData.species]['day:' + idx] !== "undefined") {
        applyDay(
            iTLMData.myData['year:' + currentYear()]['species:' + iTLMData.species]['day:' + idx],
            $(iTLMOpts.mapSelector)[0].map.eventsLayer
        );
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