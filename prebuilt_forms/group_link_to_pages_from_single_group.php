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
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/indicia-team/client_helpers/
 */

use IForm\prebuilt_forms\PageType;

require_once 'includes/dynamic.php';
require_once 'includes/groups.php';

/**
 * A page for listing a series of links to the pages related to a particular group.
 */
class iform_group_link_to_pages_from_single_group extends iform_dynamic {

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_group_link_to_pages_from_single_group_definition() {
    return [
      'title' => 'Group link to pages from single group',
      'category' => 'Recording groups',
      'description' => 'Display a list of page links on a page for a single recording group.',
      'supportsGroups'=>true,
      'recommended' => true
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function getPageType(): PageType {
    return PageType::Utility;
  }

  public static function get_parameters() {
    $retVal = array_merge(
      array(
        array(
          'name' => 'group_id',
          'caption' => 'Group For Page',
          'description' => 'Id of the group you wish to display links for',
          'type' => 'string',
          'group' => 'Page Group',
          'required'=>false
        ),
        array(
          'name' => 'instructions_configuration',
          'caption' => 'Link Names And Instructions Configuration',
          'description'=>
            'For each title you wish to specify instructions for, simply type the title inside square brackets [] '.
            'and then type the instruction to appear on the following lines (Note that titles without instructions are also allowed) e.g.<br>
            [Group Administration]<br>
            This link takes you to a page where the recording group can be setup.<br>
            [Group Records]<br>
            Display records associated with the group.<br>',
          'type' => 'textarea',
          'group' => 'User Interface',
          'required'=>false
        ),
        array(
          'name' => 'no_group_found_message',
          'caption' => 'Message displayed when user is not group member',
          'description' => 'When the user is not a group member there are no links to display, display this message instead. Supports html.',
          'type' => 'textarea',
          'group' => 'User Interface',
          'required'=>false
        ),
      )
    );
    return $retVal;
  }

  /**
   * Return the generated form output.
   * @param array $args List of parameter values passed through to the form depending on how the form has been configured.
   * This array always contains a value for language.
   * @param object $nid The Drupal node object's ID.
   * @param array $response When this form is reloading after saving a submission, contains the response from the service call.
   * Note this does not apply when redirecting (in this case the details of the saved object are in the $_GET data).
   * @return Form HTML.
   * @todo: Implement this method
   */
  public static function get_form($args, $nid, $response=null) {
    if (empty($args['group_id'])) {
      hostsite_show_message('Please specify a group_id in the page configuration.');
    }
    if (empty($args['instructions_configuration'])) {
      hostsite_show_message('Please provide a page configuration in the User Interface options.');
    }
    //Only perform if the user has specified an instruction to appear under each page like.
    if (!empty($args['instructions_configuration'])) {
      $configuration = data_entry_helper::explode_lines($args['instructions_configuration']);
      $key='';
      $description='';
      //Keep track of the ordering of the titles
      $titleNumber=0;
      //For each configured line we need to find all the descriptions and store them against the page titles in an array
      foreach ($configuration as $configLineNum => $configurationLine) {
        //If line is a link title (specified inside square brackets)
        if (preg_match('/^\[.+\]$/', $configurationLine)) {
          //If this isn't the first title, then we need to store the description for the previous title into an
          //array. The key is a number representing the order of the titles in the configuration, the sub array key is the name of the page link.
          if (!empty($key)) {
            $titleDescriptions[$titleNumber]=array($key=>$description);
            $description='';
          }
          //Get the next array key we will use from the specified page link title. Chop the square brackets off the ends.
          $key = substr($configurationLine, 1, -1);
          $titleNumber++;
        } else {
          //If the line does not use square brackets then we know it is part of the description/instruction. We do an
          //append as the instruction might span several lines.
          $description.=$configurationLine;
        }
      }
      //For the last description we still need to save it to the array.
      $titleDescriptions[$titleNumber]=array($key=>$description);
      $description='';
    }
    $r = '';
    global $user;
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    // Get all the links to display.
    $reportOptions = array(
      'dataSource' => 'library/groups/groups_list',
      'readAuth'=>$auth['read'],
      'mode' => 'report',
      'extraParams' => array('currentUser'=>hostsite_get_user_field('indicia_user_id'), 'id'=>$args['group_id'],
          'pending_path' => '{rootFolder}?q=groups/pending&group_id=','userFilterMode' => 'member')
    );
    // Automatic handling for Drupal clean urls.
    $rootFolder = helper_base::getRootFolder(true);
    iform_load_helpers(['report_helper']);
    $groupsData = report_helper::get_report_data($reportOptions);
    if (empty($groupsData)) {
      if (!empty($args['no_group_found_message'])) {
        $r = '<div>'.$args['no_group_found_message'].'</div>';
      } else {
        $r = '<div>Sorry, you do not appear to be a member of this group so there are no links to display.</div>';
      }
      return $r;
    }
    foreach ($groupsData as $groupDataItem) {
      $pageLinks = $groupDataItem['pages'];
      $groupTitle = $groupDataItem['title'];
    }
    //All the page links come out of the database in one cluster. Explode these so we have each link separately
    $explodedPageLinks = explode('</a>',$pageLinks);
    // reinsert the closing </a> used in the explode above
    foreach ($explodedPageLinks as &$pageLink)
      $pageLink .= '</a>';
    $pageLinkHtml='';
    //Go through all the page links to display
    foreach ($titleDescriptions as $titleDescArr) {
      foreach ($explodedPageLinks as &$pageLink) {
        //Each page link is a html link, we just want the plain name
        $plainPageLink=trim(strip_tags($pageLink));
        //If the user has specified an instruction/description for the page link, then display the instruction in the lines following the link
        //using italics.
        if (array_key_exists($plainPageLink,$titleDescArr)) {
          if (!empty($titleDescArr[$plainPageLink])) {
            $pageLinkHtml .= "<h3>$pageLink</a></h3><p>{$titleDescArr[$plainPageLink]}</p>\n";
          } else {
            $pageLinkHtml .= "<h3>$pageLink</a></h3>";
          }
        }
      }
    }

    $r = "<div><h2>$groupTitle Links</h2>";
    $r .= str_replace(array('{rootFolder}','{sep}'),
        array($rootFolder, strpos($rootFolder, '?')===FALSE ? '?' : '&'), $pageLinkHtml);
    $r .= '</div><br>';
    $r .= parent::get_form($args, $nid, $response=null);
    return $r;
  }
}
