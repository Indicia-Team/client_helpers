
// Functions
var etaPrep, syncPost, valueChanged, _valueChanged;
// Global Data
var formOptions;

(function ($) {
	
  syncPost = function(url, data) {
    $.ajax({
      type: 'POST',
	  url: url,
	  data: data,
	  success: function(data) {
		        if (typeof(data.error)!=="undefined") {
		          alert(data.error);
		        }},
	  dataType: 'json',
	  async: false
    });
  };

	valueChanged = function() {
		_valueChanged(this);
	}
	
	_valueChanged = function(elem) {
		$(elem).attr('disabled','disabled');
		$(elem).parent().addClass('waiting');
		// checkbox name : "TAC:<location id>:<attribute (CMS ID or Branch CMS ID)>:<value for attribute (CMS ID)>'
		var name  = $(elem).attr('name');
		var parts = name.split(':');
		if($(elem).filter(':checked').length) // we have just assigned the user: previously unassigned.
			// If the person was flagged as unassigned, then there is no existing attribute, so just create a new one.
			syncPost(formOptions.ajaxFormPostUrl,
					{'website_id' : formOptions.website_id,
					 'location_id' : parts[1],
					 'location_attribute_id' : parts[2],
					 'int_value' : parts[3]});
		else {
			// If the person is flagged as assigned we delete all the existing attributes (set the value blank) - there may be more than one.
			// In postgres can't guarantee the order of returned data without an order by.
			// We can't add an order by to the subqueries which return the attribute value and attribute IDs
			// (for some reason postgres doesn't like it) so can't guarantee id and value match positions in array
			// Thus we have to look up attribute when we click.
			var attrURL = formOptions.base_url+'/index.php/services/data/location_attribute_value' +
					'?mode=json' +
					'&auth_token='+formOptions.auth.read.auth_token+'&reset_timeout=true&nonce='+formOptions.auth.read.nonce + 
					'&location_id=' + parts[1] +
					'&location_attribute_id=' + parts[2] +
					'&value=' + parts[3];

		    $.ajax({
		        type: 'GET',
		        url: attrURL,
		        success: function(adata){
					$.each(adata, function(idx, attribute){
						syncPost(formOptions.ajaxFormPostUrl,
								{'website_id' : formOptions.website_id,
								 'id' : attribute.id,
								 'location_id' : attribute.location_id,
								 'location_attribute_id' : attribute.location_attribute_d,
								 'int_value' : '',
								 'deleted' : 't'});
					});
		        },
		  	  	dataType: 'json',
		  	  	async: false
		    });
		}
		$(elem).parent().removeClass('waiting');
		$(elem).removeAttr('disabled');
	}
	
	etaPrep = function(options) {
		
		formOptions = options;
	    
		$('#'+ formOptions.siteSelectID+',#'+ formOptions.userSelectID).change(function(){
			if(($('#'+ formOptions.siteSelectID).val()=='' || isNaN(parseInt($('#'+ formOptions.siteSelectID).val()))) &&
					$('#'+ formOptions.userSelectID).val()=='')
				$('#'+ formOptions.searchID).attr('disabled',true);
			else
				$('#'+ formOptions.searchID).removeAttr('disabled');
		})
		$('#'+ formOptions.siteSelectID).change();

		$('.'+formOptions.selectAllClass).click(function(){
			$('.'+formOptions.selectAllClass).addClass('waiting'); // attach to all buttons, as more than one.
			var x  = $('#'+formOptions.gridID+' input[name^=TAC]').not(':checked');
			if(x.length>0)
				x.each(function(idx,elem){
					$('.'+formOptions.selectAllClass).val(formOptions.selectAllButton + ' : ' + (idx+1)  + '/' + x.length);
					$(elem).attr('checked','checked');
					_valueChanged(elem);
				});
			$('.'+formOptions.selectAllClass).removeClass('waiting').val(formOptions.deselectAllButton);
		});

		$('.'+formOptions.deselectAllClass).click(function(){
			var x  = $('#'+formOptions.gridID+' input[name^=TAC]').filter(':checked'); // attach to all buttons, as more than one.
			$('.'+formOptions.deselectAllClass).addClass('waiting');
			if(x.length>0)
				x.each(function(idx,elem){
					$('.'+formOptions.deselectAllClass).val(formOptions.deselectAllButton + ' : ' + (idx+1)  + '/' + x.length);
					$(elem).removeAttr('checked');
					_valueChanged(elem);
				});
			$('.'+formOptions.deselectAllClass).removeClass('waiting').val(formOptions.deselectAllButton);
		});

		$('#'+ formOptions.searchID).click(function(){
			var type = $('#'+ formOptions.allocationSelectID).val();
			var typetext = $('#'+ formOptions.allocationSelectID).find('option[value='+type+']').text();
			if(typetext != "") typetext = '<label>'+typetext+'</label>';
			var country = $('#'+ formOptions.countrySelectID).val();
			var location = $('#'+ formOptions.siteSelectID).val();
			var user = $('#'+ formOptions.userSelectID).val();

			var reportURL = formOptions.base_url+'/index.php/services/report/requestReport?report=reports_for_prebuilt_forms/UKBMS/ebms_country_locations.xml' +
						'&reportSource=local' +
						'&mode=json' +
						'&auth_token='+formOptions.auth.read.auth_token+'&reset_timeout=true&nonce='+formOptions.auth.read.nonce + 
						'&callback=?' +
						'&location_type_id='+formOptions.site_location_type_id +
						'&country_type_id='+formOptions.country_location_type_id +
						'&locattrs=' + type +
						'&orderby=name';

			if($(this).hasClass('waiting')) return;
			$(this).addClass('waiting');

			// either the site is filled in, or the user, or both
			// get all the locations which match the country/site filters.
			if(location !== '' && !isNaN(parseInt(location)))
				reportURL += '&location_id=' + location;
			if(country !== '' && !isNaN(parseInt(country)))
				reportURL += '&country_location_id='+country;

			$('#'+formOptions.gridID+' tbody').empty();

			if(country !== '' && !isNaN(parseInt(country)) && user !== '' && !isNaN(parseInt(user))) {
				$('.'+formOptions.selectAllClass+',.'+formOptions.deselectAllClass).removeAttr('disabled');
			} else {
				$('.'+formOptions.selectAllClass+',.'+formOptions.deselectAllClass).attr('disabled','disabled');
			}

			jQuery.getJSON(reportURL,
				function(rdata){
					var user = $('#'+ formOptions.userSelectID).val();
					var userList = [];
					if(user !== '' && !isNaN(parseInt(user)))
						userList.push(user);
					else
						// userList = indiciaData.fullUserList;
						userList = ["1","2"];
					var altRow = false;
					$.each(rdata, function(idx, location){
						var allocatedUsers = location['attr_location_'+type]
						allocatedUsers = (allocatedUsers !== null ? allocatedUsers.replace(/\s+/g, '').split(',') : []);
						// extend the userList for this site to include all users returned as allocated
						var myList = (userList.length>1 ? userList.concat(allocatedUsers) : userList);
						var myListUnique = [];
					    $.each(myList, function(i, e) {
					        if ($.inArray(e, myListUnique) == -1) myListUnique.push(e);
					    });
						$.each(myListUnique, function(uidx, user){
							var row = $('<tr class="'+(altRow ? formOptions.altRowClass : '')+'"/>');
							var userName = $('#'+ formOptions.userSelectID+" option").filter('[value='+user+']');

							userName = (userName.length > 0 ? userName.text() : "CMS User "+user);
							row.append('<td>' + typetext + '<input name="TAC:' +
									location.location_id + ':' + type + ':' + user +
									'" type="checkbox" '+(allocatedUsers.indexOf(user)>=0 ? 'checked="checked"' : '')+'></td>');
							row.append('<td>'+location.country+'</td>');
							row.append('<td>'+location.name+'</td>');
							row.append('<td>'+location.centroid_sref+'</td>');
							row.append('<td>'+userName+'</td>');
							row.append('<td><a href="' + formOptions.editLinkPath + '?location_id=' + location.location_id + '">Edit</a></td>');
							$('#'+formOptions.gridID+' tbody').append(row);
							row.find('input').click(valueChanged)
							altRow = !altRow;
						});						
					});						
					$('#'+ formOptions.searchID).removeClass('waiting');
				}
			);
		});
	
	}	
}) (jQuery);