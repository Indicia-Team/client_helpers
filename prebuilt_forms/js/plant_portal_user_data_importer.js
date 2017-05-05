/*
 * Called from the php code to send any new plots to the warehouse
 */
function send_new_plots_to_warehouse(warehouseUrl,websiteId,plotsToCreateNames,plotsToCreateSrefs,plotsToCreateSrefSystems,userId,attributeIdToHoldGroup,plotLocationType) {
  //See comments in front of function code
  var arrayOfChunkTypesToSend=create_array_of_different_plot_chunks_for_warehouse(plotsToCreateNames,plotsToCreateSrefs,plotsToCreateSrefSystems);
  //Some of the information to send are just individual values and aren't grouped (such as website ID).
  //AV To Do, do we even need to send the attribute to hold the group?
  var arrayOfAttributesToSend=create_array_of_new_plot_attributes_for_warehouse(websiteId,userId,attributeIdToHoldGroup,plotLocationType);
  create_params_string_and_send_chunks_to_warehouse(warehouseUrl,websiteId,arrayOfChunkTypesToSend,arrayOfAttributesToSend,'create_new_plots');
}

/*
 *  Information is sent to the warehouse in groups to reduce calls to warehouse. For instance, 5 plot names can be sent together.
 *  As well as names, there is other information to be sent such as spatial references. Make an an array containing each different type of information.
 *  So if we are sending 5 plot names in a chunk, we also need to send 5 spatial references, spatial reference systems
 *  So the params for one warehouse call might look a bit like ?plotNames=Plot 1, Plots 2&plotSrefs=AA11,AB12&plotSrefSystems=OSGB,OSGB
 *  (eventually->once the array created here is converted into a string)
 */
function create_array_of_different_plot_chunks_for_warehouse(plotNamesToProcess,plotSrefsToProcess,plotSrefSystemsToProcess) {
  var chunks;
  var arrayOfChunkTypesToSend=[];
  //Create a group of data to send to the warehouse as a comma separated string, in this case Plot Names
  chunks = create_chunks_groupings_for_warehouse('plotNames',plotNamesToProcess);
  arrayOfChunkTypesToSend[0]=[];
  arrayOfChunkTypesToSend[0]=chunks;
  //To Do AVB - This will need to handle scenario where the spatial reference has a comma in it
  chunks = create_chunks_groupings_for_warehouse('plotSrefs',plotSrefsToProcess);
  arrayOfChunkTypesToSend[1]=[];
  arrayOfChunkTypesToSend[1]=chunks;
  chunks=create_chunks_groupings_for_warehouse('plotSrefSystems',plotSrefSystemsToProcess);
  arrayOfChunkTypesToSend[2]=[];
  arrayOfChunkTypesToSend[2]=chunks;
  return arrayOfChunkTypesToSend;
}

/*
 * Create an array of individual values (that don't need grouping) that get sent to the warehouse. In the case of new plots
 * this is very simple, but use a function to do this anyway, to be consistant with the creation of new groups
 */
function create_array_of_new_plot_attributes_for_warehouse(websiteId,userId,attributeIdToHoldGroup,plotLocationType) {
  var arrayOfAttributesToSend=[];
  arrayOfAttributesToSend[0]=[];
  arrayOfAttributesToSend[0][0]='websiteId';
  arrayOfAttributesToSend[0][1]=websiteId;
  arrayOfAttributesToSend[1]=[];
  arrayOfAttributesToSend[1][0]='userId';
  arrayOfAttributesToSend[1][1]=userId;
  arrayOfAttributesToSend[2]=[];
  arrayOfAttributesToSend[2][0]='attributeIdToHoldGroup';
  arrayOfAttributesToSend[2][1]=attributeIdToHoldGroup;
  arrayOfAttributesToSend[3]=[];
  arrayOfAttributesToSend[3][0]='plotLocationType';
  arrayOfAttributesToSend[3][1]=plotLocationType;
  return arrayOfAttributesToSend;
}

/*
 * Called from the php code to send any new groups to the warehouse
 */
function send_new_groups_to_warehouse(warehouseUrl,websiteId,groupNamesToCreate,userId,personAttributeIdThatHoldsGroupsForUser) {
  //See notes in send_new_plots_to_warehouse for warehouse, as this works in sames way but for groups
  var arrayOfChunkTypesToSend=create_array_of_different_group_chunks_for_warehouse(groupNamesToCreate);
  var arrayOfAttributesToSend=create_array_of_new_group_attributes_for_warehouse(userId,personAttributeIdThatHoldsGroupsForUser);
  create_params_string_and_send_chunks_to_warehouse(warehouseUrl,websiteId,arrayOfChunkTypesToSend,arrayOfAttributesToSend,'create_new_groups');
}

/*
 * Information is sent to the warehouse in groups to reduce calls to warehouse. For instance, 5 group names can be sent together.
 * In this case we are creating groups instead of plots, so we only need to deal with plot names, however this function remains
 * to be consistant with the way new plots are created.
 */
function create_array_of_different_group_chunks_for_warehouse(groupNamesToProcess) {
  var chunks;
  var arrayOfChunkTypesToSend=[];
  chunks=create_chunks_groupings_for_warehouse('names',groupNamesToProcess);
  arrayOfChunkTypesToSend[0]=[];
  arrayOfChunkTypesToSend[0]=chunks;
  return arrayOfChunkTypesToSend;
}

/*
 * Create an array of individual values (that don't need grouping) to the warehouse.
 */
function create_array_of_new_group_attributes_for_warehouse(userId,personAttributeIdThatHoldsGroupsForUser) {
  var arrayOfAttributesToSend=[];
  arrayOfAttributesToSend[0]=[];
  arrayOfAttributesToSend[0][0]='userId';
  arrayOfAttributesToSend[0][1]=userId;
  arrayOfAttributesToSend[1]=[];
  arrayOfAttributesToSend[1][0]='personAttributeId';
  arrayOfAttributesToSend[1][1]=personAttributeIdThatHoldsGroupsForUser;
  return arrayOfAttributesToSend;
}

function send_new_group_to_plot_attachments_to_warehouse(warehouseUrl,websiteId,plotPairsForPlotGroupAttachment,userId,attributeIdToHoldPlotGroupForPlot,attributeIdHoldsPlotGroupForPerson) {
  //We can re-use this function as we are sending plots and groups in the same way are if we are creating plots
  var arrayOfChunkTypesToSend=create_array_of_new_group_to_plot_attachment_chunks_for_warehouse(plotPairsForPlotGroupAttachment);
  var arrayOfAttributesToSend=create_array_of_new_group_to_plot_attachment_attributes_for_warehouse(attributeIdToHoldPlotGroupForPlot,attributeIdHoldsPlotGroupForPerson,userId,websiteId);
  create_params_string_and_send_chunks_to_warehouse(warehouseUrl,websiteId,arrayOfChunkTypesToSend,arrayOfAttributesToSend,'create_new_plot_to_group_attachments');
}

function  create_array_of_new_group_to_plot_attachment_chunks_for_warehouse(plotNamesForPlotGroupAttachment,plotSrefsForPlotGroupAttachment,plotSrefSystemsForPlotGroupAttachment,plotGroupsForPlotGroupAttachment) {
  var chunks;
  var arrayOfChunkTypesToSend=[];
  //Create a group of data to send to the warehouse as a comma separated string
  chunks = create_chunks_groupings_for_warehouse('plotPairsForPlotGroupAttachment',plotNamesForPlotGroupAttachment);
  arrayOfChunkTypesToSend[0]=[];
  arrayOfChunkTypesToSend[0]=chunks;
  return arrayOfChunkTypesToSend;
}

/*
 * Create an array of individual values (that don't need grouping) to the warehouse.
 */
function create_array_of_new_group_to_plot_attachment_attributes_for_warehouse(locationAttributeIdThatHoldsPlotGroup,personAttributeIdThatHoldsPlotGroup,userId,websiteId) {
  var arrayOfAttributesToSend=[];
  arrayOfAttributesToSend[0]=[];
  arrayOfAttributesToSend[0][0]='locationAttributeIdThatHoldsPlotGroup';
  arrayOfAttributesToSend[0][1]=locationAttributeIdThatHoldsPlotGroup;
  arrayOfAttributesToSend[1]=[];
  arrayOfAttributesToSend[1][0]='personAttributeIdThatHoldsPlotGroup';
  arrayOfAttributesToSend[1][1]=personAttributeIdThatHoldsPlotGroup;
  arrayOfAttributesToSend[2]=[];
  arrayOfAttributesToSend[2][0]='userId';
  arrayOfAttributesToSend[2][1]=userId;
  arrayOfAttributesToSend[3]=[];
  arrayOfAttributesToSend[3][0]='websiteId';
  arrayOfAttributesToSend[3][1]=websiteId;
  return arrayOfAttributesToSend;
}

/*
 * When we send data to the warehouse, the data is grouped into a parameters like this, plotNames=Plot 1,Plot 2,Plot 3
 * However if for example there are 15 data items, then those need to be sent in smaller groups (currently default 5).
 * So create an array structure as follows:
 * Index 0 = The name of the parameter for the data (store at 0 as there isn't anywhere else to store it)
 * From index 1 onwards each element is a comma separated string of a grouping of data, an example array might be
 * array[0]=plotNames
 * array[1]=Plot 1,Plot 2,Plot 3,Plot 4, Plot 5
 * array[2]=Plot 6,Plot 7,Plot 8,Plot 9, Plot 10
 * array[3]=Plot 11,Plot 12,Plot 13,Plot 14, Plot 15
 */
function create_chunks_groupings_for_warehouse(paramName,chunkPartsToProcess) {
  var chunkPartsToProcessChunks=[];
  //We need somewhere to save the parameter name, so just save it at index 0
  chunkPartsToProcessChunks[0]=paramName;
  //That means we start the "proper" data at index 1 instead of 0
  var chunkPartsToProcessChunksIdx = 1;
  //Track number of items in the comma separated string 
  var chunkSizeCounter=1;
  //Cycle through each data item
  for (var i=0; i<chunkPartsToProcess.length;i++) {
    if (chunkPartsToProcessChunks[chunkPartsToProcessChunksIdx]) {
      chunkPartsToProcessChunks[chunkPartsToProcessChunksIdx]=chunkPartsToProcessChunks[chunkPartsToProcessChunksIdx]+','+chunkPartsToProcess[i];
    } else {
      //If it is the first item, then it doesn't need a comma at the front.
       chunkPartsToProcessChunks[chunkPartsToProcessChunksIdx]=chunkPartsToProcess[i];
    }
    //If the comma separated chunk gets too big, start a new chunk and reset the chunk size counter to 0.
    //Currently the chunks are of 5 names, this can be altered by changing this number.
    if (chunkSizeCounter>=5) {
      chunkSizeCounter=1;
      chunkPartsToProcessChunksIdx++;
    } else {
      //Each time we add an item to the comma separated chunk, then increase the counter which tracks the size of the chunk
      chunkSizeCounter++;
    }
  }
  return chunkPartsToProcessChunks;
}

/*
 * At the moment, the parameters to send to the warehouse are held in arrays.
 * Convert that data into strings and then send the data to the warehouse
 */
function create_params_string_and_send_chunks_to_warehouse(warehouseUrl,websiteId,arrayOfChunkTypesToSend,otherAttributes,warehouseFunctionToCall) {
  var params='';
  //Add any extra attributes such as website id
  var otherAttributesString='';
  for (var i=0; i<otherAttributes.length;i++) {
    otherAttributesString=otherAttributesString+'&'+otherAttributes[i][0]+'='+otherAttributes[i][1];
  }
  //Each type of data to send is sent as a separate parameter (e.g. plot names and plot srefs). These are sent in comma separated groups e.g. plot names ["Plot 1", "Plot 2","Plot 3"], ["Plot 4", "Plot 5","Plot 6"] 
  //& sRefs ["AB10", "AB11", "AB12"],["AB13", "AB14", "AB15"]. The first element (0) holds the parameter name, so we cycle through index 1 onwards to get the values, these are held
  //as comma separated groups so we don't send too many to the warehouse at once. 
  //As all these params must have same number of items, we can just cycle through the first one of these to get the "i" index.
  for (var i=1; i<arrayOfChunkTypesToSend[0].length;i++) {
    params='';
    //Cycle through each param type (e.g. plot name, plot sref)
    for (var i2=0; i2<arrayOfChunkTypesToSend.length;i2++) {
      //If first param, then we don't need "&"
      if (params==='') {
        //For each param type, get the param name at element 0 and set it to the value group held at index i. 
        params=params+arrayOfChunkTypesToSend[i2][0]+'='+arrayOfChunkTypesToSend[i2][i];
      } else {
        params=params+'&'+arrayOfChunkTypesToSend[i2][0]+'='+arrayOfChunkTypesToSend[i2][i];
      }
    }
    params=params+otherAttributesString;
    jQuery.ajax({
      url: warehouseUrl+'index.php/services/plant_portal_import/'+warehouseFunctionToCall+'?'+params,
      dataType: 'jsonp',
      async:false,
      success: function(response) {
      }
    });
  }
}