
// Functions
var uyipPrepChart, _get_week_number;
// Global Data
var reportOptions;

(function ($) {
	
	// Initially the chart is blank.
	uyipPrepChart = function(options) {
		
	    var parseBarTrendLineOptions = function(target, data, seriesDefaults, options, plot) {
	    	// copied from original which does this for line graphs.
	        if (this.renderer.constructor == $.jqplot.BarRenderer) {
	            this.trendline = new $.jqplot.Trendline();
	            options = options || {};
	            $.extend(true, this.trendline, {color:this.color}, seriesDefaults.trendline, options.trendline);
	            this.trendline.renderer.init.call(this.trendline, null);
	        }
	    }
	    var redrawTrendline = function() {
	    	// add new canvases to draw the trendlines on: normally put on the same canvas as the series
	    	// but subsequent series will go over top. Thias all done after rest of drawing done.
	    	var me = this;
	    	$.each(this.series, function(idx, series){
	            var canvas = new $.jqplot.GenericCanvas();
                canvas._plotDimensions = series.canvas._plotDimensions;
	            me.target.append(canvas.createElement(me._gridPadding, 'jqplot-series-trendline-canvas'));
                canvas.setContext();
	            for (j=0; j<$.jqplot.postDrawSeriesHooks.length; j++) {
	                $.jqplot.postDrawSeriesHooks[j].call(series, canvas._ctx, {});
	            }
	    	});
	    }

		reportOptions = options;

	    $.jqplot.postSeriesInitHooks.push(parseBarTrendLineOptions);
	    $.jqplot.postDrawHooks.push(redrawTrendline);
	    
		$('#'+reportOptions.species1SelectID+',#'+reportOptions.species2SelectID).attr('disabled',true);

		$('#loadButton').click(function(){
			var locationType = $('#'+ reportOptions.locationTypeSelectID).val();
			var location = $('#'+ reportOptions.locationSelectIDPrefix+'-'+locationType).val();
			var dataType = $('#'+ reportOptions.dataTypeSelectID).val();
			var dataSource = dataType == 'count' ? reportOptions.countDataSource : reportOptions.indexDataSource;

			if(locationType == '' || location == '' || $(this).hasClass('waiting-button'))
				return;

			$(this).addClass('waiting-button');
			$('#'+reportOptions.species1SelectID+',#'+reportOptions.species2SelectID).attr('disabled',true);

			reportOptions.loadedLocationType = locationType;
			reportOptions.loadedSurveyID = reportOptions.surveyMapping[locationType].survey_id;
			reportOptions.loadedLocation = $('#'+ reportOptions.locationSelectIDPrefix+'-'+locationType+' option:selected').text();
			reportOptions.loadedDataType = dataType;
			
			$('#currentlyLoaded').empty().append('LOADING : ' + reportOptions.loadedLocation);

			jQuery.getJSON(reportOptions.base_url+'/index.php/services/report/requestReport?report='+dataSource+'.xml' +
							'&reportSource=local&mode=json' +
							'&auth_token='+indiciaData.read.auth_token+'&reset_timeout=true&nonce='+indiciaData.read.nonce + 
							reportOptions[dataType+"ReportExtraParams"] +
							'&callback=?' +
							'&location_type_id='+reportOptions.loadedLocationType+'&location_id='+location+
							'&survey_id='+reportOptions.loadedSurveyID+
							'&locattrs=',
				function(rdata){
					reportOptions.values = []; // taxon->year->value
					reportOptions.species = [];
					$.each(rdata, function(idx, occurrence){
						if(typeof reportOptions.values[occurrence.taxon_meaning_id] == 'undefined') {
							reportOptions.species.push({'taxon': occurrence.taxon, 'preferred_taxon': occurrence.preferred_taxon, 'taxon_meaning_id':occurrence.taxon_meaning_id});
							reportOptions.values[occurrence.taxon_meaning_id] = [];
							for(var j=0; j<reportOptions.opts.axes.xaxis.ticks.length; j++) {
								reportOptions.values[occurrence.taxon_meaning_id][j] = 0;
							}
						}
						var val = parseInt(occurrence[reportOptions[reportOptions.loadedDataType+'CountField']]);
						if(val != occurrence[reportOptions[reportOptions.loadedDataType+'CountField']])
							val = 0;
						if(occurrence.year >= reportOptions.first_year && occurrence.year <= reportOptions.last_year)
							reportOptions.values[occurrence.taxon_meaning_id][occurrence.year-reportOptions.first_year] += val;
					});
							
					// Next sort out the species list drop downs.
					$('#'+reportOptions.species1SelectID+' option,#'+reportOptions.species2SelectID+' option').remove();
					if(reportOptions.species.length) {
						$('#'+reportOptions.species1SelectID+',#'+reportOptions.species2SelectID).append('<option value="">&lt;'+reportOptions.pleaseSelectMsg+'&gt;</option><option value="0">'+reportOptions.allSpeciesMsg+'</option>');
						reportOptions.species.sort(function (a, b) { return a.taxon.localeCompare(b.taxon); } );
						$('#'+reportOptions.species1SelectID+',#'+reportOptions.species2SelectID+',#'+reportOptions.weekNumSelectID).removeAttr('disabled');
						$.each(reportOptions.species, function(idx, species){
							$('#'+reportOptions.species1SelectID+',#'+reportOptions.species2SelectID).append('<option value="'+species.taxon_meaning_id+'" title="'+(species.taxon != species.preferred_taxon ? species.preferred_taxon : '')+'">'+species.taxon+'</option>');
						});
						// reportOptions.species.unshift({'taxon': reportOptions.allSpeciesMsg, 'preferred_taxon': reportOptions.allSpeciesMsg, 'taxon_meaning_id':0});
						reportOptions.values[0] = [];
						for(var j=0; j<reportOptions.opts.axes.xaxis.ticks.length; j++) {
							reportOptions.values[0][j] = 0;
							$.each(reportOptions.species, function(idx, species){
								reportOptions.values[0][j] += reportOptions.values[species.taxon_meaning_id][j];
							});
						}
					} else {
						$('#'+reportOptions.species1SelectID+',#'+reportOptions.species2SelectID).append('<option value="">&lt;'+reportOptions.noDataMsg+'&gt;</option>');
					}
					$('#currentlyLoaded').empty().append(reportOptions.dataLoadedMsg + ' : ' + reportOptions.loadedLocation);
					$('#loadButton').removeClass('waiting-button');
				}
			);
		});
		    
		$('#'+reportOptions.species1SelectID+',#'+reportOptions.species2SelectID+',#add-trendline').change(function(){
			var seriesData = [];
			var lineRenderer;
			var series;

			reportOptions.opts.series = [];
			$('#'+reportOptions.id).empty(); // can we destroy?
			$('#'+reportOptions.id).data('jqplot','N');
			
			reportOptions.opts.title.text = reportOptions.loadedLocation;
			if($('#'+reportOptions.species1SelectID).val() != '') {
				seriesData.push(reportOptions.values[$('#'+reportOptions.species1SelectID).val()]);
				reportOptions.opts.series.push({"show":true,
												"label":$('#'+reportOptions.species1SelectID+' option:selected').text(),
												"showlabel":true,
												"trendline":{"label":$('#'+reportOptions.species1SelectID+' option:selected').text()+" Trendline",
													"show": $('#add-trendline:checked').length > 0,
													"color": "#ff0000"}});
			}
			if($('#'+reportOptions.species2SelectID).val() != '') {
				reportOptions.opts.series.push({"show":true,
												"label":$('#'+reportOptions.species2SelectID+' option:selected').text(),
												"showlabel":true,
												"trendline":{"label":$('#'+reportOptions.species2SelectID+' option:selected').text()+" Trendline",
													"show": $('#add-trendline:checked').length > 0,
													"color": "#0000ff"}});
				seriesData.push(reportOptions.values[$('#'+reportOptions.species2SelectID).val()]);
			}
			if(seriesData.length == 0) return;
			reportOptions.seriesData = seriesData;
			reportOptions.opts.seriesColors = ['#ff6060', '#6060ff'];
			$.jqplot(reportOptions.id, reportOptions.seriesData, reportOptions.opts);
			$('#'+reportOptions.id).data('jqplot','Y');
        });
		
		$('#'+ reportOptions.locationTypeSelectID).change(function(){
			$('.location-select').hide();
			$('#'+ reportOptions.locationSelectIDPrefix + '-' + $(this).val()).show();
		});
		$('#'+ reportOptions.locationTypeSelectID).change();
		
		$(window).resize(function(){
    		// Calculate scaling factor to alter dimensions according to width.
		  if(typeof $('#'+reportOptions.id).data('jqplot') == 'undefined' ||
				  $('#'+reportOptions.id).data('jqplot') != 'Y')
			  return;
          var scaling = 1;
          var shadow = true;
          // leave placement as defined by form.
          var location = 'ne';
          var width = $(window).width()
          if (width < 480) {
            scaling = 0;
            shadow = false;
            location = (reportOptions.opts.legend.placement == 'outsideGrid' ? 's' : 'n');
          } else if (width < 1024) {
            scaling = (width - 480) / (1024 - 480);
            location = (reportOptions.opts.legend.placement == 'outsideGrid' ? 's' : 'n');
          }
          reportOptions.opts.legend.location = location;  
          reportOptions.opts.seriesDefaults.rendererOptions.shadow = shadow;
       	  reportOptions.opts.seriesDefaults.rendererOptions.shadowWidth = scaling * 3;
       	  reportOptions.opts.seriesDefaults.rendererOptions.barMargin = 8 * scaling + 2;

		  $('#'+reportOptions.id).empty(); // can we destroy?
		  var plot = $.jqplot(reportOptions.id, reportOptions.seriesData, reportOptions.opts);
          // can't use replot as moving the legend about.
        });
	}	
}) (jQuery);