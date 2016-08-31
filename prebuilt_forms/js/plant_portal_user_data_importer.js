function send_new_plots_to_warehouse(warehouseUrl,websiteId,plotNamesToProcess,plotSrefsToProcess,plotSrefSystemsToProcess) {
  alert('Sending the following new plots to the warehouse '+plotNamesToProcess.toSource());
}

function send_new_groups_to_warehouse(warehouseUrl,websiteId,groupNamesToProcess,groupTermlistId) {
  alert('Sending the follow new groups to the warehouse termlist id '+groupTermlistId+' groups are, '+groupNamesToProcess.toSource());
  /*var i;
  var rawNamesArray=[];
  for (i=0; i<groupsToProcess.length;i++) {
    rawNamesArray[i]=groupsToProcess[i];
  }
  uploadNewGroup(warehouseUrl,rawNamesArray,groupTermlistId);*/
}

uploadNewGroup = function(warehouseUrl,rawNamesArray,groupTermlistId) {
  /*var i;
  for (i=0; i<rawNamesArray.length;i++) {
    alert(rawNamesArray[i]);
    jQuery.ajax({
      url: warehouseUrl+'index.php/services/plant_portal_import/create_new_group?name='+rawNamesArray[i]+'&termlist_id='+groupTermlistId,
      dataType: 'jsonp',
      success: function(response) {
        //$('#progress-bar').progressbar ('option', 'value', response.progress);
      }
    });
  }*/
}