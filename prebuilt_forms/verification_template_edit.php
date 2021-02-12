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
 * @package    Client
 * @subpackage PrebuiltForms
 * @author     Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link       https://github.com/Indicia-Team/
 */

/**
 * Prebuilt Indicia data entry form.
 * NB has Drupal specific code.
 *
 * @package    Client
 * @subpackage PrebuiltForms
 */

require_once('includes/dynamic.php');

class iform_verification_template_edit extends iform_dynamic {

    /**
     * Return the form metadata.
     * @return array The definition of the form.
     */
    public static function get_verification_template_edit_definition() {
        return array(
            'title'=>'Create or edit a verification template',
            'category' => 'Verification',
            'description'=>'A form for creating or editing verification templates. ' .
            'Although based on dynamic, this form is not customisable.',
            'recommended' => true,
        );
    }

    /**
     * Get the list of parameters for this form.
     * @return array List of parameters that this form requires.
     */
    public static function get_parameters() {
        $retVal = array_merge(
            parent::get_parameters(),
            array(
                array(
                    'name' => 'taxon_rank_break_point',
                    'caption' => 'Taxon Rank Break Point',
                    'description' => 'This is the break point value in the species list between normal species and familes. ' .
                    'Value is the max value for families, start point for species is one more than this.',
                    'type'=>'int',
                    'required'=>true,
                    'default' => 180,
                    'group' => 'User Interface',
                ),
                array(
                    'name'=>'taxon_display_field',
                    'caption'=>'Field used to display taxa',
                    'description'=>'When viewing the lists of keys, it is possible to display one taxon and a count of other ' .
                    'taxa this key applies to. Use this control to choose whther to display a taxon, and if ' .
                    'so, which field to use.',
                    'type'=>'select',
                    'options' => array(
                        'off' => 'Do not display a taxon.',
                        'preferred_taxon' => 'Preferred name of the taxon',
                        'default_common_name' => 'Common name of the taxon'
                    ),
                    'required'=>true,
                    'group' => 'User Interface',
                ),
                array(
                    'name'=>'taxon_list_id',
                    'label'=>'Species List',
                    'helpText'=>'The species list that species can be selected from.',
                    'type'=>'select',
                    'table'=>'taxon_list',
                    'valueField'=>'id',
                    'captionField'=>'title',
                    'required'=>true,
                    'group' => 'User Interface',
                )
            )
            );
        for ($i = count($retVal)-1; $i >= 0; $i--) {
            if ((isset($retVal[$i]['group']) &&
                ($retVal[$i]['group'] === 'Initial Map View' ||
                    $retVal[$i]['group'] === 'Base Map Layers' ||
                    $retVal[$i]['group'] === 'Advanced Base Map Layers' ||
                    $retVal[$i]['group'] === 'Other Map Settings' ||
                    $retVal[$i]['group'] === 'Georeferencing')) ||
                $retVal[$i]['name'] === 'structure' ||
                $retVal[$i]['name'] === 'survey_id' ||
                $retVal[$i]['name'] === 'high_volume' ||
                $retVal[$i]['name'] === 'redirect_on_success' ||
                $retVal[$i]['name'] === 'interface' ||
                $retVal[$i]['name'] === 'tabProgress' ||
                $retVal[$i]['name'] === 'force_next_previous' ||
                $retVal[$i]['name'] === 'attribute_termlist_language_filter'  ||
                $retVal[$i]['name'] === 'no_grid' ||
                $retVal[$i]['name'] === 'save_button_below_all_pages') {
                    unset($retVal[$i]);
                }
        }
        return $retVal;
    }

    /**
     * Return the generated form output.
     * @param array $args List of parameter values passed through to the form depending on how the form has been configured.
     * This array always contains a value for language.
     * @param object $nid The Drupal node object's ID.
     * @return Form HTML.
     */
    public static function get_form($args, $nid) {
        if (!hostsite_get_user_field('indicia_user_id')) {
            return 'Please ensure that you\'ve filled in your surname on your user profile before creating or editing groups.';
        }
        // Fix interface
        $args['structure'] = "=Template=\r\n" .
            "[title]\r\n" .
            "[template statuses]\r\n" .
            "[restrict to website]\r\n" .
            "[restrict to external keys]\r\n" .
            "@id=external-keys\r\n" .
            "@description=external keys\r\n" .
            "@fieldName=verification_template:restrict_to_external_keys\r\n" .
            "@rankExtraParam=min_taxon_rank_sort_order\r\n" .
            "@rankOffset=1\r\n" .
            "[restrict to external keys]\r\n" .
            "@id=family-keys\r\n" .
            "@description=external family level keys\r\n" .
            "@fieldName=verification_template:restrict_to_family_external_keys\r\n" .
            "@rankExtraParam=max_taxon_rank_sort_order\r\n" .
            "@rankOffset=0\r\n" .
            "@preferred=t\r\n" .
            "[template]";
        $args['interface'] = 'one_page';
        return parent::get_form($args, $nid);
    }

    /**
     * Get the recorder names control
     * @param array $auth Read authorisation tokens
     * @param array $args Form configuration
     * @param array $tabAlias
     * @param array $options additional options for the control
     * @return string HTML for the control.
     */
    protected static function get_control_title($auth, $args, $tabAlias, $options) {
        return data_entry_helper::text_input(array(
            'label' => lang::get('Template Title'),
            'fieldname' => 'verification_template:title',
            'validation' => array('required'),
            'class' => 'wide',
        ));
    }

    /**
     * Get the template statuses control.
     * @param array $auth Read authorisation tokens
     * @param array $args Form configuration
     * @param array $tabAlias
     * @param array $options additional options for the control
     * @return string HTML for the control.
     */
    protected static function get_control_templatestatuses($auth, $args, $tabAlias, $options) {
        return '<fieldset><legend>Template Statuses</legend>'.
            data_entry_helper::checkbox_group(array(
                'label' => 'Template Statuses',
                'fieldname' => 'verification_template:template_statuses[]',
                'lookupValues' => array(
                    'V' => lang::get('Accepted (V)'),
                    'V1' => lang::get('Accepted as correct (V1)'),
                    'V2' => lang::get('Accepted as considered correct (V2)'),
                    'C3' => lang::get('Plausible (C3)'),
                    'R' => lang::get('Not accepted (R)'),
                    'R4' => lang::get('Not accepted as unable to verify (R4)'),
                    'R5' => lang::get('Not accepted as incorrect (R5)'),
                ),
                'helpText' => lang::get('Choose which verification status changes this template will available for. ' .
                    'Note that a template available for "V" will also be available for "V1" and "V2", and similar for "R" and "R4"/"R5".'),
                'validation' => array('required'),
                'default' => isset(data_entry_helper::$entity_to_load['verification_template:template_statuses']) ?
                    data_entry_helper::$entity_to_load['verification_template:template_statuses'] : array(),
            )) .
            '</fieldset>';
    }

    /**
     * Get the restrict to website control.
     * @param array $auth Read authorisation tokens
     * @param array $args Form configuration
     * @param array $tabAlias
     * @param array $options additional options for the control
     * @return string HTML for the control.
     */
    protected static function get_control_restricttowebsite($auth, $args, $tabAlias, $options) {
        return data_entry_helper::checkbox(array(
            'label' => lang::get('Restrict to this website'),
            'class' => 'verification_website_restriction',
            'fieldname' => 'verification_template:restrict_to_website_id',
            'helpText' => lang::get('Select this option if you want this template to only apply to records from this website.'),
        ));
    }

    /**
     * Get the template control.
     * @param array $auth Read authorisation tokens
     * @param array $args Form configuration
     * @param array $tabAlias
     * @param array $options additional options for the control
     */
    protected static function get_control_template($auth, $args, $tabAlias, $options) {
        return data_entry_helper::textarea(array(
            'label' => lang::get('Template'),
            'fieldname' => 'verification_template:template',
            'rows' => 8,
            'helpText' => lang::get('Substitutions may be added using the format &quot;{{ &lt;string&gt; }}&quot;, where &lt;string&gt; can be one of the following: &quotaction&quot (represents this verification event, e.g. accepted), &quotdate&quot, &quotentered sref&quot, &quotspecies&quot, &quotcommon name&quot, &quotpreferred name&quot, or &quotlocation name&quot'),
        )
            );
    }

    /**
     * Get the restrict to external keys control.
     * @param array $auth Read authorisation tokens
     * @param array $args Form configuration
     * @param array $tabAlias
     * @param array $options additional options for the control
     */
    protected static function get_control_restricttoexternalkeys($auth, $args, $tabAlias, $options) {
        $baseParams = $auth['read'] + array('taxon_list_id' => $args['taxon_list_id']);
        $baseParams[$options['rankExtraParam']] =  $args['taxon_rank_break_point'] + $options['rankOffset'];
        if(isset($options['preferred'])) {
            $baseParams['preferred'] = $options['preferred'];
        }
        $subListOptions = array(
            'fieldname' => $options['fieldName'],
            'autocompleteControl' => 'species_autocomplete',
            'captionField' => 'searchterm',
            'captionFieldInEntity' => 'searchterm',
            'speciesIncludeBothNames' => TRUE,
            'speciesIncludeTaxonGroup' => TRUE,
            'valueField' => 'external_key',
            'extraParams' => $baseParams,
            'addToTable' => FALSE,
            'helpText' => lang::get('Previously selected entries will show as the key, with a single relevant taxon and a count of any other taxa (if applicable). Anything added during this editing session will display as just the taxon. The list will be simplified on saving to remove keys duplicates.'),
        );
        return '<fieldset><legend>' . ucfirst($options['description']) . '</legend>' .
            '<div id="' . $options['id'] . '">' . PHP_EOL .
            '<p>' .
            lang::get('Use the following control to search for taxa: these are used to build a list of {1} this template ' .
                      'will apply to.', $options['description']) .
            '</p>' . PHP_EOL .
            data_entry_helper::sub_list($subListOptions) . PHP_EOL .
            '</div></fieldset>' . PHP_EOL;
    }

    /**
     * Retrieve the path to the current page, so the form can submit to itself.
     * @return string
     */
    protected static function getReloadPath () {
        $reload = data_entry_helper::get_reload_link_parts();
        unset($reload['params']['id']);
        unset($reload['params']['new']);
        // if editing a group record, ensure group in URL on form post so it carries on to the next record input.
        $reloadPath = $reload['path'];
        if(count($reload['params'])) {
            // decode params prior to encoding to prevent double encoding.
            foreach ($reload['params'] as $key => $param) {
                $reload['params'][$key] = urldecode($param);
            }
            $reloadPath .= '?' . http_build_query($reload['params']);
        }
        return $reloadPath;
    }

    /**
     * Determine whether to show a grid of existing records or a form for either adding a new record or editing an existing one.
     * @param array $args iform parameters.
     * @param object $nid ID of node being shown.
     * @return const The mode [MODE_GRID|MODE_NEW|MODE_EXISTING].
     */
    protected static function getMode($args, $nid) {
        // Default to mode MODE_GRID or MODE_NEW depending on no_grid parameter
        $mode = (isset($args['no_grid']) && $args['no_grid']) ? self::MODE_NEW : self::MODE_GRID;

        if ($_POST && !is_null(data_entry_helper::$entity_to_load)) {
            // errors with new sample or entity populated with post, so display this data.
            $mode = self::MODE_EXISTING;
        } else if ($_POST) {
            $mode = self::MODE_GRID;
        } else if (array_key_exists('id', $_GET)){
            // request for display of existing record
            $mode = self::MODE_EXISTING;
        } else if (array_key_exists('new', $_GET)){
            // request to create new record (e.g. by clicking on button in grid view)
            $mode = self::MODE_NEW;
            data_entry_helper::$entity_to_load = array();
        }
        return $mode;
    }

    /**
     * Construct a grid of existing records.
     * @param array $args iform parameters.
     * @param object $nid ID of node being shown.
     * @param array $auth authentication tokens for accessing the warehouse.
     * @return string HTML for grid.
     */
    protected static function getGrid($args, $nid, $auth) {
        iform_load_helpers(['report_helper']);
        return '<div id="templateList">' .
            report_helper::report_grid(
                array(
                    'id' => 'templates-grid',
                    'dataSource' => 'library/verification_templates/verification_templates_for_report_grid',
                    'mode' => 'report',
                    'readAuth' => $auth['read'],
                    'columns' => self::get_report_actions(),
                    'itemsPerPage' => (isset($args['grid_num_rows']) ? $args['grid_num_rows'] : 10),
                    'autoParamsForm' => true,
                    'extraParams' => array('website_id' => $args['website_id']),
                )) .
                '<form>' .
                '<input type="button" value="' . lang::get('Create new template') . '" ' .
                'onclick="window.location.href=\'' . hostsite_get_url('node/'.$nid, array('new' => '1')) . '\'">' .
                '</form>' .
                '</div>';
    }

    /**
     * Prepare to display an existing verification template: populate data_entry_helper::$entity_to_load
     * @param array $args iform parameters.
     * @param array $auth authentication tokens for accessing the warehouse.
     */
    protected static function getEntity($args, $auth) {
        data_entry_helper::$entity_to_load = array();
        if (isset($_GET['id'])) {
            data_entry_helper::load_existing_record($auth['read'], 'verification_template', $_GET['id'], 'list', false, false);
            $keys = self::array_parse(data_entry_helper::$entity_to_load['verification_template:restrict_to_external_keys']);
            data_entry_helper::$entity_to_load['verification_template:restrict_to_external_keys'] = array();
            foreach ($keys as $key) {
                if ($args['taxon_display_field'] !== 'off') {
                    $species = data_entry_helper::get_population_data(
                        array(
                            'table' => 'cache_taxa_taxon_list',
                            'extraParams' => $auth['read'] + array('external_key' => $key, 'preferred'=>'t'),
                        )
                        );
                    $numRecords = count($species);
                    if ($numRecords) {
                        $name = $species[0][$args['taxon_display_field']];
                        if(empty($name)) {
                            $name = $species[0]['taxon'];
                        }
                    }
                } else {
                    $numRecords = 0;
                }
                data_entry_helper::$entity_to_load['verification_template:restrict_to_external_keys'][] =
                array(
                    'caption' => $key .
                    ($numRecords === 0 ? '' :
                        ' (' . $name . ($numRecords === 1 ? '' :
                            ($numRecords === 2 ? lang::get(' and one other') : lang::get(' and {1} others', $numRecords-1))) .
                        ')'),
                    'default' => $key,
                    'fieldname' => 'verification_template:restrict_to_external_keys[]',
                );
            }

            $keys = self::array_parse(data_entry_helper::$entity_to_load['verification_template:restrict_to_family_external_keys']);
            data_entry_helper::$entity_to_load['verification_template:restrict_to_family_external_keys'] = array();
            foreach ($keys as $key) {
                if ($args['taxon_display_field'] !== 'off') {
                    $species = data_entry_helper::get_population_data(
                        array(
                            'table' => 'cache_taxa_taxon_list',
                            'extraParams' => $auth['read'] + array('external_key' => $key, 'preferred'=>'t'),
                        )
                        );
                    $numRecords = count($species);
                    if ($numRecords) {
                        $name = $species[0][$args['taxon_display_field']];
                        if(empty($name)) {
                            $name = $species[0]['taxon'];
                        }
                    }
                } else {
                    $numRecords = 0;
                }
                data_entry_helper::$entity_to_load['verification_template:restrict_to_family_external_keys'][] =
                array(
                    'caption' => $key .
                    ($numRecords === 0 ? '' :
                        ' (' . $name . ($numRecords === 1 ? '' :
                            ($numRecords === 2 ? lang::get(' and one other') : lang::get(' and {1} others', $numRecords-1))) .
                        ')'),
                    'default' => $key,
                    'fieldname' => 'verification_template:restrict_to_family_external_keys[]'
                );
            }

            $statuses = self::array_parse(data_entry_helper::$entity_to_load['verification_template:template_statuses']);
            data_entry_helper::$entity_to_load['verification_template:template_statuses'] = $statuses;

        }
    }

    /**
     * Parse a single dimension postgres array represented as a string into a PHP array.
     *
     * @param string $s Postgres array represented as a string.
     * @return array Array.
     */
    private static function array_parse($s, $start = 0, &$end = null)
    {
        if (empty($s) || $s[0] != '{') {
            return array();
        }
        $return = array();
        $string = false;
        $quote = '';
        $s = str_replace('&quot;', '"', $s);
        $len = strlen($s);
        $v = '';
        for ($i = $start+1; $i < $len; $i++) {
            $ch = $s[$i];
            if (!$string && $ch == '}') {
                if ($v !== '' || !empty($return)) {
                    $return[] = $v;
                }
                $end = $i;
                break;
            } elseif (!$string && $ch == ',') {
                $return[] = $v;
                $v = '';
            } elseif (!$string && ($ch == '"' || $ch == "'")) {
                $string = true;
                $quote = $ch;
            } elseif ($string && $ch == $quote && $s[$i - 1] == "\\") {
                $v = substr($v, 0, -1) . $ch;
            } elseif ($string && $ch == $quote && $s[$i - 1] != "\\") {
                $string = false;
            } else {
                $v .= $ch;
            }
        }
        return $return;
    }

    /**
     * Retrieve the additional HTML to appear at the top of the first
     * tab or form section. This is a set of hidden inputs containing the website ID and any existing template's ID.
     * @param array $args iform parameters.
     * @param array $auth authentication tokens for accessing the warehouse.
     * @param array $attributes for this entity type: not applicable for verification_templates
     * @return string HTML to be added.
     */
    protected static function getFormHiddenInputs($args, $auth, &$attributes) {
        // Get authorisation tokens to update the Warehouse, plus any other hidden data.
        // no survey
        $r = $auth['write'] .
        '<input type="hidden" id="website_id" name="website_id" value="' . $args['website_id'] . '" />' . PHP_EOL;
        if (isset(data_entry_helper::$entity_to_load['verification_template:id'])) {
            $r .= '<input type="hidden" id="verification_template:id" name="verification_template:id" value="' .
                data_entry_helper::$entity_to_load['verification_template:id'] . '" />' . PHP_EOL;
        }
        return $r;
    }

    /**
     * Handles the construction of a submission array from a set of form values.
     * @param array $values Associative array of form data values.
     * @param array $args iform parameters.
     * @return array Submission structure.
     */
    public static function get_submission($values, $args) {
        $struct=array(
            'model' => 'verification_template'
        );

        if(isset($values['verification_template:restrict_to_external_keys'])) {
            $l = count($values['verification_template:restrict_to_external_keys']);
            for ($i = $l-1; $i >= 0; $i--) {
                if($values['verification_template:restrict_to_external_keys'][$i] === '') {
                    unset($values['verification_template:restrict_to_external_keys'][$i]);
                }
            }
            $values['verification_template:restrict_to_external_keys'] =
            array_unique(array_values($values['verification_template:restrict_to_external_keys']));
        } else {
            $values['verification_template:restrict_to_external_keys'] = array();
        }

        if(isset($values['verification_template:restrict_to_family_external_keys'])) {
            $l = count($values['verification_template:restrict_to_family_external_keys']);
            for ($i = $l-1; $i >= 0; $i--) {
                if($values['verification_template:restrict_to_family_external_keys'][$i] === '') {
                    unset($values['verification_template:restrict_to_family_external_keys'][$i]);
                }
            }
            $values['verification_template:restrict_to_family_external_keys'] =
            array_unique(array_values($values['verification_template:restrict_to_family_external_keys']));
        } else {
            $values['verification_template:restrict_to_family_external_keys'] = array();
        }

        return submission_builder::build_submission($values, $struct);
    }

    /**
     * Provide actions to edit the linked verification_template
     * @return array report actions structure.
     */
    protected static function get_report_actions() {
        return array(
            array(
                'display' => lang::get('Actions'),
                'actions' =>  array(
                    array(
                        'caption' => lang::get('Edit'),
                        'url' => '{currentUrl}',
                        'urlParams' => array('id'=>'{id}')
                    )
                )
            )
        );
    }

    /**
     * Override the default submit buttons to add a delete button where appropriate.
     * @param array $args iform parameters.
     * @return string HTML defining the buttons.
     */
    protected static function getSubmitButtons($args) {
        global $indicia_templates;
        $r = '<input type="submit" class="' . $indicia_templates['buttonDefaultClass'] . '" id="save-button" value="' .
            lang::get('Submit') . "\" />\n";
            if (!empty(data_entry_helper::$entity_to_load['verification_template:id'])) {
                // use a button here, not input, as Chrome does not post the input value
                $r .= '<button type="submit" class="' . $indicia_templates['buttonWarningClass'] .
                '" id="delete-button" name="delete-button" value="delete" >' . lang::get('Delete') . "</button>\n";
                data_entry_helper::$javascript .= "$('#delete-button').click(function(e) {
    if (!confirm(\"Are you sure you want to delete this template?\")) {
      e.preventDefault();
      return false;
    }
  });\n";
                // ??? Cancel button
            }
            return $r;
    }

}

