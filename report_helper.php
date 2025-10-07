<?php

/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/indicia-team/client_helpers
 */

/**
 * Link in other required php files.
 */
require_once 'lang.php';
require_once 'helper_base.php';

/**
 * Static helper class that provides methods for dealing with reports.
 */
class report_helper extends helper_base {

  /**
   * Accept parameter values that should be initially applied globally to all reports on a page.
   *
   * @var array
   */
  public static $initialFilterParamsToApply = [];

  /**
   * Accept parameter values that should be globally skipped on parameters forms.
   *
   * @var array
   */
  public static $filterParamsToGloballySkip = [];

 /**
  * Control which outputs a treeview of the reports available on the warehouse, with
  * radio buttons for selecting a report. The title and description of the currently selected
  * report are displayed alongside.
  *
  * @param array $options Options array which accepts the following standard options: id,
  * fieldname, class, default, readAuth.
  */
  public static function report_picker($options) {
    self::add_resource('reportPicker');
    $options = array_merge(array(
      'id' => 'report-picker',
      'fieldname' => 'report_name',
      'default' => '',
      'class' => ''
    ), $options);
    // add class rather than replacing existing
    $options['class'] .= ' report-picker-container ui-widget ui-widget-content ui-helper-clearfix';
    $reports = '';
    $response = self::http_post(self::$base_url.'index.php/services/report/report_list?nonce='.
        $options['readAuth']['nonce'].'&auth_token='.$options['readAuth']['auth_token']);
    if (isset($response['output'])) {
      $output = json_decode($response['output'], true);
      if (isset($output['error']))
        return $output['error'];
      $reports .= self::get_report_list_level($options['id'], $options['fieldname'], $options['default'], $output);
    }
    self::$javascript .= <<<JS
      $('#$options[id] > ul').treeview({collapsed: true});
      indiciaData.reportList=$response[output];
      $('#$options[id] > ul input[checked="checked"]').trigger('click');
      $('#$options[id] > ul input[checked="checked"]').parents('#$options[id] ul').show();

    JS;
    $options['reports']=$reports;
    $options['moreinfo']=lang::get('More info');
    return self::apply_template('report_picker', $options);
  }

  /**
   * Outputs a single level of the hierarchy of available reports, then iterates into sub-
   * folders.
   * @param string $ctrlId The fieldname for the report_picker control (=HTML form value)
   * @param string $default Name of the report to be initially selected
   * @param array $list Array of the reports and folders within the level to be output.
   * @return string HTML for the unordered list containing the level.
   * @access private
   */
  private static function get_report_list_level($ctrlId, $fieldName, $default, $list) {
    $r = '';
    foreach($list as $name=>$item) {
      if ($item['type']=='report') {
        $id = 'opt_'.str_replace('/','_',$item['path']);
        $checked = $item['path']==$default ? ' checked="checked"' : '';
        $r .= '<li><label class="ui-helper-reset auto">'.
            '<input type="radio" id="'.$id.'" name="'.$fieldName.'" value="'.$item['path'].
            '" onclick="displayReportMetadata(\'' . $ctrlId . '\', \'' . $item['path'] . '\');" ' . $checked . '/>'.
            htmlspecialchars($item['title']) .
            "</label></li>\n";
      }
      else {
        $name = ucwords(str_replace('_', ' ', $name));
        $r .= "<li>$name\n";
        $r .= self::get_report_list_level($ctrlId, $fieldName, $default, $item['content']);
        $r .= "</li>\n";
      }
    }
    if (!empty($r))
      $r = "<ul>\n$r\n</ul>\n";
    return $r;
  }

  /**
   * A link to download the output of a report.
   *
   * Returns a simple HTML link to download the contents of a report defined by
   * the options. The options arguments supported are the same as for the
   * report_grid method. Pagination information will be ignored (e.g.
   * itemsPerPage). If this download link is to be displayed alongside a
   * report_grid to provide a download of the same data, set the id option to
   * the same value for both the report_download_link and report_grid controls
   * to link them together. Use the itemsPerPage parameter to control how many
   * records are downloaded.
   *
   * @param array
   *   $options Options array with the following possibilities:
   *   * caption - link caption.
   *   * class - class attribute for the link generated.
   *   * dataSource - path to the report file.
   *   * format - Default to csv. Specify the download format, one of csv, json,
   *     xml, nbn.
   *   * itemsPerPage - max size of download file. Default 20000.
   */
  public static function report_download_link($options) {
    $options = array_merge(array(
      'caption' => 'Download this report',
      'format' => 'csv',
      'itemsPerPage' => 20000,
      'class' => '',
    ), $options);
    // Option for a special report for downloading.
    if (!empty($options['dataSourceDownloadLink'])) {
      $origDataSource = $options['dataSource'];
      $options['dataSource'] = $options['dataSourceDownloadLink'];
    }
    $options = self::getReportGridOptions($options);
    $options['linkOnly'] = TRUE;
    $currentParamValues = self::getReportGridCurrentParamValues($options);
    $sortAndPageUrlParams = self::getReportGridSortPageUrlParams($options);
    // Don't want to paginate the download link.
    unset($sortAndPageUrlParams['page']);
    $extras = self::getReportSortingPagingParams($options, $sortAndPageUrlParams);
    $link = self::get_report_data($options, $extras . '&' . self::array_to_query_string($currentParamValues, TRUE), TRUE);
    if (isset($origDataSource)) {
      $options['dataSource'] = $origDataSource;
    }
    $class = $options['class'] ? " class=\"$options[class]\"" : '';
    global $indicia_templates;
    return str_replace(
      ['{link}', '{caption}', '{class}'],
      [$link, lang::get($options['caption']), $class],
      $indicia_templates['report_download_link']
    );
  }

  /**
   * Converts media paths to thumbnail HTML.
   *
   * Creates thumbnails for images and other media to insert into a grid column
   * or other report output.
   *
   * @param array $mediaPaths
   *   List of paths to media.
   * @param string $preset
   *   Size preset, e.g. thumb or med.
   * @param string $entity
   *   Entity name.
   * @param int $rowId
   *   Record ID.
   *
   * @param string
   *   HTML for thumbnails.
   */
  public static function mediaToThumbnails(array $mediaPaths, $preset, $entity, $rowId) {
    $imagePath = self::get_uploaded_image_folder();
    $imgclass = count($mediaPaths)>1 ? 'multi' : 'single';
    $group = count($mediaPaths)>1 && !empty($rowId) ? "group-$rowId" : '';
    $r = '';
    foreach($mediaPaths as $path) {
      // Attach info so the file's caption and licence can be loaded
      // on view. We can only do this if we know the row's ID.
      $mediaInfoAttr = '';
      if (!empty($rowId) && !empty($entity)) {
        $mediaInfo = htmlspecialchars(json_encode([
          "{$entity}_id" => $rowId,
          'path' => $path,
        ]));
        $mediaInfoAttr = " data-media-info=\"$mediaInfo\"";
      }
      if (preg_match('/^https:\/\/static\.inaturalist\.org/', $path)) {
        $imgLarge = str_replace('/square.', '/original.', $path);
        $path = $preset === 'med' ? str_replace('/square.', '/medium.', $path) : $path;
        $r .= "<a href=\"$imgLarge\" data-fancybox=\"$group\"$mediaInfoAttr class=\"inaturalist $imgclass\"><img src=\"$path\" /></a>";
      }
      elseif (preg_match('/^http(s)?:\/\/(www\.)?(?P<site>[a-z]+(\.kr)?)/', $path, $matches)) {
        // HTTP, means an external file.
        // Flickr URLs sometimes have . in them.
        $matches['site'] = str_replace('.', '', $matches['site']);
        $r .= "<a href=\"$path\" class=\"social-icon $matches[site]\"></a>";
      }
      elseif (preg_match('/(\.wav|\.mp3)$/', strtolower($path))) {
        $r .= <<<HTML
<audio controls src="$imagePath$path"$mediaInfoAttr type="audio/mpeg" />
HTML;
      }
      else {
        $r .= <<<HTML
<a href="$imagePath$path" class="$imgclass"
  data-fancybox="$group"$mediaInfoAttr>
  <img src="$imagePath$preset-$path" />
</a>
HTML;
      }
    }
    return $r;
  }

 /**
  * Outputs a grid that loads the content of a report or Indicia table.
  *
  * The grid supports a simple pagination footer as well as column title sorting through PHP. If
  * used as a PHP grid, note that the current web page will reload when you page or sort the grid, with the
  * same $_GET parameters but no $_POST information. If you need 2 grids on one page, then you must define a different
  * id in the options for each grid.
  *
  * For summary reports, the user can optionally setup clicking functionality so that another report is called when the user clicks on the grid.
  *
  * The grid operation will be handled by AJAX calls when possible to avoid reloading the web page.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>id</b><br/>
  * Optional unique identifier for the grid's container div. This is required if there is more than
  * one grid on a single web page to allow separation of the page and sort $_GET parameters in the URLs
  * generated.</li>
  * <li><b>reportGroup</b><br/>
  * When joining multiple reports together, this can be used on a report that has autoParamsForm set to false to bind the report to the
  * parameters form from a different report by giving both report controls the same reportGroup string. This will only work when all
  * parameters required by this report are covered by the other report's parameters form.</li>
  * <li><b>rememberParamsReportGroup</b><br/>
  * Enter any value in this parameter to allow the report to save its parameters for the next time the report is loaded.
  * The parameters are saved site wide, so if several reports share the same value and the same report group then the parameter
  * settings will be shared across the reports even if they are on different pages of the site. For example if several reports on the
  * site have an ownData boolean parameter which filters the data to the user's own data, this can be set so that the reports all
  * share the setting. This functionality requires cookies to be enabled on the browser.</li>
  * <li><b>rememberGridPosition</b><br/>
  * If true, then the grid's last paging position, row filter and sort
  * information are stored in a cookie and recalled the next time the page is
  * visited.</li>
  * <li><b>mode</b><br/>
  * Pass report for a report, or direct for an Indicia table or view. Default is report.</li>
  * <li><b>readAuth</b><br/>
  * Read authorisation tokens.</li>
  * <li><b>dataSource</b><br/>
  * Name of the report file or singular form of the table/view.</li>
  * <li><b>dataSourceDownloadLink</b><br/>
  * Optionally use a different data source for the download link displayed beneath the grid..</li>
  * <li><b>view</b>
  * When loading from a view, specify list, gv or detail to determine which view variant is loaded. Default is list.
  * </li>
  * <li><b>itemsPerPage</b><br/>
  * Number of rows to display per page. Defaults to 20.</li>
  * <li><b>columns</b><br/>
  * Optional. Specify a list of the columns you want to output if you need more control over the columns, for example to
  * specify the order, change the caption or build a column with a configurable data display using a template.
  * Pass an array to this option, with each array entry containing an associative array that specifies the
  * information about the column represented by the position within the array. The associative array for the column can contain
  * the following keys:
  *  - fieldname: name of the field to output in this column. Does not need to be specified when using the template option.
  *  - display: caption of the column, which defaults to the fieldname if not specified
  *  - actions: list of action buttons to add to each grid row. Each button is defined by a sub-array containing
  *      values for caption, visibility_field, url, urlParams, class, img and javascript. The visibility field is an optional
  *      name of a field in the data which contains true or false to define the visibility of this action. The javascript, url
  *      and urlParams values can all use the field names from the report in braces as substitutions, for example {id} is replaced
  *      by the value of the field called id in the respective row. In addition, the url can use {currentUrl} to represent the
  *      current page's URL, {rootFolder} to represent the folder on the server that the current PHP page is running from, {input_form}
  *      (provided it is returned by the report) to represent the path to the form that created the record, {imageFolder} for the image
  *      upload folder and {sep} to specify either a ? or & between the URL and the first query parameter, depending on whether
  *      {rootFolder} already contains a ?. The url and urlParams can also have replacements from any query string parameter in the URL
  *      so report parameters can be passed through to linked actions. Because the javascript may pass the field values as parameters to functions,
  *      there are escaped versions of each of the replacements available for the javascript action type. Add -escape-quote or
  *      -escape-dblquote to the fieldname for quote escaping, -escape-htmlquote/-escape-htmldblquote for escaping quotes in HTML
  *      attributes, or -escape-urlpath to convert text to a format suitable for part of a URL path (lowercase with hyphens).
  *      For example this would be valid in the action javascript: foo("{bar-escape-dblquote}"); even if the field value contains a
  *      double quote which would have broken the syntax. Set img to the path to an image to use an image for the action instead of
  *      a text caption - the caption then becomes the image's title. The image path can contain {rootFolder} to be replaced by the
  *      root folder of the site, in this case it excludes the path parameter used in Drupal when dirty URLs are used (since this
  *      is a direct link to a URL).
  *  - visible: true or false, defaults to true
  *  - responsive-hide: an array, keyed by breakpoint name, with boolean values
  *      to indicate whether the column will be hidden when the breakpoint
  *      condition is met. Only takes effect if the 'responsiveOpts'option is set.
  *  - template: allows you to create columns that contain dynamic content using a template, rather than just the output
  *      of a field. The template text can contain fieldnames in braces, which will be replaced by the respective field values.
  *      Add -escape-quote or -escape-dblquote to the fieldname for quote escaping, -escape-htmlquote/-escape-htmldblquote
  *      for escaping quotes in HTML attributes, or -escape-urlpath for URL path segments as described above. Note that template
  *      columns cannot be sorted by clicking grid headers.
  *     An example array for the columns option is:
  *     array(
  *       array('fieldname' => 'survey', 'display' => 'Survey Title'),
  *       array('display' => 'action', 'template' => '<a href="www.mysite.com\survey\{id}\edit">Edit</a>'),
  *       array('display' => 'Actions', 'actions' => array(
  *         array('caption' => 'edit', 'url' => '{currentUrl}', 'urlParams'=>array('survey_id' => '{id}'))
  *       ))
  *     )
  *  - json: set to true if the column contains a json string object with properties that can be decoded to give strings that
  *      can be used as replacements in a template. For example, a column is returned from a report with fieldname='data', json=true
  *      and containing a data value '{"species":"Arnica montana","date":"14/04/2004"}'. A second column with fieldname='comment'
  *      contains the value 'Growing on a mountain pasture'. A third column is setup in the report with template set to
  *      '<div>{species} was recorded on {date}.<br/>{comment}</div>'. The json data and the second column's raw value are all
  *      available in the template replacements, so the output is set to
  *      '<div>Arnica montana was recorded on 14/04/2004.<br/>Growing on a mountain pasture</div>'
  *      template
  *  - img: set to true if the column contains a path to an image (relative to the warehouse upload folder). If so then the
  *      path is replaced by an image thumbnail with a fancybox zoom to the full image. Multiple images can be included by
  *      separating each path with a comma.
  * </li>
  * <li><b>rowId</b>
  * Optional. Names the field in the data that contains the unique identifier
  * for each row. If set, then the &lt;tr&gt; elements have their id attributes
  * set to row + this field value, e.g. row37. This is used to allow
  * synchronisation of the selected table rows with a report map output showing
  * the same data - the map should also have its @rowId property set to the
  * same field. Also used to obtain media data (caption and licence info)
  * when showing a popup after clicking on a media thumbnail.
  * </li>
  * <li><b>entity</b>
  * If the report grid contains a media column with thumbnails, then the rowId
  * is used to determine how to load the media's caption and licence info from
  * the database. If rowId is of the form '<table>_id', e.g. 'sample_id', then
  * entity will be worked out by the code so setting this option is
  * unnecessary. However if rowId is just set to 'id' or some other form, then
  * the code will default the entity to 'occurrence' so will need to be
  * overridden for data from other tables.
  * <li><b>includeAllColumns</b>
  * Defaults to true. If true, then any columns in the report, view or table which are not in the columns
  * option array are automatically added to the grid after any columns specified in the columns option array.
  * Therefore the default state for a report_grid control is to include all the report, view or table columns
  * in their default state, since the columns array will be empty.</li>
  * <li><b>headers</b>
  * Should a header row be included? Defaults to true.
  * <li><b>sortable</b>
  * If a header is included, should columns which allow sorting be sortable by clicking? Defaults to true.
  * <li><b>galleryColCount</b>
  * If set to a value greater than one, then each grid row will contain more than one record of data from the database, allowing
  * a gallery style view to be built. Defaults to 1.
  * <li><b>autoParamsForm</b>
  * Defaults to true. If true, then if a report requires parameters, a parameters input form will be auto-generated
  * at the top of the grid. If set to false, then it is possible to manually build a parameters entry HTML form if you
  * follow the following guidelines. First, you need to specify the id option for the report grid, so that your
  * grid has a reproducable id. Next, the form you want associated with the grid must itself have the same id, but with
  * the addition of params on the end. E.g. if the call to report_grid specifies the option 'id' to be 'my-grid' then
  * the parameters form must be called 'my-grid-params'. Finally the input controls which define each parameter must have
  * the name 'param-id-' followed by the actual parameter name, replacing id with the grid id. So, in our example,
  * a parameter called survey will need an input or select control with the name attribute set to 'param-my-grid-survey'.
  * The submit button for the form should have the method set to "get" and should post back to the same page.
  * As a final alternative, if parameters are required by the report but some can be hard coded then
  * those may be added to the filters array.</li>
  * <li><b>fieldsetClass</b><br/>
  * Optional. Class name(s) to add to fieldsets generated by the auto parameters form.</li>
  * <li><b>filters</b><br/>
  * Array of key value pairs to include as a filter against the data.
  * </li>
  * <li><b>extraParams</b><br/>
  * Array of additional key value pairs to attach to the request. This should include fixed values which cannot be
  * changed by the user and therefore are not needed in the parameters form. extraParams can be overridden by loaded
  * context filters (permissions filters, e.g. for verification).
  * </li>
  * <li><b>immutableParams</b><br/>
  * Immutable parameters are parameters to apply to the report grid which cannot be changed under any circumstance.
  * </li>
  * <li><b>paramDefaults</b>
  * Optional associative array of parameter default values. Default values appear in the parameter form and can be overridden.</li>
  * <li><b>paramsOnly</b>
  * Defaults to false. If true, then this method will only return the parameters form, not the grid content. autoParamsForm
  * is ignored if this flag is set.</li>
  * <li><b>ignoreParams</b>
  * Array that can be set to a list of the report parameter names that should not be included in the parameters form. Useful
  * when using paramsOnly=true to display a parameters entry form, but the system has default values for some of the parameters
  * which the user does not need to be asked about. Can also be used to provide parameter values that can be overridden only via
  * a URL parameter.</li>
  * <li><b>completeParamsForm</b>
  * Defaults to true. If false, the control HTML is returned for the params form without being wrapped in a <form> and
  * without the Run Report button, allowing it to be embedded into another form.</li>
  * <li><b>paramsFormButtonCaption</b>
  * Caption of the button to run the report on the report parameters form. Defaults to Run Report. This caption
  * is localised when appropriate.
  * <li><b>paramsInMapToolbar</b>
  * If set to true, then the parameters for this report are not output, but are passed to a map_panel control
  * (which must therefore exist on the same web page) and are output as part of the map's toolbar.
  * </li>
  * <li><b>footer</b>
  * Additional HTML to include in the report footer area. {currentUrl} is replaced by the
  * current page's URL, {rootFolder} is replaced by the folder on the server that the current PHP page
  * is running from.</li>
  * </li>
  * <li><b>downloadLink</b>
  * Should a download link be included in the report footer? Defaults to false.</li>
  * <li><b>sharing</b>
  * Assuming the report has been written to take account of website sharing agreements, set this to define the task
  * you are performing with the report and therefore the type of sharing to allow. Options are reporting (default),
  * verification, moderation, peer_review, data_flow, editing, website (this website only) or me (my data only).</li>
  * <li><b>UserId</b>
  * If sharing=me, then this must contain the Indicia user ID of the user to return data for.
  * </li>
  * <li><b>sendOutputToMap</b>
  * Default false. If set to true, then the records visible on the current page are drawn onto a map. This is different to the
  * report_map method when linked to a report_grid, which loads its own report data for display on a map, just using the same input parameters
  * as other reports. In this case the report_grid's report data is used to draw the features on the map, so only 1 report request is made.
  * </li>
  * <li><b>linkFilterToMap</b>
  * Default true but requires a rowId to be set. If true, then filtering the grid causes the map to also filter.
  * </li>
  * <li><b>includePopupFilter</b>
  * Set to true if you want to include a filter in the report header that
  * displays a popup allowing the user to select exactly what data they want to
  * display on the report.
  * </li>
  * <li><b>zoomMapToOutput</b>
  * Default true. When combined with sendOutputToMap=true, defines that the map will automatically zoom to show the records.
  * </li>
  * <li><b>rowClass</b>
  * A CSS class to add to each row in the grid. Can include field value replacements in braces, e.g. {certainty} to construct classes from
  * field values, e.g. to colour rows in the grid according to the data.
  * </li>
  * <li><b>callback</b>
  * Set to the name of a JavaScript function that should already exist which
  * will be called each time the grid reloads (e.g. when paginating or sorting).
  * </li>
  * <li><b>linkToReportPath</b>
  * Allows drill down into reports. Holds the URL of the report that is called when the user clicks on
  * a report row. When this is not set, the report click functionality is disabled. The replacement #param# will
  * be filled in with the row ID of the clicked on row.
  * </li>
  * <li><b>ajax</b>
  * If true, then the first page of records is loaded via an AJAX call after the initial page load, otherwise
  * they are loaded using PHP during page build. This means the grid load will be delayed till after the
  * rest of the page, speeding up the load time of the rest of the page. If used on a tabbed output then
  * the report will load when the tab is first viewed.
  * Default false.
  * </li>
  * <li><b>ajaxLinksOnly</b>
  * If true, then sort and pagination links designed for use when JavaScript is disabled will be ommitted. Useful
  * on public facing pages to prevent search engines navigating links.
  * Default false.
  * </li>
  * <li><b>autoloadAjax</b>
  * Set to true to prevent autoload of the grid in Ajax mode. You would then need to call the grid's ajaxload() method
  * when ready to load. This might be useful e.g. if a parameter is obtained from some other user input beforehand.
  * Default false.
  * </li>
  * <li><b>pager</b>
  * Include a pager? Default true. Removing the pager can have a big improvement on performance where there are lots of records to count.
  * </li>
  * <li><b>imageThumbPreset</b>
  * Defaults to thumb. Preset name for the image to be loaded from the warehouse as the preview thumbnail for images, e.g. thumb or med.
  * </li>
  * <li><b>responsiveOpts</b>
  * Set to an array of options to pass to FooTable to make the table responsive.
  * Used in conjunction with the columns['responsive-hide'] option to determine
  * which columns are hidden at different breakpoints.
  * Supported options are
  *   - breakpoints: an array keyed by breakpoint name with values of screen
  *     width at which to apply the breakpoint. Footable defaults apply if
  *     omitted.
  * </li>
  * <li><b>includeColumnsPicker</b>
  * Adds a menu button to the header which allows the user to pick which columns are visible.
  * When using this option you must set the id option as well to a unique identifier
  * for the grid in order to enable saving of the settings in a cookie.
  * </li>
  * </ul>
  */
  public static function report_grid($options) {
    global $indicia_templates;
    self::add_resource('fancybox');
    $options = self::getReportGridOptions($options);
    // Make sure jquery.indiciaMapPanel.js code has access to rowId.
    if (isset($options['rowId'])) {
      self::$javascript .= "indiciaData.dataGridRowIdOption='" . $options['rowId'] . "';\n";
    }
    $sortAndPageUrlParams = self::getReportGridSortPageUrlParams($options);
    $extras = self::getReportSortingPagingParams($options, $sortAndPageUrlParams);
    if ($options['ajax'])
      $options['extraParams']['limit']=0;
    // Request report data.
    self::request_report(
      $response,
      $options,
      $currentParamValues,
      // Only get a count if doing a pager and not doing Ajax population as Ajax can update the pager later.
      $options['pager'] && !$options['ajax'],
      $extras
    );
    if (isset($response['count'])) {
      // Pass knownCount into any subsequent AJAX calls as this allows better performance
      $options['extraParams']['knownCount'] = $response['count'];
    }
    if ($options['ajax'])
      unset($options['extraParams']['limit']);
    if (isset($response['error'])) return $response['error'];
    $r = self::paramsFormIfRequired($response, $options, $currentParamValues);
    // return the params form, if that is all that is being requested, or the parameters are not complete.
    if ((isset($options['paramsOnly']) && $options['paramsOnly']) || !isset($response['records'])) return $r;
    $records = $response['records'];
    self::report_grid_get_columns($response, $options);
    $pageUrl = self::reportGridGetReloadUrl($sortAndPageUrlParams);
    $thClass = $options['thClass'];
    $r .= $indicia_templates['loading_overlay'];
    $r .= "\n";
    $thead = '';
    $tbody = '';
    $tfoot = '';
    if ($options['headers']!==false) {
      //$thead .= "\n<thead class=\"$thClass\">\n";
      // build a URL with just the sort order bit missing, so it can be added for each table heading link
      $sortUrl = $pageUrl . ($sortAndPageUrlParams['page']['value'] ?
          $sortAndPageUrlParams['page']['name'].'='.$sortAndPageUrlParams['page']['value'].'&' :
          ''
      );
      $sortdirval = $sortAndPageUrlParams['sortdir']['value'] ? strtolower($sortAndPageUrlParams['sortdir']['value']) : 'asc';
      // Flag if we know any column data types and therefore can display a filter row
      $wantFilterRow=false;
      $filterRow='';
      $imgPath = empty(self::$images_path) ? self::relative_client_helper_path()."../media/images/" : self::$images_path;
      // Output the headers. Repeat if galleryColCount>1;
      for ($i=0; $i<$options['galleryColCount']; $i++) {
        foreach ($options['columns'] as &$field) {
          if (isset($field['visible']) && ($field['visible'] === 'false' || $field['visible'] === false)) {
             // skip this column as marked invisible
            continue;
          }
          if (isset($field['actions']))
            report_helper::translateActions($field['actions']);
          // allow the display caption to be overriden in the column specification
          if (empty($field['display']) && empty($field['fieldname'])) {
            $caption = '';
          }
          else {
            $caption = empty($field['display']) ? $field['fieldname'] : lang::get($field['display']);
          }

          if ($options['sortable'] && isset($field['fieldname']) && !(isset($field['img']) && $field['img'] == 'true')) {
            if (empty($field['orderby'])) {
              $field['orderby'] = $field['fieldname'];
            }
            $sortLink = $sortUrl.$sortAndPageUrlParams['orderby']['name'].'='.$field['orderby'];
            // reverse sort order if already sorted by this field in ascending dir
            if ($sortAndPageUrlParams['orderby']['value'] == $field['orderby'] && $sortAndPageUrlParams['sortdir']['value'] != 'DESC') {
              $sortLink .= '&'.$sortAndPageUrlParams['sortdir']['name']."=DESC";
            }
            $sortHref = self::getGridNavHref($sortLink, $options['ajaxLinksOnly']);
            // store the field in a hidden input field
            $sortBy = lang::get("Sort by {1}", $caption);
            $captionLink = "<input type=\"hidden\" value=\"$field[orderby]\"/>" .
                "<a$sortHref title=\"$sortBy\">$caption</a>";
            // set a style for the sort order
            $orderStyle = ($sortAndPageUrlParams['orderby']['value'] == $field['orderby']) ? ' '.$sortdirval : '';
            $orderStyle .= ' sortable';
            $fieldId = ' id="' . $options['id'] . '-th-' . $field['orderby'] . '"';
          }
          else {
            $orderStyle = '';
            $fieldId = '';
            $captionLink=$caption;
          }
          $colClass = isset($field['fieldname']) ? " col-$field[fieldname]" : '';
          if (!$colClass && isset($field['actions'])) {
            $colClass = ' col-actions';
          }

          // Create a data-hide attribute for responsive tables.
          $datahide = '';
          if (isset($field['responsive-hide'])) {
            $datahide = implode(',', array_keys(array_filter($field['responsive-hide'])));
            if($datahide != '') {
              $datahide = " data-hide=\"$datahide\" data-editable=\"true\"";
            }
          }
          $thead .= "<th$fieldId class=\"$thClass$colClass$orderStyle\"$datahide>$captionLink</th>\n";
          if (isset($field['datatype']) && !empty($caption)) {
            switch ($field['datatype']) {
              case 'text':
              case 'species':
                $title = lang::get("Search for {1} text begins with .... Use * as a wildcard.", $caption);
                break;

              case 'date':
                $title = lang::get("Search on {1} - search for an exact date or use a vague date such as a year to select a range of dates.", $caption);
                break;

              default:
                $title = lang::get("Search on {1} - either enter an exact number, use >, >=, <, or <= before the number to filter for " .
                      "{1} more or less than your search value, or enter a range such as 1000-2000.", $caption);
            }
            $title = htmlspecialchars(lang::get('Type here to filter then press Tab or Return to apply the filter.').' '.$title);
            // Filter, which when clicked, displays a popup with a series of
            // checkboxes representing a distinct set of data from a column on
            // the report. The user can then deselect these checkboxes to
            // remove data from the report.
            if (!empty($options['includePopupFilter'])&&$options['includePopupFilter']===true) {
              self::$javascript.="indiciaData.includePopupFilter=true;";
              $popupFilterIcon = $imgPath."desc.gif";
              $popupFilterIconHtml='<img class="col-popup-filter" id="col-popup-filter-'.$field['fieldname'].'-'.$options['id'].'" src="'.$popupFilterIcon.'"  >';
            }
            if (empty($popupFilterIconHtml))
              $popupFilterIconHtml='';
            //The filter's input id includes the grid id ($options['id']) in its id as there maybe more than one grid and we need to make the id unique.
            $filterRow .= "<th class=\"$colClass\"><input title=\"$title\" type=\"text\" class=\"col-filter\" id=\"col-filter-".$field['fieldname']."-".$options['id']."\"/>$popupFilterIconHtml</th>";//Add a icon for the popup filter
            $wantFilterRow = true;
          } else
            $filterRow .= "<th class=\"$colClass\"></th>";
        }
        // Clean up dangling reference variable
        unset($field);
      }
      $thead = str_replace(array('{class}','{title}','{content}'), array('','',$thead), $indicia_templates['report-thead-tr']);
      if ($wantFilterRow && (!isset($options["forceNoFilterRow"]) || !$options["forceNoFilterRow"]))
        $thead .= str_replace(array('{class}','{title}','{content}'),
            array(' class="filter-row"',' title="'.lang::get('Use this row to filter the grid').'"',$filterRow), $indicia_templates['report-thead-tr']);
      $thead = str_replace(array('{class}', '{content}'), array(" class=\"$thClass\"", $thead), $indicia_templates['report-thead']);
    }
    $currentUrl = self::get_reload_link_parts();
    // automatic handling for Drupal clean urls.
    $pathParam = (function_exists('variable_get') && variable_get('clean_url', 0)=='0') ? 'q' : '';
    $rootFolder = self::getRootFolder(true);
    // amend currentUrl path if we have Drupal 6/7 dirty URLs so javascript will work properly
    if (isset($currentUrl['params']['q']) && strpos($currentUrl['path'], '?')===false) {
      $currentUrl['path'] = $currentUrl['path'].'?q='.$currentUrl['params']['q'];
    }
    $tfoot .= '<tfoot>';
    $tfoot .= '<tr><td colspan="'.count($options['columns'])*$options['galleryColCount'].'">'.self::outputPager($options, $pageUrl, $sortAndPageUrlParams, $response).'</td></tr>'.
    $extraFooter = '';
    if (isset($options['footer']) && !empty($options['footer'])) {
      $footer = helper_base::getStringReplaceTokens($options['footer'], $options['readAuth']);
      // Allow other modules to hook in.
      if (function_exists('hostsite_invoke_alter_hooks')) {
        hostsite_invoke_alter_hooks('iform_user_replacements', $footer);
      }
      // Merge in any references to the parameters sent to the report: could extend this in the future to pass in the extraParams
      foreach($currentParamValues as $key=>$param){
        $footer = str_replace(array('{'.$key.'}'), array($param), $footer);
      }
      $extraFooter .= '<div class="left">'.$footer.'</div>';
    }
    if (isset($options['downloadLink']) && $options['downloadLink'] && (count($records)>0 || $options['ajax'])) {
      $downloadOpts = array_merge($options);
      unset($downloadOpts['itemsPerPage']);
      $extraFooter .= '<div class="right">'.self::report_download_link($downloadOpts).'</div>';
    }
    if (!empty($extraFooter))
      $tfoot .= '<tr><td colspan="'.count($options['columns']).'">'.$extraFooter.'</td></tr>';
    $tfoot .= '</tfoot>';
    $altRowClass = '';
    $outputCount = 0;
    $imagePath = self::get_uploaded_image_folder();
    $addFeaturesJs = '';
    $haveUpdates = FALSE;
    $updateformID = 0;
    // Knowing the entity helps build image metadata for popups.
    if (isset($options['rowId']) && preg_match('/^([a-z_]+)_id$/', $options['rowId'], $matches)) {
      $entity = $matches[1];
    }
    else {
      // Assume the ID is for occurrence data unless specified.
      $entity = isset($options['entity']) ? $options['entity'] : 'occurrence';
    }
    if (count($records)>0) {
      $rowInProgress = FALSE;
      $rowTitle = !empty($options['rowId']) ?
          ' title="'.lang::get('Click the row to highlight the record on the map. Double click to zoom in.').'"' : '';
      foreach ($records as $rowIdx => $row) {
        // Don't output the additional row we requested just to check if the next page link is required.
        if ($outputCount>=$options['itemsPerPage'])
          break;
        // Put some extra useful paths into the row data, so it can be used in the templating
        $row = array_merge($row, array(
            'rootFolder'=>$rootFolder,
            'sep'=>strpos($rootFolder, '?')===FALSE ? '?' : '&',
            'imageFolder'=>$imagePath,
            // allow the current URL to be replaced into an action link. We extract url parameters from the url, not $_GET, in case
            // the url is being rewritten.
            'currentUrl' => $currentUrl['path']
        ));
        // set a unique id for the row if we know the identifying field.
        $rowId = isset($options['rowId']) ? $row[$options['rowId']] : '';
        $rowIdAttr = $rowId ? " id=\"row$rowId\"" : '';
        if ($rowIdx % $options['galleryColCount']==0) {
          $classes = [];
          if ($altRowClass)
            $classes[]=$altRowClass;
          if (isset($options['rowClass']))
            $classes[]=self::mergeParamsIntoTemplate($row, $options['rowClass'], true, true);
          $classes=implode(' ',$classes);
          $rowClass = empty($classes) ? '' : " class=\"$classes\"";
          $tr = '';
          $rowInProgress=true;
        }
        // decode any data in columns that are defined as containing JSON
        foreach ($options['columns'] as $field) {
          if (isset($field['json']) && $field['json'] && isset($row[$field['fieldname']])) {
            $row = array_merge(json_decode($row[$field['fieldname']], true), $row);
          }
        }
        foreach ($options['columns'] as $field) {
          $classes = [];
          if ($options['sendOutputToMap'] && isset($field['mappable']) && ($field['mappable']==='true' || $field['mappable']===true)) {
            $data = json_encode($row + array('type' => 'linked'));
            $addFeaturesJs.= "div.addPt(features, ".$data.", '".$field['fieldname']."', {}".
                ", '$rowId');\n";
          }
          if (isset($field['visible']) && ($field['visible']==='false' || $field['visible']===false))
            continue; // skip this column as marked invisible
          if (!empty($row[$field['fieldname'] ?? ''] ?? '') && !isset($field['template'])) {
            if (isset($field['img']) && $field['img']=='true') {
              $imgs = explode(',', $row[$field['fieldname']]);
              $value='';
              $row[$field['fieldname']] = self::mediaToThumbnails($imgs, $options['imageThumbPreset'], $entity, $rowId);
            }
            elseif (isset($field['html_safe']) && $field['html_safe']=='true') {
              // HTML output from report column so no escaping.
              $value = $row[$field['fieldname']];
            }
            else {
              // Fields that are neither images nor templates can be HTML escaped.
              $row[$field['fieldname']] = htmlspecialchars($row[$field['fieldname']]);
            }
          }

          if (isset($field['img']) && $field['img']=='true')
            $classes[] = 'table-gallery';
          if (isset($field['actions'])) {
            $value = self::get_report_grid_actions($field['actions'], $row, $pathParam);
            $classes[]='col-actions';
          } elseif (isset($field['template'])) {
            $value = self::mergeParamsIntoTemplate($row, $field['template'], true, true, true);
          } else if (isset($field['update']) &&(!isset($field['update']['permission']) || hostsite_user_has_permission($field['update']['permission']))){
          	// TODO include checks to ensure method etc are included in structure -
          	$updateformID++;
            $update = $field['update'];
            $url = iform_ajaxproxy_url(null, $update['method']);
            $class = isset($field['update']['class']) ? $field['update']['class'] : '';
            $value = isset($field['fieldname']) && isset($row[$field['fieldname']]) ? $row[$field['fieldname']] : '';
          	$value = <<<FORM
<form id="updateform-$updateformID" method="post" action="$url">
<input type="hidden" name="website_id" value="$update[website_id]">
<input type="hidden" name="transaction_id" value="updateform-$updateformID-field">
<input id="updateform-$updateformID-field" name="$update[tablename]:$update[fieldname]"
  class="update-input $class" value="$value">
FORM;

            if(isset($field['update']['parameters'])){
              foreach($field['update']['parameters'] as $pkey=>$pvalue){
                $value.="<input type=\"hidden\" name=\"".$field['update']['tablename'].":".$pkey."\" value=\"".$pvalue."\">";
              }
            }
            $value.="</form>";
          	$value=self::mergeParamsIntoTemplate($row, $value, true);
          	$haveUpdates = true;
            self::$javascript .= <<<JS
$('#updateform-$updateformID').ajaxForm({
    async: true,
    dataType:  'json',
    success:   function(data, status, form){
      if (checkErrors(data)) {
        var selector = '#'+data.transaction_id.replace(/:/g, '\\:');
        $(selector).removeClass('input-saving');
        $(selector).removeClass('input-edited');
      }
    }
  });
JS;
          }
          else {
            $value = isset($field['fieldname']) && isset($row[$field['fieldname']]) ? $row[$field['fieldname']] : '';
            // The verification_1 form depends on the tds in the grid having a class="data fieldname".
            $classes[]='data';
          }
          if (isset($field['fieldname'])) {
            $classes[]="col-$field[fieldname]";
          }
          if (isset($field['class']))
            $classes[] = $field['class'];
          if (count($classes)>0)
            $class = ' class="'.implode(' ', $classes).'"';
          else
            $class = '';
          $tr .= str_replace(array('{class}','{content}'), [$class, $value], $indicia_templates['report-tbody-td']);
        }
        if ($rowIdx % $options['galleryColCount']==$options['galleryColCount']-1) {
          $rowInProgress=false;
          $tbody .= str_replace(array('{class}','{rowId}','{rowTitle}','{content}'), array($rowClass, $rowIdAttr, $rowTitle, $tr), $indicia_templates['report-tbody-tr']);
        }
        $altRowClass = empty($altRowClass) ? $options['altRowClass'] : '';
        $outputCount++;
      }
      // implement links from the report grid rows if configuration options set
      if (isset($options['linkToReportPath'])) {
        $path=$options['linkToReportPath'];
        if (isset($options['rowId'])) {
          //if the user clicks on a summary table row then open the report specified using the row ID as a parameter.
            self::$javascript .= "
              $('#".$options['id']." tbody').on('click', function(evt) {
                var tr=$(evt.target).parents('tr')[0], rowId=tr.id.substr(3);
                window.location='$path'.replace(/#param#/g, rowId);
              });
            ";
        }
      }
      if ($rowInProgress)
        $tbody .= str_replace(array('{class}','{rowId}','{title}','{content}'), array($rowClass, $rowIdAttr, $rowTitle, $tr), $indicia_templates['report-tbody-tr']);
    } else {
      $tbody .= str_replace(array('{class}','{rowId}','{rowTitle}','{content}'), array(' class="empty-row"','','','<td colspan="'.count($options['columns'])*$options['galleryColCount'].
          '">' . lang::get('No information available') . '</td>'), $indicia_templates['report-tbody-tr']);
    }
    $tbody = str_replace('{content}', $tbody, $indicia_templates['report-tbody']);
    $r .= str_replace(array('{class}', '{content}'), array(' class="'.$options['class'].'"', "$thead\n$tbody\n$tfoot"), $indicia_templates['report-table'])."\n";
    if($haveUpdates){
      self::$javascript .= "
function checkErrors(data) {
  if (typeof data.error!==\"undefined\") {
    if (typeof data.errors!==\"undefined\") {
      $.each(data.errors, function(idx, error) {
        alert(error);
      });
    } else {
      alert('An error occured when trying to save the data: '+data.error);
    }
    // data.transaction_id stores the last cell at the time of the post.
    var selector = '#'+data.transaction_id.replace(/:/g, '\\\\:');
    $(selector).focus();
    $(selector).select();
    return false;
  } else {
    return true;
  }
}
$('.update-input').focus(function(evt) {
  $(evt.target).addClass('input-selected');
}).on('change', function(evt) {
  $(evt.target).addClass('input-edited');
}).on('blur', function(evt) {
  var selector = '#'+evt.target.id.replace(/:/g, '\\:');
  currentCell = evt.target.id;
  $(selector).removeClass('input-selected');
  if ($(selector).hasClass('input-edited')) {
    $(selector).addClass('input-saving');
    // WARNING No validation currently applied...
    $(selector).parent().submit();
  }
});
";
    }
    if ($options['sendOutputToMap']) {
      $strokeWidthFn = "getstrokewidth: function(feature) {
        var width=feature.geometry.getBounds().right - feature.geometry.getBounds().left,
          strokeWidth=(width===0) ? 1 : %d - (width / feature.layer.map.getResolution());
        return (strokeWidth<%d) ? %d : strokeWidth;
      }";
      self::addFeaturesLoadingJs($addFeaturesJs, 'OpenLayers.Util.extend(OpenLayers.Feature.Vector.style[\'default\'], '.
          '{"strokeColor":"#0000ff","fillColor":"#3333cc","fillOpacity":0.6,"strokeWidth":"${getstrokewidth}"})',
          '{"strokeColor":"#ff0000","fillColor":"#ff0000","fillOpacity":0.6,"strokeWidth":"${getstrokewidth}"}',
          ', {context: { '.sprintf($strokeWidthFn, 9, 2, 2).' }}',
          ', {context: { '.sprintf($strokeWidthFn, 10, 3, 3).' }}', $options['zoomMapToOutput']);
    }
    $uniqueName = 'grid_' . preg_replace( "/[^a-z0-9]+/", "_", $options['id']);
    $group = preg_replace( "/[^a-zA-Z0-9]+/", "_", $options['reportGroup']);
    // $r may be empty if a spatial report has put all its controls on the map toolbar, when using params form only mode.
    // In which case we don't need to output anything.
    if (!empty($r)) {
      if ($options['includeColumnsPicker']) {
        $icon = $imgPath."plus.gif";
        $r .='<img class="col-picker" style="position: absolute; right: 4px; top: 4px;" src="'.$icon.'"  >';
        self::add_resource('jquery_cookie');
      }
      // Output a div to keep the grid and pager together
      $r = "<div id=\"".$options['id']."\" class=\"report-grid-container\">$r</div>\n";

      // Add responsive behaviour to table if specified in options.
      // The table is made responsive with the footables plugin based on the
      // data-hide attributes added to the <th> elements
      if (!empty($options['responsiveOpts'])) {
        // Add the javascript plugins.
        self::add_resource('indiciaFootableReport');
        // Add inline javascript to invoke the plugins on this grid.
        $footable_options = json_encode($options['responsiveOpts']);
        self::$javascript .= "jQuery('#{$options['id']}').indiciaFootableReport($footable_options);\n";

        // Footable needs calling after each Ajax update. There is an existing
        // callback option which we can use but it only accepts a single function.
        // Therefore, create a new function and append any pre-existing callback.
        // Note, '-' is not allowed in JavaScript identifiers.
        $callback = 'callback_' . str_replace('-', '_', $options['id']);
        // create JS call to existing callback if it exists
        $callToCallback = empty($options['callback']) ? '' : "  window['$options[callback]']();\n";
        self::$javascript .= "
window['$callback'] = function() {
  jQuery('#$options[id]').find('table').trigger('footable_redraw');
$callToCallback}";
        // Store the callback name to pass to jquery.reportgrid.js.
        $options['callback'] = $callback;
      }

      // Now AJAXify the grid
      self::add_resource('reportgrid');
      global $indicia_templates;
      $warehouseUrl = self::$base_url;
      $rootFolder = self::getRootFolder() . (empty($pathParam) ? '' : "?$pathParam=");
      if (isset($options['sharing'])) {
        $options['extraParams']['sharing']=$options['sharing'];
      }
      // Full list of report parameters to load on startup.
      $extraParams = array_merge(
        $options['extraParams'],
        $currentParamValues,
        self::$initialFilterParamsToApply
      );
      $extraParams = json_encode($extraParams, JSON_FORCE_OBJECT);
      // List of report parameters that cannot be changed by the user.
      $fixedParams = json_encode($options['extraParams'], JSON_FORCE_OBJECT);
      $immutableParams = json_encode($options['immutableParams'], JSON_FORCE_OBJECT);
      self::$javascript .= "
if (typeof indiciaData.reports.$group==='undefined') { indiciaData.reports.$group={}; }
indiciaFns.simpleTooltip('input.col-filter','tooltip');
indiciaData.reports.$group.$uniqueName = $('#".$options['id']."').reportgrid({
  id: '$options[id]',
  mode: '$options[mode]',
  dataSource: '" . str_replace('\\','/',$options['dataSource']) . "',
  extraParams: $extraParams,
  fixedParams: $fixedParams,
  immutableParams: $immutableParams,
  view: '$options[view]',
  itemsPerPage: $options[itemsPerPage],
  auth_token: '{$options['readAuth']['auth_token']}',
  nonce: '{$options['readAuth']['nonce']}',
  callback: '$options[callback]',
  url: '$warehouseUrl',
  reportGroup: '$options[reportGroup]',
  rememberGridPosition: " . ($options['rememberGridPosition'] ? 'true' : 'false') . ",
  autoParamsForm: '$options[autoParamsForm]',
  rootFolder: '" . $rootFolder . "',
  imageFolder: '" . self::get_uploaded_image_folder() . "',
  imageThumbPreset: '$options[imageThumbPreset]',
  currentUrl: '$currentUrl[path]',
  rowId: '" . (isset($options['rowId']) ? $options['rowId'] : '') . "',
  currentPageCount: " . min($options['itemsPerPage'], count($records)) . ",
  galleryColCount: $options[galleryColCount],
  pagingTemplate: '$indicia_templates[paging]',
  pathParam: '$pathParam',
  sendOutputToMap: " . ((isset($options['sendOutputToMap']) && $options['sendOutputToMap']) ? 'true' : 'false') . ",
  linkFilterToMap: " . (!empty($options['rowId']) && $options['linkFilterToMap'] ? 'true' : 'false') . ",
  msgRowLinkedToMapHint: '" . addslashes(lang::get('Click the row to highlight the record on the map. Double click to zoom in.')) . "',
  msgNoInformation: '" . addslashes(lang::get('No information available')) . "',
  langFirst: '" . lang::get('first') . "',
  langPrev: '" . lang::get('prev') . "',
  langNext: '" . lang::get('next') . "',
  langLast: '" . lang::get('last') . "',
  langShowing: '" . lang::get('Showing records {1} to {2} of {3}') . "',
  noRecords: '" . lang::get('No records')."',
  noInfoAsPageTooHigh: '" . lang::get('No information available for the requested page of data. Use the page buttons below to load a different page.')."',
  altRowClass: '$options[altRowClass]',
  actionButtonTemplate: '" . $indicia_templates['report-action-button'] ."'";
      if (!empty($options['rowClass']))
        self::$javascript .= ",\n  rowClass: '".$options['rowClass']."'";
      if (isset($options['filters']))
        self::$javascript .= ",\n  filters: ".json_encode($options['filters']);
      if (isset($orderby))
        self::$javascript .= ",\n  orderby: '".$orderby."'";
      if (isset($sortdir))
        self::$javascript .= ",\n  sortdir: '".$sortdir."'";
      if (isset($response['count']))
        self::$javascript .= ",\n  recordCount: ".$response['count'];
      if (isset($options['columns']))
        self::$javascript .= ",\n  columns: ".json_encode($options['columns']);
      self::$javascript .= "\n});\n";
    }
    if (isset($options['sendOutputToMap']) && $options['sendOutputToMap']) {
      self::$javascript.= "mapSettingsHooks.push(function(opts) {\n";
      self::$javascript.= "  opts.clickableLayers.push(indiciaData.reportlayer);\n";
      self::$javascript.= "  opts.clickableLayersOutputMode='reportHighlight';\n";
      self::$javascript .= "});\n";
    }
    if ($options['ajax'] && $options['autoloadAjax']) {
      self::$onload_javascript .= <<<JS
if (!indiciaData.reports.$group.{$uniqueName}[0].settings.populated) {
  indiciaData.reports.$group.$uniqueName.ajaxload(true);
}

JS;
    }
    elseif (!$options['ajax']) {
      self::$onload_javascript .= "indiciaData.reports.$group.$uniqueName.setupPagerEvents();\n";
    }
    return $r;
  }

  /**
   * Returns a navigation link for report_grid data.
   *
   * If JavaScript disabled, links are used in column headers and pagination to
   * reload the page with parameters added to sort or page through the data.
   * These can be disabled, e.g. on public facing pages where you don't want
   * search engines paging through the data.
   *
   * @param string $link
   *   URL to load.
   * @param bool $ajaxLinksOnly
   *   If TRUE, then an empty string is returned.
   *
   * @return string
   *   Href attribute HTML.
   */
  private static function getGridNavHref($link, $ajaxLinksOnly) {
    return $ajaxLinksOnly ? '' : ' href="' . htmlspecialchars($link) . '" rel="nofollow"';
  }

  /**
   * Loops through the actions defined in a report column configuration and passes the captions through translation.
   * @param array $actions List of actions defined for the column in the config.
   */
  private static function translateActions(&$actions) {
    foreach ($actions as &$action) {
      if (!empty($action['caption']))
        $action['caption'] = lang::get($action['caption']);
    }
  }

 /**
  * Requests the data for a report from the reporting services.

  * @param array $response
  *   Data to be returned.
  * @param array $options
  *   Options array defining the report request.
  * @param array $currentParamValues
  *   Array of current parameter values, e.g. the contents of parameters form.
  * @param boolean $wantCount
  *   Set to true if a count of total results (ignoring limit) is required in
  *   the response.
  * @param string $extras
  *   Set any additional URL filters if required, e.g. taxon_list_id=1 to
  *   filter for taxon list 1.
  */
  public static function request_report(&$response, &$options, &$currentParamValues, $wantCount, $extras='') {
    $extras .= '&wantColumns=1&wantParameters=1&wantCount=' . ($wantCount ? '1' : '0');
    // Find the definitive list of parameters to exclude from the parameters returned by the API
    $options['paramsToExclude'] = array_keys(self::$filterParamsToGloballySkip);
    if (isset($options['ignoreParams'])) {
      $options['paramsToExclude'] = array_merge($options['paramsToExclude'], $options['ignoreParams']);
    }
        // Any extraParams are fixed values that don't need to be available in the params form, so they can be added to the
    // list of parameters to exclude from the params form.
    if (isset($options['extraParams'])) {
      $options['paramsToExclude'] = array_merge($options['paramsToExclude'], array_keys($options['extraParams']));
    }
    if (!empty($options['paramsToExclude'])) {
      $extras .= '&paramsFormExcludes='.json_encode($options['paramsToExclude']);
    }
    // specify the view variant to load, if loading from a view
    if ($options['mode']=='direct') $extras .= '&view='.$options['view'];
    $currentParamValues = self::getReportGridCurrentParamValues($options);
    // if loading the parameters form only, we don't need to send the parameter values in the report request but instead
    // mark the request not to return records
    if (isset($options['paramsOnly']) && $options['paramsOnly'])
      $extras .= '&wantRecords=0&wantCount=0&wantColumns=0';
    else
      $extras .= '&'.self::array_to_query_string(
        array_merge($currentParamValues, self::$initialFilterParamsToApply), true);
    // allow URL parameters to override any extra params that are set. Default params
    // are handled elsewhere.
    if (isset($options['extraParams']) && isset($options['reportGroup'])) {
      foreach ($options['extraParams'] as $key=>&$value) {
        // allow URL parameters to override the provided params
        if (isset($_REQUEST[$options['reportGroup'] . '-' . $key]))
          $value = $_REQUEST[$options['reportGroup'] . '-' . $key];
      }
    }
    $response = self::get_report_data($options, $extras);
  }

  /**
   * Returns the parameters form for a report, only if it is needed because there are
   * parameters without preset values to fill in.
   * @param array $response Response from the call to the report services, which may contain
   * a parameter request.
   * @param array $options Array of report options.
   * @param string $currentParamValues Array of current parameter values, e.g. the contents
   * of a submitted parameters form.
   */
  private static function paramsFormIfRequired($response, $options, $currentParamValues) {
    if (isset($response['parameterRequest'])) {
      // We put report param controls into their own divs, making layout easier. Unless going in the
      // map toolbar as they will then be inline.
      global $indicia_templates;
      $oldprefix = $indicia_templates['prefix'];
      $oldsuffix = $indicia_templates['suffix'];
      if (isset($options['paramPrefix']))
        $indicia_templates['prefix']=$options['paramPrefix'];
      if (isset($options['paramSuffix']))
        $indicia_templates['suffix']=$options['paramSuffix'];
      $r = self::getReportGridParametersForm($response, $options, $currentParamValues);
      $indicia_templates['prefix'] = $oldprefix;
      $indicia_templates['suffix'] = $oldsuffix;
      return $r;
    } elseif ($options['autoParamsForm'] && $options['mode']=='direct') {
      // loading records from a view (not a report), so we can put a simple filter parameter form at the top.
      return self::getDirectModeParamsForm($options);
    }

    return ''; // no form required
  }

  /**
   * Output pagination links.
   * @param array $options Report options array.
   * @param string $pageUrl The URL of the page to reload when paginating (normally the current page). Only
   * used when JavaScript is disabled.
   * @param array $sortAndPageUrlParams Current parameters for the page and sort order.
   * @param array $response Response from the call to reporting services, which we are paginating.
   * @return string The HTML for the paginator.
   */
  private static function outputPager($options, $pageUrl, $sortAndPageUrlParams, $response) {
    if ($options['pager']) {
      global $indicia_templates;
      $pagLinkUrl = $pageUrl . ($sortAndPageUrlParams['orderby']['value'] ? $sortAndPageUrlParams['orderby']['name'].'='.$sortAndPageUrlParams['orderby']['value'].'&' : '');
      $pagLinkUrl .= $sortAndPageUrlParams['sortdir']['value'] ? $sortAndPageUrlParams['sortdir']['name'].'='.$sortAndPageUrlParams['sortdir']['value'].'&' : '';
      if (!isset($response['count'])) {
        $r = self::simplePager($options, $sortAndPageUrlParams, $response, $pagLinkUrl);
      } else {
        $r = self::advancedPager($options, $sortAndPageUrlParams, $response, $pagLinkUrl);
      }
      $r = str_replace('{paging}', $r, $indicia_templates['paging_container']);
      if (count($response['records'])===0)
        self::$javascript .= "$('#$options[id] .pager').hide();\n";
      return $r;
    }
    else
      return '';
  }

 /**
  * Creates the HTML for the simple version of the pager.
  * @param array $options Report options array.
  * @param array $sortAndPageUrlParams Current parameters for the page and sort order.
  * @param array $response Response from the call to reporting services, which we are paginating.
  * @param string $pagLinkUrl The basic URL used to construct page reload links in the pager.
  * @return string The HTML for the simple paginator.
  */
  private static function simplePager($options, $sortAndPageUrlParams, $response, $pagLinkUrl) {
    // Skip pager if all records fit on one page, or if doing AJAX initial population as JS will add pager
    // when ready.
    if ($sortAndPageUrlParams['page']['value'] == 0 && count($response['records']) <= $options['itemsPerPage']) {
      return '';
    }
    else {
      $r = '';
      // If not on first page, we can go back.
      if ($sortAndPageUrlParams['page']['value']>0) {
        $prev = max(0, $sortAndPageUrlParams['page']['value']-1);
        $pagingHref = self::getGridNavHref($pagLinkUrl . $sortAndPageUrlParams['page']['name'] . "=$prev", $options['ajaxLinksOnly']);
        $r .= "<a class=\"pag-prev pager-button\"$pagingHref>".lang::get('previous')."</a> \n";
      } else
        $r .= "<span class=\"pag-prev ui-state-disabled pager-button\">".lang::get('previous')."</span> \n";
      // if the service call returned more records than we are displaying (because we asked for 1 more), then we can go forward
      if (count($response['records'])>$options['itemsPerPage']) {
        $next = $sortAndPageUrlParams['page']['value'] + 1;
        $pagingHref = self::getGridNavHref($pagLinkUrl . $sortAndPageUrlParams['page']['name'] . "=$next", $options['ajaxLinksOnly']);
        $r .= "<a class=\"pag-next pager-button\"$pagingHref>".lang::get('next')." &#187</a> \n";
      } else
        $r .= "<span class=\"pag-next ui-state-disabled pager-button\">".lang::get('next')."</span> \n";
      return $r;
    }
  }

 /**
  * Creates the HTML for the advanced version of the pager.
  * @param array $options Report options array.
  * @param array $sortAndPageUrlParams Current parameters for the page and sort order.
  * @param array $response Response from the call to reporting services, which we are paginating.
  * @param string $pagLinkUrl The basic URL used to construct page reload links in the pager.
  * @return string The HTML for the advanced paginator.
  */
  private static function advancedPager($options, $sortAndPageUrlParams, $response, $pagLinkUrl) {
    global $indicia_templates;
    $replacements = [];
    // build a link URL to an unspecified page
    $pagLinkUrl .= $sortAndPageUrlParams['page']['name'];
    // If not on first page, we can include previous link.
    if ($sortAndPageUrlParams['page']['value']>0) {
      $prev = max(0, $sortAndPageUrlParams['page']['value']-1);
      $replacements['prev'] = "<a class=\"pag-prev pager-button\"" . self::getGridNavHref("$pagLinkUrl=$prev", $options['ajaxLinksOnly']) . ">".lang::get('previous')."</a> \n";
      $replacements['first'] = "<a class=\"pag-first pager-button\"" . self::getGridNavHref("$pagLinkUrl=0", $options['ajaxLinksOnly']) . ">".lang::get('first')."</a> \n";
    } else {
      $replacements['prev'] = "<span class=\"pag-prev pager-button ui-state-disabled\">".lang::get('prev')."</span>\n";
      $replacements['first'] = "<span class=\"pag-first pager-button ui-state-disabled\">".lang::get('first')."</span>\n";
    }
    $pagelist = '';
    $page = ($sortAndPageUrlParams['page']['value'] ? $sortAndPageUrlParams['page']['value'] : 0)+1;
    for ($i=max(1, $page-5); $i<=min(ceil($response['count']/$options['itemsPerPage']), $page+5); $i++) {
      if ($i===$page)
        $pagelist .= "<span class=\"pag-page pager-button ui-state-disabled\" id=\"page-".$options['id']."-$i\">$i</span>\n";
      else
        $pagelist .= "<a class=\"pag-page pager-button\"" . self::getGridNavHref("$pagLinkUrl=" . ($i - 1), $options['ajaxLinksOnly']) . " id=\"page-".$options['id']."-$i\">$i</a>\n";
    }
    $replacements['pagelist'] = $pagelist;
    // if not on the last page, display a next link
    if ($page<$response['count']/$options['itemsPerPage']) {
      $next = $sortAndPageUrlParams['page']['value'] + 1;
      $replacements['next'] = "<a class=\"pag-next pager-button\"" . self::getGridNavHref("$pagLinkUrl=$next", $options['ajaxLinksOnly']) . ">".lang::get('next')."</a>\n";
      $last = round($response['count'] / $options['itemsPerPage'] - 1);
      $replacements['last'] = "<a class=\"pag-last pager-button\"" . self::getGridNavHref("$pagLinkUrl=$last", $options['ajaxLinksOnly']) . ">".lang::get('last')."</a>\n";
    } else {
      $replacements['next'] = "<span class=\"pag-next pager-button ui-state-disabled\">".lang::get('next')."</span>\n";
      $replacements['last'] = "<span class=\"pag-last pager-button ui-state-disabled\">".lang::get('last')."</span>\n";
    }
    if ($response['count']) {
      $replacements['showing'] = '<span class="pag-showing">'.lang::get('Showing records {1} to {2} of {3}',
          ($page-1)*$options['itemsPerPage']+1,
          min($page*$options['itemsPerPage'], $response['count']),
          $response['count']).'</span>';
    } else {
      $replacements['showing'] = lang::get('No records');
    }
    $r = $indicia_templates['paging'];
    foreach($replacements as $search => $replace)
      $r = str_replace('{'.$search.'}', $replace, $r);
    return $r;
  }

/**
  * <p>Outputs a div that contains a chart.</p>
  * <p>The chart is rendered by the jqplot plugin.</p>
  * <p>The chart loads its data from a report, table or view indicated by the dataSource parameter, and the
  * method of loading is indicated by xValues, xLabels and yValues. Each of these can be an array to define
  * a multi-series chart. The largest array from these 4 options defines the total series count. If an option
  * is not an array, or the array is smaller than the total series count, then the last option is used to fill
  * in the missing values. For example, by setting:<br/>
  * 'dataSource' => array('report_1', 'report_2'),<br/>
  * 'yValues' => 'count',<br/>
  * 'xLabels' => 'month'<br/>
  * then you get a chart of count by month, with 2 series' loaded separately from report 1 and report 2. Alternatively
  * you can use a single report, with 2 different columns for count to define the 2 series:<br/>
  * 'dataSource' => 'combined_report',<br/>
  * 'yValues' => array('count_1','count_2'),<br/>
  * 'xLabels' => 'month'<br/>
  * The latter is obviuosly slightly more efficient as only a single report is run. Pie charts will always revert to a
  * single series.</p>
  * <p>For summary reports, the user can optionally setup clicking functionality so that another report is called when the user clicks on the chart.</p>
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>mode</b><br/>
  * Pass report to retrieve the underlying chart data from a report, or direct for an Indicia table or view. Default is report.</li>
  * <li><b>readAuth</b><br/>
  * Read authorisation tokens.</li>
  * <li><b>dataSource</b><br/>
  * Name of the report file or table/view(s) to retrieve underlying data. Can be an array for multi-series charts.</li>
  * <li><b>class</b><br/>
  * CSS class to apply to the outer div.</li>
  * <li><b>headerClass</b><br/>
  * CSS class to apply to the box containing the header.</li>
  * <li><b>reportGroup</b><br/>
  * When joining multiple reports together, this can be used on a report that has autoParamsForm set to false to bind the report to the
  * parameters form from a different report by giving both report controls the same reportGroup string. This will only work when all
  * parameters required by this report are covered by the other report's parameters form.</li>
  * <li><b>rememberParamsReportGroup</b><br/>
  * Enter any value in this parameter to allow the report to save its parameters for the next time the report is loaded.
  * The parameters are saved site wide, so if several reports share the same value and the same report group then the parameter
  * settings will be shared across the reports even if they are on different pages of the site. For example if several reports on the
  * site have an ownData boolean parameter which filters the data to the user's own data, this can be set so that the reports all
  * share the setting. This functionality requires cookies to be enabled on the browser.</li>
  * <li><b>height</b><br/>
  * Chart height in pixels.</li>
  * <li><b>width</b><br/>
  * Chart width in pixels or as a percentage followed by a % symbol.</li>
  * <li><b>chartType</b><br/>
  * Currently supports line, bar or pie.</li>
  * <li><b>rendererOptions</b><br/>
  * Associative array of options to pass to the jqplot renderer.
  * </li>
  * <li><b>gridOptions</b><br/>
  * Associative array of options to pass to the jqplot grid object.
  * </li>
  * <li><b>legendOptions</b><br/>
  * Associative array of options to pass to the jqplot legend. For more information see links below.
  * </li>
  * <li><b>seriesOptions</b><br/>
  * For line and bar charts, associative array of options to pass to the jqplot series. For example:<br/>
  * 'seriesOptions' => array(array('label' => 'My first series','label' => 'My 2nd series'))<br/>
  * For more information see links below.
  * </li>
  * <li><b>seriesColors</b><br/>
  * JSON array of CSS colour specifications for each consecutive data point in the series.
  * </li>
  * <li><b>axesOptions</b><br/>
  * For line and bar charts, associative array of options to pass to the jqplot axes. For example:<br/>
  * 'axesOptions' => array('yaxis'=>array('min' => 0, 'max' => '3', 'tickInterval' => 1))<br/>
  * For more information see links below.
  * </li>
  * <li><b>yValues</b><br/>
  * Report or table field name(s) which contains the data values for the y-axis (or the pie segment sizes). Can be
  * an array for multi-series charts.</li>
  * <li><b>xValues</b><br/>
  * Report or table field name(s) which contains the data values for the x-axis. Only used where the x-axis has a numerical value
  * rather than showing arbitrary categories. Can be an array for multi-series charts.</li>
  * <li><b>xLabels</b><br/>
  * When the x-axis shows arbitrary category names (e.g. a bar chart), then this indicates the report or view/table
  * field(s) which contains the labels. Also used for pie chart segment names. Can be an array for multi-series
  * charts.</li>
  * <li><b>sharing</b>
  * Assuming the report has been written to take account of website sharing agreements, set this to define the task
  * you are performing with the report and therefore the type of sharing to allow. Options are reporting (default),
  * verification, moderation, peer_review, data_flow, editing, website (this website only) or me (my data only).</li>
  * <li><b>UserId</b>
  * If sharing=me, then this must contain the Indicia user ID of the user to return data for.
  * </li>
  * <li><b>linkToReportPath</b>
  * Allows drill down into reports. Holds the URL of the report that is called when the user clicks on
  * a chart data item. When this is not set, the report click functionality is disabled. The path will have replacement
  * tokens replaced where the token is the report output field name wrapped in # and the token will be replaced by the
  * report output value for the row clicked on. For example, you can specify id=#id# in the URL to define a URL
  * parameter to receive the id field in the report output. In addition, create a global JavaScript function
  * on the page called handle_chart_click_path and this will be called with the path, series index, point index and row data as parameters. It can
  * then return the modified path, so you can write custom logic, e.g. to map the series index to a specific report filter.
  * </li>
  * <li><b>responsive</b>
  * If set to true, redraws plot to fit screen width.
  * </li>
  * </ul>
  * @todo look at the ReportEngine to check it is not prone to SQL injection (eg. offset, limit).
  * @link http://www.jqplot.com/docs/files/jqplot-core-js.html#Series
  * @link http://www.jqplot.com/docs/files/jqplot-core-js.html#Axis
  * @link http://www.jqplot.com/docs/files/plugins/jqplot-barRenderer-js.html
  * @link http://www.jqplot.com/docs/files/plugins/jqplot-lineRenderer-js.html
  * @link http://www.jqplot.com/docs/files/plugins/jqplot-pieRenderer-js.html
  * @link http://www.jqplot.com/docs/files/jqplot-core-js.html#Legend
  */
  public static function report_chart($options) {
    $options = self::getReportGridOptions($options);
    if (empty($options['rendererOptions']))
      $options['rendererOptions'] = [];
    if (empty($options['axesOptions']))
      $options['axesOptions'] = [];
    $currentParamValues = self::getReportGridCurrentParamValues($options);
    //If we want the report_chart to only return the parameters control, then don't provide
    //the report with parameters so that it will return parameter requests for all the
    //parameters which can then be displayed on the screen.
    //Use != 1, as am not sure what style all the existing code would provide the $options['paramsOnly']
    //as being set to true.
    if (empty($options['paramsOnly']) || $options['paramsOnly']!=1)
      $options['extraParams'] = array_merge($options['extraParams'],$currentParamValues);
    // @todo Check they have supplied a valid set of data & label field names
    self::add_resource('jqplot');
    $opts = [];
    switch ($options['chartType']) {
      case 'bar' :
        self::add_resource('jqplot_bar');
        $renderer='$.jqplot.BarRenderer';
        break;
      case 'pie' :
        self::add_resource('jqplot_pie');
        $renderer='$.jqplot.PieRenderer';
        break;
      // default is line
    }
    self::checkForJqplotPlugins($options);
    $opts[] = "seriesDefaults:{\n      " .
      (isset($renderer) ? "renderer:$renderer,\n      " : '') .
      "rendererOptions:" . json_encode($options['rendererOptions']) .
      "\n    }";
    $optsToCopyThrough = [
      'legend' => 'legendOptions',
      'series' => 'seriesOptions',
      'seriesColors' => 'seriesColors',
      'grid' => 'gridOptions'
    ];
    foreach ($optsToCopyThrough as $key=>$settings) {
      if (!empty($options[$settings]))
        $opts[] = "$key:".json_encode($options[$settings]);
    }
    // make yValues, xValues, xLabels and dataSources into arrays of the same length so we can treat single and multi-series the same
    $yValues = is_array($options['yValues']) ? $options['yValues'] : array($options['yValues']);
    $dataSources = is_array($options['dataSource']) ? $options['dataSource'] : array($options['dataSource']);
    if (isset($options['xValues'])) $xValues = is_array($options['xValues']) ? $options['xValues'] : array($options['xValues']);
    if (isset($options['xLabels'])) $xLabels = is_array($options['xLabels']) ? $options['xLabels'] : array($options['xLabels']);
    // What is this biggest array? This is our series count.
    $seriesCount = max(
        count($yValues),
        count($dataSources),
        (isset($xValues) ? count($xValues) : 0),
        (isset($xLabels) ? count($xLabels) : 0)
    );
    // any array that is too short must be padded out with the last entry
    if (count($yValues)<$seriesCount) $yValues = array_pad($yValues, $seriesCount, $yValues[count($yValues)-1]);
    if (count($dataSources)<$seriesCount) $dataSources = array_pad($dataSources, $seriesCount, $dataSources[count($dataSources)-1]);
    if (isset($xValues) && count($xValues)<$seriesCount) $xValues = array_pad($xValues, $seriesCount, $xValues[count($xValues)-1]);
    if (isset($xLabels) && count($xLabels)<$seriesCount) $xLabels = array_pad($xLabels, $seriesCount, $xLabels[count($xLabels)-1]);
    // other chart options
    if (isset($options['stackSeries']) && $options['stackSeries'])
      $opts[] = 'stackSeries: true';
    if(isset($options['linkToReportPath']))
      // if linking to another report when clicked, store the full data so we can pass it as a parameter to the report
      self::$javascript .= "indiciaData.reportData=[];\n";
    // build the series data
    $seriesData = [];
    $lastRequestSource = '';
    $xLabelsForSeries=[];
    $r = '';
    for ($idx=0; $idx<$seriesCount; $idx++) {
      // copy the array data back into the options array to make a normal request for report data
      $options['yValues'] = $yValues[$idx];
      $options['dataSource'] = $dataSources[$idx];
      if (isset($xValues)) $options['xValues'] = $xValues[$idx];
      if (isset($xLabels)) $options['xLabels'] = $xLabels[$idx];
      // now request the report data, only if the last request was not for the same data
      if ($lastRequestSource != $options['dataSource'])
        $data=self::get_report_data($options);
      if (isset($data['error']))
        // data returned must be an error message so may as well display it
        return $data['error'];
      $r = self::paramsFormIfRequired($data, $options, $currentParamValues);
      //If we don't have any data for the chart, or we only want to display the params form,
      //then return $r before we even reach the chart display code.
      //Use '==' as the comparison once again as am not sure what style the exiting code will provide
      //$options['paramsOnly'] as being true.
      if ((!empty($options['paramsOnly']) && ($options['paramsOnly'])==1) || !isset($data[0])) {
        return $r;
      }
      if (isset($data['parameterRequest']))
        $r .= self::build_params_form(array_merge($options, array('form'=>$data['parameterRequest'], 'defaults'=>$currentParamValues)), $hasVisibleContent);

      $lastRequestSource = $options['dataSource'];
      $values=[];
      $jsData = [];
      foreach ($data as $row) {
        if (isset($options['xValues']))
          // 2 dimensional data
          $values[] = array(self::stringOrFloat($row[$options['xValues']]), self::stringOrFloat($row[$options['yValues']]));
        else {
          // 1 dimensional data, so we should have labels. For a pie chart these are use as x data values. For other charts they are axis labels.
          if ($options['chartType']=='pie') {
            $values[] = array(lang::get($row[$options['xLabels']]), self::stringOrFloat($row[$options['yValues']]));
          } else {
            $values[] = self::stringOrFloat($row[$options['yValues']]);
            if (isset($options['xLabels']) && $idx===0)
              // get x labels from the first series only
              $xLabelsForSeries[] = $row[$options['xLabels']];
          }
        }
        // pie charts receive click information with the pie segment label. Bar charts receive the bar index.
        if (isset($options['linkToReportPath'])) {
          if ($options['chartType']==='pie')
            $jsData[$row['name']] = $row;
          else
            $jsData[] = $row;
        }
      }
      // each series will occupy an entry in $seriesData
      $seriesData[] = $values;
      if(isset($options['linkToReportPath']))
        self::$javascript .= "indiciaData.reportData[$idx]=" . json_encode($jsData) . ";\n";
    }

    if (isset($options['xLabels']) && $options['chartType']!='pie') {
      // make a renderer to output x axis labels
      $options['axesOptions']['xaxis']['renderer'] = '$.jqplot.CategoryAxisRenderer';
      $options['axesOptions']['xaxis']['ticks'] = $xLabelsForSeries;
    }
    if (isset($options['axesOptions']['yaxis']) && isset($options['axesOptions']['yaxis']['label'])) {
      $options['axesOptions']['yaxis']['labelRenderer'] = '$.jqplot.CanvasAxisLabelRenderer';
    }
    // We need to fudge the json so the renderer class is not a string
    $opts[] = str_replace(array('"$.jqplot.CategoryAxisRenderer"','"$.jqplot.CanvasAxisLabelRenderer"'), array('$.jqplot.CategoryAxisRenderer','$.jqplot.CanvasAxisLabelRenderer'),
        'axes:'.json_encode($options['axesOptions']));

    // Finally, dump out the Javascript with our constructed parameters
    if (empty($options['id'])) {
      $options['id'] = 'report-chart-' . rand();
    }
    $plotName = preg_replace('/[^a-zA-Z0-9]/', '_', $options['id']);
    $encodedData = json_encode($seriesData);
    $optsList = implode(",\n    ", $opts);
    self::$javascript .= <<<JS
var $plotName = $.jqplot(
  '$options[id]',
  $encodedData,
  {
    $optsList
  }
);

JS;

    // Store the plot object with its div.
    self::$javascript .= "$('#{$options['id']}').data('jqplot', $plotName);\n";
    //once again we only include summary report clicking functionality if user has setup the appropriate options
    if(isset($options['linkToReportPath'])) {
      //open the report, note that data[0] varies depending on whether we are using a pie or bar. But we have
      //saved the data to the array twice already to handle this
      // Note the data[0] is a pie label, or a 1 indexed bar index.
      self::$javascript .= "$('#$options[id]').on('jqplotDataClick',
  function(ev, seriesIndex, pointIndex, data) {
    var path='$options[linkToReportPath]';
    var rowId = " . ($options['chartType']==='pie' ? 'data[0]' : 'data[0]-1') . ";
    if (typeof handle_chart_click_path!=='undefined') {
      // custom path handler
      path=handle_chart_click_path(path, seriesIndex, pointIndex, indiciaData.reportData[seriesIndex][rowId]);
    }
    // apply field replacements from the report row that we clicked on
    $.each(indiciaData.reportData[seriesIndex][rowId], function(field, val) {
      path = path.replace(new RegExp('#'+field+'#', 'g'), val);
    });
    window.location=path.replace(/#series#/g, seriesIndex);
  }
);\n";
    }
    self::$javascript .= <<<JS
$('#$options[id]').on('jqplotDataHighlight', function(ev, seriesIndex, pointIndex, data) {
  $('table.jqplot-table-legend td').removeClass('highlight');
  $('table.jqplot-table-legend td').filter(function() {
    return this.textContent == data[0];
  }).addClass('highlight');
});
$('#$options[id]').on('jqplotDataUnhighlight', function(ev, seriesIndex, pointIndex, data) {
  $('table.jqplot-table-legend td').removeClass('highlight');
});

JS;
    if (!empty($options['responsive'])) {
      // Make plots responsive.
      $options['width'] = '100%';

      static $handlers_once = false;
      if (!$handlers_once) {
        // Only need to emit event handlers once.
        self::$javascript .= "$(window).on('resize', function(){
          // Calculate scaling factor to alter dimensions according to width.
          var scaling = 1;
          var shadow = true;
          var placement = 'outsideGrid';
          var location = 'ne';
          var width = $(window).width()
          if (width < 480) {
            scaling = 0;
            shadow = false;
            placement = 'outside';
            location = 's';
          }
          else if (width < 1024) {
            scaling = (width - 480) / (1024 - 480);
          }

          $('.jqplot-target').each(function() {
            var jqp = $(this).data('jqplot');
            for (var plugin in jqp.plugins) {
              if (plugin == 'pieRenderer' && jqp.legend.show) {
                jqp.legend.placement = placement;
                jqp.legend.location = location;
              }
              else if (plugin == 'barRenderer') {
                $.each(jqp.series, function(i, series) {
                  series.barWidth = undefined;
                  series.shadow = shadow;
                  series.shadowWidth = scaling * 3;
                  series.barMargin = 8 * scaling + 2;
                });
              }
            }
            jqp.replot({resetAxes: true});
          });
        });\n";
        $handlers_once = true;
      }
    }
    // If plots are hidden across several tabs, replot when tab is activated.
    self::$javascript .= <<<JS
indiciaFns.reflowAllCharts = function(evt, ui) {
  var panel;
  var plots;
  if (typeof ui === 'undefined') {
    plots = $('.jqplot-target');
  } else {
    panel = ui.newPanel==='undefined' ? ui.panel : ui.newPanel[0];
    plots = $(panel).find('.jqplot-target');
  }
  if (plots.length > 0) {
    // The activated panel holds jqplots.
    plots.each(function() {
      var jqp = $(this).data('jqplot');
      jqp.replot({resetAxes: true});
    });
  }
};
var tabs = $('#$options[id]').closest('#controls');
if (tabs.length > 0) {
  indiciaFns.bindTabsActivate(tabs, indiciaFns.reflowAllCharts);
};

JS;
    $heightStyle = '';
    $widthStyle = '';
    if (!empty($options['height']))
      $heightStyle = "height: $options[height]px;";
    if (!empty($options['width'])) {
      if (substr($options['width'], -1)!=='%')
        $options['width'] .= 'px';
      $widthStyle = "width: $options[width];";
    }
    $r .= "<div class=\"$options[class]\" style=\"$widthStyle\">";
    if (isset($options['title']))
      $r .= '<div class="'.$options['headerClass'].'">'.$options['title'].'</div>';
    $r .= "<div id=\"$options[id]\" style=\"$heightStyle $widthStyle\"></div>\n";
    $r .= "</div>\n";
    return $r;
  }

  /**
   * Json_encode puts quotes around numbers read from the db, since they end up in string objects.
   * So, convert them back to numbers. If the value is a string, then it is run through translation
   * and returned as a string.
   */
  private static function stringOrFloat($value) {
    return (string)((float) $value) == $value ? (float) $value : lang::get($value);
  }

  /**
   * Checks through the options array for the chart to look for any jqPlot plugins that
   * have been referred to so should be included.
   * Currently only scans for the trendline and category_axis_rendered plugins.
   * @param Array $options Chart control's options array
   */
  private static function checkForJqplotPlugins($options) {
    if (!empty($options['seriesOptions'])) {
      foreach($options['seriesOptions'] as $series) {
        if (isset($series['trendline']))
          self::add_resource('jqplot_trendline');
      }
    }
    if (isset($options['xLabels'])) {
      self::add_resource('jqplot_category_axis_renderer');
    }
    if (isset($options['axesOptions']['yaxis']) && isset($options['axesOptions']['yaxis']['label'])) {
      self::add_resource('jqplot_canvas_axis_label_renderer');
    }
  }

  /**
   * When loading records from a view, put a simple filter parameters form at the top as the view does not specify any
   * parameters.
   * @param array $options Options passed to the report control, which should contain the column definitions.
   */
  private static function getDirectModeParamsForm($options) {
    global $indicia_templates;
    $reloadUrl = self::get_reload_link_parts();
    $r = '<form action="'.$reloadUrl['path'].'" method="get" class="form-inline" id="filterForm-'.$options['id'].'">';
    $r .= '<label for="filters" class="auto">' . lang::get('Filter for') . '</label> ';
    $value = (isset($_GET['filters'])) ? ' value="'.$_GET['filters'].'"' : '';
    $r .= '<input type="text" name="filters" id="filters" class="filterInput form-control"'.$value.'/> ';
    $r .= '<label for="columns" class="auto">'.lang::get('in').'</label> <select name="columns" class="filterSelect form-control" id="columns">';

    foreach ($options['columns'] as $column) {
      if (isset($column['fieldname']) && isset($column['display']) && (!isset($column['visible']) || $column['visible']===false)) {
        $selected = (isset($_GET['columns']) && $_GET['columns']==$column['fieldname']) ? ' selected="selected"' : '';
        $r .= "<option value=\"".$column['fieldname']."\"$selected>".$column['display']."</option>";
      }
    }
    $r .= "</select>\n";
    $r .= '<input type="submit" value="Filter" class="run-filter ' . $indicia_templates['buttonHighlightedClass'] . '"/>'.
        '<button class="clear-filter ' . $indicia_templates['buttonDefaultClass'] . '" style="display: none">Clear</button>';
    $r .= "</form>\n";
    return $r;
  }

 /**
  * A flexible "freeform" report output control.
  *
  * Outputs the content of a report using freeform text templates to create
  * output as required, as opposed to the report_grid which forces a table
  * based output. Has a header and footer plus any number of bands which are
  * output once per row, or once each time a particular field value changes
  * (i.e. acting as a header band).
  *
  * @param array $options
  *   Options array with the following possibilities:
  *   * *mode* - Pass 'report' to retrieve the underlying data from a report,
  *     or 'direct' for an Indicia table or view. Default is report.
  *   * *readAuth* - Read authorisation tokens.
  *   * *dataSource* - Name of the report file or table/view(s) to retrieve
  *     underlying data.
  *   * *id* - CSS id to apply to the outer div. Default is banded-report-n
  *     where n is a unique number.
  *   * *class* - CSS class to apply to the outer div. Default is
  *     banded-report.
  *   * *ajax* - defaults to false. Set to TRUE to enable loading of data on
  *     the client side using AJAX.
  *   * *proxy* - path to a local proxy which will fetch the report data,
  *     instead of directly accessing the warehouse. Allows extra data handling
  *     steps, e.g. caching, to be implemented.
  *   * *reportGroup* - When joining multiple reports together, this can be
  *     used on a report that has autoParamsForm set to false to bind the
  *     report to the parameters form from a different report by giving both
  *     report controls the same reportGroup string. This will only work when
  *     all parameters required by this report are covered by the other
  *     report's parameters form.
  *   * *rememberParamsReportGroup* - Enter any value in this parameter to
  *     allow  the report to save its parameters for the next time the report
  *     is loaded. The parameters are saved site wide, so if several reports
  *     share the same value and the same report group then the parameter
  *     settings will be shared across the reports even if they are on
  *     different pages of the site. For example if several reports on the site
  *     have an ownData boolean parameter which filters the data to the user's
  *     own data, this can be set so that the reports all share the setting.
  *     This functionality requires cookies to be enabled on the browser.
  *   * *header* - Text to output as the header of the report.
  *   * *footer* - Text to output as the footer of the report.
  *   * *bands* - Array of bands to output per row. Each band is itself an
  *     array, with at least an item called 'content' which contains an HTML
  *     template for the output of the band. The template can contain
  *     replacements for each field value in the row, e.g. the replacement
  *     {survey} is replaced with the value of the field called survey. In
  *     addition, the band array can contain a triggerFields element, which
  *     contains an array of the names of fields which act as triggers for the
  *     band to be output. The band will then only be output once at the
  *     beginning of the report, then once each time one of the named trigger
  *     fields' values change. Therefore when using trigger fields the band
  *     acts as a group header band.
  *   * *emptyText* - Text to output in the event of no data being available.
  *   * *sharing* - Assuming the report has been written to take account of
  *     website sharing agreements, set this to define the task you are
  *     performing with the report and therefore the type of sharing to allow.
  *     Options are reporting (default), verification, moderation, peer_review,
  *     data_flow, editing, website (this website only) or me (my data only).
  *   * *UserId* - If sharing=me, then this must contain the Indicia user ID of
  *     the user to return data for.
  *   * *customFieldFns* - works for AJAX mode only. An array of function names
  *     which are added to `indiciaFns` by custom page JavaScript. Each
  *     function takes the data for a row as a parameter and returns formatted
  *     content. The formatted content will then be used to replace any tokens
  *     in a band's content HTML template where the token is `{fn:name}`,
  *     replacing name with the name of the function.
  */
  public static function freeform_report($options) {
    static $freeform_report_idx = 1;
    $options = array_merge(array(
      'id' => 'banded-report-' . $freeform_report_idx,
      'class' => 'banded-report',
      'emptyText' => '',
      'ajax' => FALSE,
    ), $options);
    $freeform_report_idx++;
    if (empty($options['class']))
      // prevent default report grid classes as this is not a grid
      $options['class'] = 'banded-report';
    $options = self::getReportGridOptions($options);
    if ($options['ajax']) {
      self::add_resource('freeformReport');
      if (!isset(self::$indiciaData['freeformReports'])) {
        self::$indiciaData['freeformReports'] = [];
      }
      self::$indiciaData['freeformReports'][$options['id']] = [
        'id' => $options['id'],
        'dataSource' => $options['dataSource'] ?? NULL,
        'proxy' => $options['proxy'] ?? NULL,
        'bands' => $options['bands'],
        'extraParams' => $options['extraParams'] ?? [],
        'customFieldFns' => $options['customFieldFns'] ?? [],
      ];
      return <<<HTML
        <div id="$options[id]" class="$options[class]">
          $options[header]
          <a class="freeform-row-placeholder"></a>
          $options[footer]
        </div>
HTML;
    }
    else {
      self::request_report($response, $options, $currentParamValues, false);
      if (isset($response['error'])) return $response['error'];
      $r = self::paramsFormIfRequired($response, $options, $currentParamValues);
      // return the params form, if that is all that is being requested, or the parameters are not complete.
      if ($options['paramsOnly'] || !isset($response['records'])) return $r;
      $records = $response['records'];

      $options = array_merge(array(
        'header' => '',
        'footer' => '',
        'bands' => []
      ), $options);

      if (!isset($records) || count($records)===0) {
        return $r . $options['emptyText'];
      }
      // add a header
      $r .= "<div id=\"$options[id]\" class=\"$options[class]\">$options[header]";
      $rootFolder = self::getRootfolder(true);
      $sep = strpos($rootFolder, '?')===FALSE ? '?' : '&';
      // output each row
      foreach ($records as $row) {
        // Add some extra replacements for handling links.
        $row['rootFolder'] = $rootFolder;
        $row['sep'] = $sep;
        // For each row, check through the list of report bands.
        foreach ($options['bands'] as &$band) {
          // default is to output a band
          $outputBand = true;
          // if the band has fields which trigger it to be output when they change value between rows,
          // we need to check for changes to see if the band is to be output
          if (isset($band['triggerFields'])) {
            $outputBand = false;
            // Make sure we have somewhere to store the current field values for checking against
            if (!isset($band['triggerValues'])) {
              $band['triggerValues'] = [];
            }
            // look for changes in each trigger field
            foreach ($band['triggerFields'] as $triggerField) {
              if (!isset($band['triggerValues'][$triggerField]) || $band['triggerValues'][$triggerField]!=$row[$triggerField])
                // one of the trigger fields has changed value, so it means the band gets output
                $outputBand=true;
              // store the last value to compare against next time
              $band['triggerValues'][$triggerField] = $row[$triggerField];
            }
          }
          // output the band only if it has been triggered, or has no trigger fields specified.
          if ($outputBand) {
            $row['imageFolder'] = self::get_uploaded_image_folder();
            $r .= self::apply_replacements_to_template($band['content'], $row);
          }
        }
      }
      // add a footer
      $r .= $options['footer'].'</div>';
      return $r;
    }
  }

 /**
  * Function to output a report onto a map rather than a grid.

  * Because there are many options for the map, this method does not generate
  * the map itself, rather it sends the output of the report onto a map_panel
  * output elsewhere on the page. Like the report_grid, this can output a
  * parameters form or can be set to use the parameters form from another
  * output report (e.g. another call to report_grid, allowing both a grid and
  * map of the same data to be generated). The report definition must contain a
  * single column which is configured as a mappable column or the report must
  * specify a parameterised CQL query to draw the map using WMS.
  *
  * @param array $options Options array with the following possibilities:
  * * *id* - Optional unique identifier for the report. This is required if
  *   there is more than one different report (grid, chart or map) on a single
  *   web page to allow separation of the page and sort $_GET parameters in the
  *   URLs generated.
  * * *reportGroup* - When joining multiple reports together, this can be used
  *   on a report that has autoParamsForm set to false to bind the report to
  *   the parameters form from a different report by giving both report
  *   controls the same reportGroup string. This will only work when all
  *   parameters required by this report are covered by the other report's
  *   parameters form.
  * * *rememberParamsReportGroup* - Enter any value in this parameter to allow
  *   the report to save its parameters for the next time the report is loaded.
  *   The parameters are saved site wide, so if several reports share the same
  *   value and the same report group then the parameter settings will be
  *   shared across the reports even if they are on different pages of the
  *   site. For example if several reports on the site have an ownData boolean
  *   parameter which filters the data to the user's own data, this can be set
  *   so that the reports all share the setting. This functionality requires
  *   cookies to be enabled on the browser.
  * * *mode* - Pass report for a report, or direct for an Indicia table or
  *   view. Default is report.
  * * *readAuth* - Read authorisation tokens.
  * * *dataSource* - Name of the report file or table/view.
  * * *dataSourceLoRes* - Name of the report file or table/view to use when
  *   zoomed out. For example this might aggregate records to 1 km or 10 km
  *   grid squares.
  * * *autoParamsForm* - Defaults to true. If true, then if a report requires
  *   parameters, a parameters input form will be auto-generated at the top of
  *   the grid. If set to false, then it is possible to manually build a
  *   parameters entry HTML form if you follow the following guidelines.
  *   First, you need to specify the id option for the report grid, so that
  *   your grid has a reproducable id. Next, the form you want associated with
  *   the grid must itself have the same id, but with the addition of params on
  *   the end. E.g. if the call to report_grid specifies the option 'id' to be
  *   'my-grid' then the parameters form must be called 'my-grid-params'.
  *   Finally the input controls which define each parameter must have the name
  *   'param-id-' followed by the actual parameter name, replacing id with the
  *   grid id. So, in our example, a parameter called survey will need an input
  *   or select control with the name attribute set to 'param-my-grid-survey'.
  *   The submit button for the form should have the method set to "get" and
  *   should post back to the same page. As a final alternative, if parameters
  *   are required by the report but some can be hard coded then those may be
  *   added to the filters array.
  * * *filters* - Array of key value pairs to include as a filter against the
  *   data.
  * * *extraParams* - Array of additional key value pairs to attach to the
  *   request.
  * * *paramDefaults* - Optional associative array of parameter default values.
  * * *paramsOnly* - Defaults to false. If true, then this method will only
  *   return the parameters form, not the grid content. autoParamsForm is
  *   ignored if this flag is set.
  * * *ignoreParams* - Array that can be set to a list of the report parameter
  *   names that should not be included in the parameters form. Useful when
  *   using paramsOnly=true to display a parameters entry form, but the system
  *   has default values for some of the parameters which the user does not
  *   need to be asked about.
  * * *completeParamsForm* - Defaults to true. If false, the control HTML is
  *   returned for the params form without being wrapped in a <form> and
  *   without the Run Report button, allowing it to be embedded into another
  *   form.
  * * *paramsFormButtonCaption* - Caption of the button to run the report on
  *   the report parameters form. Defaults to Run Report. This caption is
  *   localised when appropriate.
  * * *geoserverLayer* - For improved mapping performance, specify a layer on
  *   GeoServer which has the same attributes and output as the report file.
  *   Then the report map can output the contents of this layer filtered by the
  *   report parameters, rather than build a layer from the report data.
  * * *geoserverLayerStyle* - Optional name of the SLD file available on
  *   GeoServer which is to be applied to the GeoServer layer.  *
  * * *cqlTemplate* - Use with the geoserver_layer to provide a template for
  *   the CQL to filter the layer according to the parameters of the report.
  *   For example, if you are using the report called
  *   <em>map_occurrences_by_survey</em> then you can set the geoserver_layer
  *   to the indicia:detail_occurrences layer and set this to
  *   <em>INTERSECTS(geom, #searchArea#) AND survey_id=#survey#</em>.
  * * *proxy* - URL of a proxy on the local server to direct GeoServer WMS
  *   requests to. This proxy must be able to cache filters in the same way as
  *   the iform_proxy Drupal module.
  * * *locationParams* - Set to a comma seperated list of report parameters
  *   that are associated with locations. For instance, this might be
  *   `location_id,region_id`. The system then knows to zoom the map when these
  *   parameters are supplied. The bigger locations should always appear to the
  *   right in the list so that if multiple parameters are filled in by the
  *   user the system will always zoom to the biggest one. Default location_id.
  * * *clickable* - Set to true to enable clicking on the data points to see
  *   the underlying data. Default true.
  * * *clickableLayersOutputMode* - Set popup, div or report to display popups,
  *   output data to a div, or filter associated reports when clicking on data
  *   points with the query tool selected.
  * * *clickableLayersOutputDiv* - Set to the id of a div to display the
  *   clicked data in, or leave blank to display a popup.
  * * *clickableLayersOutputColumns* - An associated array of column field
  *   names with column titles as the values which defines the columns that are
  *   output when clicking on a data point. If ommitted, then all available
  *   columns are output using their original field names.
  * * *displaySymbol* - Symbol to display instead of the actual polygon. The
  *   symbol is displayed at the centre of the polygon. If not set then
  *   defaults to output the original polygon. Allowed values are circle,
  *   square, star, x, cross, triangle.
  * * *valueOutput* - Allows definition of how a data value in the report
  *   output is used to change the output of each symbol. This allows symbol
  *   size, colour and/or opacity to be used to provide an indication of data
  *   values. Provide an array of entries. The key of the entries should match
  *   the style parameter you want to control which should be one of
  *   fillOpacity, fillColor, strokeOpacity, strokeWidth or strokeColor. If
  *   using displaySymbol to render symbols rather than polygons then
  *   pointRadius (the symbol size) and rotation are also available. If the
  *   report defines labels (using the feature_style attribute of a column to
  *   define a column that outputs labels), then fontSize, fontColor and
  *   fontOpacity are also available. Each array entry is a sub-array with
  *   associative array values set for the following:
  *   * "from" is the start value of the range of output values (e.g. the
  *     minimum opacity or first colour in a range).
  *   * "to" is the end value of the range of output values (e.g. the maximum
  *     opacity or last colour in a range).
  *   * "valueField" is the name of the numeric field in the report output to
  *     be used to control display.
  *   * "minValue" is the data value that equates to the output value specified
  *     by "from". This can be a fieldname if wrapped in braces.
  *   * "maxValue" is the data value that equates to the output value specified
  *     by "from". This can be a fieldname if wrapped in braces.
  *   The following example maps a field called value (with minvalue and
  *   maxvalue also output by the report) to a range of colours from blue to
  *   red.
  *   ```php
  *   [
  *     'fillColor' => [
  *       'from' => '#0000ff',
  *       'to' => '#ff0000',
  *       'valueField' => 'value',
  *       'minValue'=> '{minvalue}',
  *       'maxValue'=> '{maxvalue}',
  *     ],
  *   ]
  *   ```
  * * *sharing* - Assuming the report has been written to take account of
  *   website sharing agreements, set this to define the task you are
  *   performing with the report and therefore the type of sharing to allow.
  *   Options are reporting (default), verification, moderation, peer_review,
  *   data_flow, editing, website (this website only) or me (my data only).
  * * *UserId* - If sharing=me, then this must contain the Indicia user ID of
  *   the user to return data for.  *
  * * *rowId* - Optional. Set this to the name of a field in the report to
  *   define which field is being used to define the feature ID created on the
  *   map layer. For example this can be used in conjunction with rowId on a
  *   report grid to allow a report's rows to be linked to the associated
  *   features. Note that the row ID can point to either an integer value, or a
  *   list of integers separated by commas if the rows returned by the report
  *   map to features which are shared by multiple records.
  * * *ajax* - Optional. Set to true to load the records onto the map using an
  *   AJAX request after the initial page load. Not relevant for GeoServer
  *   layers. Note that when ajax loading the map, the map will not
  *   automatically zoom to the layer extent.
  * * *zoomMapToOutput* - Default true. Defines that the map will
  *   automatically zoom to show the records. If using AJAX then note that the
  *   zoom will happen after initial page load and the map will zoom again if
  *   several pages of records are loaded.
  * * populateOnPageLoad - set to false to disable population of the map when
  *   the page initially loads. Should be used alongside a report_grid control
  *   which has the rowId option set, enabling the use of this report grid to
  *   output it's data to the map once a filter has been applied.
  * * *minMapReportZoom* - If set to a map zoom level (typically from 1-18)
  *   then the map does not show the report output until zoomed to this level.
  * * *featureDoubleOutlineColour* - If set to a CSS colour class, then feature
  *   outlines will be doubled up, for example a 1 pixel dark outline over a 3
  *   pixel light outline, creating a line halo effect which can make the map
  *   clearer.
  */
  public static function report_map($options) {
    $options = array_merge(array(
      'clickable' => true,
      'clickableLayersOutputMode' => 'popup',
      'clickableLayersOutputDiv' => '',
      'displaySymbol' => 'vector',
      'ajax' => false,
      'extraParams' => '',
      'featureDoubleOutlineColour' => '',
      'dataSourceLoRes' => '',
      'minMapReportZoom' => 'false',
      'populateOnPageLoad' => TRUE,
    ), $options);
    $options = self::getReportGridOptions($options);

    // Keep track of the columns in the report output which we need to draw the layer and popups.
    $colsToInclude=[];

    if (empty($options['geoserverLayer'])) {
      // We are doing vector mapping from an Indicia report.

      if ($options['ajax']) {
        // Just load the report structure, as Ajax will load content later.
        $options['extraParams']['limit']=0;
      }

      self::request_report($response, $options, $currentParamValues, false, '');
      if (isset($response['error'])) {
        // Return immediately on error.
        return $response['error'];
      }
      $r = self::paramsFormIfRequired($response, $options, $currentParamValues);
      if ($options['paramsOnly'] || !isset($response['records'])) {
        // Return the params form, if that is all that is being requested, or the parameters are not complete.
        return $r;
      }
      $records = $response['records'];

      // Find the geom column.
      foreach($response['columns'] as $col=>$cfg) {
        if (isset($cfg['mappable']) && $cfg['mappable']=='true') {
          $wktCol = $col;
          break;
        }
      }
      if (!isset($wktCol)) {
        $r .= "<p>".lang::get("The report's configuration does not output any mappable data")."</p>";
      }
      else {
        // Always include geom in output even if marked as not visible in report.
        $colsToInclude[$wktCol]='';
      }

      if (isset($options['rowId'])) {
        // Always include Id in output even if marked as not visible in report.
        $colsToInclude[$options['rowId']] = '';
      }

    }
    else {
      // We are doing WMS mapping using geoserver, so we just need to know the param values.
      $currentParamValues = self::getReportGridCurrentParamValues($options);
      $response = self::get_report_data($options, self::array_to_query_string($currentParamValues, true).'&wantRecords=0&wantParameters=1');
      $r = self::getReportGridParametersForm($response, $options, $currentParamValues);
    }

    if (isset($response['records']) ||
        !isset($response['parameterRequest']) ||
        count(array_intersect_key($currentParamValues, $response['parameterRequest'])) == count($response['parameterRequest'])) {
      // We are ready to draw the map.

      if (empty($options['geoserverLayer'])) {
        // We are doing vector mapping  from an Indicia report.

        // Build a default style object which is blue or red if selected.
        $defsettings = array(
          'fillColor' => '#0000ff',
          'strokeColor' => '#0000ff',
          'strokeWidth' => empty($options['featureDoubleOutlineColour']) ? "\${getstrokewidth}" : 1,
          'fillOpacity' => "\${getfillopacity}",
          'strokeOpacity' => 0.8,
          'pointRadius' => "\${getpointradius}",
          'graphicZIndex' => "\${getgraphiczindex}");
        $selsettings = array_merge($defsettings, array(
          'fillColor' => '#ff0000',
          'strokeColor' => '#ff0000',
          'strokeOpacity' => 0.9)
        );
        $defStyleFns = [];
        $selStyleFns = [];
        // Default fill opacity, more opaque if selected, and gets more transparent as you zoom in.
        $defStyleFns['fillOpacity'] = "getfillopacity: function(feature) {
          return Math.max(0, 0.4 - feature.layer.map.zoom/100);
        }";
        // When selected, a little bit more opaque.
        $selStyleFns['fillOpacity'] = "getfillopacity: function(feature) {
          return Math.max(0, 0.7 - feature.layer.map.zoom/100);
        }";
        // Default radius based on precision of record but limited to maintain visibility when zoomed out.
        // Note that the number of map units only approximates a metre in web-mercator, accurate
        // near the equator but not near the poles. We use a very crude adjustment if necessary
        // which works well around the UK's latitude.
        $defStyleFns['pointRadius'] = "getpointradius: function(feature) {
          var units = feature.attributes.sref_precision || 20;
          if (feature.geometry.getCentroid().y > 4000000) {
            units = units * (feature.geometry.getCentroid().y / 8200000);
          }
          return Math.max(5, units / (feature.layer.map.getResolution()));
        }";
        // Default z index, smaller objects on top.
        $defStyleFns['graphicZIndex'] = "getgraphiczindex: function(feature) {
          return Math.round(feature.geometry.getBounds().left - feature.geometry.getBounds().right) + 100000;
        }";
        // When selected, move objects upwards.
        $selStyleFns['graphicZIndex'] = "getgraphiczindex: function(feature) {
          return Math.round(feature.geometry.getBounds().left - feature.geometry.getBounds().right) + 200000;
        }";

        // Override the default style object using columns in the report output that define style settings.
        foreach($response['columns'] as $col => $def) {
          if (!empty($def['feature_style'])) {
            // Found a column that defines a style setting.

            if ($def['feature_style'] === 'fillOpacity') {
              // Replace the fill opacity functions to use a column value.
              $defStyleFns['fillOpacity'] = "getfillopacity: function(feature) {
                return Math.max(0, feature.attributes.$col - feature.layer.map.zoom/100);
              }";
              // When selected, don't increase transparency as zoomed in.
              $selStyleFns['fillOpacity'] = "getfillopacity: function(feature) {
                return feature.attributes.$col;
              }";
            }
            elseif ($def['feature_style'] === 'graphicZIndex') {
              // Replace the default z index with the column value.
              // ${} syntax is explained at http://docs.openlayers.org/library/feature_styling.html.
              $defsettings['graphicZIndex'] = '${'.$col.'}';
              // When selected, move objects upwards.
              $selStyleFns['graphicZIndex'] = "getgraphiczindex: function(feature) {
                return feature.attributes.$col + 1000;
              }";
              $selsettings['graphicZIndex'] = '${getgraphiczindex}';
            }
            else {
              // Found a column that outputs data to input into a feature style parameter.
              // ${} syntax is explained at http://docs.openlayers.org/library/feature_styling.html.
              $defsettings[$def['feature_style']] = '${'.$col.'}';
              if ($def['feature_style'] !== 'strokeColor') {
                // Override the style for selected items too except red stroke colour.
                $selsettings[$def['feature_style']] = '${'.$col.'}';
              }
            }

            // We need to include in output any columns involved in the feature style.
            $colsToInclude[$col] = '';
          }
        }

        if ($options['displaySymbol'] !== 'vector') {
          // Use a symbol marker on the map rather than vector.
          $defsettings['graphicName'] = $options['displaySymbol'];
        }

        // The following function uses the strokeWidth to pad out the squares which go too small when zooming the map
        // out. Points  always display the same size so are no problem. Also, no need if using a double outline.
        if (empty($options['featureDoubleOutlineColour'])) {
          $strokeWidthFn = "getstrokewidth: function(feature) {
            var width = feature.geometry.getBounds().right - feature.geometry.getBounds().left,
              strokeWidth = (width === 0) ? 1 : %d - (width / feature.layer.map.getResolution());
            return (strokeWidth < %d) ? %d : strokeWidth;
          }";
          $defStyleFns['getStrokeWidth'] = sprintf($strokeWidthFn, 9, 2, 2);
          $selStyleFns['getStrokeWidth'] = sprintf($strokeWidthFn, 10, 3, 3);
        }

        // The style objects can also be overriden by column values as specified in the valueOutput option.
        if (isset($options['valueOutput'])) {
          foreach($options['valueOutput'] as $type => $outputdef) {
            $value = $outputdef['valueField'];
            // We need this value in the output.
            $colsToInclude[$value]='';

            if (preg_match('/{(?P<name>.+)}/', $outputdef['minValue'], $matches)) {
              // Min value is obtained from column.
              $minvalue = 'feature.data.'.$matches['name'];
              $colsToInclude[$matches['name']]='';
            }
            else {
              $minvalue = $outputdef['minValue'];
            }

            if (preg_match('/{(?P<name>.+)}/', $outputdef['maxValue'], $matches)) {
              // Max value is obtained frm column.
              $maxvalue = 'feature.data.'.$matches['name'];
              $colsToInclude[$matches['name']]='';
            }
            else {
              $maxvalue = $outputdef['maxValue'];
            }

            $from = $outputdef['from'];
            $to = $outputdef['to'];
            if (substr($type, -5)==='Color')
              $defStyleFns[$type] = "get$type: function(feature) { \n".
                  "var from_r, from_g, from_b, to_r, to_g, to_b, r, g, b, ratio = Math.pow((feature.data.$value - $minvalue) / ($maxvalue - $minvalue), .2); \n".
                  "from_r = parseInt('$from'.substring(1,3),16);\n".
                  "from_g = parseInt('$from'.substring(3,5),16);\n".
                  "from_b = parseInt('$from'.substring(5,7),16);\n".
                  "to_r = parseInt('$to'.substring(1,3),16);\n".
                  "to_g = parseInt('$to'.substring(3,5),16);\n".
                  "to_b = parseInt('$to'.substring(5,7),16);\n".
                  "r=Math.round(from_r + (to_r-from_r)*ratio);\n".
                  "g=Math.round(from_g + (to_g-from_g)*ratio);\n".
                  "b=Math.round(from_b + (to_b-from_b)*ratio);\n".
                  "return 'rgb('+r+','+g+','+b+')';\n".
                '}';
            else
              $defStyleFns[$type] = "get$type: function(feature) { \n".
                  "var ratio = Math.pow((feature.data.$value - $minvalue) / ($maxvalue - $minvalue), .2); \n".
                  "return $from + ($to-$from)*ratio; \n".
                  '}';
            $defsettings[$type]="\${get$type}";
          }
        }

        // Convert these styles into a JSON definition ready to feed into JS.
        $selStyleFns = implode(",\n", array_values(array_merge($defStyleFns, $selStyleFns)));
        $defStyleFns = implode(",\n", array_values($defStyleFns));
        $defStyleFns = ", {context: {\n    $defStyleFns\n  }}";
        $selStyleFns = ", {context: {\n    $selStyleFns\n  }}";
        $defsettings = json_encode($defsettings);
        $selsettings = json_encode($selsettings);

        $addFeaturesJs = "";
        // No need to pass the default type of vector display, so use empty obj to keep JavaScript size down
        $opts = $options['displaySymbol'] === 'vector' ? '{}' : json_encode(array('type' => $options['displaySymbol']));

        if ($options['clickableLayersOutputMode'] == 'popup' || $options['clickableLayersOutputMode'] == 'div'
            || $options['clickableLayersOutputMode'] == 'customFunction') {
          // Add in all other visible columns from report if needed for feature clicks. They can be omitted otherwise
          // to minimise JS.
          foreach ($response['columns'] as $name => $cfg) {
            if (!isset($cfg['visible']) || ($cfg['visible'] !== 'false' && $cfg['visible'] !== false)) {
              $colsToInclude[$name] = '';
            }
          }
        }
        $populateOnPageLoad = $options['populateOnPageLoad'] ? 'true' : 'false';
        if ($options['ajax']) {
          // Output scripts to get map loading by Ajax.
          $mapDataSource = json_encode([
            'fullRes' => $options['dataSource'],
            'loRes' => $options['dataSourceLoRes']
          ]);
          self::$javascript .= <<<JS
indiciaData.mapDataSource = $mapDataSource;
indiciaData.minMapReportZoom = $options[minMapReportZoom];
mapInitialisationHooks.push(function(div) {
  var wantToMap = typeof indiciaData.mapZoomPlanned === 'undefined';
  // Find the best report grid to use as a map report controller.
  if (typeof indiciaData.reports.$options[reportGroup] !== 'undefined') {
    $.each(indiciaData.reports.$options[reportGroup], function(idx, grid) {
      if (typeof indiciaData.mapReportControllerGrid === 'undefined') {
        // Use the first grid to contol the map report...
        indiciaData.mapReportControllerGrid = grid;
      }
      if (grid[0].settings.linkFilterToMap) {
        // ...Unless there is a better filter linked grid.
        indiciaData.mapReportControllerGrid = grid;
        // Only need one grid to draw the map.
        return false;
      }
    });
  }
  if ($populateOnPageLoad && wantToMap && typeof indiciaData.mapReportControllerGrid !== 'undefined') {
    indiciaData.mapReportControllerGrid.mapRecords();
  }
  if (indiciaData.mapDataSource.loRes !== '') {
    // hook up a zoom and pan handler so we can switch reports.
    div.map.events.on({'moveend': function() {
      if (!indiciaData.disableMapDataLoading) {
        indiciaData.selectedRows=[];
        $.each($(indiciaData.mapReportControllerGrid).find('tr.selected'), function(idx, tr) {
          indiciaData.selectedRows.push($(tr).attr('id').replace(/^row/, ''));
        });
        indiciaData.mapReportControllerGrid.mapRecords(true);
      }
    }});
  }
});

JS;
        }
        else {
          // Not Ajax so output data to be loaded.
          $geoms = [];
          $imagePath = self::get_uploaded_image_folder();
          foreach ($records as $record) {
            // Loop through all records.

            if (isset($wktCol) && !empty($record[$wktCol])) {
              // Only ouput records which can be mapped.

              // Truncate fractional parts of WKT
              $record[$wktCol] = preg_replace('/\.(\d+)/', '', $record[$wktCol]);
              // Rather than output every geom separately, do a list of distinct geoms.
              if (!$geomIdx = array_search('"'.$record[$wktCol].'"', $geoms)) {
                $geoms[] = '"'.$record[$wktCol].'"';
                $geomIdx = count($geoms) - 1;
              }
              $record[$wktCol] = $geomIdx;

              // Process image columns and templated columns as defined in report file.
              foreach ($response['columns'] as $col => $cfg) {
                if (isset($cfg['img']) && $cfg['img']=='true' && !empty($record[$col]) && !isset($cfg['template'])) {
                  // Output thumbnails from image columns
                  $imgs = explode(',', $record[$col]);
                  $value='';
                  foreach($imgs as $img) {
                      $value .= "<img src=\"$imagePath" . "thumb-$img\" />";
                  }
                }
                elseif (isset($cfg['template'])) {
                  // Build value from template in report.
                  $value = self::mergeParamsIntoTemplate($record, $cfg['template'], true, true, true);
                }
                else {
                  // Don't display any null values returned.
                  $value = isset($record[$col]) ? $record[$col] : '';
                }
                $record[$col] = $value;
              }

              if (!empty($colsToInclude)) {
                // Remove all columns from record which are not needed.
                $record = array_intersect_key($record, $colsToInclude);
              }

              $addFeaturesJs.= "div.addPt(features, ".json_encode($record).", '$wktCol', $opts" . (empty($options['rowId']) ? '' : ", '" . $record[$options['rowId']] . "'") . ");\n";
            }
          }
          self::$javascript .= 'indiciaData.geoms=['.implode(',',$geoms)."];\n";
        }


        self::addFeaturesLoadingJs($addFeaturesJs, $defsettings, $selsettings, $defStyleFns, $selStyleFns, $options['zoomMapToOutput'] && !$options['ajax'], $options['featureDoubleOutlineColour']);
      }
      else {
        // doing WMS reporting via GeoServer
        $replacements = [];
        foreach(array_keys($currentParamValues) as $key)
          $replacements[] = "#$key#";
        $options['cqlTemplate'] = str_replace($replacements, $currentParamValues, $options['cqlTemplate']);
        $options['cqlTemplate'] = str_replace("'", "\'", $options['cqlTemplate']);
        $style = empty($options['geoserverLayerStyle']) ? '' : ", STYLES: '".$options['geoserverLayerStyle']."'";
        if (isset($options['proxy'])) {
          $proxyResponse = self::http_post($options['proxy'], array(
            'cql_Filter' => urlencode($options['cqlTemplate'])
          ));
          $filter = 'CACHE_ID: "'.$proxyResponse['output'].'"';
          $proxy = $options['proxy'];
        } else {
          $filter = "cql_Filter: '".$options['cqlTemplate']."'";
          $proxy = '';
        }
        $layerUrl = $proxy . self::$geoserver_url . 'wms';
        $layerTitle = lang::get('Report output');
        report_helper::$javascript .= "  indiciaData.reportlayer = new OpenLayers.Layer.WMS('$layerTitle',
      '$layerUrl', { layers: '".$options['geoserverLayer']."', transparent: true,
          $filter, $style},
      {singleTile: true, isBaseLayer: false, sphericalMercator: true});\n";
      }

      $setLocationJs = '';
      //When the user uses a page like dynamic report explorer with a map, then there might be more than
      //one parameter that is a location based parameter. For instance, Site and Region might be seperate parameters,
      //the user should supply options to the map that specify which parameters are location based, this is used by the
      //code below to allow the map to show records for those locations and to zoom the map appropriately.
      //The bigger location types should always appear to the right in the list so that if multiple parameters are filled in by the user
      //the system will always zoom to the biggest one (but still show records for any smaller locations inside the bigger one).
      //If the user chooses two locations that don't intersect then no records are shown as the records need to satisfy both criteria.

      //Default is that there is just a location_id parameter if user doesn't give options.
      if (!empty($currentParamValues['location_id']))
        $locationParamVals=array($currentParamValues['location_id']);
      //User has supplied location parameter options.
      if (!empty($options['locationParams'])) {
        $locationParamVals=[];
        $locationParamsArray = explode(',',$options['locationParams']);
        //Create an array of the user's supplied location parameters.
        foreach ($locationParamsArray as $locationParam) {
          if (!empty($currentParamValues[$locationParam]))
            array_push($locationParamVals,$currentParamValues[$locationParam]);
        }
      }
      if (!empty($locationParamVals)) {
        foreach ($locationParamVals as $locationParamVal) {
          $location=data_entry_helper::get_population_data(array(
            'table' => 'location',
            'nocache'=>true,
            'extraParams'=>$options['readAuth'] + array('id'=>$locationParamVal,'view' => 'detail')
          ));
          if (count($location)===1) {
            $location=$location[0];
            $setLocationJs = "\n  opts.initialFeatureWkt='".(!empty($location['boundary_geom']) ? $location['boundary_geom'] : $location['centroid_geom'])."';";
          }
        }
      }
      report_helper::$javascript.= "
mapSettingsHooks.push(function(opts) { $setLocationJs
  opts.reportGroup = '".$options['reportGroup']."';
  if (typeof indiciaData.reportlayer!=='undefined') {
    opts.layers.push(indiciaData.reportlayer);\n";
      if ($options['clickable'])
        report_helper::$javascript .= "    opts.clickableLayers.push(indiciaData.reportlayer);\n";
      report_helper::$javascript .= "  }\n";
      if (!empty($options["customClickFn"]))
        report_helper::$javascript .= "  opts.customClickFn=".$options['customClickFn'].";\n";
      report_helper::$javascript .= "  opts.clickableLayersOutputMode='".$options['clickableLayersOutputMode']."';\n";
      if ($options['clickableLayersOutputDiv'])
        report_helper::$javascript .= "  opts.clickableLayersOutputDiv='".$options['clickableLayersOutputDiv']."';\n";
      if (isset($options['clickableLayersOutputColumns']))
        report_helper::$javascript .= "  opts.clickableLayersOutputColumns=".json_encode($options['clickableLayersOutputColumns']).";\n";
      report_helper::$javascript .= "});\n";
    }
    return $r;
  }

  /**
   * Retrieve report data.
   *
   * Method that retrieves the data from a report or a table/view, ready to
   * display in a chart or grid. Respects the filters and columns $_GET
   * variables generated by a grid's filter form when JavaScript is disabled.
   * Also automatically respects the user's current training mode setting.
   *
   * @param array $options
   *   Options array with the following possibilities:
   *   * mode - Defaults to report, which means report data is being loaded.
   *     Set to direct to load data directly from an entity's view.
   *   * dataSource - Name of the report or entity being queried. If set to
   *     'static', then provide an option staticData containing an array of
   *     preloaded report data to return, which can be used to override report
   *     data fetches for reporting components.
   *   * readAuth - Read authentication tokens.
   *   * filters - Array of key value pairs to include as a filter against the
   *     data.
   *   * extraParams - Array of additional key value pairs to attach to the
   *     request.
   *   * linkOnly - Pass true to return a link to the report data request rather
   *     than the data itself. Default false.
   *   * sharing - Assuming the report has been written to take account of
   *     website sharing agreements, set this to define the task you are
   *     performing with the report and therefore the type of sharing to allow.
   *     verification, moderation, peer_review, data_flow, editing, website
   *     (this website only) or me (my data only).
   *   * UserId - If sharing=me, then this must contain the Indicia user ID of
   *     the user to return data for.
   *   * caching - If true, then the response will be cached and the cached
   *     copy used for future calls. Default false. If 'store' then although
   *     the response is not fetched from a cache, the response will be stored
   *     in the cache for possible later use.
   *   * cachePerUser - Default true. Because a report automatically receives
   *     the user_id of a user as a parameter, if the user is linked to the
   *     warehouse, report caching will be granular to the user level. That is,
   *     if a user loads a report and another user loads the same report, the
   *     cache is not used because they have different user IDs. Set this to
   *     false to make the cache entry global so that all users will receive
   *     the same copy of the report. Generally you should only use this on
   *     reports that are non-user specific.
   *
   * @param string $extra Any additional parameters to append to the request URL, for example orderby, limit or offset.
   * @return mixed If linkOnly is set in the options, returns the link string, otherwise returns the response as an array.
   */
  public static function get_report_data($options, $extra='') {
    if ($options['dataSource']==='static' && isset($options['staticData']))
      return $options['staticData'];
    $options = array_merge([
      'mode' => 'report',
      'format' => 'json',
      'extraParams' => [],
    ], $options);
    if (function_exists('hostsite_get_user_field') && hostsite_get_user_field('training')) {
      $options['extraParams']['training'] = 'true';
    }
    $query = [];
    if ($options['mode']=='report') {
      $serviceCall = 'report/requestReport?report='.$options['dataSource'].'.xml&reportSource=local&'.
          (isset($options['filename']) ? 'filename='.$options['filename'].'&' : '');
    } elseif ($options['mode']=='direct') {
      $serviceCall = 'data/'.$options['dataSource'].'?';
      if (isset($_GET['filters']) && isset($_GET['columns'])) {
        $filters=explode(',', $_GET['filters']);
        $columns=explode(',', $_GET['columns']);
        $assoc = array_combine($columns, $filters);
        $query['like'] = $assoc;
      }
    } else {
      throw new Exception('Invalid mode parameter for call to report_grid - '.$options['mode']);
    }
    if (!empty($extra) && substr($extra, 0, 1)!=='&')
      $extra = '&'.$extra;
    $request = 'index.php/services/'.
        $serviceCall .
        'mode=' . $options['format'] . '&nonce=' . $options['readAuth']['nonce'] .
        '&auth_token=' . $options['readAuth']['auth_token'] .
        $extra;
    if (isset($options['filters'])) {
      foreach ($options['filters'] as $key=>$value) {
        if (is_array($value)) {
          if (!isset($query['in'])) $query['in'] = [];
          $query['in'][$key] = $value;
        } else {
          if (!isset($query['where'])) $query['where'] = [];
          $query['where'][$key] = $value;
        }
      }
    }
    if (!empty($query)) {
      $request .= "&query=".urlencode(json_encode($query));
    }
    foreach ($options['extraParams'] as $key => $value) {
      // Must urlencode the keys and parameters, as things like spaces cause curl
      // to hang.
      $request .= '&' . urlencode($key) . '=' . urlencode($value ?? '');
    }
	  // Pass through the type of data sharing.
    if (isset($options['sharing']))
      $request .= '&sharing='.$options['sharing'];
    if (isset($options['userId']))
      $request .= '&user_id='.$options['userId'];
    if (isset($options['linkOnly']) && $options['linkOnly']) {
      return self::$base_url . $request;
    }
    return self::getCachedServicesCall($request, $options);
  }

  /**
   * Generates the extra URL parameters that need to be appended to a report service call request, in order to
   * include the sorting and pagination parameters.
   * @param array @options Options array sent to the report.
   * @param array @sortAndPageUrlParams Paging and sorting info returned from a call to getReportGridSortPageUrlParams.
   * @return string Snippet of URL containing the required URL parameters.
   */
  private static function getReportSortingPagingParams($options, $sortAndPageUrlParams) {
    // Work out the names and current values of the params we expect in the report request URL for sort and pagination
    $page = (isset($sortAndPageUrlParams['page']) && $sortAndPageUrlParams['page']['value']
        ? $sortAndPageUrlParams['page']['value'] : 0);
    // set the limit to one higher than we need, so the extra row can trigger the pagination next link
    if($options['itemsPerPage'] !== false) {
      $extraParams = '&limit='.($options['itemsPerPage']+1);
      $extraParams .= '&offset=' . $page * $options['itemsPerPage'];
    } else $extraParams = '';
    // Add in the sort parameters
    foreach ($sortAndPageUrlParams as $param => $content) {
      if ($content['value']!=null) {
        if ($param != 'page')
          $extraParams .= '&' . $param .'='. $content['value'];
      }
    }
    return $extraParams;
  }

  /**
   * Works out the orderby, sortdir and page URL param names for this report grid, and also gets their
   * current values.
   * @param $options Control options array
   * @return array Contains the orderby, sortdir and page params, as an assoc array. Each array value
   * is an array containing name & value.
   */
  private static function getReportGridSortPageUrlParams($options) {
    $orderBy = NULL;
    $sortDir = NULL;
    $page = NULL;
    if (isset($_GET["orderby$options[id]"])) {
      $orderBy = $_GET["orderby$options[id]"];
    }
    elseif ($options['rememberGridPosition'] && isset($_COOKIE["report-orderby-$options[id]"])) {
      $orderBy = $_COOKIE["report-orderby-$options[id]"];
    }
    if (isset($_GET["sortdir$options[id]"])) {
      $sortDir = $_GET["sortdir$options[id]"];
    }
    elseif ($options['rememberGridPosition'] && isset($_COOKIE["report-sortdir-$options[id]"])) {
      $sortDir = $_COOKIE["report-sortdir-$options[id]"];
    }
    if (isset($_GET["page$options[id]"])) {
      $page = $_GET["page$options[id]"];
    }
    elseif ($options['rememberGridPosition'] && isset($_COOKIE["report-page-$options[id]"])) {
      $page = $_COOKIE["report-page-$options[id]"];
    }
    return [
      'orderby' => [
        'name' => "orderby$options[id]",
        'value' => $orderBy,
      ],
      'sortdir' => [
        'name' => "sortdir$options[id]",
        'value' => $sortDir
      ],
      'page' => [
        'name' => "page$options[id]",
        'value' => $page,
      ],
    ];
  }


  /**
   * Build a url suitable for inclusion in the links for the report grid column headings or pagination
   * bar. This effectively re-builds the current page's URL, but drops the query string parameters that
   * indicate the sort order and page number.
   * @param array $sortAndPageUrlParams List of the sorting and pagination parameters which should be excluded.
   * @return string
   */
  private static function reportGridGetReloadUrl($sortAndPageUrlParams) {
    // get the url parameters. Don't use $_GET, because it contains any parameters that are not in the
    // URL when search friendly URLs are used (e.g. a Drupal path node/123 is mapped to index.php?q=node/123
    // using Apache mod_alias but we don't want to know about that)
    $reloadUrl = self::get_reload_link_parts();
    // Build a basic URL path back to this page, but with the page, sortdir and orderby removed
    $pageUrl = $reloadUrl['path'].'?';
    // find the names of the params we must not include
    $excludedParams = [];
    foreach($sortAndPageUrlParams as $param) {
      $excludedParams[] = $param['name'];
    }
    foreach ($reloadUrl['params'] as $key => $value) {
      if (!in_array($key, $excludedParams))
        $pageUrl .= "$key=$value&";
    }
    return $pageUrl;
  }

  /**
   * Private function that builds a parameters form according to a parameterRequest recieved
   * when calling a report. If the autoParamsForm is false then an empty string is returned.
   * @param $response
   * @param $options
   * @param $params
   * @return string HTML for the form.
   */
  private static function getReportGridParametersForm($response, $options, $params) {
    if ($options['autoParamsForm'] || $options['paramsOnly']) {
      $r = '';
      // The building of params form has been moved here
      // (earlier in the function than previously) as we
      // need any changes to $hasVisibleContent earlier in the function than before.
      $builtParamsForm = self::build_params_form(array_merge($options, array('form'=>$response['parameterRequest'], 'defaults'=>$params)), $hasVisibleContent);
      // The form must use POST, because polygon parameters can be too large for GET.
      if (isset($options['completeParamsForm']) && $options['completeParamsForm']) {
        $cls = $options['paramsInMapToolbar'] ? 'no-border' : '';
        if (!empty($options['fieldsetClass']))
          $cls .= ' '.$options['fieldsetClass'];
        $r .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post" id="'.$options['reportGroup'].'-params">'."\n<fieldset class=\"$cls\">";
        // Don't use the fieldset legend in toolbar mode,
        // and also only if the params have visible content.
        if (!$options['paramsInMapToolbar'] && $hasVisibleContent === TRUE) {
          // don't use the fieldset legend in toolbar mode
          $r .= '<legend>' . lang::get('Report parameters') . '</legend>';
        }
      }
      $reloadUrl = self::get_reload_link_parts();
      // Output any other get parameters from our URL as hidden fields
      foreach ($reloadUrl['params'] as $key => $value) {
        // since any param will be from a URL it could be encoded. So we don't want to double encode if they repeatedly
        // run the report.
        $value = urldecode($value);
        // ignore any parameters that are going to be in the grid parameters form
        if (substr($key,0,6)!='param-')
          $r .= "<input type=\"hidden\" value=\"$value\" name=\"$key\" />\n";
      }
      if ($options['paramsInMapToolbar'])
        $options['helpText']=false;
      if (isset($options['ignoreParams']))
        // tell the params form builder to hide the ignored parameters.
        $options['paramsToHide']=$options['ignoreParams'];
      $r .= $builtParamsForm;
      // Don't include the submit button unless the parameters are showing
      if (isset($options['completeParamsForm']) && $options['completeParamsForm'] &&
          $hasVisibleContent === TRUE) {
        global $indicia_templates;
        $suffix = '<input type="submit" value="'.lang::get($options['paramsFormButtonCaption']).'" id="run-report" ' .
            "class=\"$indicia_templates[buttonHighlightedClass]\" />" .
            '</fieldset></form>';
      } else
        $suffix = '';
      // look for idlist parameters with an alias. If we find one, we need to pass this information to any map panel, because the
      // alias provides the name of the key field in the features loaded onto the map. E.g. if you click on the feature, the alias
      // allows the map to find the primary key value and therefore filter the report to show the matching feature.
      foreach($response['parameterRequest'] as $key=>$param)
        if (!empty($param['alias']) && $param['datatype']=='idlist') {
          $alias = $param['alias'];
          self::$javascript .= "
if (typeof mapSettingsHooks!=='undefined') {
  mapSettingsHooks.push(function(opts) {
    opts.featureIdField='$alias';
  });
}\n";
        }
      if ($options['paramsInMapToolbar']) {
        $toolbarControls = str_replace(array('<br/>', "\n", "'"), array('', '', "\'"), $r);
        self::$javascript .= "$.fn.indiciaMapPanel.defaults.toolbarPrefix+='$toolbarControls';\n";
        self::$javascript .= "$.fn.indiciaMapPanel.defaults.toolbarSuffix+='$suffix';\n";
        return '';
      } else
        return "$r$suffix\n";
    } else {
      return '';
    }
  }

  /**
   * Add any columns that don't have a column definition to the end of the columns list, by first
   * building an array of the column names of the columns we did specify, then adding any missing fields
   * from the results to the end of the options['columns'] array.
   * @param $response
   * @param $options
   */
  private static function report_grid_get_columns($response, &$options) {
    if (isset($response['columns'])) {
      $specifiedCols = [];
      $actionCols = [];
      $idx=0;
      if (!isset($options['columns']))
        $options['columns'] = [];
      foreach ($options['columns'] as &$col) {
        if (isset($col['fieldname'])) $specifiedCols[] = $col['fieldname'];
        // action columns need to be removed and added to the end
        if (isset($col['actions'])) {
          // remove the action col from its current location, store it so we can add it to the end
          unset($options['columns'][$idx]);
          $actionCols[] = $col;
        }
        $idx++;
        //Make sure all the information relating to the columns are used from the report response, such as the display name
        if (isset($col['fieldname']) && array_key_exists($col['fieldname'], $response['columns']))
          $col = array_merge($response['columns'][$col['fieldname']],$col);
      }
      if ($options['includeAllColumns']) {
        foreach ($response['columns'] as $resultField => $value) {
          if (!in_array($resultField, $specifiedCols)) {
            $options['columns'][] = array_merge(
              $value,
              array('fieldname'=>$resultField)
            );
          }
        }
      }
      // add the actions columns back in at the end
      $options['columns'] = array_merge($options['columns'], $actionCols);
    }
  }

  /**
   * Retrieve the HTML for the actions in a grid row.
   *
   * @param array $actions
   *   List of the action definitions to convert to HTML.
   * @param array $row
   *   The content of the row loaded from the database.
   * @param string $pathParam
   *   Set to the name of a URL param used to pass the path to this page. E.g.
   *   in Drupal with clean urls disabled, this is set to q. Otherwise leave
   *   empty.
   */
  private static function get_report_grid_actions($actions, $row, $pathParam = '') {
    $jsReplacements = [];
    foreach ($row as $key=>$value) {
      $value = $value === NULL ? '' : $value;
      $jsReplacements[$key] = $value;
      $jsReplacements["$key-escape-quote"] =
        isset($value) ? str_replace("'", "\'", $value) : NULL;
      $jsReplacements["$key-escape-dblquote"] =
        isset($value) ? str_replace('"', '\"', $value) : NULL;
      $jsReplacements["$key-escape-htmlquote"] =
        isset($value) ? str_replace("'", "&#39;", $value) : NULL;
      $jsReplacements["$key-escape-htmldblquote"] =
        isset($value) ? str_replace('"', '&quot;', $value) : NULL;
      $jsReplacements["$key-escape-urlpath"] = trim(preg_replace('/[^a-z0-9\-]/', '', str_replace(' ', '-', strtolower($value))), '-');
    }
    $links = [];
    $currentUrl = self::get_reload_link_parts(); // needed for params
    if (!empty($pathParam)) {
      // If we are using a path parameter (like Drupal's q= dirty URLs), then we must ignore this part of the current URL's parameters
      // so that it can be replaced by the path we are navigating to.
      unset($currentUrl['params'][$pathParam]);
    }
    foreach ($actions as $action) {
      // skip any actions which are marked as invisible for this row.
      if (isset($action['visibility_field']) && $row[$action['visibility_field']]==='f')
        continue;
      if (isset($action['permission']) && !hostsite_user_has_permission($action['permission']))
        continue;
      if (isset($action['url'])) {
        // Catch lazy cases where the URL does not contain the rootFolder so assumes a relative path
        if ( strcasecmp(substr($action['url'], 0, 12), '{rootfolder}') !== 0 &&
             strcasecmp(substr($action['url'], 0, 12), '{currentUrl}') !== 0 &&
             strcasecmp(substr($action['url'], 0, 4), 'http') !== 0 &&
             strcasecmp(substr($action['url'], 0, 12), '{input_form}') !== 0 ) {
          $action['url'] = '{rootFolder}'.$action['url'];
        }

        // Catch cases where {input_form} is unavailable, a relative path or null
        // You may want the report to return a default value if input_form is null.
        if ( strcasecmp(substr($action['url'], 0, 12), '{input_form}') === 0 ) {
          if ( array_key_exists('input_form', $row) ) {
            // The input_form field is available
            if ( !isset($row['input_form']) || $row['input_form'] == '' ) {
              // If it has no value, use currentUrl as default
              $action['url'] = '{currentUrl}';
            } elseif (strcasecmp(substr($row['input_form'], 0, 4), 'http') !== 0 ) {
              // assume a relative path if it doesn't begin with 'http'
              $action['url'] = '{rootFolder}'.$action['url'];
            }
          } else {
            // If input_form is not available use currentUrl as default
            $action['url'] = '{currentUrl}';
          }
        }
        // field values available for merging into the action include the row data and the
        // query string parameters
        $row = array_merge($currentUrl['params'], $row);
        // merge field value replacements into the URL
        $actionUrl = self::mergeParamsIntoTemplate($row, $action['url'], true);
        // merge field value replacements into the URL parameters
        if (array_key_exists('urlParams', $action) && count($action['urlParams'])>0) {
          $actionUrl .= (strpos($actionUrl, '?')===false) ? '?' : '&';
          $actionUrl .= self::mergeParamsIntoTemplate($row, self::array_to_query_string($action['urlParams']), true);
        }
        $href=" href=\"$actionUrl\"";
      } else {
        $href='';
      }
      if (isset($action['javascript'])) {
        $onclick=' onclick="'.self::mergeParamsIntoTemplate($jsReplacements,$action['javascript'],true).'"';
      } else {
        $onclick = '';
      }
      $classes = array('action-button');
      if (!empty($action['class']))
        $classes[] = $action['class'];
      $class = ' class="' . implode(' ', $classes) . '"';
      if (isset($action['img'])) {
        $rootFolder = self::getRootfolder();
        $img=str_replace(array('{rootFolder}', '{sep}'), array($rootFolder, strpos($rootFolder, '?')===FALSE ? '?' : '&'), $action['img']);
        $title = empty($action['caption']) ? lang::get('Click to run the action') : $action['caption'];
        $content = "<img src=\"$img\" title=\"$title\" />";
      } elseif (isset($action['caption']))
        $content = $action['caption'];
      global $indicia_templates;
      $links[] = str_replace(
          array('{class}', '{href}', '{onclick}', '{content}'),
          array($class, $href, $onclick, $content),
          $indicia_templates['report-action-button']);
    }
    return implode('', $links);
  }

  /**
   * Apply the defaults to the options for the report grid.
   * @param array $options Array of control options.
   */
  private static function getReportGridOptions($options) {
    $options = array_merge(array(
      'mode' => 'report',
      'id' => 'report-output', // this needs to be set explicitly when more than one report on a page
      'itemsPerPage' => 20,
      'class' => 'ui-widget ui-widget-content report-grid',
      'thClass' => 'ui-widget-header',
      'altRowClass' => 'odd',
      'columns' => [],
      'galleryColCount' => 1,
      'headers' => TRUE,
      'sortable' => TRUE,
      'includeAllColumns' => TRUE,
      'autoParamsForm' => TRUE,
      'paramsOnly' => FALSE,
      'extraParams' => [],
      'immutableParams' => [],
      'completeParamsForm' => TRUE,
      'callback' => '',
      'paramsFormButtonCaption' => 'Run Report',
      'paramsInMapToolbar' => FALSE,
      'view' => 'list',
      'caching' => isset($options['paramsOnly']) && $options['paramsOnly'],
      'sendOutputToMap' => FALSE,
      'zoomMapToOutput' => TRUE,
      'ajax' => FALSE,
      'ajaxLinksOnly' => FALSE,
      'autoloadAjax' => TRUE,
      'linkFilterToMap' => TRUE,
      'pager' => TRUE,
      'imageThumbPreset' => 'thumb',
      'includeColumnsPicker' => FALSE,
      'rememberGridPosition' => FALSE,
    ), $options);
    // if using AJAX we are only loading parameters and columns, so may as well use local cache
    if ($options['ajax'])
      $options['caching']=true;
    if ($options['galleryColCount']>1) $options['class'] .= ' gallery';
    // use the current report as the params form by default
    if (empty($options['reportGroup'])) $options['reportGroup'] = str_replace('-', '_', $options['id']);
    if (empty($options['fieldNamePrefix'])) $options['fieldNamePrefix'] = $options['reportGroup'];
    if (function_exists('hostsite_get_user_field')) {
      // If the host environment (e.g. Drupal module) can tell us which Indicia user is logged in, pass that
      // to the report call as it might be required for filters.
      if (!isset($options['extraParams']['user_id']) && $indiciaUserId = hostsite_get_user_field('indicia_user_id'))
        $options['extraParams']['user_id'] = $indiciaUserId;
      if (hostsite_get_user_field('training'))
        $options['extraParams']['training'] = 'true';
    }
    return $options;
  }

  /**
   * Returns the parameters for the report grid data services call which are embedded in the query string or
   * default param value data.
   * @param $options
   * @return Array Associative array of parameters.
   */
  private static function getReportGridCurrentParamValues($options) {
    $params = [];
    // get defaults first
    if (isset($options['paramDefaults'])) {
      foreach ($options['paramDefaults'] as $key=>$value) {
        // trim data to ensure blank lines are not handled.
        $key = trim($key);
        $value = trim($value);
        // We have found a parameter, so put it in the request to the report service
        if (!empty($key))
          $params[$key]=$value;
      }
    }
    // Are there any parameters embedded in the request data, e.g. after submitting the params form?
    $providedParams = $_REQUEST;
    // Is there a saved cookie containing previously used report parameters?
    if (isset($_COOKIE['providedParams']) && !empty($options['rememberParamsReportGroup'])) {
      $cookieData = json_decode($_COOKIE['providedParams'], true);
      // guard against a corrupt cookie
      if (!is_array($cookieData))
        $cookieData=[];
      if (!empty($cookieData[$options['rememberParamsReportGroup']])) {
        $cookieParams = $cookieData[$options['rememberParamsReportGroup']];
        if (isset($cookieParams) && is_array($cookieParams)) {
          // We shouldn't use the cookie values to overwrite any parameters that are hidden in the form as this is confusing.
          $ignoreParamNames = [];
          foreach($options['paramsToExclude'] as $param)
            $ignoreParamNames[$options['reportGroup']."-$param"] = '';
          $cookieParams = array_diff_key($cookieParams, $ignoreParamNames);
          $providedParams = array_merge(
            $cookieParams,
            $providedParams
          );
        }
      }
    }
    if (!empty($options['rememberParamsReportGroup'])) {
      // need to store the current set of saved params. These need to be merged into an array to go in
      // the single stored cookie with the array key being the rememberParamsReportGroup and the value being
      // an associative array of params.
      if (!isset($cookieData))
        $cookieData = [];
      $cookieData[$options['rememberParamsReportGroup']]=$providedParams;
      hostsite_set_cookie('providedParams', json_encode($cookieData));
    }
    // Get the report group prefix required for each relevant parameter
    $paramKey = (isset($options['reportGroup']) ? $options['reportGroup'] : '').'-';
    foreach ($providedParams as $key=>$value) {
      if (substr($key, 0, strlen($paramKey))==$paramKey) {
        // We have found a parameter, so put it in the request to the report service
        $param = substr($key, strlen($paramKey));
        $params[$param]=$value;
      }
    }
    return $params;
  }

 /**
  * <p>Outputs a calendar grid that loads the content of a report.</p>
  * <p>The grid supports a pagination header (year by year). If you need 2 grids on one page, then you must
  * define a different id in the options for each grid.</p>
  * <p>The grid operation has NOT been AJAXified.</p>
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>year</b><br/>
  * The year to output the calendar for. Default is this year.</li>
  * <li><b>id</b><br/>
  * Optional unique identifier for the grid's container div. This is required if there is more than
  * one grid on a single web page to allow separation of the page and sort $_GET parameters in the URLs
  * generated.</li>
  * <li><b>mode</b><br/>
  * Pass report for a report, or direct for an Indicia table or view. Default is report.</li>
  * <li><b>readAuth</b><br/>
  * Read authorisation tokens.</li>
  * <li><b>dataSource</b><br/>
  * Name of the report file or table/view. when used, any user_id must refer to the CMS user ID, not the Indicia
  * User.</li>
  * <li><b>view</b>
  * When loading from a view, specify list, gv or detail to determine which view variant is loaded. Default is list.
  * </li>
  * <li><b>extraParams</b><br/>
  * Array of additional key value pairs to attach to the request. This should include fixed values which cannot be changed by the
  * user and therefore are not needed in the parameters form.
  * </li>
  * <li><b>paramDefaults</b>
  * Optional associative array of parameter default values. Default values appear in the parameter form and can be overridden.</li>
  * <li><b>includeWeekNumber</b>
  * Should a Week Number column be included in the grid? Defaults to false.</li>
  * <li><b>weekstart</b>
  * Defines the first day of the week. There are 2 options.<br/>'.
  *  weekday=<n> where <n> is a number between 1 (for Monday) and 7 (for Sunday). Default is 'weekday=7'
  *  date=MMM-DD where MMM-DD is a month/day combination: e.g. choosing Apr-1 will start each week on the day of the week on which the 1st of April occurs.</li>
  * <li><b>weekOneContains</b>
  * Defines week one as the week which contains this date. Format should be MMM-DD, which is a month/day combination: e.g. choosing Apr-1 will define
  * week one as being the week containing the 1st of April. Defaults to the 1st of January.</li>
  * <li><b>weekNumberFilter</b>
  * Restrict displayed weeks to between 2 weeks defined by their week numbers. Colon separated.
  * Leaving an empty value means the end of the year.
  * Examples: "1:30" - Weeks one to thirty inclusive.
  * "4:" - Week four onwards.
  * ":5" - Upto and including week five.</li>
  * <li><b>viewPreviousIfTooEarly</b>
  * Boolean. When using week filters, it is possible to bring up a calendar for this year which is entirely in the future. This
  * option will cause the display of the previous year.
  * <li><b>newURL</b>
  * The URL to invoke when selecting a date which does not have a previous sample associated with it.
  * To the end of this will be appended "&date=<X>" whose value will be the date selected.</li>
  * <li><b>existingURL</b>
  * The URL to invoke when selecting an existing sample.
  * To the end of this will be appended "&sample_id=<n>".
  * <li><b>buildLinkFunc</b>
  * A callback (taking 3 arguments - record array, options, and baseline cell contents - just the date as a string)
  * to generate the link. This is optional. Can be used if special classes are to be added, or to
  * handle extra filter constraints.
  * </li>
  * </ul>
  * @todo Future Enhancements? Allow restriction to month.
  */
  public static function report_calendar_grid($options) {
    global $indicia_templates;
    // TODO : i8n
    $warnings="";
    self::add_resource('jquery_ui');
    // there are some report parameters that we can assume for a calendar based request...
    // the report must have a date field, a user_id field if set in the configuration, and a location_id.
    // default is samples_list_for_cms_user.xml
    $options = self::get_report_calendar_grid_options($options);
    $extras = '';
    $currentParamValues = self::getReportGridCurrentParamValues($options);
    self::request_report($response, $options, $currentParamValues, FALSE, $extras);
    if (isset($response['error'])) {
      return "ERROR RETURNED FROM request_report:".$response['error'];
    }
    // We're not even going to bother with asking the user to populate a partially filled in report parameter set.
    if (isset($response['parameterRequest'])) {
      return '<p>Internal Error: Report request parameters not set up correctly.<br />'.(print_r($response,TRUE)).'<p>';
    }
    self::$javascript .= "
var pageURI = \"".$_SERVER['REQUEST_URI']."\";
function rebuild_page_url(oldURL, overrideparam, overridevalue, removeparam) {
  var parts = oldURL.split('?');
  var params = [];
  if(overridevalue!=='') params.push(overrideparam+'='+overridevalue);
  if(parts.length > 1) {
    var oldparams = parts[1].split('&');
    for(var i = 0; i < oldparams.length; i++){
      var bits = oldparams[i].split('=');
      if(bits[0] != overrideparam && removeparam.indexOf(bits[0])<0) params.push(oldparams[i]);
    }
  }
  var retVal = parts[0]+(params.length > 0 ? '?'+(params.join('&')) : '');
  return retVal;
};
";

    // convert records to a date based array so it can be used when generating the grid.
    $records = $response['records'];
    $dateRecords=[];
    foreach($records as $record){
      if(isset($dateRecords[$record['date']])) {
        $dateRecords[$record['date']][] = $record;
      } else {
        $dateRecords[$record['date']] = [$record];
      }
    }
    $pageUrlParams = self::get_report_calendar_grid_page_url_params();
    $pageUrl = self::report_calendar_grid_get_reload_url($pageUrlParams);
    $pageUrl .= (strpos($pageUrl , '?') === FALSE) ? '?' : '&';
    $thClass = $options['thClass'];
    $r = PHP_EOL . '<table class="' . $options['class'] . '">';
    $r .= PHP_EOL . '<thead class="' . $thClass . '">' .
      '<tr>' .
      ($options['includeWeekNumber'] ?
        '<th class="' . $thClass . '" colspan=2>' . lang::get('Week Number') . '</th><th class="' . $thClass . '" colspan=4></th>' :
        '<th class="' . $thClass . '" colspan=5></th>');

    $baseTheme = hostsite_get_config_value('iform.settings', 'base_theme', 'generic');
    $reloadURL = $pageUrl . $pageUrlParams['year']['name']."=";
    $firstYear = (!empty($options["first_year"]) && $options["year"] >= $options["first_year"] ? $options["first_year"] : $options["year"]);
    $prevYear = $options["year"] - 1;
    $nextYear = $options["year"] + 1;
    $lang = [
      'Next' => lang::get('Next'),
      'Prev' => lang::get('Prev'),
    ];
    if ($baseTheme === 'generic') {
      $nextLink = $options["year"] < date('Y') ? <<<HTML
          <a id="year-control-next" title="$nextYear" rel="nofollow" href="$reloadURL$nextYear" class="ui-datepicker-next ui-corner-all">
            <span class="ui-icon ui-icon-circle-triangle-e">$lang[Next]</span>
          </a>
        HTML
        : '';
      $template = <<<HTML
        <div>
          <a id="year-control-previous" title="$prevYear" rel="nofollow" href="$reloadURL$prevYear" class="ui-datepicker-prev ui-corner-all">
            <span class="ui-icon ui-icon-circle-triangle-w">$lang[Prev]</span>
          </a>
          {control}
          $nextLink
        </div>

      HTML;
    } else {
      $nextLink = $options["year"] < date('Y') ? <<<HTML
          <div class="$indicia_templates[inputGroupAddon] ctrl-addons">
            <a id="year-control-next" title="$nextYear" rel="nofollow" href="$reloadURL$nextYear" class="yearControl">
              <span class="glyphicon glyphicon-step-forward" aria-hidden="true"></span>
            </a>
          </div>
        HTML
        : '';
      $template = <<<HTML
        <div id="ctrl-wrap-{id}" class="ctrl-wrap right">
          <div class="$indicia_templates[inputGroup]">
            <div class="$indicia_templates[inputGroupAddon] ctrl-addons">
              <a id="year-control-previous" title="$prevYear" rel="nofollow" href="$reloadURL$prevYear" class="yearControl">
                <span class="glyphicon glyphicon-step-backward"></span>
              </a>
            </div>
            {control}
            $nextLink
          </div>
        </div>

      HTML;
    }
    $r .= '<th class="' . $thClass . '" colspan=3 class="year-picker">';
    $indicia_templates['rcg_controlWrap'] = $template;
    $ctrlid = 'year-select';
    $lookUpValues = range(date('Y'), $firstYear, -1);
    $lookUpValues = array_combine($lookUpValues, $lookUpValues);
    if ($baseTheme === 'generic') {
      $r .= str_replace(['{control}', '{id}'], ['<span class="thisYear">' . $options["year"] . '</span>', $ctrlid], $template);
    } else {
      if(empty($options["first_year"]) && $options["year"] == date('Y')) {
        $r .= data_entry_helper::text_input([
          'id' => $ctrlid,
          'class' => 'year-select',
          'disabled' => ' disabled="disabled" ',
          'fieldname' => 'year-select',
          'default' => $options["year"],
          'controlWrapTemplate' => 'rcg_controlWrap'
        ]);
      } else {
        $r .= data_entry_helper::select([
          'id' => $ctrlid,
          'class' => 'year-select',
          'fieldname' => 'year-select',
          'lookupValues' => $lookUpValues,
          'default' => $options["year"],
          'controlWrapTemplate' => 'rcg_controlWrap'
        ]);
        report_helper::$javascript .= "$('#".$ctrlid."').on('input', function() { window.location.href= '" . $reloadURL . "'+$(this).val(); });" . PHP_EOL;
    }
    }
    $r .= '</th>';
    $r .= "</tr></thead>\n";

    // don't need a separate "Add survey" button as they just need to click the day....
    // Not implementing a download.
    $r .= "<tbody>\n";
    $date_from = ['year'=>$options["year"], 'month'=>1, 'day'=>1];
    $weekno=0;
    // ISO Date - Mon=1, Sun=7
    // Week 1 = the week with date_from in
    if(!isset($options['weekstart']) || $options['weekstart']=='') {
      $options['weekstart']="weekday=7"; // Default Sunday
    }
    $weekstart=explode('=',$options['weekstart']);
    if(!isset($options['weekNumberFilter']) || $options['weekNumberFilter']=='') {
      $options['weekNumberFilter']=":";
    }
    $weeknumberfilter=explode(':',$options['weekNumberFilter']);
    if(count($weeknumberfilter)!=2){
      $warnings .= "Week number filter unrecognised {".$options['weekNumberFilter']."} defaulting to all<br />";
      $weeknumberfilter = ['',''];
    } else {
      if($weeknumberfilter[0] != '' && (intval($weeknumberfilter[0])!=$weeknumberfilter[0] || $weeknumberfilter[0]>52)){
        $warnings .= "Week number filter start unrecognised or out of range {".$weeknumberfilter[0]."} defaulting to year start<br />";
        $weeknumberfilter[0] = '';
      }
      if($weeknumberfilter[1] != '' && (intval($weeknumberfilter[1])!=$weeknumberfilter[1] || $weeknumberfilter[1]<$weeknumberfilter[0] || $weeknumberfilter[1]>52)){
        $warnings .= "Week number filter end unrecognised or out of range {".$weeknumberfilter[1]."} defaulting to year end<br />";
        $weeknumberfilter[1] = '';
      }
    }
    if($weekstart[0]=='date'){
      $weekstart_date = date_create($date_from['year']."-".$weekstart[1]);
      if(!$weekstart_date){
        $warnings .= "Weekstart month-day combination unrecognised {".$weekstart[1]."} defaulting to weekday=7 - Sunday<br />";
        $weekstart[1]=7;
      } else {
        $weekstart[1] = $weekstart_date->format('N');
      }
    }
    if(intval($weekstart[1])!=$weekstart[1] || $weekstart[1]<1 || $weekstart[1]>7) {
      $warnings .= "Weekstart unrecognised or out of range {".$weekstart[1]."} defaulting to 7 - Sunday<br />";
      $weekstart[1]=7;
    }
    $consider_date = new DateTime($date_from['year'].'-'.$date_from['month'].'-'.$date_from['day']);
    while($consider_date->format('N')!=$weekstart[1]) {
      $consider_date->modify('-1 day');
    }
    $header_date=clone $consider_date;
    $r .= "<tr>".($options['includeWeekNumber'] ? "<td></td>" : "")."<td></td>";

    require_once('prebuilt_forms/includes/language_utils.php');
    $lang = iform_lang_iso_639_2(hostsite_get_user_field('language'));
    setlocale (LC_TIME, $lang);

    for($i=0; $i<7; $i++){
      $r .= "<td class=\"day\">" .
        lang::get(mb_convert_encoding(date('D', $header_date->getTimestamp()), 'UTF-8', 'ISO-8859-1')) .
        "</td>"; // i8n
      $header_date->modify('+1 day');
    }
    $r .= "</tr>";
    if(isset($options['weekOneContains']) && $options['weekOneContains']!=""){
      $weekOne_date = date_create($date_from['year'].'-'.$options['weekOneContains']);
      if(!$weekOne_date){
        $warnings .= "Week one month-day combination unrecognised {".$options['weekOneContains']."} defaulting to Jan-01<br />";
        $weekOne_date = date_create($date_from['year'].'-Jan-01');
      }
    } else {
      $weekOne_date = date_create($date_from['year'].'-Jan-01');
    }
    while($weekOne_date->format('N')!=$weekstart[1]){
      $weekOne_date->modify('-1 day'); // scan back to start of week
    }
    while($weekOne_date > $consider_date){
      $weekOne_date->modify('-7 days');
      $weekno--;
    }
    if($weeknumberfilter[0]!=''){
      while($weekno < ($weeknumberfilter[0]-1)){
        $consider_date->modify('+7 days');
        $weekno++;
      }
    }
    $now = new DateTime();
    if($now < $consider_date && $options["viewPreviousIfTooEarly"]){
      $options["year"]--;
      $options["viewPreviousIfTooEarly"] = FALSE;
      unset($options['extraParams']['date_from']);
      unset($options['extraParams']['date_to']);
      return self::report_calendar_grid($options);
    }
    $options["newURL"] .= (strpos($options["newURL"] , '?') === FALSE) ? '?' : '&';
    $options["existingURL"] .= (strpos($options["existingURL"] , '?') === FALSE) ? '?' : '&';

    while($consider_date->format('Y') <= $options["year"] && ($weeknumberfilter[1]=='' || $consider_date->format('N')!=$weekstart[1] || $weekno < $weeknumberfilter[1])){
      if($consider_date->format('N')==$weekstart[1]) {
        $weekno++;
        $r .= "<tr class=\"datarow\">" .
          ($options['includeWeekNumber'] ? "<td class=\"weeknum\">" . $weekno . "</td>" : "") .
          "<td class\"month\">" .
          t(mb_convert_encoding(date('M', $consider_date->getTimestamp()), 'UTF-8', 'ISO-8859-1')) .
          "</td>";
      }
      $cellContents=$consider_date->format('j');  // day in month.
      $cellclass="";
      if($now < $consider_date){
        $cellclass="future"; // can't enter data in the future.
      } else if($consider_date->format('Y') == $options["year"]){ // only allow data to be entered for the year being considered.
        if(isset($options['buildLinkFunc'])){
          $options['consider_date'] = $consider_date->format('d/m/Y');
          $callbackVal = call_user_func_array($options['buildLinkFunc'],
              [isset($dateRecords[$consider_date->format('d/m/Y')]) ? $dateRecords[$consider_date->format('d/m/Y')] : [],
                    $options, $cellContents]);
          $cellclass=$callbackVal['cellclass'];
          $cellContents=$callbackVal['cellContents'];
        } else if(isset($dateRecords[$consider_date->format('d/m/Y')])){ // check if there is a record on this date
          $cellclass="existingLink";
          $cellContents .= ' <a href="'.$options["newURL"].'date='.$consider_date->format('d/m/Y').'" class="newLink" title="Create a new sample on '.$consider_date->format('d/m/Y').'" ><div class="ui-state-default add-button">&nbsp;</div></a> ';
          foreach ($dateRecords[$consider_date->format('d/m/Y')] as $record) {
            $cellContents.='<a href="'.$options["existingURL"].'sample_id='.$record["sample_id"].'" title="View existing sample for '.$record["location_name"].' on '.$consider_date->format('d/m/Y').'" ><div class="ui-state-default view-button">&nbsp;</div></a>';
          }
        } else {
          $cellclass="newLink";
          $cellContents .= ' <a href="'.$options["newURL"].'date='.$consider_date->format('d/m/Y').'" class="newLink" title="Create a new sample on '.$consider_date->format('d/m/Y').'" ><div class="ui-state-default add-button">&nbsp;</div></a>';
        }
      }
      $r .= "<td class=\"".$cellclass." ".($consider_date->format('N')>=6 ? "weekend" : "weekday")."\" >".$cellContents."</td>";
      $consider_date->modify('+1 day');
      $r .= ($consider_date->format('N')==$weekstart[1] ? "</tr>" : "");
    }
    if($consider_date->format('N')!=$weekstart[1]) { // need to fill up rest of week
      while($consider_date->format('N')!=$weekstart[1]){
        $r .= "<td class=\"".($consider_date->format('N')>=6 ? "weekend" : "weekday")."\">".$consider_date->format('j')."</td>";
        $consider_date->modify('+1 day');
      }
      $r .= "</tr>";
    }
    $r .= "</tbody>";
    $extraFooter = '';
    if (isset($options['footer']) && !empty($options['footer'])) {
      $rootFolder = self::getRootfolder();
      $currentUrl = self::get_reload_link_parts();
      $footer = str_replace(['{rootFolder}',
                '{currentUrl}',
                '{sep}',
                '{warehouseRoot}',
                '{geoserverRoot}',
                '{nonce}',
                '{auth}',
                '{iUserID}',
                '{website_id}',
      			'{startDate}',
            '{endDate}'],
          [$rootFolder,
                $currentUrl['path'],
                strpos($rootFolder, '?')===FALSE ? '?' : '&',
                self::$base_url,
                self::$geoserver_url,
                'nonce='.$options['readAuth']['nonce'],
                'auth_token='.$options['readAuth']['auth_token'],
                (function_exists('hostsite_get_user_field') ? hostsite_get_user_field('indicia_user_id') : ''),
                self::$website_id,
                $options['extraParams']['date_from'],
                $options['extraParams']['date_to']
          ], $options['footer']);
      // Merge in any references to the parameters sent to the report: could extend this in the future to pass in the extraParams
      foreach($currentParamValues as $key=>$param){
        $footer = str_replace(['{'.$key.'}'], [$param], $footer);
      }
      $extraFooter .= '<div class="left">'.$footer.'</div>';
    }
    if (!empty($extraFooter))
      $r .= '<tfoot><tr><td colspan="'.($options['includeWeekNumber'] ? 9 : 8).'">'.$extraFooter.'</td></tr></tfoot>';
    $r .= "</table>\n";
    return $warnings.$r;
  }

  /**
   * Applies the defaults to the options array passed to a report_calendar_grid.
   * @param array $options Options array passed to the control.
   */
  private static function get_report_calendar_grid_options($options) {
    if (function_exists('hostsite_get_config_value')) {
      $baseTheme = hostsite_get_config_value('iform.settings', 'base_theme', 'generic');
    } else {
      $baseTheme = 'generic';
    }

    $userId = hostsite_get_user_field('id');
    $options = array_merge([
      'mode' => 'report',
      'id' => 'calendar-report-output', // this needs to be set explicitly when more than one report on a page
      'class' => $baseTheme === 'generic' ? 'ui-widget ui-widget-content report-grid' : 'table report-grid',
      'thClass' => $baseTheme === 'generic' ? 'ui-widget-header' : '',
      'extraParams' => [],
      'year' => date('Y'),
      'viewPreviousIfTooEarly' => TRUE, // if today is before the start of the calendar, display last year.
        // it is possible to create a partial calendar.
      'includeWeekNumber' => FALSE,
      'weekstart' => 'weekday=7', // Default Sunday
      'weekNumberFilter' => ':'
    ], $options);
    $options["extraParams"] = array_merge([
      'date_from' => $options["year"].'-01-01',
      'date_to' => $options["year"].'-12-31',
      'user_id' => $userId, // Initially CMS User, changed to Indicia User later if in Easy Login mode.
      'cms_user_id' => $userId, // CMS User, not Indicia User.
      'smpattrs' => ''], $options["extraParams"]);
    $options['my_user_id'] = $userId; // Initially CMS User, changed to Indicia User later if in Easy Login mode.
    // Note for the calendar reports, the user_id is assumed to be the CMS user id as recorded in the CMS User ID attribute,
    // not the Indicia user id.
    if (function_exists('hostsite_get_user_field') && $options["extraParams"]['user_id'] == $options["extraParams"]['cms_user_id']) {
      $indicia_user_id = hostsite_get_user_field('indicia_user_id');
      if ($indicia_user_id) {
        $options["extraParams"]['user_id'] = $indicia_user_id;
      }
      if ($options['my_user_id']) { // false switches this off.
        $user_id = hostsite_get_user_field('indicia_user_id', FALSE, FALSE, $options['my_user_id']);
        if(!empty($user_id)) {
          $options['my_user_id'] = $user_id;
        }
      }
    }
    return $options;
  }

 /**
   * Works out the page URL param names for this report calendar grid, and also gets their current values.
   * Note there is no need to sort for the calender grid.
   * @return array Contains the page params, as an assoc array. Each array value is an array containing name & value.
   */
  private static function get_report_calendar_grid_page_url_params() {
    $yearKey = 'year';
    return [
      'year' => [
        'name' => $yearKey,
        'value' => isset($_GET[$yearKey]) ? $_GET[$yearKey] : null
      ]
    ];
  }
  /**
   * Build a url suitable for inclusion in the links for the report calendar grid column pagination
   * bar. This effectively re-builds the current page's URL, but drops the query string parameters that
   * indicate the year and site.
   * Note there is no need to sort for the calender grid.
   * @param array $pageUrlParams List pagination parameters which should be excluded.
   * @return string
   */
  private static function report_calendar_grid_get_reload_url($pageUrlParams) {
    // get the url parameters. Don't use $_GET, because it contains any parameters that are not in the
    // URL when search friendly URLs are used (e.g. a Drupal path node/123 is mapped to index.php?q=node/123
    // using Apache mod_alias but we don't want to know about that)
    $reloadUrl = self::get_reload_link_parts();
    // find the names of the params we must not include
    $excludedParams = [];
    foreach($pageUrlParams as $param) {
      $excludedParams[] = $param['name'];
    }
    foreach ($reloadUrl['params'] as $key => $value) {
      if (!in_array($key, $excludedParams)){
        $reloadUrl['path'] .= (strpos($reloadUrl['path'],'?') === FALSE ? '?' : '&')."$key=$value";
      }
    }
    return $reloadUrl['path'];
  }

  /**
   * Inserts into the page javascript a function for loading features onto the map as a result of report output.
   * @param string $addFeaturesJs JavaScript which creates the list of features.
   * @param string $defsettings Default style settings.
   * @param string $selsettings Selected item style settings.
   * @param string $defStyleFns JavaScript snippet which places any style functions required into the
   * context parameter when creating a default Style.
   * @param string $selStyleFns JavaScript snippet which places any style functions required into the
   * context parameter when creating a selected Style.
   * @param boolean $zoomToExtent If true, then the map will zoom to show the extent of the features added.
   */
  private static function addFeaturesLoadingJs($addFeaturesJs, $defsettings='',
      $selsettings='{"strokeColor":"#ff0000","fillColor":"#ff0000","strokeWidth":2}', $defStyleFns='', $selStyleFns='', $zoomToExtent=true,
      $featureDoubleOutlineColour='') {
    $layerTitle = lang::get('Report output');
    // Note that we still need the Js to add the layer even if using AJAX (when $addFeaturesJs will be empty)
    report_helper::$javascript.= "
  if (typeof OpenLayers !== \"undefined\") {
    var defaultStyle = new OpenLayers.Style($defsettings$defStyleFns);
    var selectStyle = new OpenLayers.Style($selsettings$selStyleFns);
    var styleMap = new OpenLayers.StyleMap({'default' : defaultStyle, 'select' : selectStyle});
    if (typeof indiciaData.reportlayer==='undefined') {
    indiciaData.reportlayer = new OpenLayers.Layer.Vector('$layerTitle', {styleMap: styleMap, rendererOptions: {zIndexing: true}});
    }";
    // If there are some special styles to apply, but the layer exists already, apply the styling
    if ($defStyleFns!=='' || $selStyleFns) {
      report_helper::$javascript.= "
    else {
      indiciaData.reportlayer.styleMap = styleMap;
    }";
    }
    report_helper::$javascript .= "\n    mapInitialisationHooks.push(function(div) {\n";
    report_helper::$javascript .= "      div.map.addLayer(indiciaData.reportlayer);\n";
    if (!empty($addFeaturesJs)) {
      report_helper::$javascript .= "      var features = [];\n";
      report_helper::$javascript .= "$addFeaturesJs\n";
      report_helper::$javascript .= "      indiciaData.reportlayer.addFeatures(features);\n";
      if ($zoomToExtent && !empty($addFeaturesJs)) {
        self::$javascript .= <<<JS
      if (indiciaData['zoomToAfterFetchingGoogleApiScript-' + div.map.id]) {
        indiciaData['zoomToAfterFetchingGoogleApiScript-' + div.map.id] = indiciaData.reportlayer.getDataExtent();
      } else {
        div.map.zoomToExtent(indiciaData.reportlayer.getDataExtent());
      }

JS;
      }
      if (!empty($featureDoubleOutlineColour)) {
        // push a clone of the array of features onto a layer which will draw an outline.
        report_helper::$javascript .= "
        var defaultStyleOutlines = new OpenLayers.Style({\"strokeWidth\":5,\"strokeColor\":\"$featureDoubleOutlineColour\",
            \"fillOpacity\":0});
        var styleMap = new OpenLayers.StyleMap({'default' : defaultStyleOutlines});
        indiciaData.outlinelayer = new OpenLayers.Layer.Vector('Outlines', {styleMap: defaultStyleOutlines});
        outlinefeatures=[];
        $.each(features, function(idx, f) { outlinefeatures.push(f.clone()); });
        indiciaData.outlinelayer.addFeatures(outlinefeatures);
        div.map.addLayer(indiciaData.outlinelayer);
        div.map.setLayerIndex(indiciaData.outlinelayer, 1);\n";
      }
    }
    self::$javascript .= "    });
  }\n";
  }


 /**
  * <p>Outputs a calendar based summary grid that loads the content of a report.</p>
  * <p>If you need 2 grids on one page, then you must define a different id in the options for each grid.</p>
  * <p>The grid operation has NOT been AJAXified. There is no download option.</p>
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>id</b><br/>
  * Optional unique identifier for the grid's container div. This is required if there is more than
  * one grid on a single web page to allow separation of the page and sort $_GET parameters in the URLs
  * generated.</li>
  * <li><b>mode</b><br/>
  * Pass report for a report, or direct for an Indicia table or view. Default is report.</li>
  * <li><b>readAuth</b><br/>
  * Read authorisation tokens.</li>
  * <li><b>dataSource</b><br/>
  * Name of the report file or table/view. when used, any user_id must refer to the CMS user ID, not the Indicia
  * User.</li>
  * <li><b>view</b>
  * When loading from a view, specify list, gv or detail to determine which view variant is loaded. Default is list.
  * </li>
  * <li><b>extraParams</b><br/>
  * Array of additional key value pairs to attach to the request. This should include fixed values which cannot be changed by the
  * user and therefore are not needed in the parameters form.
  * </li>
  * <li><b>paramDefaults</b>
  * Optional associative array of parameter default values. Default values appear in the parameter form and can be overridden.</li>
  * <li><b>tableHeaders</b>
  * Defines which week column headers should be included: date, number or both
  * <li><b>weekstart</b>
  * Defines the first day of the week. There are 2 options.<br/>'.
  *  weekday=<n> where <n> is a number between 1 (for Monday) and 7 (for Sunday). Default is 'weekday=7'
  *  date=MMM-DD where MMM-DD is a month/day combination: e.g. choosing Apr-1 will start each week on the day of the week on which the 1st of April occurs.</li>
  * <li><b>weekOneContains</b>
  * Defines week one as the week which contains this date. Format should be MMM-DD, which is a month/day combination: e.g. choosing Apr-1 will define
  * week one as being the week containing the 1st of April. Defaults to the 1st of January.</li>
  * <li><b>weekNumberFilter</b>
  * Restrict displayed weeks to between 2 weeks defined by their week numbers. Colon separated.
  * Leaving an empty value means the end of the year.
  * Examples: "1:30" - Weeks one to thirty inclusive.
  * "4:" - Week four onwards.
  * ":5" - Upto and including week five.</li>
  * <li><b>rowGroupColumn</b>
  * The column in the report which is used as the label for the vertical axis on the grid.</li>
  * <li><b>rowGroupID</b>
  * The column in the report which is used as the id for the vertical axis on the grid.</li>
  * <li><b>countColumn</b>
  * OPTIONAL: The column in the report which contains the count for this occurrence. If omitted then the default
  * is to assume one occurrence = count of 1</li>
  * <li><b>includeChartItemSeries</b>
  * Defaults to true. Include a series for each item in the report output.
  * </li>
  * <li><b>includeChartTotalSeries</b>
  * Defaults to true. Include a series for the total of each item in the report output.
  * </li>
  * </ul>
  * @todo: Future Enhancements? Allow restriction to month.
  */
  public static function report_calendar_summary($options) {
    // I know that there are better ways to approach some of the date manipulation, but they are PHP 5.3+.
    // We support back to PHP 5.2
    // TODO : i8n
    // TODO invariant IDs and names prevents more than one on a page.
    // TODO convert to tabs when switching between chart and table.
    $warnings = '<span style="display:none;">Starting report_calendar_summary : '.date(DATE_ATOM).'</span>'."\n";
    self::add_resource('jquery_ui');
    // there are some report parameters that we can assume for a calendar based request...
    // the report must have a date field, a user_id field if set in the configuration, and a location_id.
    // default is samples_list_for_cms_user.xml
    $options = self::get_report_calendar_summary_options($options);
    $extras = '';
    self::request_report($response, $options, $currentParamValues, false, $extras);
    if (isset($response['error'])) {
      return "ERROR RETURNED FROM request_report:<br />".(print_r($response,true));
    }
    // We're not even going to bother with asking the user to populate a partially filled in report parameter set.
    if (isset($response['parameterRequest'])) {
      return '<p>Internal Error: Report request parameters not set up correctly.<br />'.(print_r($response,true)).'<p>';
    }
    // convert records to a date based array so it can be used when generating the grid.
    $warnings .= '<span style="display:none;">Report request finish : '.date(DATE_ATOM).'</span>'."\n";
    $records = $response['records'];
    self::$javascript .= "
var pageURI = \"".$_SERVER['REQUEST_URI']."\";
function rebuild_page_url(oldURL, overrideparam, overridevalue) {
  var parts = oldURL.split('?');
  var params = [];
  if(overridevalue!=='') params.push(overrideparam+'='+overridevalue);
  if(parts.length > 1) {
    var oldparams = parts[1].split('&');
    for(var i = 0; i < oldparams.length; i++){
      var bits = oldparams[i].split('=');
      if(bits[0] != overrideparam) params.push(oldparams[i]);
    }
  }
  return parts[0]+(params.length > 0 ? '?'+params.join('&') : '');
};
var pageURI = \"".$_SERVER['REQUEST_URI']."\";
function update_controls(){
  $('#year-control-previous').attr('href',rebuild_page_url(pageURI,'year',".substr($options['date_start'],0,4)."-1));
  $('#year-control-next').attr('href',rebuild_page_url(pageURI,'year',".substr($options['date_start'],0,4)."+1));
  // user and location ids are dealt with in the main form. their change functions look a pageURI
}
update_controls();
";

    // ISO Date - Mon=1, Sun=7
    // Week 1 = the week with date_from in
    if(!isset($options['weekstart']) || $options['weekstart']=="") {
      $options['weekstart']="weekday=7"; // Default Sunday
    }
    if(!isset($options['weekNumberFilter']) ||$options['weekNumberFilter']=="") {
      $options['weekNumberFilter']=":";
    }
    $weeknumberfilter=explode(':',$options['weekNumberFilter']);
    if(count($weeknumberfilter)!=2){
      $warnings .= "Week number filter unrecognised {".$options['weekNumberFilter']."} defaulting to all<br />";
      $weeknumberfilter=array('','');
    } else {
      if($weeknumberfilter[0] != '' && (intval($weeknumberfilter[0])!=$weeknumberfilter[0] || $weeknumberfilter[0]>52)){
        $warnings .= "Week number filter start unrecognised or out of range {".$weeknumberfilter[0]."} defaulting to year start<br />";
        $weeknumberfilter[0] = '';
      }
      if($weeknumberfilter[1] != '' && (intval($weeknumberfilter[1])!=$weeknumberfilter[1] || $weeknumberfilter[1]<$weeknumberfilter[0] || $weeknumberfilter[1]>52)){
        $warnings .= "Week number filter end unrecognised or out of range {".$weeknumberfilter[1]."} defaulting to year end<br />";
        $weeknumberfilter[1] = '';
      }
    }
    $weekstart=explode('=',$options['weekstart']);
    if($weekstart[0]=='date'){
      $weekstart_date = date_create(substr($options['date_start'],0,4)."-".$weekstart[1]);
      if(!$weekstart_date){
        $warnings .= "Weekstart month-day combination unrecognised {".$weekstart[1]."} defaulting to weekday=7 - Sunday<br />";
        $weekstart[1]=7;
      } else $weekstart[1]=$weekstart_date->format('N');
    }
    if(intval($weekstart[1])!=$weekstart[1] || $weekstart[1]<1 || $weekstart[1]>7) {
      $warnings .= "Weekstart unrecognised or out of range {".$weekstart[1]."} defaulting to 7 - Sunday<br />";
      $weekstart[1]=7;
    }
    if(isset($options['weekOneContains']) && $options['weekOneContains']!=""){
      $weekOne_date = date_create(substr($options['date_start'],0,4).'-'.$options['weekOneContains']);
      if(!$weekOne_date){
        $warnings .= "Week one month-day combination unrecognised {".$options['weekOneContains']."} defaulting to Jan-01<br />";
        $weekOne_date = date_create(substr($options['date_start'],0,4).'-Jan-01');
      }
    } else
      $weekOne_date = date_create(substr($options['date_start'],0,4).'-Jan-01');
    $weekOne_date_weekday = $weekOne_date->format('N');
    if($weekOne_date_weekday > $weekstart[1]) // scan back to start of week
      $weekOne_date->modify('-'.($weekOne_date_weekday-$weekstart[1]).' day');
    else if($weekOne_date_weekday < $weekstart[1])
      $weekOne_date->modify('-'.(7+$weekOne_date_weekday-$weekstart[1]).' day');
    $firstWeek_date = clone $weekOne_date; // date we start providing data for
    $weekOne_date_yearday = $weekOne_date->format('z'); // day within year note year_start_yearDay is by definition 0
    $minWeekNo = $weeknumberfilter[0]!='' ? $weeknumberfilter[0] : 1;
    $numWeeks = ceil($weekOne_date_yearday/7); // number of weeks in year prior to $weekOne_date - 1st Jan gives zero, 2nd-8th Jan gives 1, etc
    if($minWeekNo-1 < (-1 * $numWeeks)) $minWeekNo=(-1 * $numWeeks)+1; // have to allow for week zero
    if($minWeekNo < 1)
      $firstWeek_date->modify((($minWeekNo-1)*7).' days'); // have to allow for week zero
    else if($minWeekNo > 1)
      $firstWeek_date->modify('+'.(($minWeekNo-1)*7).' days');

    if($weeknumberfilter[1]!=''){
      $maxWeekNo = $weeknumberfilter[1];
    } else {
      $year_end = date_create(substr($options['date_start'],0,4).'-Dec-25'); // don't want to go beyond the end of year: this is 1st Jan minus 1 week: it is the start of the last full week
      $year_end_yearDay = $year_end->format('z'); // day within year
      $maxWeekNo = 1+ceil(($year_end_yearDay-$weekOne_date_yearday)/7);
    }
    $warnings .= '<span style="display:none;">Initial date processing complete : '.date(DATE_ATOM).'</span>'."\n";
    $tableNumberHeaderRow = "";
    $tableDateHeaderRow = "";
    $downloadNumberHeaderRow = "";
    $downloadDateHeaderRow = "";
    $chartNumberLabels=[];
    $chartDateLabels=[];
    $fullDates=[];
    for($i= $minWeekNo; $i <= $maxWeekNo; $i++){
      $tableNumberHeaderRow.= '<td class="week">'.$i.'</td>';
      $tableDateHeaderRow.= '<td class="week">'.$firstWeek_date->format('M').'<br/>'.$firstWeek_date->format('d').'</td>';
      $downloadNumberHeaderRow.= ','.$i;
      $downloadDateHeaderRow.= ','.$firstWeek_date->format('d/m/Y');
      $chartNumberLabels[] = "".$i;
      $chartDateLabels[] = $firstWeek_date->format('M-d');
      $fullDates[$i] = $firstWeek_date->format('d/m/Y');
      $firstWeek_date->modify('+7 days');
    }
    $summaryArray=[]; // this is used for the table output format
    $rawArray=[]; // this is used for the table output format
    // In order to apply the data combination and estmation processing, we assume that the the records are in taxon, location_id, sample_id order.
    $locationArray=[]; // this is for a single species at a time.
    $lastLocation=false;
    $seriesLabels=[];
    $lastTaxonID=false;
    $locationSamples = [];
    $weekList = [];
    $avgFieldList = !empty($options['avgFields']) ? explode(',',$options['avgFields']) : false;
    if(!$avgFieldList || count($avgFieldList)==0) $avgFields = false;
    else {
      $avgFields = [];
      foreach($avgFieldList as $avgField) {
        $avgFields[$avgField] = array('caption'=>$avgField, 'attr'=>false);
        $parts = explode(':',$avgField);
        if(count($parts)==2 && $parts[0]='smpattr') {
          $smpAttribute=data_entry_helper::get_population_data(array(
              'table' => 'sample_attribute',
              'extraParams'=>$options['readAuth'] + array('view' => 'list', 'id'=>$parts[1])
          ));
          if(count($smpAttribute)>=1){ // may be assigned to more than one survey on this website. This is not relevant to info we want.
            $avgFields[$avgField]['id'] = $parts[1];
            $avgFields[$avgField]['attr'] = $smpAttribute[0];
            $avgFields[$avgField]['caption'] = $smpAttribute[0]['caption'];
            if($smpAttribute[0]['data_type']=='L')
              $avgFields[$avgField]['attr']['termList'] = data_entry_helper::get_population_data(array(
                'table' => 'termlists_term',
                'extraParams'=>$options['readAuth'] + array('view' => 'detail', 'termlist_id'=>$avgFields[$avgField]['attr']['termlist_id'])
            ));
          }
        }
      }
    }

    // we are assuming that there can be more than one occurrence of a given taxon per sample.
    if($options['location_list'] != 'all' && count($options['location_list']) == 0) $options['location_list'] = 'none';
    foreach($records as $recid => $record){
      // If the taxon has changed
      $this_date = date_create(str_replace('/','-',$record['date'])); // prevents day/month ordering issues
      $this_index = $this_date->format('z');
      $this_weekday = $this_date->format('N');
      if($this_weekday > $weekstart[1]) // scan back to start of week
      	$this_date->modify('-'.($this_weekday-$weekstart[1]).' day');
      else if($this_weekday < $weekstart[1])
      	$this_date->modify('-'.(7+$this_weekday-$weekstart[1]).' day');
      // this_date now points to the start of the week. Next work out the week number.
      $this_yearday = $this_date->format('z');
      $weekno = (int)floor(($this_yearday-$weekOne_date_yearday)/7)+1;
      if(isset($weekList[$weekno])){
        if(!in_array($record['location_name'],$weekList[$weekno])) $weekList[$weekno][] = $record['location_name'];
      } else $weekList[$weekno] = array($record['location_name']);
      if(!isset($rawArray[$this_index])){
        $rawArray[$this_index] = array('weekno'=>$weekno, 'counts'=>[], 'date'=>$record['date'], 'total'=>0, 'samples'=>[], 'avgFields'=>[]);
      }
      // we assume that the report is configured to return the user_id which matches the method used to generate my_user_id
      if (($options['my_user_id']==$record['user_id'] ||
           $options['location_list'] == 'all' ||
           ($options['location_list'] != 'none' && in_array($record['location_id'], $options['location_list'])))
          && !isset($rawArray[$this_index]['samples'][$record['sample_id']])){
        $rawArray[$this_index]['samples'][$record['sample_id']]=array('id'=>$record['sample_id'], 'location_name'=>$record['location_name'], 'avgFields'=>[]);
        if($avgFields){
          foreach($avgFields as $field => $avgField) {
            if(!$avgField['attr'])
              $rawArray[$this_index]['samples'][$record['sample_id']]['avgFields'][$field] = $record[$field];
            else if($avgField['attr']['data_type']=='L') {
              $term = trim($record['attr_sample_term_'.$avgField['id']], "% \t\n\r\0\x0B");
              $rawArray[$this_index]['samples'][$record['sample_id']]['avgFields'][$field] = is_numeric($term) ? $term : null;
            } else
              $rawArray[$this_index]['samples'][$record['sample_id']]['avgFields'][$field] = $record['attr_sample_'.$avgField['id']];
          }
        }
      }
      $records[$recid]['weekno']=$weekno;
      $records[$recid]['date_index']=$this_index;
      if(isset($locationSamples[$record['location_id']])){
        if(isset($locationSamples[$record['location_id']][$weekno])) {
          if(!in_array($record['sample_id'], $locationSamples[$record['location_id']][$weekno]))
            $locationSamples[$record['location_id']][$weekno][] = $record['sample_id'];
        } else $locationSamples[$record['location_id']][$weekno] = array($record['sample_id']);
      } else $locationSamples[$record['location_id']] = array($weekno => array($record['sample_id']));
    }
    $warnings .= '<span style="display:none;">Records date pre-processing complete : '.date(DATE_ATOM).'</span>'."\n";
    if($avgFields) {
      foreach($rawArray as $dateIndex => $rawData) {
        foreach($avgFields as $field=>$avgField){
          $total=0;
          $count=0;
          foreach($rawArray[$dateIndex]['samples'] as $sample) {
            if($sample['avgFields'][$field] != null){
              $total += $sample['avgFields'][$field];
              $count++;
            }
          }
          $rawArray[$dateIndex]['avgFields'][$field] = $count ? $total/$count : "";
          if($options['avgFieldRound']=='nearest' && $rawArray[$dateIndex]['avgFields'][$field]!="")
            $rawArray[$dateIndex]['avgFields'][$field] = (int)round($rawArray[$dateIndex]['avgFields'][$field]);
        }
      }
    }
    $warnings .= '<span style="display:none;">Sample Attribute processing complete : '.date(DATE_ATOM).'</span>'."\n";
    $count = count($records);
    self::report_calendar_summary_initLoc1($minWeekNo, $maxWeekNo, $weekList);
    if($count>0) $locationArray = self::report_calendar_summary_initLoc2($minWeekNo, $maxWeekNo, $locationSamples[$records[0]['location_id']]);
    $warnings .= '<span style="display:none;">Number of records processed : '.$count.' : '.date(DATE_ATOM).'</span>'."\n";
    $downloadList = 'Location,'.
          ($options['tableHeaders'] == 'both' || $options['tableHeaders'] == 'number' ? lang::get('Week Number').',' : '').
          lang::get('Week Commencing').','.lang::get('Species').','.lang::get('Type').','.lang::get('Value')."\n";
    foreach($records as $idex => $record){
      // If the taxon has changed
      if(($lastTaxonID && $lastTaxonID!=$record[$options['rowGroupID']]) ||
         ($lastLocation && $lastLocation!=$record['location_id'])) {
        self::report_calendar_summary_processEstimates($summaryArray, $locationArray, $locationSamples[$lastLocation], $minWeekNo, $maxWeekNo, $fullDates, $lastTaxonID, $seriesLabels[$lastTaxonID], $options, $downloadList);
        $locationArray = self::report_calendar_summary_initLoc2($minWeekNo, $maxWeekNo, $locationSamples[$record['location_id']]);
      }
      $lastTaxonID=$record[$options['rowGroupID']];
      $seriesLabels[$lastTaxonID]=$record[$options['rowGroupColumn']];
      $lastLocation=$record['location_id'];
      $lastSample=$record['sample_id'];
      $weekno = $record['weekno'];
      if($lastTaxonID === null) $count = 0;
      else if(isset($options['countColumn']) && $options['countColumn']!=''){
        $count = (isset($record[$options['countColumn']])?$record[$options['countColumn']]:0);
      } else
        $count = 1; // default to single row = single occurrence
      // leave this conditional in - not sure what may happen in future, and it works.
      if($weekno >= $minWeekNo && $weekno <= $maxWeekNo){
        if($locationArray[$weekno]['this_sample'] != $lastSample) {
          $locationArray[$weekno]['max'] = max($locationArray[$weekno]['max'], $locationArray[$weekno]['sampleTotal']);
          $locationArray[$weekno]['this_sample'] = $lastSample;
          $locationArray[$weekno]['numSamples']++;
          $locationArray[$weekno]['sampleTotal'] = $count;
        } else
          $locationArray[$weekno]['sampleTotal'] += $count;
        $locationArray[$weekno]['total'] += $count;
        $locationArray[$weekno]['forcedZero'] = false;
        $locationArray[$weekno]['location'] = $record['location_name'];
      }
      $this_index = $record['date_index'];
      if($lastTaxonID != null) {
      	if(isset($rawArray[$this_index]['counts'][$lastTaxonID]))
          $rawArray[$this_index]['counts'][$lastTaxonID] += $count;
        else
          $rawArray[$this_index]['counts'][$lastTaxonID] = $count;
        $rawArray[$this_index]['total'] += $count;
      }
    }
    if($lastTaxonID || $lastLocation) {
      self::report_calendar_summary_processEstimates($summaryArray, $locationArray, $locationSamples[$lastLocation], $minWeekNo, $maxWeekNo, $fullDates, $lastTaxonID, $seriesLabels[$lastTaxonID], $options, $downloadList);
    }
    $warnings .= '<span style="display:none;">Estimate processing finished : '.date(DATE_ATOM).'</span>'."\n";
    if(count($summaryArray)==0)
      return $warnings.'<p>'.lang::get('No data returned for this period.').'</p>';
    $r="";
    // will storedata in an array[Y][X]
    $format= [];
    if(isset($options['outputTable']) && $options['outputTable']){
      $format['table'] = array('include'=>true,
          'display'=>(isset($options['simultaneousOutput']) && $options['simultaneousOutput'])||(isset($options['outputFormat']) && $options['outputFormat']=='table')||!isset($options['outputFormat']));
    }
    if(isset($options['outputChart']) && $options['outputChart']){
      $format['chart'] = array('include'=>true,
          'display'=>(isset($options['simultaneousOutput']) && $options['simultaneousOutput'])||(isset($options['outputFormat']) && $options['outputFormat']=='chart'));
      self::add_resource('jqplot');
      switch ($options['chartType']) {
        case 'bar' :
          self::add_resource('jqplot_bar');
          $renderer='$.jqplot.BarRenderer';
          break;
        case 'pie' :
          self::add_resource('jqplot_pie');
          $renderer='$.jqplot.PieRenderer';
          break;
        default :
          $renderer='$.jqplot.LineRenderer';
          break;
        // default is line
      }
      self::add_resource('jqplot_category_axis_renderer');
      $opts = [];
      $options['legendOptions']["show"]=true;
      $opts[] = "seriesDefaults:{\n".(isset($renderer) ? "  renderer:$renderer,\n" : '')."  rendererOptions:".json_encode($options['rendererOptions'])."}";
      $opts[] = 'legend:'.json_encode($options['legendOptions']);
    }
    if(count($format)==0) $format['table'] = array('include'=>true);
    $defaultSet=false;
    foreach($format as $type=>$info){
      $defaultSet=$defaultSet || $info['display'];
    }
    if(!$defaultSet){
      if(isset($format['table'])) $format['table']['display']=true;
      else if(isset($format['chart'])) $format['chart']['display']=true;
    }
    $r .= "\n<div class=\"inline-control report-summary-controls\">";
    $userPicksFormat = count($format)>1 && !(isset($options['simultaneousOutput']) && $options['simultaneousOutput']);
    $userPicksSource = ($options['includeRawData'] ? 1 : 0) +
       ($options['includeSummaryData'] ? 1 : 0) +
       ($options['includeEstimatesData'] ? 1 : 0) > 1;
    if(!$userPicksFormat && !$userPicksSource) {
        $r .= '<input type="hidden" id="outputSource" name="outputSource" value="'.
    			($options['includeRawData'] ? "raw" :
    					($options['includeSummaryData'] ? "summary" : "estimates")).'"/>';
    	if(isset($options['simultaneousOutput']) && $options['simultaneousOutput']) {
    		// for combined format its fairly obvious what it is, so no need to add text.
    		$r .= '<input type="hidden" id="outputFormat" name="outputFormat" value="both"/>';
    	} else { // for single format its fairly obvious what it is, so no need to add text.
    		foreach($format as $type => $details){
    			$r .= '<input type="hidden" id="outputFormat" name="outputFormat" value="'.$type.'"/>';
    		}
    	}
    	// don't need to set URI as only 1 option.
    } else {
    	$r .= lang::get('View ');
    	if($userPicksSource) {
    		$r .= '<select id="outputSource" name="outputSource">'.
    				($options['includeRawData'] ? '<option id="viewRawData" value="raw"/>'.lang::get('raw data').'</option>' : '').
    				($options['includeSummaryData'] ? '<option id="viewSummaryData" value="summary"/>'.lang::get('summary data').'</option>' : '').
    				($options['includeEstimatesData'] ? '<option id="viewDataEstimates" value="estimates"/>'.lang::get('summary data with estimates').'</option>' : '').
    				'</select>';
    		self::$javascript .= "jQuery('#outputSource').on('change', function(){
  pageURI = rebuild_page_url(pageURI, \"outputSource\", jQuery(this).val());
  update_controls();
  switch(jQuery(this).val()){
    case 'raw':
        jQuery('#".$options['tableID']."-raw,#".$options['chartID']."-raw').show();
        jQuery('#".$options['tableID'].",#".$options['chartID']."-summary,#".$options['chartID']."-estimates').hide();
        break;
    case 'summary':
        jQuery('#".$options['tableID'].",.summary,#".$options['chartID']."-summary').show();
        jQuery('#".$options['tableID']."-raw,#".$options['chartID']."-raw,.estimates,#".$options['chartID']."-estimates').hide();
        break;
    case 'estimates':
        jQuery('#".$options['tableID'].",.estimates,#".$options['chartID']."-estimates').show();
        jQuery('#".$options['tableID']."-raw,#".$options['chartID']."-raw,.summary,#".$options['chartID']."-summary').hide();
        break;
   }
   if(jQuery('#outputFormat').val() != 'table')
     replot();
});\n";
    	} else $r .= '<input type="hidden" id="outputSource" name="outputSource" value="'.
           ($options['includeRawData'] ? "raw" :
               ($options['includeSummaryData'] ? "summary" : "estimates")).'"/>';
        if($userPicksFormat) {
            $defaultTable = !isset($options['outputFormat']) || $options['outputFormat']=='' || $options['outputFormat']=='table';
            $r .= lang::get(' as a ').'<select id="outputFormat" name="outputFormat">'.
                  '<option '.($defaultTable?'selected="selected"':'').' value="table"/>'.lang::get('table').'</option>'.
                  '<option '.(!$defaultTable?'selected="selected"':'').' value="chart"/>'.lang::get('chart').'</option>'.
                  '</select>'; // not providing option for both at moment
            self::$javascript .= "jQuery('[name=outputFormat]').on('change', function(){
  pageURI = rebuild_page_url(pageURI, \"outputFormat\", jQuery(this).val());
  update_controls();
  switch($(this).val()) {
    case 'table' :
        jQuery('#".$options['tableContainerID']."').show();
        jQuery('#".$options['chartContainerID']."').hide();
        break;
    default : // chart
        jQuery('#".$options['tableContainerID']."').hide();
        jQuery('#".$options['chartContainerID']."').show();
        replot();
        break;
  }
});\n";
    	} else if(isset($options['simultaneousOutput']) && $options['simultaneousOutput']) {
    		// for combined format its fairly obvious what it is, so no need to add text.
            $r .= '<input type="hidden" id="outputFormat" name="outputFormat" value="both"/>';
    	} else { // for single format its fairly obvious what it is, so no need to add text.
    		foreach($format as $type => $details){
    			$r .= '<input type="hidden" id="outputFormat" name="outputFormat" value="'.$type.'"/>';
    		}
    	}
    }
    $r .= "</div>\n";
    $warnings .= '<span style="display:none;">Controls complete : '.date(DATE_ATOM).'</span>'."\n";
    ksort($rawArray);
    $warnings .= '<span style="display:none;">Raw data sort : '.date(DATE_ATOM).'</span>'."\n";
    if(isset($format['chart'])){
      $seriesToDisplay=(isset($options['outputSeries']) ? explode(',', $options['outputSeries']) : 'all');
      $seriesIDs=[];
      $rawSeriesData=[];
      $rawTicks= [];
      $summarySeriesData=[];
      $estimatesSeriesData=[];
      $seriesOptions=[];
      // Series options are not configurable as we need to setup for ourselves...
      // we need show, label and show label filled in. rest are left to defaults
      $rawTotalRow = [];
      $summaryTotalRow = [];
      $estimatesTotalRow = [];
      for($i= $minWeekNo; $i <= $maxWeekNo; $i++) {
      	$summaryTotalRow[$i] = 0;
      	$estimatesTotalRow[$i] = 0;
      }
      foreach($rawArray as $dateIndex => $rawData) {
      	$rawTotalRow[] = 0;
      	$rawTicks[] = "\"".$rawData['date']."\"";
      }
      foreach($summaryArray as $seriesID => $summaryRow){
        if (empty($seriesLabels[$seriesID])) continue;
        $rawValues=[];
        $summaryValues=[];
        $estimatesValues=[];
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++){
          if(isset($summaryRow[$i])){
            $estimatesValues[]=$summaryRow[$i]['estimates'];
            $estimatesTotalRow[$i] += $summaryRow[$i]['estimates'];
            if($summaryRow[$i]['summary']!==false){
              $summaryValues[]=$summaryRow[$i]['summary'];
              $summaryTotalRow[$i] += $summaryRow[$i]['summary'];
            } else {
              $summaryValues[]=0;
              $summaryTotalRow[$i]+=0;
            }
          } else {
            $summaryValues[]=0;
            $estimatesValues[]=0;
          }
        }
        // we want to ensure that series match between summary and raw data. raw data is indexed by date.
        foreach($rawArray as $dateIndex => $rawData) {
          $rawValues[] = (isset($rawData['counts'][$seriesID]) ? $rawData['counts'][$seriesID] : 0);
        }
        foreach($rawValues as $idx => $rawValue) {
          $rawTotalRow[$idx] += $rawValue;
        }
        // each series will occupy an entry in $...SeriesData
        if ($options['includeChartItemSeries']) {
          $seriesIDs[] = $seriesID;
          $rawSeriesData[] = '['.implode(',', $rawValues).']';
          $summarySeriesData[] = '['.implode(',', $summaryValues).']';
          $estimatesSeriesData[] = '['.implode(',', $estimatesValues).']';
          $seriesOptions[] = '{"show":'.($seriesToDisplay == 'all' || in_array($seriesID, $seriesToDisplay) ? 'true' : 'false').',"label":"'.$seriesLabels[$seriesID].'","showlabel":true}';
        }
      }
      if(isset($options['includeChartTotalSeries']) && $options['includeChartTotalSeries']){ // totals are put at the start
        array_unshift($seriesIDs,0); // Total has ID 0
      	array_unshift($rawSeriesData, '['.implode(',', $rawTotalRow).']');
      	array_unshift($summarySeriesData, '['.implode(',', $summaryTotalRow).']');
        array_unshift($estimatesSeriesData, '['.implode(',', $estimatesTotalRow).']');
        array_unshift($seriesOptions, '{"show":'.($seriesToDisplay == 'all' || in_array(0, $seriesToDisplay) ? 'true' : 'false').',"label":"'.lang::get('Total').'","showlabel":true}');
      }
      $opts[] = 'series:['.implode(',', $seriesOptions).']';
      $options['axesOptions']['xaxis']['renderer'] = '$.jqplot.CategoryAxisRenderer';
      if(isset($options['chartLabels']) && $options['chartLabels'] == 'number')
        $options['axesOptions']['xaxis']['ticks'] = $chartNumberLabels;
      else
        $options['axesOptions']['xaxis']['ticks'] = $chartDateLabels;
      // We need to fudge the json so the renderer class is not a string
      $axesOpts = str_replace('"$.jqplot.CategoryAxisRenderer"', '$.jqplot.CategoryAxisRenderer',
        'axes:'.json_encode($options['axesOptions']));
      $opts[] = $axesOpts;
      self::$javascript .= "var seriesData = {ids: [".implode(',', $seriesIDs)."], raw: [".implode(',', $rawSeriesData)."], summary: [".implode(',', $summarySeriesData)."], estimates: [".implode(',', $estimatesSeriesData)."]};\n";
      // Finally, dump out the Javascript with our constructed parameters.
      // width stuff is a bit weird, but jqplot requires a fixed width, so this just stretches it to fill the space.
      self::$javascript .= "\nvar plots = [];
var replotActive = true;
function replot(){
  if(!replotActive) return;
  // there are problems with the coloring of series when added to a plot: easiest just to completely redraw.
  var max=0;
  var type = jQuery('#outputSource').val();
  jQuery('#".$options['chartID']."-'+type).empty();";
      if(!isset($options['width']) || $options['width'] == '')
        self::$javascript .= "\n  jQuery('#".$options['chartID']."-'+type).width(jQuery('#".$options['chartID']."-'+type).width());";
      self::$javascript .= "
  var opts = {".implode(",\n", $opts)."};
  if(type == 'raw') opts.axes.xaxis.ticks = [".implode(',',$rawTicks)."];
  // copy series from checkboxes.
  jQuery('[name=".$options['chartID']."-series]').each(function(idx, elem){
      opts.series[idx].show = (jQuery(elem).filter(':checked').length > 0);
  });
  for(var i=0; i<seriesData[type].length; i++)
    if(opts.series[i].show)
      for(var j=0; j<seriesData[type][i].length; j++)
          max=(max>seriesData[type][i][j]?max:seriesData[type][i][j]);
  opts.axes.yaxis.max=max+1;
  opts.axes.yaxis.tickInterval = Math.floor(max/15); // number of ticks - too many takes too long to display
  if(!opts.axes.yaxis.tickInterval) opts.axes.yaxis.tickInterval=1;
  plots[type] = $.jqplot('".$options['chartID']."-'+type,  seriesData[type], opts);
};\n";
      // div are full width.
      $r .= '<div id="'.$options['chartContainerID'].'" class="'.$options['chartClass'].'" style="'.(isset($options['width']) && $options['width'] != '' ? 'width:'.$options['width'].'px;':'').($format['chart']['display']?'':'display:none;').'">';
      if (isset($options['title']))
        $r .= '<div class="'.$options['headerClass'].'">'.$options['title'].'</div>';
      if($options['includeRawData'])
        $r .= '<div id="'.$options['chartID'].'-raw" style="height:'.$options['height'].'px;'.(isset($options['width']) && $options['width'] != '' ? 'width:'.$options['width'].'px;':'').(($options['includeSummaryData']) || ($options['includeEstimatesData']) ? ' display:none;':'').'"></div>'."\n";
      if($options['includeSummaryData'])
        $r .= '<div id="'.$options['chartID'].'-summary" style="height:'.$options['height'].'px;'.(isset($options['width']) && $options['width'] != '' ? 'width:'.$options['width'].'px;':'').($options['includeEstimatesData'] ? ' display:none;':'').'"></div>'."\n";
      if($options['includeEstimatesData'])
        $r .= '<div id="'.$options['chartID'].'-estimates" style="height:'.$options['height'].'px;'.(isset($options['width']) && $options['width'] != '' ? 'width:'.$options['width'].'px;':'').'"></div>'."\n";
      if(isset($options['disableableSeries']) && $options['disableableSeries'] &&
           (count($summaryArray)>(isset($options['includeChartTotalSeries']) && $options['includeChartTotalSeries'] ? 0 : 1)) &&
           isset($options['includeChartItemSeries']) && $options['includeChartItemSeries']) {
        $class='series-fieldset';
        if (function_exists('hostsite_add_library') && (!defined('DRUPAL_CORE_COMPATIBILITY') || DRUPAL_CORE_COMPATIBILITY!=='7.x')) {
          hostsite_add_library('collapse');
          $class.=' collapsible collapsed';
        }
        $r .= '<fieldset id="'.$options['chartID'].'-series" class="'.$class.'"><legend>'.lang::get('Display Series')."</legend><span>\n";
        $idx=0;
        if(isset($options['includeChartTotalSeries']) && $options['includeChartTotalSeries']){
          // use value = 0 for Total
          $r .= '<span class="chart-series-span"><input type="checkbox" checked="checked" id="'.$options['chartID'].'-series-'.$idx.'" name="'.$options['chartID'].'-series" value="'.$idx.'"/><label for="'.$options['chartID'].'-series-'.$idx.'">'.lang::get('Total')."</label></span>\n";
          $idx++;
          self::$javascript .= "\njQuery('[name=".$options['chartID']."-series]').filter('[value=0]').".($seriesToDisplay == 'all' || in_array(0, $seriesToDisplay) ? 'attr("checked","checked");' : 'removeAttr("checked");');
        }
        $r .= '<input type="button" class="disable-button" id="'.$options['chartID'].'-series-disable" value="'.lang::get('Hide all ').$options['rowGroupColumn']."\"/>\n";
        foreach($summaryArray as $seriesID => $summaryRow){
          if (empty($seriesLabels[$seriesID])) continue;
          $r .= '<span class="chart-series-span"><input type="checkbox" checked="checked" id="'.$options['chartID'].'-series-'.$idx.'" name="'.$options['chartID'].'-series" value="'.$seriesID.'"/><label for="'.$options['chartID'].'-series-'.$idx.'">'.$seriesLabels[$seriesID]."</label></span>\n";
          $idx++;
          self::$javascript .= "\njQuery('[name=".$options['chartID']."-series]').filter('[value=".$seriesID."]').".($seriesToDisplay == 'all' || in_array($seriesID, $seriesToDisplay) ? 'attr("checked","checked");' : 'removeAttr("checked");');
        }
        $r .= "</span></fieldset>\n";
        // Known issue: jqplot considers the min and max of all series when drawing on the screen, even those which are not displayed
        // so replotting doesn't scale to the displayed series!
        self::$javascript .= "
// above done due to need to ensure get around field caching on browser refresh.
setSeriesURLParam = function(){
  var activeSeries = [],
    active = jQuery('[name=".$options['chartID']."-series]').filter(':checked'),
    total = jQuery('[name=".$options['chartID']."-series]');
  if(active.length == total.length) {
    pageURI = rebuild_page_url(pageURI, 'outputSeries', '');
  } else {
    active.each(function(idx,elem){ activeSeries.push($(elem).val()); });
    pageURI = rebuild_page_url(pageURI, 'outputSeries', activeSeries.join(','));
  }
  update_controls();
}
jQuery('[name=".$options['chartID']."-series]').on('change', function(){
  var seriesID = jQuery(this).val(), index;
  $.each(seriesData.ids, function(idx, elem){
    if(seriesID == elem) index = idx;
  });
  if(jQuery(this).filter(':checked').length){
    if(typeof plots['raw'] != 'undefined') plots['raw'].series[index].show = true;
    if(typeof plots['summary'] != 'undefined') plots['summary'].series[index].show = true;
    if(typeof plots['estimates'] != 'undefined') plots['estimates'].series[index].show = true;
  } else {
    if(typeof plots['raw'] != 'undefined') plots['raw'].series[index].show = false;
    if(typeof plots['summary'] != 'undefined') plots['summary'].series[index].show = false;
    if(typeof plots['estimates'] != 'undefined') plots['estimates'].series[index].show = false;
  }
  setSeriesURLParam();
  replot();
});
jQuery('#".$options['chartID']."-series-disable').on('click', function(){
  if(jQuery(this).is('.cleared')){ // button is to show all
    jQuery('[name=".$options['chartID']."-series]').not('[value=0]').attr('checked','checked');
    $.each(seriesData.ids, function(idx, elem){
      if(elem == 0) return; // ignore total series
      if(typeof plots['raw'] != 'undefined') plots['raw'].series[idx].show = true;
      if(typeof plots['summary'] != 'undefined') plots['summary'].series[idx].show = true;
      if(typeof plots['estimates'] != 'undefined') plots['estimates'].series[idx].show = true;
    });
    jQuery(this).removeClass('cleared').val(\"".lang::get('Hide all ').$options['rowGroupColumn']."\");
  } else {
    jQuery('[name=".$options['chartID']."-series]').not('[value=0]').removeAttr('checked');
    $.each(seriesData.ids, function(idx, elem){
      if(elem == 0) return; // ignore total series
      if(typeof plots['raw'] != 'undefined') plots['raw'].series[idx].show = false;
      if(typeof plots['summary'] != 'undefined') plots['summary'].series[idx].show = false;
      if(typeof plots['estimates'] != 'undefined') plots['estimates'].series[idx].show = false;
    });
    jQuery(this).addClass('cleared').val(\"".lang::get('Show all ').$options['rowGroupColumn']."\");
  }
  setSeriesURLParam();
  replot();
});
";
      }
      $r .= "</div>\n";
      $warnings .= '<span style="display:none;">Output chart complete : '.date(DATE_ATOM).'</span>'."\n";
    }
    if(isset($format['table'])){
      $thClass = $options['thClass'];
      $r .= '<div id="'.$options['tableContainerID'].'">';
      if($options['includeRawData']){
        $rawDataDownloadGrid="";
        $rawDataDownloadList='Location,'.(($options['tableHeaders'] == 'both' || $options['tableHeaders'] == 'number') ? 'Week Number,' : '')."Date,Species,Count\n";
        $r .= "\n<table id=\"".$options['tableID']."-raw\" class=\"".$options['tableClass']."\" style=\"".($format['table']['display']?'':'display:none;')."\">";
        $r .= "\n<thead class=\"$thClass\">";
        // raw data headers: %Sun, mean temp, Date, Week Number, Location?
        // the Total column is driven as per summary
        // no total row
        if($options['tableHeaders'] == 'both' || $options['tableHeaders'] == 'number'){
          $r .= '<tr><td>Week</td>';
          $rawDataDownloadGrid .= "Week";
          foreach($rawArray as $idx => $rawColumn){
            $r .= '<td class="week">'.$rawColumn['weekno'].'</td>';
            $rawDataDownloadGrid .= ','.$rawColumn['weekno'];
          }
          if($options['includeTableTotalColumn']){
            $r.= '<td class="total-column"></td>';
            $rawDataDownloadGrid .= ',';
          }
        }
        $r .= '</tr><tr><td>Date</td>';
        $rawDataDownloadGrid .= "\nDate";
        $rawTotalRow = "";
        $rawDataDownloadGridTotalRow = "";
        $rawGrandTotal = 0;
        foreach($rawArray as $idx => $rawColumn){
          $this_date = date_create(str_replace('/','-',$rawColumn['date'])); // prevents day/month ordering issues
          $r .= '<td class="week">'.$this_date->format('M').'<br/>'.$this_date->format('d').'</td>';
          $rawDataDownloadGrid .= ','.$this_date->format('d/m/Y');
          $rawTotalRow .= '<td>'.$rawColumn['total'].'</td>';
          $rawDataDownloadGridTotalRow .= ','.$rawColumn['total'];
          $rawGrandTotal += $rawColumn['total'];
        }
        if($options['includeTableTotalColumn']){
          $r.= '<td class="total-column">Total</td>';
          $rawDataDownloadGrid .= ',Total';
        }
        $r .= "</tr>";
        $rawDataDownloadGrid .= "\n";
        // don't include links in download
        if(isset($options['linkURL']) && $options['linkURL']!= ''){
          $r .= '<tr><td>Sample Links</td>';
          foreach($rawArray as $idx => $rawColumn){
            $links = [];
            if(count($rawColumn['samples'])>0)
              foreach($rawColumn['samples'] as $sample)
            	$links[] = '<a href="'.$options['linkURL'].$sample['id'].'" target="_blank" title="'.$sample['location_name'].'">('.$sample['id'].')</a>';
            $r .= '<td class="links">'.implode('<br/>',$links).'</td>';
          }
          $r.= ($options['includeTableTotalColumn'] ? '<td class="total-column"></td>' : '')."</tr>";
        }
        $r.= "</thead>\n<tbody>\n";
        $altRow=false;
        if($avgFields) {
          foreach($avgFieldList as $i => $field){
            $r .= "<tr class=\"sample-datarow ".($altRow?$options['altRowClass']:'')." ".($i==(count($avgFields)-1)?'last-sample-datarow':'')."\">";
            $caption = t('Mean '.ucwords($avgFields[$field]['caption']));
            $r .= '<td>'.$caption.'</td>';
            $rawDataDownloadGrid .= '"'.$caption.'"';
            foreach($rawArray as $dateIndex => $rawData) {
              $r.= '<td>'.$rawData['avgFields'][$field].'</td>';
              $rawDataDownloadGrid .= ','.$rawData['avgFields'][$field];
            }
            if($options['includeTableTotalColumn']){
              $r.= '<td class="total-column"></td>';
              $rawDataDownloadGrid .= ',';
            }
            $r .= "</tr>";
            $rawDataDownloadGrid .= "\n";
            $altRow=!$altRow;
          }
        }
        foreach($summaryArray as $seriesID => $summaryRow){ // use the same row headings as the summary table.
          if (!empty($seriesLabels[$seriesID])) {
            $total=0;  // row total
            $r .= "<tr class=\"datarow ".($altRow?$options['altRowClass']:'')."\">";
            $r.= '<td>'.$seriesLabels[$seriesID].'</td>';
            $rawDataDownloadGrid .= '"'.$seriesLabels[$seriesID].'"';
            foreach($rawArray as $date => $rawColumn){
              if(isset($rawColumn['counts'][$seriesID])) {
                $r.= '<td>'.$rawColumn['counts'][$seriesID].'</td>';
                $total += $rawColumn['counts'][$seriesID];
                $rawDataDownloadGrid .= ','.$rawColumn['counts'][$seriesID];
                $locations = [];
                if(count($rawColumn['samples'])>0)
                  foreach($rawColumn['samples'] as $sample)
                    $locations[$sample['location_name']]=true;
                $this_date = date_create(str_replace('/','-',$rawColumn['date'])); // prevents day/month ordering issues
                $rawDataDownloadList .= '"'.implode(': ',array_keys($locations)).'"'.
                     ($options['tableHeaders'] == 'both' || $options['tableHeaders'] == 'number' ? ','.$rawColumn['weekno'] : '').
                     ','.$this_date->format('d/m/Y').',"'.$seriesLabels[$seriesID].'",'.$rawColumn['counts'][$seriesID]."\n";
              } else {
                $r.= '<td></td>';
                $rawDataDownloadGrid .= ',';
              }
            }
            if($options['includeTableTotalColumn']){
              $r.= '<td class="total-column">'.$total.'</td>';
              $rawDataDownloadGrid .= ','.$total;
            }
            $r .= "</tr>";
            $rawDataDownloadGrid .= "\n";
            $altRow=!$altRow;
          }
        }
        if($options['includeTableTotalRow']){
          $r.= '<tr class="totalrow"><td>Total</td>'.$rawTotalRow.
            ($options['includeTableTotalColumn'] ? '<td>'.$rawGrandTotal.'</td>' : '').'</tr>';
          $rawDataDownloadGrid .= 'Total'.$rawDataDownloadGridTotalRow.
            ($options['includeTableTotalColumn'] ? ','.$rawGrandTotal : '')."\n";
        }
        $r .= "</tbody></table>\n";
      }
      $summaryDataDownloadGrid = "";
      $r .= "\n<table id=\"".$options['tableID']."\" class=\"".$options['tableClass']."\" style=\"".($format['table']['display']?'':'display:none;')."\">";
      $r .= "\n<thead class=\"$thClass\">";
      if($options['tableHeaders'] == 'both' || $options['tableHeaders'] == 'number'){
        $r .= '<tr><td>Week</td>'.$tableNumberHeaderRow.($options['includeTableTotalColumn']
                                                         ? ($options['includeSummaryData'] ? '<td>Total</td>' : '') .
                                                           ($options['includeEstimatesData'] ? '<td class="estimates">Total with<br />estimates</td>' : '')
                                                         : '') . '</tr>';
        $summaryDataDownloadGrid .= 'Week'.$downloadNumberHeaderRow.($options['includeTableTotalColumn']
        		                                         ?($options['includeSummaryData'] ? ',Total' : '')
        		                                         :'')."\n";
      }
      if($options['tableHeaders'] != 'number'){
        $r .= '<tr><td>'.lang::get('Date').'</td>'.$tableDateHeaderRow.($options['includeTableTotalColumn']
        		                                         ?($options['tableHeaders'] == 'both' || $options['tableHeaders'] == 'number' ?
        		                                              ($options['includeSummaryData'] && $options['includeEstimatesData']
        		                                              		?'<td></td><td class="estimates"></td>':'<td '.($options['includeEstimatesData'] ? 'class="estimates"' : '').'></td>') :
        		                                              ($options['includeSummaryData'] ? '<td>Total</td>' : '').
        		                                          ($options['includeEstimatesData'] ? '<td>Total with<br />estimates</td>' : ''))
        		                                         :'').'</tr>';
        $summaryDataDownloadGrid .= lang::get('Date').$downloadDateHeaderRow.($options['includeTableTotalColumn']
        		                                         ? ($options['tableHeaders'] == 'both' || $options['tableHeaders'] == 'number' ? ',' : ',Total')
        		                                         :'')."\n";
      }
      $estimateDataDownloadGrid = $summaryDataDownloadGrid;
      $r.= "</thead>\n";
      $r .= "<tbody>\n";
      $altRow=false;
      $grandTotal=0;
      $totalRow = [];
      $estimatesGrandTotal=0;
      $totalEstimatesRow = [];
      for($i= $minWeekNo; $i <= $maxWeekNo; $i++) {
        $totalRow[$i] = 0;
        $totalEstimatesRow[$i] = 0;
      }

      foreach($summaryArray as $seriesID => $summaryRow){
        // skip rows with no labels, caused by report left joins to fill in all date columns even if no records
        if (!empty($seriesLabels[$seriesID])) {
          $total=0;  // row total
          $estimatesTotal=0;  // row total
          $r .= "<tr class=\"datarow ".($altRow?$options['altRowClass']:'')."\">";
          $r.= '<td>'.$seriesLabels[$seriesID].'</td>';
          $summaryDataDownloadGrid .= '"'.$seriesLabels[$seriesID].'"';
          $estimateDataDownloadGrid .= '"'.$seriesLabels[$seriesID].'"';
          for($i= $minWeekNo; $i <= $maxWeekNo; $i++){
            $r.= '<td>';
            $summaryDataDownloadGrid .= ',';
            $estimateDataDownloadGrid .= ',';
            if(isset($summaryRow[$i])){
              $summaryValue = $summaryRow[$i]['forcedZero'] ? 0 : ($summaryRow[$i]['hasData'] ? $summaryRow[$i]['summary'] : '');
              $class = '';
              $estimatesClass = '';
              if($summaryValue!=='' && $options['includeSummaryData'])
              	$class = ($options['includeEstimatesData'] && $summaryRow[$i]['hasEstimates'] && $summaryRow[$i]['estimates']!==$summaryValue ? 'summary' : '').($summaryRow[$i]['forcedZero'] && $options['highlightEstimates'] ? ' forcedZero' : '');
              if($options['includeEstimatesData'])
                $estimatesClass = ($options['includeSummaryData'] ? 'estimates' : '').($options['highlightEstimates'] ? ' highlight-estimates' : '');
              $summaryDataDownloadGrid .= $summaryValue;
              if($summaryRow[$i]['hasEstimates'] || $summaryRow[$i]['forcedZero']) $estimateDataDownloadGrid .= $summaryRow[$i]['estimates'];
              if($options['includeSummaryData'] && $summaryValue !== '') {
                if($class == '') $r .= $summaryValue;
                else $r.= '<span class="'.$class.'">'.$summaryValue.'</span>';
              }
              if(!$options['includeSummaryData'] || ($options['includeEstimatesData'] && $summaryRow[$i]['hasEstimates'] && $summaryRow[$i]['estimates']!==$summaryValue))
                $r.= '<span class="'.$estimatesClass.'">'.$summaryRow[$i]['estimates'].'</span>';
              if($summaryValue !== '' && $summaryValue !== 0){
                $total += $summaryValue;
                $totalRow[$i] += $summaryValue;
                $grandTotal += $summaryValue;
              }
              $estimatesTotal += $summaryRow[$i]['estimates'];
              $totalEstimatesRow[$i] += $summaryRow[$i]['estimates'];
              $estimatesGrandTotal += $summaryRow[$i]['estimates'];
            } // else absolutely nothing - so leave blank.
            $r .= '</td>';
          }
          if($options['includeTableTotalColumn']){
            if($options['includeSummaryData']) {
              $r.= '<td class="total-column">'.$total.'</td>';
              $summaryDataDownloadGrid .= ','.$total;
            }
            if($options['includeEstimatesData']) {
              $r.= '<td class="total-column estimates">'.$estimatesTotal.'</td>';
              $estimateDataDownloadGrid .= ','.$estimatesTotal;
            }
          }
          $r .= "</tr>";
          $summaryDataDownloadGrid .= "\n";
          $estimateDataDownloadGrid .= "\n";
          $altRow=!$altRow;
        }
      }

      if($options['includeTableTotalRow']){
        if($options['includeSummaryData']){
          $r .= "<tr class=\"totalrow\"><td>".lang::get('Total (Summary)').'</td>';
          $summaryDataDownloadGrid .= '"'.lang::get('Total (Summary)').'"';
          for($i= $minWeekNo; $i <= $maxWeekNo; $i++) {
            $r .= '<td>'.$totalRow[$i].'</td>';
            $summaryDataDownloadGrid .= ','.$totalRow[$i];
          }
          if($options['includeTableTotalColumn']) {
            $r .= '<td class="total-column grand-total">'.$grandTotal.'</td>'.($options['includeEstimatesData'] ? '<td class="estimates"></td>' : '');
            $summaryDataDownloadGrid .= ','.$grandTotal;
          }
          $r .= "</tr>";
          $summaryDataDownloadGrid .= "\n";
        }
        if($options['includeEstimatesData']){
          $r .= "<tr class=\"totalrow estimates\"><td>".lang::get('Total inc Estimates').'</td>';
          $estimateDataDownloadGrid .= '"'.lang::get('Total').'"';
          for($i= $minWeekNo; $i <= $maxWeekNo; $i++) {
            $r.= '<td>'.$totalEstimatesRow[$i].'</td>';
            $estimateDataDownloadGrid .= ','.$totalEstimatesRow[$i];
          }
          if($options['includeTableTotalColumn']) {
            $r .= ($options['includeSummaryData'] ? '<td></td>' : '').'<td class="total-column grand-total estimates">'.$estimatesGrandTotal.'</td>';
            $estimateDataDownloadGrid .= ','.$estimatesGrandTotal;
          }
          $r .= "</tr>";
          $estimateDataDownloadGrid .= "\n";
        }
      }
      $r .= "</tbody></table>\n";
      $r .= "</div>";
      $downloads="";

      $timestamp = (isset($options['includeReportTimeStamp']) && $options['includeReportTimeStamp'] ? '_'.date('YmdHis') : '');
      // No need for saved reports to be atomic events. Will be purged automatically.
      global $base_url;
      $cacheFolder = self::$cache_folder ? self::$cache_folder : self::relative_client_helper_path() . 'cache/';
      if($options['includeRawData']){
        if($options['includeRawGridDownload']) {
          $cacheFile = $options['downloadFilePrefix'].'rawDataGrid'.$timestamp.'.csv';
          $handle = fopen($cacheFolder.$cacheFile, 'wb');
          fwrite($handle, $rawDataDownloadGrid);
          fclose($handle);
          $downloads .= '<th><a target="_blank" href="'.$base_url.'/'.$cacheFolder.$cacheFile.'" download type="text/csv"><button type="button">Raw Grid Data</button></a></th>'."\n";
        }
        if($options['includeRawListDownload']) {
          $cacheFile = $options['downloadFilePrefix'].'rawDataList'.$timestamp.'.csv';
          $handle = fopen($cacheFolder.$cacheFile, 'wb');
          fwrite($handle, $rawDataDownloadList);
          fclose($handle);
          $downloads .= '<th><a target="_blank" href="'.$base_url.'/'.$cacheFolder.$cacheFile.'" download type="text/csv"><button type="button">Raw List Data</button></a></th>'."\n";
        }
      }
      if($options['includeSummaryData'] && $options['includeSummaryGridDownload']) {
        $cacheFile = $options['downloadFilePrefix'].'summaryDataGrid'.$timestamp.'.csv';
        $handle = fopen($cacheFolder.$cacheFile, 'wb');
        fwrite($handle, $summaryDataDownloadGrid);
        fclose($handle);
        $downloads .= '<th><a target="_blank" href="'.$base_url.'/'.$cacheFolder.$cacheFile.'" download type="text/csv"><button type="button">Summary Grid Data</button></a></th>'."\n";
      }
      if($options['includeEstimatesData'] && $options['includeEstimatesGridDownload']) {
        $cacheFile = $options['downloadFilePrefix'].'estimateDataGrid'.$timestamp.'.csv';
        $handle = fopen($cacheFolder.$cacheFile, 'wb');
        fwrite($handle, $estimateDataDownloadGrid);
        fclose($handle);
        $downloads .= '<th><a target="_blank" href="'.$base_url.'/'.$cacheFolder.$cacheFile.'" download type="text/csv"><button type="button">Estimate Grid Data</button></a></th>'."\n";
      }
      if(($options['includeSummaryData'] || $options['includeEstimatesData']) && $options['includeListDownload']) {
        $cacheFile = $options['downloadFilePrefix'].'dataList'.$timestamp.'.csv';
        $handle = fopen($cacheFolder.$cacheFile, 'wb');
        fwrite($handle, $downloadList);
        fclose($handle);
        $downloads .= '<th><a target="_blank" href="'.$base_url.'/'.$cacheFolder.$cacheFile.'" download type="text/csv"><button type="button">List Data</button></a></th>'."\n";
      }
      $r .= '<br/><table id="downloads-table" class="ui-widget ui-widget-content ui-corner-all downloads-table" ><thead class="ui-widget-header"><tr>'.
            ($downloads == '' ? '' : '<th class="downloads-table-label">Downloads</th>'.$downloads).
            "</tr></thead></table>\n";
      $warnings .= '<span style="display:none;">Output table complete : '.date(DATE_ATOM).'</span>'."\n";
    }
    // Set up initial view: only want to replot once as that can be very intensive.
    self::$javascript .= "replotActive = false;\n";
    if($userPicksSource) {
      self::$javascript .= (isset($options['outputSource']) ?
          "$('#outputSource').val('".$options['outputSource']."').trigger('change');\n" :
          "if($('#viewDataEstimates').length > 0){\n  $('#outputSource').val('estimates').trigger('change');\n".
          "} else if($('#viewSummaryData').length > 0){\n  $('#outputSource').val('summary').trigger('change');\n".
          "} else {\n  $('#outputSource').val('raw').trigger('change');\n}\n");
    }
    if($userPicksFormat) {
      self::$javascript .= "jQuery('[name=outputFormat]').trigger('change');\n";
    }
    self::$javascript .= "replotActive = true;\n";
    if($format['chart']['display']){
    	self::$javascript .= "replot();\n";
    }

    if(count($summaryArray)==0)
      $r .= '<p>'.lang::get('No data returned for this period.').'</p>';
    $warnings .= '<span style="display:none;">Finish report_calendar_summary : '.date(DATE_ATOM).'</span>'."\n";
    return $warnings.$r;
  }

  /**
   * Creates a default array of entries for any location.
   * @param integer $minWeekNo start week number : index in array.
   * @param integer $maxWeekNo end week number : index in array
   * @param array $weekList list of samples in a particular week.
   */
  private static function report_calendar_summary_initLoc1($minWeekNo, $maxWeekNo, $weekList){
  	$locationArray= [];
  	for($weekno = $minWeekNo; $weekno <= $maxWeekNo; $weekno++)
  		$locationArray[$weekno] = array('this_sample'=>-1,
  				'total'=>0,
  				'sampleTotal'=>0,
  				'max'=>0,
  				'numSamples'=>0,
  				'estimates'=>0,
  				'summary'=>false,
  				'hasData'=>false,
  				'hasEstimates'=>false,
  				'forcedZero'=>isset($weekList[$weekno]),
  				'location' => '');
  	self::$initLoc = $locationArray;
  }

  /*
   * store the initial default location array, so doesn't have to be rebuilt each time.
   */
  private static $initLoc;

  /**
   * Creates an array of entries for a specific location.
   * @param integer $minWeekNo start week number : index in array.
   * @param integer $maxWeekNo end week number : index in array
   * @param array $weekList list of samples in a particular week for the location.
   */
  private static function report_calendar_summary_initLoc2($minWeekNo, $maxWeekNo, $inWeeks){
  	$locationArray= self::$initLoc;
  	for($weekno = $minWeekNo; $weekno <= $maxWeekNo; $weekno++) {
  		$locationArray[$weekno]['hasData']=isset($inWeeks[$weekno]);
    }
  	return $locationArray;
  }

  /**
   * @todo: document this method
   * @param array $summaryArray
   * @param array $locationArray
   * @param integer $numSamples
   * @param integer $minWeekNo
   * @param integer $maxWeekNo
   * @param string $taxon
    *@param array $options
   */
  private static function report_calendar_summary_processEstimates(&$summaryArray, $locationArray, $numSamples, $minWeekNo, $maxWeekNo, $weekList, $taxonID, $taxon, $options, &$download) {
  	switch($options['summaryDataCombining']){
      case 'max':
        for($i = $minWeekNo; $i <= $maxWeekNo; $i++)
            $locationArray[$i]['summary'] = max($locationArray[$i]['max'], $locationArray[$i]['sampleTotal']);
        break;
      case 'sample':
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++) {
          if($locationArray[$i]['numSamples'])
            $locationArray[$i]['summary'] = ($locationArray[$i]['total'].'.0')/$locationArray[$i]['numSamples'];
          else $locationArray[$i]['summary'] = 0;
          if($locationArray[$i]['summary']>0 && $locationArray[$i]['summary']<1) $locationArray[$i]['summary']=1;
        }
        break;
      case 'location':
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++) {
      	  $count=isset($numSamples[$i]) ? count($numSamples[$i]) : 0;
          if($count) $locationArray[$i]['summary'] = ($locationArray[$i]['total'].'.0')/$count;
          else $locationArray[$i]['summary'] = 0;
          if($locationArray[$i]['summary']>0 && $locationArray[$i]['summary']<1) $locationArray[$i]['summary']=1;
        }
        break;
      default :
      case 'add':
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++)
          $locationArray[$i]['summary'] = $locationArray[$i]['total'];
        break;
    }
    if($options['summaryDataCombining'] == 'sample' || $options['summaryDataCombining'] == 'location') // other 2 are interger anyway : preformance
     switch($options['dataRound']){
      case 'nearest':
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++)
          if($locationArray[$i]['summary']) $locationArray[$i]['summary'] = (int)round($locationArray[$i]['summary']);
        break;
      case 'up':
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++)
          if($locationArray[$i]['summary']) $locationArray[$i]['summary'] = (int)ceil($locationArray[$i]['summary']);
        break;
      case 'down':
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++)
          if($locationArray[$i]['summary']) $locationArray[$i]['summary'] = (int)floor($locationArray[$i]['summary']);
        break;
      case 'none':
      default : break;
    }
    $anchors=explode(',',$options['zeroPointAnchor']);
    $firstAnchor = false;
    $lastAnchor = false;
    if(count($anchors)>0)
      $firstAnchor = $anchors[0]!='' ? $anchors[0] : false;
    if(count($anchors)>1)
      $lastAnchor = $anchors[1]!='' ? $anchors[1] : false;
    $thisLocation=false;
    for($i= $minWeekNo, $foundFirst=false; $i <= $maxWeekNo; $i++){
      if(!$foundFirst) {
        if(($locationArray[$i]['hasData'])){
          if(($firstAnchor===false || $i-1>$firstAnchor) && $options['firstValue']=='half') {
            $locationArray[$i-1]['estimates'] = $locationArray[$i]['summary']/2;
            $locationArray[$i-1]['hasEstimates'] = true;
          }
          $foundFirst=true;
        }
      }
      if(!$thisLocation && $locationArray[$i]['numSamples'] > 0)
        $thisLocation = $locationArray[$i]['location'];
      if($foundFirst){
       $locationArray[$i]['estimates'] = $locationArray[$i]['summary'];
       $locationArray[$i]['hasEstimates'] = true;
       if($i < $maxWeekNo && !$locationArray[$i+1]['hasData']) {
        for($j= $i+2; $j <= $maxWeekNo; $j++)
          if($locationArray[$j]['hasData']) break;
        if($j <= $maxWeekNo) { // have found another value later on, so interpolate between them
          for($m=1; $m<($j-$i); $m++) {
            $locationArray[$i+$m]['estimates']=$locationArray[$i]['summary']+$m*($locationArray[$j]['summary']-$locationArray[$i]['summary'])/($j-$i);
            $locationArray[$i+$m]['hasEstimates'] = true;
          }
          $i = $j-1;
        } else {
          if(($lastAnchor===false || $i+1<$lastAnchor) && ($i-1>$firstAnchor) && $options['lastValue']=='half'){
            $locationArray[$i+1]['estimates']= $locationArray[$i]['summary']/2;
            $locationArray[$i+1]['hasEstimates'] = true;
          }
          $i=$maxWeekNo+1;
        }
       }
      }
    }
    switch($options['dataRound']){
      case 'nearest':
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++)
          $locationArray[$i]['estimates'] = round($locationArray[$i]['estimates']);
        break;
      case 'up':
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++)
          $locationArray[$i]['estimates'] = ceil($locationArray[$i]['estimates']);
        break;
      case 'down':
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++)
          $locationArray[$i]['estimates'] = floor($locationArray[$i]['estimates']);
        break;
      case 'none':
      default : break;
    }
    // add the location array into the summary data.
    foreach($locationArray as $weekno => $data){
      if($taxonID !== null){ // don't include lines for the sample only entries
        if($data['hasData']) {
          $download .= '"'.$thisLocation.'",'.
            ($options['tableHeaders'] == 'both' || $options['tableHeaders'] == 'number' ? $weekno.',' : '').
            $weekList[$weekno].','.$taxon.','.lang::get('Actual').','.$data['summary']."\n";
        } else if($options['includeEstimatesData'] && $data['hasEstimates']){
          $download .= '"'.$thisLocation.'",'.
            ($options['tableHeaders'] == 'both' || $options['tableHeaders'] == 'number' ? $weekno.',' : '').
            $weekList[$weekno].','.$taxon.','.lang::get('Estimate').','.$data['estimates']."\n";
        }
      }
      if(isset($summaryArray[$taxonID])) {
        if(isset($summaryArray[$taxonID][$weekno])){
          $summaryArray[$taxonID][$weekno]['hasEstimates'] |= $data['hasEstimates'];
          $summaryArray[$taxonID][$weekno]['hasData'] |= $data['hasData'];
          $summaryArray[$taxonID][$weekno]['summary'] += (int)$data['summary'];
          $summaryArray[$taxonID][$weekno]['estimates'] += (int)$data['estimates'];
          if($data['hasEstimates'] && !$data['hasData']) {
            $summaryArray[$taxonID][$weekno]['estimatesLocations'] .= ($summaryArray[$taxonID][$weekno]['estimatesLocations']=""?' : ':'').$thisLocation;
          }
          $summaryArray[$taxonID][$weekno]['forcedZero'] &= $data['forcedZero'];
        } else {
          $summaryArray[$taxonID][$weekno] = array('summary'=>(int)$data['summary'], 'estimates'=>(int)$data['estimates'], 'forcedZero' => $data['forcedZero'], 'hasEstimates' => $data['hasEstimates'], 'hasData' => $data['hasData'], 'estimatesLocations' => ($data['hasEstimates'] && !$data['hasData'] ? $thisLocation : ''));
        }
      } else {
        $summaryArray[$taxonID] = array($weekno => array('summary'=>(int)$data['summary'], 'estimates'=>(int)$data['estimates'], 'forcedZero' => $data['forcedZero'], 'hasEstimates' => $data['hasEstimates'], 'hasData' => $data['hasData'], 'estimatesLocations' => ($data['hasEstimates'] && !$data['hasData'] ? $thisLocation : '')));
      }
    }
  }

  /**
   * Applies defaults to the options array passed to a report calendar summary control.
   * @param array $options Options array passed to the control.
   * @return array The processed options array.
   */
  private static function get_report_calendar_summary_options($options) {
    $options = array_merge([
      'mode' => 'report',
      'id' => 'calendar-report-output', // this needs to be set explicitly when more than one report on a page
      'tableContainerID' => 'tablediv-container',
      'tableID' => 'report-table',
      'tableClass' => 'ui-widget ui-widget-content report-grid',
      'theadClass' => 'ui-widget-header',
      'thClass' => 'ui-widget-header',
      'altRowClass' => 'odd',
      'extraParams' => [],
      'viewPreviousIfTooEarly' => TRUE, // if today is before the start of the calendar, display last year.
        // it is possible to create a partial calendar.
      'includeWeekNumber' => FALSE,
      'weekstart' => 'weekday=7', // Default Sunday
      'weekNumberFilter' => ':',
      'inSeasonFilter' => '',
      'rowGroupColumn' => 'taxon',
      'rowGroupID' => 'taxa_taxon_list_id',
      'chartContainerID' => 'chartdiv-container',
      'chartID' => 'chartdiv',
      'chartClass' => 'ui-widget ui-widget-content ui-corner-all',
      'headerClass' => 'ui-widget-header ui-corner-all',
      'height' => 400,
      // 'width' is optional
      'chartType' => 'line', // bar, pie
      'rendererOptions' => [],
      'legendOptions' => [],
      'axesOptions' => [],
      'includeRawData' => TRUE,
      'includeSummaryData' => TRUE,
      'includeEstimatesData' => FALSE,
      'includeTableTotalColumn' => TRUE,
      'includeTableTotalRow' => TRUE,
      'tableHeaders' => 'date',
      'rawDataCombining' => 'add',
      'dataRound' => 'nearest',
      'avgFieldRound' => 'nearest',
      'avgFields' => '',
      'zeroPointAnchor' => ',',
      'interpolation' => 'linear',
      'firstValue' => 'none',
      'lastValue' => 'none',
      'highlightEstimates' => FALSE,
      'includeRawGridDownload' => FALSE,
      'includeRawListDownload' => TRUE,
      'includeSummaryGridDownload' => FALSE,
      'includeEstimatesGridDownload' => FALSE,
      'includeListDownload' => TRUE,
      'downloadFilePrefix' => '',
      'training' => FALSE
    ], $options);
    $options["extraParams"] = array_merge([
        'date_from' => $options['date_start'],
        'date_to' => $options['date_end'],
//      'user_id' => '', // CMS User, not Indicia User.
//      'smpattrs' => '',
        'occattrs' => '',
        'training' => 'f'
      ],
      $options["extraParams"]
    );

    // Note for the calendar reports, the user_id is initially assumed to be the CMS user id as recorded in the CMS User ID attribute,
    // not the Indicia user id: we do the conversion here.
    if (isset($options["extraParams"]['user_id'])) {
      $options["extraParams"]['cms_user_id'] = $options["extraParams"]['user_id'];
      if (function_exists('hostsite_get_user_field') && $options["extraParams"]['user_id']!='') {
        $user_id = hostsite_get_user_field('indicia_user_id', FALSE, FALSE, $options["extraParams"]['user_id']);
        if (!empty($user_id)) {
          $options["extraParams"]['user_id'] = $user_id;
        }
      }
    }
    if (function_exists('hostsite_get_user_field') && hostsite_get_user_field('training', FALSE)) {
      $options['training'] = TRUE;
      $options['extraParams']['training'] = 't';
    }

    // Note for the calendar reports, the user_id is assumed to be the CMS user id as recorded in the CMS User ID attribute,
    // not the Indicia user id.
    return $options;
  }

  /**
   * <p>Outputs a calendar based summary grid that loads the results of the summary builder module.</p>
   * <p>If you need 2 grids on one page, then you must define a different id in the options for each grid.</p>
   * <p>The grid operation has NOT been AJAXified. There is no download option.</p>
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>id</b><br/>
   * Optional unique identifier for the grid's container div. This is required if there is more than
   * one grid on a single web page to allow separation of the page and sort $_GET parameters in the URLs
   * generated.</li>
   * <li><b>mode</b><br/>
   * Pass report for a report, or direct for an Indicia table or view. Default is report.</li>
   * <li><b>readAuth</b><br/>
   * Read authorisation tokens.</li>
   * <li><b>dataSource</b><br/>
   * Name of the report file or table/view. when used, any user_id must refer to the CMS user ID, not the Indicia
   * User.</li>
   * <li><b>view</b>
   * When loading from a view, specify list, gv or detail to determine which view variant is loaded. Default is list.
   * </li>
   * <li><b>extraParams</b><br/>
   * Array of additional key value pairs to attach to the request. This should include fixed values which cannot be changed by the
   * user and therefore are not needed in the parameters form.
   * </li>
   * <li><b>paramDefaults</b>
   * Optional associative array of parameter default values. Default values appear in the parameter form and can be overridden.</li>
   * <li><b>tableHeaders</b>
   * Defines which week column headers should be included: date, number or both
   * <li><b>weekOneContains</b>
   * Defines week one as the week which contains this date. Format should be MMM-DD, which is a month/day combination: e.g. choosing Apr-1 will define
   * week one as being the week containing the 1st of April. Defaults to the 1st of January.</li>
   * <li><b>weekNumberFilter</b>
   * Restrict displayed weeks to between 2 weeks defined by their week numbers. Colon separated.
   * Leaving an empty value means the end of the year.
   * Examples: "1:30" - Weeks one to thirty inclusive.
   * "4:" - Week four onwards.
   * ":5" - Upto and including week five.</li>
   * <li><b>inSeasonFilter</b>
   * Optional colon separated number pair. Used to produce an additional In-season total column in Estimates grid.
   * Leave blank to omit the column. If provided, both numbers must be given.
   * Examples: "1:26" - Weeks one to twemty-six inclusive.</li>
   * <li><b>rowGroupColumn</b>
   * The column in the report which is used as the label for the vertical axis on the grid.</li>
   * <li><b>rowGroupID</b>
   * The column in the report which is used as the id for the vertical axis on the grid.</li>
   * <li><b>countColumn</b>
   * OPTIONAL: The column in the report which contains the count for this occurrence. If omitted then the default
   * is to assume one occurrence = count of 1</li>
   * <li><b>includeChartItemSeries</b>
   * Defaults to TRUE. Include a series for each item in the report output.
   * </li>
   * <li><b>includeChartTotalSeries</b>
   * Defaults to TRUE. Include a series for the total of each item in the report output.
   * </li>
   * </ul>
   * @todo: Future Enhancements? Allow restriction to month.
   */
  public static function report_calendar_summary2($options) {
    $r = "";
    // I know that there are better ways to approach some of the date manipulation, but they are PHP 5.3+.
    // We support back to PHP 5.2
    // TODO put following JS into a control JS file.

    self::add_resource('jquery_ui');

    $definition = data_entry_helper::get_population_data([
      'table' => 'summariser_definition',
      'extraParams' => $options['readAuth'] + ['survey_id' => $options['survey_id']],
    ]);
    if (isset($records['error'])) {
      return $records['error'];
    }
    if (count($definition) != 1) {
      return 'ERROR: could not find a single summariser_definition records for survey_id ' .
        $options['survey_id'] . PHP_EOL .
        print_r($definition, TRUE);
    }

    $options = self::get_report_calendar_summary_options($options);
    $options['caching'] = isset($options['caching']) ? $options['caching'] : TRUE;
    // Don't use all of these now, eg. extraParams: this is used later for raw data
    // At the moment the summary_builder module indexes the user_id on the created_by_id field on the parent sample.
    // This effectively means that it assumes easy_login.
    // user_id and location_ids values of '0' imply "all"

    // set up extra params for the summary_occurrence fetch: options extra params is for Raw data report
    $extraParams = $options['readAuth'] + [
      'year' => $options['year'],
      'survey_id' => $options['survey_id'],
      'user_id' => $options['summary_user_id'],
      'training' => $options['training']
    ];
    if (!empty($options['extraParams']['taxon_list_id'])) {
      $extraParams['taxon_list_id'] = $options['extraParams']['taxon_list_id'];
    }
    if (isset($options['summary_location_id'])) {
      $extraParams['location_id'] = $options['summary_location_id'];
      $options['extraParams']['location_id'] = $options['summary_location_id'];
    }
    else if (isset($options['extraParams']['location_list'])) {
      $extraParams['query'] = urlencode(json_encode(['in' => ['location_id', explode(',', $options['extraParams']['location_list'])]]));
    }
    else {
      $options['valid'] = FALSE; // default to none
    }
    $extraParams['columns'] = 'type,taxa_taxon_list_id,taxonomic_sort_order,taxon,preferred_taxon,' .
                              'default_common_name,taxon_meaning_id,summarised_data';

    $records = $options['valid'] ?
      data_entry_helper::get_population_data([
        'table' => 'summary_occurrence',
        'extraParams'=>$extraParams,
        'caching'=> $options['caching']
        ]) :
        [];
    if (isset($records['error'])) {
        hostsite_show_message(print_r($records,true));
        return $records['error'];
    }

    self::$javascript .= "
var pageURI = '" . $_SERVER['REQUEST_URI'] . "';
function rebuild_page_url(oldURL, overrideparam, overridevalue, removeparam) {
  var parts = oldURL.split('?');
  var params = [];
  if (overridevalue!=='') {
    params.push(overrideparam+'='+overridevalue);
  }
  if(parts.length > 1) {
    var oldparams = parts[1].split('&');
    for(var i = 0; i < oldparams.length; i++){
      var bits = oldparams[i].split('=');
      if (bits[0] != overrideparam && removeparam.indexOf(bits[0])<0) {
        params.push(oldparams[i]);
      }
    }
  }
  return parts[0] + (params.length > 0 ? '?' + (params.join('&')) : '');
};
$('#year-control-previous').attr('href',rebuild_page_url(pageURI,'year',".$options['year']."-1, []));
$('#year-control-next').attr('href',rebuild_page_url(pageURI,'year',".$options['year']."+1, []));
// user and location ids are dealt with in the main form. Their change functions look at pageURI
";

    // ISO Date - Mon=1, Sun=7
    // Week 1 = the week with date_from in
    // The summariser_definition period_start is mandatory
    $options['weekNumberFilter'] = empty($options['weekNumberFilter']) ? ':' : $options['weekNumberFilter'];
    $periodNumberFilter=explode(':',$options['weekNumberFilter']);
    if (count($periodNumberFilter)!=2) {
      return "Period number filter unrecognised {".$options['weekNumberFilter']."}";
    }
    if ($periodNumberFilter[0] != '' && (intval($periodNumberFilter[0]) != $periodNumberFilter[0] || $periodNumberFilter[0] > 52)) {
      return "Period number filter start unrecognised or out of range {" . $periodNumberFilter[0] . "}";
    }
    if ($periodNumberFilter[1] != '' && (intval($periodNumberFilter[1]) != $periodNumberFilter[1] || $periodNumberFilter[1] < $periodNumberFilter[0] || $periodNumberFilter[1] > 52)) {
      return "Period number filter end unrecognised or out of range {" . $periodNumberFilter[1] . "}";
    }

    if (empty($options['inSeasonFilter'])) {
      $inSeason = FALSE;
    } else {
      $inSeason = explode(':', $options['inSeasonFilter']);
      if (count($inSeason) != 2) {
        return "In-season specification format unrecognised {".$options['weekNumberFilter']."}";
      }
      if ($inSeason[0] != '' && (intval($inSeason[0]) != $inSeason[0] || $inSeason[0] > 52)) {
        return "In-season specification start unrecognised or out of range {" . $inSeason[0] . "}";
      }
      if ($inSeason[1] != '' && (intval($inSeason[1]) != $inSeason[1] || $inSeason[1] < $inSeason[0] || $inSeason[1] > 52)) {
        return "In-season specification end unrecognised or out of range {" . $periodNumberFilter[1] . "}";
      }
    }

    $periodStart = explode('=',$definition[0]['period_start']);
    if ($periodStart[0] == 'date') {
      if(!($periodStartDate = date_create($options['year'] . "-" . $periodStart[1]))){
        return "Period start unrecognised {" . $definition[0]['period_start']  ."}";
      }
      $periodStart = $periodStartDate->format('N');
    } else {
      $periodStart = $periodStart[1];
    }
    if (intval($periodStart) != $periodStart || $periodStart < 1 || $periodStart > 7) {
        return "Period start unrecognised or out of range {" . $periodStart . "}";
    }
    if (!($periodOneDate = date_create($options['year'] . '-' . $definition[0]['period_one_contains']))) {
      return "Period one unrecognised {" . $definition[0]['period_one_contains'] . "}";
    }
    $periodOneDateWeekday = $periodOneDate->format('N');
    if ($periodOneDateWeekday > $periodStart) { // scan back to start of week
      $periodOneDate->modify('-' . ($periodOneDateWeekday-$periodStart) . ' day');
    }
    else if ($periodOneDateWeekday < $periodStart) {
      $periodOneDate->modify('-' . (7+$periodOneDateWeekday-$periodStart) . ' day');
    }
    $firstPeriodDate = clone $periodOneDate; // date we start providing data for
    $periodOneDateYearDay = $periodOneDate->format('z'); // day within year note year_start_yearDay is by definition 0
    $minPeriodNo = $periodNumberFilter[0] != '' ? $periodNumberFilter[0] : 1;
    $numPeriods = ceil($periodOneDateYearDay/7); // number of periods in year prior to $periodOneDate - 1st Jan gives zero, 2nd-8th Jan gives 1, etc
    if ($minPeriodNo-1 < (-1 * $numPeriods)) {
      $minPeriodNo=(-1 * $numPeriods)+1; // have to allow for period zero
    }
    if ($minPeriodNo < 1) {
      $firstPeriodDate->modify((($minPeriodNo-1)*7).' days'); // have to allow for period zero
    }
    else if ($minPeriodNo > 1) {
      $firstPeriodDate->modify('+'.(($minPeriodNo-1)*7).' days');
    }

    if ($periodNumberFilter[1]!='') {
      $maxPeriodNo = $periodNumberFilter[1];
    } else {
      $yearEnd = date_create($options['year'] . '-Dec-25'); // don't want to go beyond the end of year: this is 1st Jan minus 1 week: it is the start of the last full week
      $yearEndYearDay = $yearEnd->format('z'); // day within year
      $maxPeriodNo = 1+ceil(($yearEndYearDay-$periodOneDateYearDay)/7);
    }

    // Initialise data
    $tableNumberHeaderRow = $tableDateHeaderRow = $downloadNumberHeaderRow = $downloadDateHeaderRow = "";
    $summaryDataDownloadGrid = $estimateDataDownloadGrid = $rawDataDownloadGrid = '';
    $chartNumberLabels = [];
    $chartDateLabels = [];
    $fullDates = [];
    $summaryArray = []; // this is used for the table output format
    $rawArray = []; // this is used for the table output format
    // In order to apply the data combination and estmation processing, we assume that the the records are in taxon, location_id, sample_id order.
    $locationArray = []; // this is for a single species at a time.
    $lastLocation = FALSE;
    $seriesLabels = [];
    $lastTaxonID = FALSE;
    $lastSample = FALSE;
    $locationSamples = [];
    $periodList = [];
    $grandTotal=0;
    $totalRow = [];
    $estimatesGrandTotal = $inSeasonEstimatesGrandTotal = 0;
    $totalEstimatesRow = [];
    $seriesIDs = [];
    $summarySeriesData = [];
    $estimatesSeriesData = [];
    $seriesOptions = [];

    for ($i= $minPeriodNo; $i <= $maxPeriodNo; $i++) {
      $tableNumberHeaderRow.= '<th class="' . $options['thClass'] . ' week">' . $i . '</th>';
      $tableDateHeaderRow.= '<th class="' . $options['thClass'] . ' week">' . $firstPeriodDate->format('M') . '<br/>' . $firstPeriodDate->format('d') . '</th>';
      $downloadNumberHeaderRow.= ',' . $i;
      $downloadDateHeaderRow.= ',' . $firstPeriodDate->format('d/m/Y');
      $chartNumberLabels[] = "" . $i;
      $chartDateLabels[] = $firstPeriodDate->format('M-d');
      $fullDates[$i] = $firstPeriodDate->format('d/m/Y');
      $firstPeriodDate->modify('+7 days');
    }

    $sampleFieldList = !empty($options['sampleFields']) ? explode(',',$options['sampleFields']) : FALSE;
    if (empty($sampleFieldList)) {
      $sampleFields = FALSE;
    } else {
      $sampleFields = [];
      foreach ($sampleFieldList as $sampleField) {
        $parts = explode(':', $sampleField);
        $field = ['caption' => $parts[0], 'field' => $parts[1], 'attr' => FALSE];
        if (count($parts) == 3 && $parts[1] == 'smpattr') {
          $smpAttribute=data_entry_helper::get_population_data([
              'table' => 'sample_attribute',
              'extraParams' => $options['readAuth'] + ['view' => 'list', 'id' => $parts[2]]
          ]);
          if (count($smpAttribute) >= 1) { // may be assigned to more than one survey on this website. This is not relevant to info we want.
            $field['id'] = $parts[2];
            $field['attr'] = $smpAttribute[0];
          }
        }
        $sampleFields[] = $field;
      }
    }

    $count = count($records);
    $sortData=[];
    // EBMS Only. Override taxon name display depending on scheme
    if (\Drupal::moduleHandler()->moduleExists('ebms_scheme') &&
          !empty($options['taxon_column_overrides'])) {
      $records = self::injectLanguageIntoDefaultCommonName($options['readAuth'], $records, $options['taxon_column_overrides']);
    }
    foreach ($records as $index => $record) {
      $taxonMeaningID = $record['taxon_meaning_id'];
      if (empty($seriesLabels[$taxonMeaningID])) {
        if($options['taxon_column'] === 'common_name' && !empty($record['default_common_name'])) {
          $seriesLabels[$taxonMeaningID] = ['label'=>$record['default_common_name']];
          if (!empty($record['preferred_taxon'])) {
            $seriesLabels[$taxonMeaningID]['tip'] = $record['preferred_taxon'];
          }
        } else if(!empty($record['preferred_taxon'])) {
          $seriesLabels[$taxonMeaningID] = ['label'=>$record['preferred_taxon']];
          if(!empty($record['default_common_name'])) {
            $seriesLabels[$taxonMeaningID]['tip'] = $record['default_common_name'];
          }
        } else if(!empty($record['taxon'])) {
          $seriesLabels[$taxonMeaningID] = ['label' => $record['taxon']]; // various fall backs.
        } else {
          $seriesLabels[$taxonMeaningID] = ['label' => '[' . $record['taxa_taxon_list_id'] . ']'];
        }
        $summaryArray[$taxonMeaningID] = [];
        $sortData[] = [
          'order' => $record['taxonomic_sort_order'],
          'label' => $seriesLabels[$taxonMeaningID]['label'],
          'meaning' => $taxonMeaningID
        ];
      }
      $summarisedData = json_decode($record['summarised_data'], FALSE);
      foreach ($summarisedData as $summary) {
        $periodNo = $summary->period;
        if ($periodNo >= $minPeriodNo && $periodNo <= $maxPeriodNo) {
          if (!isset($summaryArray[$taxonMeaningID][$periodNo])) {
            $summaryArray[$taxonMeaningID][$periodNo] = ['total' => NULL,'estimate' => 0];
          }
          if ($summary->summary !== NULL && $summary->summary !== "NULL") {
            $summaryArray[$taxonMeaningID][$periodNo]['total'] = ($summaryArray[$taxonMeaningID][$periodNo]['total'] == NULL
                ? 0 : $summaryArray[$taxonMeaningID][$periodNo]['total']) + $summary->summary;
          }
          $summaryArray[$taxonMeaningID][$periodNo]['estimate'] += $summary->estimate;
        }
      }
    }
    usort($sortData, ['report_helper', 'report_calendar_summary_sort1']);
    // will storedata in an array[Y][X]
    self::add_resource('jqplot');
    switch ($options['chartType']) {
      case 'bar' :
        self::add_resource('jqplot_bar');
        $renderer='$.jqplot.BarRenderer';
        break;
      case 'pie' :
        self::add_resource('jqplot_pie');
        $renderer='$.jqplot.PieRenderer';
        break;
      default : // default is line
        $renderer='$.jqplot.LineRenderer';
        break;
    }
    self::add_resource('jqplot_category_axis_renderer');
    $opts = ["seriesDefaults:{\n" . (isset($renderer) ? "  renderer:$renderer,\n" : '') .
                " rendererOptions:" . json_encode($options['rendererOptions']) . "}"];
    $seriesToDisplay = (isset($options['outputSeries']) ? explode(',', $options['outputSeries']) : (empty($options['includeChartTotalSeries']) ? 'all' : ['0']));
    $summaryTab =
              '<div><a class="btn btn-small btn-info" onClick="indiciaData.copyClipboard(\'' . $options['tableID'] . '-summary\');">' .
                lang::get('Copy summary table to clipboard') . '</a></div>' .
              '<table id="'.$options['tableID'].'-summary" class="'.$options['tableClass'].'">' .
                '<thead class="' . $options['theadClass'] . '">' .
                  '<tr>' .
                    '<th class="' . $options['thClass'] . '">' . lang::get('Week') . '</th>' .
                    $tableNumberHeaderRow .
                    '<th class="' . $options['thClass'] . '">' . lang::get('Total') . '</th>' .
                  '</tr>' .
                  '<tr>' .
                    '<th class="' . $options['thClass'] . '">' . lang::get('Date') . '</th>' .
                    $tableDateHeaderRow .
                    '<th class="' . $options['thClass'] . '"></th>' .
                  '</tr>' .
                '</thead>' .
                '<tbody>';
    $estimateTab =
              '<div><a class="btn btn-small btn-info" onClick="indiciaData.copyClipboard(\'' . $options['tableID'] . '-estimate\');">' .
                lang::get('Copy estimates table to clipboard') . '</a></div>' .
              '<table id="' . $options['tableID'] . '-estimate" class="' . $options['tableClass'] . '">' .
                '<thead class="' . $options['theadClass'] . '">' .
                  '<tr>' .
                    '<th class="' . $options['thClass'] . '">' . lang::get('Week') . '</th>' .
                    $tableNumberHeaderRow .
                    ($inSeason ? '<th class="' . $options['thClass'] . '">' . lang::get('In-season total') . '</th>' : '') .
                    '<th class="' . $options['thClass'] . '">' . lang::get('Total') . '</th>' .
                  '</tr>' .
                  '<tr>' .
                    '<th class="' . $options['thClass'] . '">' . lang::get('Date') . '</th>' .
                    $tableDateHeaderRow .
                    ($inSeason ? '<th class="' . $options['thClass'] . '">(wks ' . $inSeason[0] . ' > '.  $inSeason[1] . ')</th>' : '') .
                    '<th class="' . $options['thClass'] . '">' . lang::get('(with<br/>estimates)') . '</th>' .
                  '</tr>' .
                '</thead>' .
                '<tbody>';
    $summaryDataDownloadGrid .= lang::get('Week') . ',' . $downloadNumberHeaderRow . ',' . lang::get('Total') . "\n" .
                                lang::get('Date') . ',' . $downloadDateHeaderRow . ",\n";
    $estimateDataDownloadGrid .= lang::get('Week') . ',' . $downloadNumberHeaderRow . ',' .
                                ($inSeason ? lang::get('In-season estimates total') . ',' : '') .
                                lang::get('Estimates Total') . "\n" .
                                lang::get('Date') . ',' . $downloadDateHeaderRow . ($inSeason ? ',' : '') . ",\n";
    $altRow = FALSE;
    for($i = $minPeriodNo; $i <= $maxPeriodNo; $i++) {
      $totalRow[$i] = $totalEstimatesRow[$i] = 0;
    }
    foreach ($sortData as $sortedTaxon) {
      $seriesID = $sortedTaxon['meaning'];
      $summaryRow = $summaryArray[$seriesID];
      $summaryValues = [];
      $estimatesValues = [];
      if (!empty($seriesLabels[$seriesID])) {
        $total = $estimatesTotal = $inSeasonEstimatesTotal = 0;  // row totals
        $summaryTab .= '<tr class="datarow ' . ($altRow ? $options['altRowClass'] : '') . '">' .
                '<td' . (isset($seriesLabels[$seriesID]['tip']) ? ' title="' . $seriesLabels[$seriesID]['tip'] . '"' : '') . '>' .
                    $seriesLabels[$seriesID]['label'] . '</td>';
        $estimateTab .= '<tr class="datarow ' . ($altRow ? $options['altRowClass'] : '') . '">' .
                '<td' . (isset($seriesLabels[$seriesID]['tip']) ? ' title="' . $seriesLabels[$seriesID]['tip'] . '"' : '') . '>' .
                    $seriesLabels[$seriesID]['label'] . '</td>';
        $summaryDataDownloadGrid .= '"' . $seriesLabels[$seriesID]['label'] . '","' .
                (isset($seriesLabels[$seriesID]['tip']) ? $seriesLabels[$seriesID]['tip'] : '') . '"';
        $estimateDataDownloadGrid .= '"' . $seriesLabels[$seriesID]['label'] . '","' .
                (isset($seriesLabels[$seriesID]['tip']) ? $seriesLabels[$seriesID]['tip'] : '') . '"';
        for ($i = $minPeriodNo; $i <= $maxPeriodNo; $i++) {
          $summaryDataDownloadGrid .= ',';
          $estimateDataDownloadGrid .= ',';
          if (isset($summaryRow[$i])) {
            $summaryValue = $summaryRow[$i]['total'];
            $estimateValue = $summaryRow[$i]['estimate'];
            $class = ($summaryValue===0 ? 'forcedZero' : '');
            if ($summaryValue === 0 && $estimateValue === 0) {
              $estimatesClass='forcedZero';
            } else {
              $estimatesClass = ($summaryValue===null || $summaryValue!=$estimateValue ? 'highlight-estimates' : '');
            }
            $summaryDataDownloadGrid .= $summaryValue;
            $estimateDataDownloadGrid .= $estimateValue;
            $summaryTab .= '<td class="' . $class . '">' . ($summaryValue !== NULL ? $summaryValue : '') . '</td>';
            $estimateTab .= '<td class="' . $estimatesClass . '">' . $estimateValue . '</td>';
            if ($summaryValue !== NULL) {
              $total += $summaryValue;
              $totalRow[$i] += $summaryValue; // = $summaryTotalRow
              $grandTotal += $summaryValue;
              $summaryValues[]=$summaryValue;
            } else {
              $summaryValues[]=0;
            }
            $estimatesValues[] = $estimateValue;
            $estimatesTotal += $estimateValue;
            if ($inSeason && $i >= $inSeason[0] && $i <= $inSeason[1]) {
              $inSeasonEstimatesTotal += $estimateValue;
              $inSeasonEstimatesGrandTotal += $estimateValue;
            }
            $totalEstimatesRow[$i] += $estimateValue; // = $estimatesTotalRow
            $estimatesGrandTotal += $estimateValue;
          } else {
            $summaryTab .= '<td></td>';
            $estimateTab .= '<td></td>';
            $summaryValues[] = 0;
            $estimatesValues[] = 0;
          }
        }
        if ($options['includeChartItemSeries']) {
          $seriesIDs[] = $seriesID;
          $summarySeriesData[] = '[' . implode(',', $summaryValues) . ']';
          $estimatesSeriesData[] = '[' . implode(',', $estimatesValues) . ']';
          $seriesOptions[] = '{"show":' . ($seriesToDisplay == 'all' || in_array($seriesID, $seriesToDisplay) ? 'true' : 'false') .
                  ',"label":"' . htmlspecialchars ($seriesLabels[$seriesID]['label']) . '","showlabel":true}';
        }
        $summaryTab .= '<td class="total-column">' . $total . '</td></tr>';
        $summaryDataDownloadGrid .= ',' . $total."\n";
        if ($inSeason) {
          $estimateTab .= '<td class="total-column estimates">' . $inSeasonEstimatesTotal . '</td>';
          $estimateDataDownloadGrid .= ',' . $inSeasonEstimatesTotal;
        }
        $estimateTab .= '<td class="total-column estimates">' . $estimatesTotal . '</td></tr>';
        $estimateDataDownloadGrid .= ',' . $estimatesTotal . "\n";
        $altRow = !$altRow;
      }
    }
    if (!empty($options['includeChartTotalSeries'])) { // totals are put at the start
      array_unshift($seriesIDs,0); // Total has ID 0
      array_unshift($summarySeriesData, '[' . implode(',', $totalRow) . ']');
      array_unshift($estimatesSeriesData, '[' . implode(',', $totalEstimatesRow) . ']');
      array_unshift($seriesOptions, '{"show":' . ($seriesToDisplay == 'all' || in_array(0, $seriesToDisplay) ? 'true' : 'false') .
            ',"label":"' . lang::get('Total') . '","showlabel":true}');
    }
    $opts[] = 'series:[' . implode(',', $seriesOptions) . ']';
    $options['axesOptions']['xaxis']['renderer'] = '$.jqplot.CategoryAxisRenderer';
    if(isset($options['chartLabels']) && $options['chartLabels'] == 'number') {
      $options['axesOptions']['xaxis']['ticks'] = $chartNumberLabels;
    } else {
      $options['axesOptions']['xaxis']['ticks'] = $chartDateLabels;
    }
    // We need to fudge the json so the renderer class is not a string
    $axesOpts = str_replace('"$.jqplot.CategoryAxisRenderer"', '$.jqplot.CategoryAxisRenderer',
        'axes:'.json_encode($options['axesOptions']));
    $opts[] = $axesOpts;

    $summaryTab .= '<tr class="totalrow"><td>' . lang::get('Total (Summary)') . '</td>';
    $estimateTab .= '<tr class="totalrow estimates"><td>' . lang::get('Total inc Estimates') . '</td>';
    $summaryDataDownloadGrid .= '"' . lang::get('Total (Summary)') . '",';
    $estimateDataDownloadGrid .= '"' . lang::get('Total') . '",';
    for ($i= $minPeriodNo; $i <= $maxPeriodNo; $i++) {
      $summaryTab .= '<td>' . $totalRow[$i] . '</td>';
      $estimateTab.= '<td>' . $totalEstimatesRow[$i] . '</td>';
      $estimateDataDownloadGrid .= ',' . $totalEstimatesRow[$i];
      $summaryDataDownloadGrid .= ',' . $totalRow[$i];
    }
    $summaryTab .= '<td class="total-column grand-total">' . $grandTotal . '</td></tr>';
    $summaryDataDownloadGrid .= ',' . $grandTotal . "\n";
    if ($inSeason) {
      $estimateTab .= '<td class="total-column in-season-grand-total estimates">' . $inSeasonEstimatesGrandTotal . '</td>';
      $estimateDataDownloadGrid .= ',' . $inSeasonEstimatesGrandTotal;
    }
    $estimateTab .= '<td class="total-column grand-total estimates">' . $estimatesGrandTotal . '</td></tr>';
    $estimateDataDownloadGrid .= ',' . $estimatesGrandTotal . "\n";
    $summaryTab .= "</tbody></table>\n";
    $estimateTab .= "</tbody></table>\n";
    self::$javascript .= "
var seriesData = {ids: [" . implode(',', $seriesIDs) . "], summary: [" . implode(',', $summarySeriesData) . "], estimates: [" . implode(',', $estimatesSeriesData) . "]};
function replot(type){
  // there are problems with the coloring of series when added to a plot: easiest just to completely redraw.
  var max=0;
  $('#{$options['chartID']}-' + type).empty();
" .
(!isset($options['width']) || $options['width'] == '' ? "  jQuery('#{$options['chartID']}-'+type).width(jQuery('#{$options['chartID']}-'+type).width());\n" : '') .
"  var opts = {" . implode(",\n", $opts) . "};
  // copy series from checkboxes.
  $('#{$options['chartID']}-'+type).parent().find('[name={$options['chartID']}-series]').each(function(idx, elem){
      opts.series[idx].show = (jQuery(elem).filter(':checked').length > 0);
  });
  for(var i=0; i<seriesData[type].length; i++)
    if(opts.series[i].show)
      for(var j=0; j<seriesData[type][i].length; j++)
          max=(max>seriesData[type][i][j]?max:seriesData[type][i][j]);
  opts.axes.yaxis.max=max+1;
  opts.axes.yaxis.tickInterval = Math.floor(max/15); // number of ticks - too many takes too long to display
  if(!opts.axes.yaxis.tickInterval) opts.axes.yaxis.tickInterval=1;
  $('.legend-colours').remove();
  if($('#{$options['chartID']}-'+type).parent().find('[name={$options['chartID']}-series]').filter(':checked').length == 0) return;
  var plot = $.jqplot('{$options['chartID']}-'+type, seriesData[type], opts);
  for(var i=0; i<plot.series.length; i++){
    if(plot.series[i].show === true) {
      var elem = $('#{$options['chartID']}-'+type).parent().find('[name={$options['chartID']}-series]').eq(i);
      elem.after('<div class=\"legend-colours\"><div class=\"legend-colours-inner\" style=\"background:'+plot.series[i].color+';\">&nbsp;</div></div>');
    }
  }
};
indiciaFns.bindTabsActivate($('#controls'), function(event, ui) {
  panel = typeof ui.newPanel==='undefined' ? ui.panel : ui.newPanel[0];
  if (panel.id==='summaryChart') { replot('summary'); }
  if (panel.id==='estimateChart') { replot('estimates'); }
});
";
    $summarySeriesPanel="";
    if (!empty($options['disableableSeries']) &&
          (count($summaryArray)>(!empty($options['includeChartTotalSeries']) ? 0 : 1)) &&
          !empty($options['includeChartItemSeries'])) {
      $class = 'series-fieldset';
      if (function_exists('hostsite_add_library') && (!defined('DRUPAL_CORE_COMPATIBILITY') || DRUPAL_CORE_COMPATIBILITY!=='7.x')) {
        hostsite_add_library('collapse');
        $class.=' collapsible collapsed';
      }
      $summarySeriesPanel .= '<fieldset id="' . $options['chartID'] . '-series" class="' . $class . '">' .
            '<legend>' . lang::get('Display Series') . "</legend><span>" .
            '<input type="button" class="disable-button cleared" value="' . lang::get('Show all') . "\"/>\n";
      $idx = 0;
      if (!empty($options['includeChartTotalSeries'])) {
        // use series ID = 0 for Total
        $summarySeriesPanel .= '<span class="chart-series-span"><input type="checkbox" id="' .
                $options['chartID'] . '-series-' . $idx . '" name="' . $options['chartID'] . '-series" value="' . $idx . '"/>'.
                '<label for="' . $options['chartID'] . '-series-' . $idx . '">' . lang::get('Total') . "</label></span>\n";
        $idx++;
        self::$javascript .= "jQuery('[name={$options['chartID']}-series]').filter('[value=0]')." .
            ($seriesToDisplay == 'all' || in_array(0, $seriesToDisplay) ? 'prop("checked","checked");' : 'removeProp("checked");') .
            "\n";
      }
      foreach ($sortData as $sortedTaxon) {
        $seriesID = $sortedTaxon['meaning'];
        $summaryRow = $summaryArray[$seriesID];
        $summarySeriesPanel .= '<span class="chart-series-span">' .
                '<input type="checkbox" id="' . $options['chartID'] . '-series-' . $idx .
                '" name="' . $options['chartID'] . '-series" value="' . $seriesID . '"/>' .
                '<label for="' . $options['chartID'] . '-series-' . $idx . '"' .
                (isset($seriesLabels[$seriesID]['tip']) ? ' title="' . $seriesLabels[$seriesID]['tip'] . '"' : '') . '>' .
                $seriesLabels[$seriesID]['label'] . "</label></span>\n";
        $idx++;
        self::$javascript .= "jQuery('[name=" . $options['chartID']  ."-series]').filter('[value=" . $seriesID . "]').".
            ($seriesToDisplay == 'all' || in_array($seriesID, $seriesToDisplay) ? 'prop("checked","checked");' : 'removeProp("checked");') .
            "\n";
      }
      $summarySeriesPanel .= "</span></fieldset>\n";
      // Known issue: jqplot considers the min and max of all series when drawing on the screen, even those which are not displayed
      // so replotting doesn't scale to the displayed series!
      // Note we are keeping the 2 charts in sync.
      self::$javascript .= "
jQuery('#summaryChart [name={$options['chartID']}-series]').on('change', function(){
  $('#estimateChart [name={$options['chartID']}-series]').filter('[value='+$(this).val()+']').prop('checked',!!$(this).prop('checked'));
  replot('summary');
});
jQuery('#estimateChart [name={$options['chartID']}-series]').on('change', function(){
  $('#summaryChart [name={$options['chartID']}-series]').filter('[value='+$(this).val()+']').prop('checked',!!$(this).prop('checked'));
  replot('estimates');
});
jQuery('#summaryChart .disable-button').on('click', function(){
  if(jQuery(this).is('.cleared')){ // button is to show all
    jQuery('[name={$options['chartID']}-series]').not('[value=0]').prop('checked','checked');
    jQuery('.disable-button').removeClass('cleared').val(\"" . lang::get('Hide all') . "\");
  } else {
    jQuery('[name={$options['chartID']}-series]').not('[value=0]').prop('checked',false);
    jQuery('.disable-button').addClass('cleared').val(\"" . lang::get('Show all') . "\");
  }
  replot('summary');
});
jQuery('#estimateChart .disable-button').on('click', function(){
  if(jQuery(this).is('.cleared')){ // button is to show all
    jQuery('[name={$options['chartID']}-series]').not('[value=0]').prop('checked','checked');
    jQuery('.disable-button').removeClass('cleared').val(\"" . lang::get('Hide all') . "\");
  } else {
    jQuery('[name={$options['chartID']}-series]').not('[value=0]').prop('checked',false);
    jQuery('.disable-button').addClass('cleared').val(\"" . lang::get('Show all') . "\");
  }
  replot('estimates');
});
";
    }
    $hasRawData = FALSE;
    if (!empty($options['extraParams']['location_id'])) {
      // only get the raw data if a single location is specified.
      $options['extraParams']['orderby'] = 'date';
      if (!isset($options['extraParams']['user_id'])) {
        $options['extraParams']['user_id'] = 0;
      }
      self::request_report($response, $options, $currentParamValues, FALSE, '');
      if (isset($response['error'])) {
        $rawTab = "ERROR RETURNED FROM request_report:<br />" . (print_r($response, TRUE));
      } else if (isset($response['parameterRequest'])) {
        // We're not even going to bother with asking the user to populate a partially filled in report parameter set.
        $rawTab = '<p>INTERNAL ERROR: Report request parameters not set up correctly.<br />' . (print_r($response, TRUE)) . '<p>';
      } else {
        // convert records to a date based array so it can be used when generating the grid.
        $altRow = FALSE;
        $records = $response['records'];
        $rawTab = (isset($options['linkMessage']) ? $options['linkMessage'] : '');
        $rawDataDownloadGrid = lang::get('Week') . ',';
        $rawArray = [];
        $sampleList = [];
        $sampleDateList = [];
        $smpIdx = 0;
        $hasRawData = (count($records) > 0);
        if (!$hasRawData) {
          $rawTab .= '<p>' . lang::get('No raw data available for this location/period/user combination.') . '</p>';
        } else {
          foreach ($records as $occurrence) {
            if (!in_array($occurrence['sample_id'], $sampleList)) {
              $sampleList[] = $occurrence['sample_id'];
              $sampleData = ['id' => $occurrence['sample_id'], 'date' => $occurrence['date'], 'location' => $occurrence['location_name']];
              $rawArray[$occurrence['sample_id']] = [];
              if ($sampleFields) {
                foreach ($sampleFields as $sampleField) {
                  if ($sampleField['attr'] === FALSE) {
                    $sampleData[$sampleField['caption']] = $occurrence[$sampleField['field']];
                  } else if ($sampleField['attr']['data_type']=='L') {
                    $sampleData[$sampleField['caption']] = $occurrence['attr_sample_term_'.$sampleField['id']];
                  } else {
                    $sampleData[$sampleField['caption']] = $occurrence['attr_sample_'.$sampleField['id']];
                  }
                }
              }
              $sampleDateList[] = $sampleData;
            }
            if ($occurrence['taxon_meaning_id'] !== NULL && $occurrence['taxon_meaning_id'] != '') {
              $count = (isset($options['countColumn']) && $options['countColumn'] != '') ?
                        (isset($occurrence[$options['countColumn']]) ? $occurrence[$options['countColumn']] : 0) : 1;
              if (!isset($rawArray[$occurrence['sample_id']][$occurrence['taxon_meaning_id']])) {
                $rawArray[$occurrence['sample_id']][$occurrence['taxon_meaning_id']] = $count;
              } else {
                $rawArray[$occurrence['sample_id']][$occurrence['taxon_meaning_id']] += $count;
              }
            }
          }
          $rawTab .= '<div><a class="btn btn-small btn-info" onClick="indiciaData.copyClipboard(\'' . $options['tableID'] . '-raw\');">' .
               lang::get('Copy raw table to clipboard') . '</a></div>' .
              '<table id="' . $options['tableID'] . '-raw" class="' . $options['tableClass'] . '"><thead class="' . $options['theadClass'] . '">' .
                '<tr><th class="' . $options['thClass'] . '">' . lang::get('Week') . '</th>';
          foreach ($sampleDateList as $sample) {
            $sampleDate = date_create($sample['date']);
//          $this_index = $this_date->format('z');
            $thisYearDay = $sampleDate->format('N');
            if ($thisYearDay > $periodStart) { // scan back to start of week
              $sampleDate->modify('-' . ($thisYearDay-$periodStart) . ' day');
            } else if($thisYearDay < $periodStart) {
              $sampleDate->modify('-' . (7+$thisYearDay-$periodStart) . ' day');
            }
            $thisYearDay = $sampleDate->format('z');
            $periodNo = (int)floor(($thisYearDay-$periodOneDateYearDay)/7)+1;
            $rawTab .= '<th class="' . $options['theadClass'] . '">' . $periodNo . '</th>';
            $rawDataDownloadGrid .= ',' . $periodNo;
          }
          $rawTab .= '</tr><tr><th class="' . $options['theadClass'] . '">' . lang::get('Date') . '</th>';
          $rawDataDownloadGrid .= "\n" . lang::get('Date') . ',';
          foreach ($sampleDateList as $sample) {
            $sample_date = date_create($sample['date']);
            $rawTab .= '<th class="' . $options['theadClass'] . '">' .
                (isset($options['linkURL']) && $options['linkURL']!= '' ? '<a href="' . $options['linkURL'] . $sample['id'] . '" target="_blank" title="Link to data entry form for ' . $sample['location'] . ' on ' . $sample['date'] .' (Sample ID ' . $sample['id'] . ')">' : '').
                $sample_date->format('M') . '<br/>' . $sample_date->format('d') .
                (isset($options['linkURL']) && $options['linkURL'] != '' ? '</a>' : '') .
                '</th>';
            $rawDataDownloadGrid .= ',' . $sample['date'];
          }
          $rawTab .= '</tr></thead><tbody>';
          $rawDataDownloadGrid .= "\n";
          if ($sampleFields) {
            foreach ($sampleFields as $sampleField) { // last-sample-datarow
              $rawTab .= '<tr class="sample-datarow ' . ($altRow?$options['altRowClass']:'') . '"><td>' . $sampleField['caption'] . '</td>';
              $rawDataDownloadGrid .= '"' . $sampleField['caption'] . '",';
              foreach ($sampleDateList as $sample) {
                $rawTab .= '<td>' . ($sample[$sampleField['caption']] === NULL || $sample[$sampleField['caption']]=='' ? '&nbsp;' : $sample[$sampleField['caption']]) . '</td>';
                $rawDataDownloadGrid .= ',' . $sample[$sampleField['caption']];
              }
              $rawTab .= '</tr>';
              $rawDataDownloadGrid .= "\n";
              $altRow = !$altRow;
            }
            self::$javascript .= "
var sampleDatarows = $('#rawData .sample-datarow').length;
$('#rawData .sample-datarow').eq(sampleDatarows-1).addClass('last-sample-datarow');\n";
          }
          foreach ($sortData as $sortedTaxon) {
            $seriesID = $sortedTaxon['meaning'];
            if (!empty($seriesLabels[$seriesID])) {
              $rawTab .= '<tr class="datarow ' . ($altRow?$options['altRowClass'] : '') . '"><td' . (isset($seriesLabels[$seriesID]['tip']) ? ' title="' . $seriesLabels[$seriesID]['tip'] . '"' : '').'>' . $seriesLabels[$seriesID]['label'] . '</td>';
              $rawDataDownloadGrid .= '"'.$seriesLabels[$seriesID]['label'] . '","' . (isset($seriesLabels[$seriesID]['tip']) ? $seriesLabels[$seriesID]['tip'] : '') . '"';
              foreach ($sampleList as $sampleID) {
                $rawTab .= '<td>' . (isset($rawArray[$sampleID][$seriesID]) ? $rawArray[$sampleID][$seriesID] : '&nbsp;') . '</td>';
                $rawDataDownloadGrid .= ',' . (isset($rawArray[$sampleID][$seriesID]) ? $rawArray[$sampleID][$seriesID] : '');
              }
              $rawTab .= '</tr>';
              $rawDataDownloadGrid .= "\n";
              $altRow = !$altRow;
            }
          }
          $rawTab .= '</tbody></table>';
        }
      }
    } else {
      $rawTab = '<p>' . lang::get('Raw Data is only available when a location is specified.') . '</p>';
    }
    $hasData = (count($summaryArray)>0);

    if ($hasData) {
      $tabs = [
        '#summaryData' => lang::get('Summary Table'),
        '#summaryChart' => lang::get('Summary Chart'),
        '#estimateData' => lang::get('Estimate Table'),
        '#estimateChart' => lang::get('Estimate Chart')
      ];
    } else {
      $tabs = ['#summaryData' => lang::get('No Summary Data')];
    }
    $tabs['#rawData'] = lang::get('Raw Data');
    $downloadTab = "";
    $timestamp = (isset($options['includeReportTimeStamp']) && $options['includeReportTimeStamp'] ? '_' . date('YmdHis') : '');
    // No need for saved reports to be atomic events.
    // purging??
    global $base_url;
    $downloadsFolder = hostsite_get_public_file_path() . '/reportsDownloads/';
    if ($hasData && $options['includeSummaryGridDownload']) {
      if (!is_dir($downloadsFolder) || !is_writable($downloadsFolder)) {
        return lang::get('Internal Config error: directory {1} does not exist or is not writeable', $downloadsFolder);
      }
      $cacheFile = $options['downloadFilePrefix'] . 'summaryDataGrid' . $timestamp . '.csv';
      $handle = fopen($downloadsFolder.$cacheFile, 'wb');
      fwrite($handle, $summaryDataDownloadGrid);
      fclose($handle);
      $downloadTab .= '<tr><td>' . lang::get('Download Summary Grid (CSV Format)') . ' : </td><td><a target="_blank" href="' . $base_url . '/' . $downloadsFolder . $cacheFile . '" download type="text/csv"><button type="button">' . lang::get('Download') . '</button></a></td></tr>' . "\n";
    }
    if ($hasData && $options['includeEstimatesGridDownload']) {
      if (!is_dir($downloadsFolder) || !is_writable($downloadsFolder)) {
        return lang::get('Internal Config error: directory {1} does not exist or is not writeable', $downloadsFolder);
      }
      $cacheFile = $options['downloadFilePrefix'] . 'estimateDataGrid' . $timestamp.'.csv';
      $handle = fopen($downloadsFolder.$cacheFile, 'wb');
      fwrite($handle, $estimateDataDownloadGrid);
      fclose($handle);
      $downloadTab .= '<tr><td>' . lang::get('Download Estimates Grid (CSV Format)') . ' : </td><td><a target="_blank" href="' . $base_url . '/' . $downloadsFolder . $cacheFile . '" download type="text/csv"><button type="button">' . lang::get('Download') . '</button></a></td></tr>' . "\n";
    }
    if ($hasRawData && $options['includeRawGridDownload']) {
      if (!is_dir($downloadsFolder) || !is_writable($downloadsFolder)) {
        return lang::get('Internal Config error: directory {1} does not exist or is not writeable', $downloadsFolder);
      }
      $cacheFile = $options['downloadFilePrefix'].'rawDataGrid' . $timestamp . '.csv';
      $handle = fopen($downloadsFolder.$cacheFile, 'wb');
      fwrite($handle, $rawDataDownloadGrid);
      fclose($handle);
      $downloadTab .= '<tr><td>' . lang::get('Download Raw Data Grid (CSV Format)')  .' : </td><td><a target="_blank" href="' . $base_url . '/' . $downloadsFolder . $cacheFile . '" download type="text/csv"><button type="button">' . lang::get('Download') . '</button></a></td></tr>' . "\n";
    }
    if ($hasData && count($options['downloads'])>0) {
      // format is assumed to be CSV
      global $indicia_templates;
      $indicia_templates['report_download_link'] = '<a target="_blank" href="{link}" download ><button type="button">' . lang::get('Download') . '</button></a>';

      $downloadExtraParams = [];
      // copy if set
      foreach(['survey_id', 'user_id', 'taxon_list_id', 'location_id', 'location_list', 'location_type_id', 'occattrs'] as $parameter) {
        if (isset($options['extraParams'][$parameter])) {
          $downloadExtraParams[$parameter] = $options['extraParams'][$parameter];
        }
      }
      foreach(['year', 'summary_user_id', 'summary_location_id'] as $parameter) {
        if (isset($options[$parameter])) {
          $downloadExtraParams[$parameter] = $options[$parameter];
        }
      }
      // 'date_to' and 'date_from' Deprecated
      foreach(['date_start' => 'date_from', 'date_end' => 'date_to'] as $key => $parameter) {
        if (isset($options[$key])) {
          $downloadExtraParams[$parameter] = $options[$key];
        }
      }
      // locattrs and smpattrs etc for download reports are all set in download specific presets
      $downloadOptions = [
        'readAuth' => $options['readAuth'],
        'itemsPerPage' => FALSE
      ];
      if (!isset($downloadExtraParams['user_id'])) {
        $downloadExtraParams['user_id'] = '';
      }
      foreach ($options['downloads'] as $download) {
        $downloadOptions['extraParams'] = array_merge(
            $downloadExtraParams,
            (isset($download['param_presets']) ? $download['param_presets'] : [])
        );
        $downloadOptions['dataSource'] = $download['dataSource'];
        $downloadOptions['filename'] = $download['filename'];
        $downloadTab .= '<tr><td>' . $download['caption'] . ' : </td><td>' .
            report_helper::report_download_link($downloadOptions) . '</td></tr>';
      }
    }

    if ($downloadTab !== "") {
      $tabs['#dataDownloads'] = lang::get('Downloads');
    }
    $r .= '<div id="controls">' .
        data_entry_helper::tab_header(['tabs' => $tabs]) .
          ($hasData ?
              '<div id="summaryData">' . $summaryTab . '</div>' .
              '<div id="summaryChart">' .
                '<div>' .
                  '<a class="btn btn-small btn-info copyCanvas" onClick="indiciaData.copyImageToClipboard(this, \'' . $options['chartID'] . '-summary\');">' .
                    lang::get('Copy summary chart to clipboard') .
                  '</a>' .
                '</div>' .
                '<div id="' . $options['chartID'] .'-summary" style="height:' . $options['height'] . 'px;' . (isset($options['width']) && $options['width'] != '' ? 'width:' . $options['width'] . 'px;'  :'') . '"></div>' .
                $summarySeriesPanel .
              '</div>' .
              '<div id="estimateData">' . $estimateTab . '</div>'.
              '<div id="estimateChart">' .
                '<div>' .
                  '<a class="btn btn-small btn-info copyCanvas" onClick="indiciaData.copyImageToClipboard(this, \'' . $options['chartID'] . '-estimates\');">' .
                    lang::get('Copy estimates chart to clipboard') .
                  '</a>' .
                '</div>' .
                '<div id="' . $options['chartID'] . '-estimates" style="height:' . $options['height'] . 'px;' . (isset($options['width']) && $options['width'] != '' ? 'width:' . $options['width'] . 'px;' : '').'"></div>' .
                $summarySeriesPanel .
              '</div>'
            : '<div id="summaryData"><p>' . lang::get('No data available for this period with these filter values.') . '</p></div>') .
          '<div id="rawData">' . $rawTab . '</div>'.
          ($downloadTab !== "" ? '<div id="dataDownloads"><table><tbody style="border:none;">' . $downloadTab . '</tbody></table></div>' : '').
        '</div>';
    data_entry_helper::enable_tabs(['divId' => 'controls']);
    return $r;
  }

  /**
   * Override the type of taxon label for some EBMS schemes
   *
   * EBMS ONLY. If user is member of scheme that specifies it should use common names
   * then override the taxon names to use common names in the specified language.
   *
   * @param array $readAuth Authorisation tokens.
   * @param array $records Records for report to display.
   * @param array $taxonColumnOverrides Configuration for which EBMS schemes should show
   * names in which language.
   * @return array Records array with altered names.
   */
  private static function injectLanguageIntoDefaultCommonName($readAuth, $records, $taxonColumnOverrides) {
    $taxonColumnOverrides = json_decode($taxonColumnOverrides, TRUE);
    $myId = hostsite_get_user_field('id');
    $schemes = ebms_scheme_list_user_schemes($myId);
    $mySchemeOverrideLangId = FALSE;
    if (count($schemes) > 0) {
      $userSchemeId = $schemes[0]['id'];
      // If we identify user as a scheme member then get the Warehouse language ID specified for the scheme
      if (!empty($userSchemeId) && !empty($taxonColumnOverrides[$userSchemeId])) {
        $mySchemeOverrideLangId = $taxonColumnOverrides[$userSchemeId];
      }
    }
    // Only override taxon labels if there is configuration for my scheme to do so
    if ($mySchemeOverrideLangId) {
      $recordsTaxonMeaningIds = [];
      // For all the records shown on the report, create an taxon meaning id array to pass to the Warehouse
      foreach ($records as $record) {
        $recordsTaxonMeaningIds[] = $record['taxon_meaning_id'];
      }
      if (!empty($recordsTaxonMeaningIds)) {
        // Get taxon rows associated with the report in the language we want
        $taxonRowsInDetail = data_entry_helper::get_population_data([
          'table' => 'taxa_taxon_list',
          'nocache' => TRUE,
          'extraParams' => $readAuth + [
            'query' => json_encode(['in' => [
              'taxon_meaning_id' => $recordsTaxonMeaningIds,
              'language_id' => [$mySchemeOverrideLangId]
              ]]),
            'view' => 'detail'
          ]
        ]);
        // Process the data so that the meaning id is the array key, and the name
        // in the language we want is the value
        foreach($taxonRowsInDetail as $taxonRowInDetail) {
          $meaningsWithLangCommonNames[$taxonRowInDetail['taxon_meaning_id']] = $taxonRowInDetail['taxon'];
        }
        // Now simply ovewrite the names in the report
        foreach ($records as $recordIdx => $record) {
          $records[$recordIdx]['preferred_taxon'] = $meaningsWithLangCommonNames[$record['taxon_meaning_id']];
          $records[$recordIdx]['default_common_name'] = $meaningsWithLangCommonNames[$record['taxon_meaning_id']];
        }
      }
    }
    return $records;
  }

  static function report_calendar_summary_sort1($a, $b)
  {
    // No sort order > end of list, sorted by label
    if (empty($a['order'])) {
      return empty($b['order']) ? strnatcasecmp($a['label'], $b['label']) : 1;
    }
    return empty($b['order']) ? -1 : ($a['order'] <= $b['order'] ? -1 : 1);
  }

}