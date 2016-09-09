//Send new plots to the warehouse, currently this is just a placeholder function.
function send_new_plots_to_warehouse(warehouseUrl,websiteId,plotNamesToProcess,plotSrefsToProcess,plotSrefSystemsToProcess) {
  alert('Sending the following new plots to the warehouse '+plotNamesToProcess.toSource());
}

//Send any new groups to the warehouse. Used for both plot and sample groups.
function send_new_groups_to_warehouse(warehouseUrl,websiteId,groupNamesToProcess,groupType,userId,personAttributeIdThatHoldsGroupsForUser) {
  alert('Sending the follow new '+groupType+' to the warehouse, the groups are, '+groupNamesToProcess.toSource());
  var i;
  //To improve efficiency we send the new groups in chunks to the warehouse. e.g. this might be 5 at a time comma separated
  var groupNamesToProcessChunks=[];
  var groupNamesToProcessChunksIdx = 0;
  //Track number of groups in a chunk
  var chunkSizeCounter=1;
  //Cycle through each group name.
  //We add it to the chunk, if we detect the number of items in the chunk is now too great we start a new chunk and reset the counter that checks its size
  for (i=0; i<groupNamesToProcess.length;i++) {
    if (groupNamesToProcessChunks[groupNamesToProcessChunksIdx]) {
      groupNamesToProcessChunks[groupNamesToProcessChunksIdx]=groupNamesToProcessChunks[groupNamesToProcessChunksIdx]+','+groupNamesToProcess[i];
    } else {
       groupNamesToProcessChunks[groupNamesToProcessChunksIdx]=groupNamesToProcess[i];
    }

    //If chunk gets too big start a new chunk and rest the chunk size counter to 0
    //Currently the chunks are of 5 names, this can be altered by changing this number.
    if (chunkSizeCounter>=5) {
      chunkSizeCounter=1;
      groupNamesToProcessChunksIdx++;
    } else {
      //Each time we add a group to a chunk, then increase the counter which tracks the size of the chunk
      chunkSizeCounter++;
    }
  }
  //Send each chunk to the warehouse
  for (i=0; i<groupNamesToProcessChunks.length;i++) {
    jQuery.ajax({
      url: warehouseUrl+'index.php/services/plant_portal_import/create_new_group?names='+groupNamesToProcessChunks[i]+'&groupType='+groupType+'&userId='+userId+'&personAttributeId='+personAttributeIdThatHoldsGroupsForUser,
      dataType: 'jsonp',
      success: function(response) {
      }
    });
  }
}