//Send new plots to the warehouse, currently this is just a placeholder function.
function send_new_plots_to_warehouse(warehouseUrl,websiteId,plotNamesToProcess,plotSrefsToProcess,plotSrefSystemsToProcess) {
  alert('Sending the following new plots to the warehouse '+plotNamesToProcess.toSource());
}

//Send any new groups to the warehouse. Used for both plot and sample groups.
function send_new_groups_to_warehouse(warehouseUrl,websiteId,groupNamesToProcess,groupType,userId,personAttributeIdThatHoldsGroupsForUser) {
  alert('Sending the follow new '+groupType+' to the warehouse, the groups are, '+groupNamesToProcess.toSource());
  var i;
  for (i=0; i<groupNamesToProcess.length;i++) {
    jQuery.ajax({
      url: warehouseUrl+'index.php/services/plant_portal_import/create_new_group?name='+groupNamesToProcess[i]+'&groupType='+groupType+'&userId='+userId+'&personAttributeId='+personAttributeIdThatHoldsGroupsForUser,
      dataType: 'jsonp',
      success: function(response) {
      }
    });
  }
}