
// Functions
var etaPrep;
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
		$(this).attr('disabled','disabled');
		$(this).parent().addClass('waiting');
		// checkbox name : "TAC:<location id>:<value for attribute (CMS ID)>'
		var name  = $(this).attr('name');
		var parts = name.split(':');
		if($(this).filter(':checked').length) // we have just assigned the user: previously unassigned.
			// If the person was flagged as unassigned, then there is no existing attribute, so just create a new one.
			syncPost(formOptions.ajaxFormPostUrl,
					{'website_id' : formOptions.website_id,
					 'location_id' : parts[1],
					 'location_attribute_id' : formOptions.assignment_attr_id,
					 'int_value' : parts[2]});
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
					'&location_attribute_id=' + formOptions.assignment_attr_id
					'&value=' + parts[2];

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
		$(this).parent().removeClass('waiting');
		$(this).removeAttr('disabled');
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

		$('#'+ formOptions.searchID).click(function(){
			var country = $('#'+ formOptions.countrySelectID).val();
			var location = $('#'+ formOptions.siteSelectID).val();
			var user = $('#'+ formOptions.userSelectID).val();

			var reportURL = formOptions.base_url+'/index.php/services/report/requestReport?report=reports_for_prebuilt_forms/UKBMS/ebms_country_locations.xml' + //TODO convert report to form argument
						'&reportSource=local' +
						'&mode=json' +
						'&auth_token='+formOptions.auth.read.auth_token+'&reset_timeout=true&nonce='+formOptions.auth.read.nonce + 
						'&callback=?' +
						'&location_type_id='+formOptions.site_location_type_id +
						'&country_type_id='+formOptions.country_location_type_id +
						'&locattrs=' + formOptions.assignment_attr_id +
						'&orderby=name';

			if($(this).hasClass('waiting')) return;
			$(this).addClass('waiting');

			// either the site is filled in, or the user, or both
			// get all the locations which match the country/site filters.
			if(location !== '' && !isNaN(parseInt(location)))
				reportURL += '&location_id=' + location;
			if(country !== '' && !isNaN(parseInt(country)))
				reportURL += '&country_location_id='+country;

			$('#report-table-summary tbody').empty();

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
						var allocatedUsers = location['attr_location_'+formOptions.assignment_attr_id]
						allocatedUsers = (allocatedUsers !== null ? allocatedUsers.replace(/\s+/g, '').split(',') : []);
						// extend the userList for this site to include all users returned as allocated
						var myList = (userList.length>1 ? userList.concat(allocatedUsers) : userList);
						var myListUnique = [];
					    $.each(myList, function(i, e) {
					        if ($.inArray(e, myListUnique) == -1) myListUnique.push(e);
					    });
						// TODO ensure myList unique
						$.each(myListUnique, function(uidx, user){
							var row = $('<tr class="'+(altRow ? formOptions.altRowClass : '')+'"/>');
							var userName = $('#'+ formOptions.userSelectID+" option").filter('[value='+user+']');
							
							userName = (userName.length > 0 ? userName.text() : "CMS User "+user);
							row.append('<td><input name="TAC:' +
									location.location_id + ':' + user +
									'" type="checkbox" '+(allocatedUsers.indexOf(user)>=0 ? 'checked="checked"' : '')+'></td>');
							row.append('<td>'+location.country+'</td>');
							row.append('<td>'+location.name+'</td>');
							row.append('<td>'+location.centroid_sref+'</td>');
							row.append('<td>'+userName+'</td>');
							row.append('<td><a href="' + formOptions.editLinkPath + '?location_id=' + location.location_id + '">Edit</a></td>');
							$('#report-table-summary tbody').append(row);
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