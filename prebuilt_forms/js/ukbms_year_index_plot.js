
// Functions
var uspPrepChart, _get_week_number;
// Global Data
var reportOptions;

(function ($) {
	
	// Initially the chart is blank.
	uspPrepChart = function(options) {
		
		reportOptions = options;

		$('#'+reportOptions.species1SelectID+',#'+reportOptions.species2SelectID).attr('disabled',true);

		$('#loadButton').click(function(){
			var locationType = $('#'+ reportOptions.locationTypeSelectID).val();
			var location = $('#'+ reportOptions.locationSelectIDPrefix+'-'+locationType).val();

			if(locationType == '' || location == '' || $(this).hasClass('waiting-button'))
				return;

			$(this).addClass('waiting-button');
			$('#'+reportOptions.species1SelectID+',#'+reportOptions.species2SelectID).attr('disabled',true);

			reportOptions.loadedLocationType = locationType;
			reportOptions.loadedLocation = $('#'+ reportOptions.locationSelectIDPrefix+'-'+locationType+' option:selected').text();
			$('#currentlyLoaded').empty().append('LOADING : ' + reportOptions.loadedLocation);

			jQuery.getJSON(reportOptions.base_url+'/index.php/services/report/requestReport?report='+reportOptions.dataSource+'.xml' +
							'&reportSource=local&mode=json' +
							'&auth_token='+indiciaData.read.auth_token+'&reset_timeout=true&nonce='+indiciaData.read.nonce + 
							reportOptions.reportExtraParams +
							'&callback=?' +
//							'&year='+reportOptions.loadedYear+'&date_from='+reportOptions.loadedYear+'-01-01&date_to='+reportOptions.loadedYear+'-12-31' +
							'&location_type_id='+reportOptions.loadedLocationType+'&location_id='+location+
							'&locattrs=',
				function(rdata){
					reportOptions.values = []; // taxon->year
					reportOptions.species = [];
					$.each(rdata, function(idx, occurrence){
						if(typeof reportOptions.values[occurrence.taxon_meaning_id] == 'undefined') {
							reportOptions.species.push({'taxon': occurrence.taxon, 'preferred_taxon': occurrence.preferred_taxon, 'taxon_meaning_id':occurrence.taxon_meaning_id});
							reportOptions.values[occurrence.taxon_meaning_id] = [];
							for(var j=0; j<reportOptions.opts.axes.xaxis.ticks.length; j++) {
								reportOptions.values[occurrence.taxon_meaning_id][j] = 0;
							}
						}
						if(parseInt(occurrence[reportOptions.countOccAttr]) != occurrence[reportOptions.countOccAttr]) occurrence[reportOptions.countOccAttr] = 0;
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
		    
		$('#'+reportOptions.species1SelectID+',#'+reportOptions.species2SelectID).change(function(){
			var seriesData = [];

			reportOptions.opts.series = [];
			$('#'+reportOptions.id).empty(); // can we destroy?
			$('#'+reportOptions.id).data('jqplot','N');
			
			reportOptions.opts.title.text = reportOptions.loadedLocation;
			if($('#'+reportOptions.species1SelectID).val() != '') {
				reportOptions.opts.series.push({"show":true,"label":$('#'+reportOptions.species1SelectID+' option:selected').text(),"showlabel":true});
 				seriesData.push(reportOptions.values[$('#'+reportOptions.species1SelectID).val()]);
			}
			if($('#'+reportOptions.species2SelectID).val() != '') {
				reportOptions.opts.series.push({"show":true,"label":$('#'+reportOptions.species2SelectID+' option:selected').text(),"showlabel":true});
				seriesData.push(reportOptions.values[$('#'+reportOptions.species2SelectID).val()]);
			}
			if(seriesData.length == 0) return;
			reportOptions.seriesData = seriesData;
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
          // var placement = 'outsideGrid'; always the case here
          var location = 'ne';
          var width = $(window).width()
          if (width < 480) {
            scaling = 0;
            shadow = false;
            location = 's';
          } else if (width < 1024) {
            scaling = (width - 480) / (1024 - 480);
            location = 's';
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