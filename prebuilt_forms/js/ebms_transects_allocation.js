
// Functions
var eta_prep, post, value_changed, _value_changed;
// Global Data
var form_options;

(function ($) {

  post = function(url, data) {
    $.ajax({
      type: 'POST',
      url: url,
      data: data,
      success: function(data) {
        if (typeof(data.error)!=="undefined") {
          alert(data.error);
        }},
      dataType: 'json',
    });
  };

  value_changed = function() {
    _value_changed(this);
  }

  _value_changed = function(elem) {
    $(elem).attr('disabled','disabled');
    $(elem).parent().addClass('waiting');
    // checkbox name : "TAC:<location id>:<attribute (CMS ID or Branch CMS ID)>:<value for attribute (CMS ID)>'
    var name  = $(elem).attr('name');
    var parts = name.split(':');
    if($(elem).filter(':checked').length) // we have just assigned the user: previously unassigned.
      // If the person was flagged as unassigned, then there is no existing attribute, so just create a new one.
      post(form_options.ajax_location_post_URL,
          {'website_id' : form_options.website_id,
           'location_id' : parts[1],
           'location_attribute_id' : parts[2],
           'int_value' : parts[3]});
    else {
      // If the person is flagged as assigned we delete all the existing attributes (set the value blank) - there may be more than one.
      // In postgres can't guarantee the order of returned data without an order by.
      // We can't add an order by to the subqueries which return the attribute value and attribute IDs
      // (for some reason postgres doesn't like it) so can't guarantee id and value match positions in array
      // Thus we have to look up attribute when we click.
      var attr_URL = form_options.base_url+'/index.php/services/data/location_attribute_value' +
          '?mode=json' +
          '&auth_token='+form_options.auth.read.auth_token+'&reset_timeout=true&nonce='+form_options.auth.read.nonce +
          '&location_id=' + parts[1] +
          '&location_attribute_id=' + parts[2] +
          '&value=' + parts[3];

        $.ajax({
            type: 'GET',
            url: attr_URL,
            success: function(adata){
          $.each(adata, function(idx, attribute){
            post(form_options.ajax_location_post_URL,
                {'website_id' : form_options.website_id,
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

  eta_prep = function(options) {

    form_options = options;

    $('#'+ form_options.allocation_select_id).change(function(){
      var found = false,
          i,
          dialog = $('<p>Please wait whilst this page is reloaded for the new allocation type.</p>').dialog({ title: 'Please Wait...', buttons: {"OK": function() { $(this).dialog('close'); } } }); // TODO i18n
          query = window.location.search.substr(1).split('&'); // cut off question mark before splitting
      if(window.location.search !== '')
        for(i = 0; i< query.length; i++) {
          if(query[i].substr(0,5) == 'type=') {
            found = true;
            query[i] = 'type='+$(this).val();
          }
        }
      if(!found)
        window.location.search += (window.location.search == '' ? '?' : '&')+'type='+$(this).val();
      else
        window.location.search = '?' + query.join('&');
    })

    var populateUsers = function () {
      var request = form_options.ajax_fetch_user_list_URL + "?region_id=" + $('#'+ form_options.region_select_id).val();
      $('#'+ form_options.user_select_id).addClass('ui-state-disabled').find('option').filter(function() {
          return this.value != "";
      }).remove();
      indiciaData.full_user_list=[];
      $.ajax({
          type: 'GET',
          url: request,
          success: function(adata){
            if(adata.length > 0) {
              $('#'+ form_options.user_select_id).removeClass('ui-state-disabled');
            }
            $.each(adata, function(idx, user){
              $('#'+ form_options.user_select_id).append('<option value="'+user[0]+'">'+user[1]+'</option>');
              indiciaData.full_user_list.push(user[0]);
            });
          },
          dataType: 'json',
      });
    }
    if ($('#'+ form_options.region_select_id).length>0) {
      $('#'+ form_options.region_select_id).change(populateUsers);
      populateUsers();
    }
    
    $('#'+ form_options.site_select_id+',#'+ form_options.user_select_id).change(function(){
      if(($('#'+ form_options.site_select_id).val()=='' || isNaN(parseInt($('#'+ form_options.site_select_id).val()))) &&
          $('#'+ form_options.user_select_id).val()=='')
        $('#'+ form_options.search_id).attr('disabled',true);
      else
        $('#'+ form_options.search_id).removeAttr('disabled');
      $('#site-count').html($('#'+ form_options.site_select_id + " option").filter(function(){return typeof $(this).attr('value') != 'undefined' && $(this).val() != ''}).length);
    })
    $('#'+ form_options.site_select_id).change();

    $('.'+form_options.select_all_class).click(function(){
      $('.'+form_options.select_all_class).addClass('waiting'); // attach to all buttons, as more than one.
      var x  = $('#'+form_options.grid_id+' input[name^=TAC]').not(':checked');
      if(x.length>0)
        x.each(function(idx,elem){
          $('.'+form_options.select_all_class).val(form_options.select_all_button + ' : ' + (idx+1)  + '/' + x.length);
          $(elem).attr('checked','checked');
          _value_changed(elem);
        });
      $('.'+form_options.select_all_class).removeClass('waiting').val(form_options.select_all_button);
    });

    $('.'+form_options.deselect_all_class).click(function(){
      var x  = $('#'+form_options.grid_id+' input[name^=TAC]').filter(':checked'); // attach to all buttons, as more than one.
      $('.'+form_options.deselect_all_class).addClass('waiting');
      if(x.length>0)
        x.each(function(idx,elem){
          $('.'+form_options.deselect_all_class).val(form_options.deselect_all_button + ' : ' + (idx+1)  + '/' + x.length);
          $(elem).removeAttr('checked');
          _value_changed(elem);
        });
      $('.'+form_options.deselect_all_class).removeClass('waiting').val(form_options.deselect_all_button);
    });

    $('#'+ form_options.search_id).click(function(){
      var type = form_options.config.attr_id,
          region = $('#'+ form_options.region_select_id).val(), // This may not exist
          location = $('#'+ form_options.site_select_id).val(),
          user = $('#'+ form_options.user_select_id).val(),
          report_URL = form_options.base_url+'/index.php/services/report/requestReport?report=' + form_options.lookup_report_name + '.xml' +
            '&reportSource=local' +
            '&mode=json' +
            '&auth_token='+form_options.auth.read.auth_token+'&reset_timeout=true&nonce='+form_options.auth.read.nonce +
            '&callback=?' +
            '&location_type_ids='+form_options.config.location_type_ids.join(',') +
            '&locattrs=' + type +
            '&orderby=name' +
            '&' + $.param(form_options.lookup_param_presets);

      if($(this).hasClass('waiting')) return;
      $(this).addClass('waiting');

      // either the site is filled in, or the user, or both
      // get all the locations which match the region/site filters.
      if(location !== '' && !isNaN(parseInt(location)))
        report_URL += '&location_id=' + location;
      if($('#'+ form_options.region_select_id).length > 0)
        report_URL += '&region_type_id='+form_options.config.region_control_location_type_id;
      else
        report_URL += '&region_type_id=0';
      if(region !== '' && !isNaN(parseInt(region)))
        report_URL += '&region_location_id='+region;

      $('#'+form_options.grid_id+' tbody').empty();

      if(region !== '' && !isNaN(parseInt(region)) && user !== '' && !isNaN(parseInt(user))) {
        $('.'+form_options.select_all_class+',.'+form_options.deselect_all_class).removeAttr('disabled');
      } else {
        $('.'+form_options.select_all_class+',.'+form_options.deselect_all_class).attr('disabled','disabled');
      }

      jQuery.getJSON(report_URL,
        function(rdata){
          var user = $('#'+ form_options.user_select_id).val(),
              user_list = [],
              has_region = $('#'+ form_options.region_select_id).length>0,
              alt_row = false,
              user_list_names = [];

          if(user !== '' && !isNaN(parseInt(user)))
            user_list.push(user);
          else
            $.each(indiciaData.full_user_list, function(i, e) {user_list.push(e.toString())});

            $.each(user_list, function(i, e) { // don't need to check for uniqueness here
            var user_name = $('#'+ form_options.user_select_id+" option").filter('[value='+e+']').text();
            // all users from either the control or the full list are CMS users, and have their name filled in, even if looking at the Indicia User ID
            user_list_names.push(user_name);
            });

          $.each(rdata, function(idx, location){
            // extend the myList for this site to include all users returned as allocated
            var allocated_users = (location['attr_location_'+type] !== null ? location['attr_location_'+type].replace(/\s+/g, '').split(',') : []),
                my_list_unique = user_list.concat([]),
                my_list_names = user_list_names.concat([]);
            if(my_list_unique.length > 1) // user filter field not specified
              $.each(allocated_users, function(i, e) {
                  if ($.inArray(e, my_list_unique) == -1) {
                    // at this point any additional people do not match with a CMS user, so have no name.
                    my_list_unique.push(e); // the index into these two match for a given user
                    my_list_names.push(form_options.config.user_prefix + +e);
                  }
              });
            $.each(my_list_unique, function(uidx, user){
              var row = $('<tr class="'+(alt_row ? form_options.alt_row_class : '')+'">' +
                    '<td>' + '<input name="TAC:' + location.location_id + ':' + type + ':' + user +
                      '" type="checkbox" '+(allocated_users.indexOf(user)>=0 ? 'checked="checked"' : '')+'></td>' +
                    (has_region ? '<td>'+location.region+'</td>' : '') +
                    '<td>'+location.location_id+'</td>' +
                    '<td>'+location.name+'</td>' +
                    '<td>'+location.centroid_sref+'</td>' +
                    '<td>'+my_list_names[uidx]+'</td>' +
                    '<td><a href="' + form_options.config.edit_link_path + location.location_id + '">Edit</a></td>' +
                    '</tr>');
              $('#'+form_options.grid_id+' tbody').append(row);
              row.find('input').click(value_changed)
              alt_row = !alt_row;
            });
          });
          $('#'+ form_options.search_id).removeClass('waiting');
        }
      );
    });

  }
}) (jQuery);
