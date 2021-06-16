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
 * @link https://github.com/Indicia-Team/client_helpers
 */

require_once 'includes/map.php';

/**
 * A form for subscribing to notifications when a certain species is recorded.
 */
class iform_subscribe_species_alert {

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_subscribe_species_alert_definition() {
    return [
      'title' => 'Subscribe to a species alert',
      'category' => 'Miscellaneous',
      'description' => 'Provides a simple form for picking a species and optional geographic filter to subscribe to receive an alert notification ' .
          'when that species is recorded or verified.'
    ];
  }

  /**
   * Get the list of parameters for this form.
   *
   * @return array
   *   List of parameters that this form requires.
   */
  public static function get_parameters() {
    return array_merge(
      iform_map_get_map_parameters(),
      [
        [
          'fieldname' => 'list_id',
          'label' => 'Species list ',
          'helpText' => 'The species list that individual species can be picked from to receive alerts for.',
          'type' => 'checkbox_group',
          'table' => 'taxon_list',
          'valueField' => 'id',
          'captionField' => 'title',
          'required' => FALSE,
          'group' => 'Lookups',
          'siteSpecific' => TRUE,
        ],
        [
          'name' => 'full_lists',
          'caption' => 'Available full lists',
          'description' => 'Tick the species lists that allow users to receive alerts for any species in the list.',
          'type' => 'checkbox_group',
          'table' => 'taxon_list',
          'valueField' => 'id',
          'captionField' => 'title',
          'sharing' => 'reporting',
          'required' => FALSE,
          'group' => 'Lookups',
          'siteSpecific' => TRUE,
        ],
        [
          'fieldname' => 'location_type_id',
          'label' => 'Location type',
          'helpText' => 'The location type available to filter against. This location type must be indexed by the warehouse\'s spatial_index_builder module.',
          'type' => 'checkbox_group',
          'table' => 'termlists_term',
          'valueField' => 'id',
          'captionField' => 'term',
          'extraParams' => ['termlist_external_key' => 'indicia:location_types'],
          'required' => FALSE,
          'group' => 'Lookups',
          'siteSpecific' => TRUE,
        ],
        [
          'fieldname' => 'location_control',
          'label' => 'Location selection control',
          'helpText' => 'For larger lists of locations using an autocomplete search box is easier than a drop-down select.',
          'type' => 'select',
          'options' => [
            'select' => 'Drop-down select',
            'autocomplete' => 'Autocomplete search box',
          ],
          'requred' => TRUE,
          'default' => 'select',
          'group' => 'Lookups',
        ],
      ]
    );
  }

  /**
   * Return the generated form output.
   *
   * @param array $args
   *   List of parameter values passed through to the form depending on how the
   *   form has been configured. This array always contains a value for
   *   language.
   * @param object $nid
   *   The Drupal node object's ID.
   * @param array $response
   *   When this form is reloading after saving a submission, contains the
   *   response from the service call. Note this does not apply when
   *   redirecting (in this case the details of the saved object are in the
   *   $_GET data).
   *
   * @return string
   *   Form HTML.
   */
  public static function get_form($args, $nid, $response = NULL) {
    $form = '<form action="#" method="POST" id="entry_form">';
    if ($_POST) {
      $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
      self::subscribe($args, $auth);
    }
    else {
      // Don't bother with write auth for initial form load, as read auth is
      // cached and faster.
      $auth = ['read' => data_entry_helper::get_read_auth($args['website_id'], $args['password'])];
    }
    if (!empty($_GET['id'])) {
      data_entry_helper::load_existing_record($auth['read'], 'species_alert', $_GET['id']);
      // Enforce permissions.
      if (data_entry_helper::$entity_to_load['species_alert:user_id'] !== hostsite_get_user_field('indicia_user_id')) {
        return lang::get('You cannot modify a species alert subscription created by someone else');
      }
      $form .= data_entry_helper::hidden_text([
        'fieldname' => 'species_alert:id',
        'default' => $_GET['id'],
      ]);
    }
    // If not logged in, then ask for details to register against.
    global $user;
    if (!hostsite_get_user_field('id') || !isset($user) || empty($user->mail) || !hostsite_get_user_field('last_name')) {
      $form .= "<fieldset><legend>" . lang::get('Your details') . ":</legend>\n";
      $default = empty($_POST['first_name']) ? hostsite_get_user_field('first_name', '') : $_POST['first_name'];
      $form .= data_entry_helper::text_input([
        'label' => lang::get('First name'),
        'fieldname' => 'first_name',
        'validation' => ['required'],
        'default' => $default,
        'class' => 'control-width-4',
      ]);
      $default = empty($_POST['surname']) ? hostsite_get_user_field('last_name', '') : $_POST['surname'];
      $form .= data_entry_helper::text_input([
        'label' => lang::get('Last name'),
        'fieldname' => 'surname',
        'validation' => ['required'],
        'default' => $default,
        'class' => 'control-width-4',
      ]);
      $default = empty($_POST['email']) ? (empty($user->mail) ? '' : $user->mail) : $_POST['email'];
      $form .= data_entry_helper::text_input([
        'label' => lang::get('Email'),
        'fieldname' => 'email',
        'validation' => ['required', 'email'],
        'default' => $default,
        'class' => 'control-width-4',
      ]);
      $form .= "</fieldset>\n";
    }
    else {
      $form .= data_entry_helper::hidden_text([
        'fieldname' => 'first_name',
        'default' => hostsite_get_user_field('first_name'),
      ]);
      $form .= data_entry_helper::hidden_text([
        'fieldname' => 'surname',
        'default' => hostsite_get_user_field('last_name'),
      ]);
      $form .= data_entry_helper::hidden_text([
        'fieldname' => 'email',
        'default' => $user->mail,
      ]);
      $form .= data_entry_helper::hidden_text([
        'fieldname' => 'user_id',
        'default' => hostsite_get_user_field('indicia_user_id'),
      ]);
    }
    $form .= "<fieldset><legend>" . lang::get('Alert criteria') . ":</legend>\n";
    // Output the species selection control
    // Default after saving with a validation failure can be pulled direct from the post, but
    // when reloading we don't need a default taxa taxon list ID since we already know the meaning
    // ID or external key.
    $default = empty($_POST['taxa_taxon_list_id']) ? '' : $_POST['taxa_taxon_list_id'];
    if (empty($_POST['taxa_taxon_list_id:taxon'])) {
      $defaultCaption = empty(data_entry_helper::$entity_to_load['species_alert:preferred_taxon']) ?
        '' : data_entry_helper::$entity_to_load['species_alert:preferred_taxon'];
    } else {
      $defaultCaption = $_POST['taxa_taxon_list_id:taxon'];
    }
    $form .= data_entry_helper::species_autocomplete([
      'label' => lang::get('Alert species'),
      'helpText' => lang::get(
          'Select the species you are interested in receiving alerts in ' .
          'relation to if you want to receive alerts on a single species.'
      ),
      'fieldname' => 'taxa_taxon_list_id',
      'extraParams' => $auth['read'] + ['taxon_list_id' => json_encode($args['list_id'])],
      'class' => 'control-width-4',
      'default' => $default,
      'defaultCaption' => $defaultCaption,
    ]);
    if (empty($default)) {
      // Unless we've searched for the species name then posted (and failed), then the
      // default will be empty. We might therefore be reloading existing data which has
      // a meaning ID or external key.
      if (!empty(data_entry_helper::$entity_to_load['species_alert:external_key'])) {
        $form .= data_entry_helper::hidden_text([
          'fieldname' => 'species_alert:external_key',
          'default' => data_entry_helper::$entity_to_load['species_alert:external_key'],
        ]);
      }
      elseif (!empty(data_entry_helper::$entity_to_load['species_alert:taxon_meaning_id'])) {
        $form .= data_entry_helper::hidden_text([
          'fieldname' => 'species_alert:taxon_meaning_id',
          'default' => data_entry_helper::$entity_to_load['species_alert:taxon_meaning_id'],
        ]);
      }
    }
    if (!empty($args['full_lists'])) {
      $form .= data_entry_helper::select([
        'label' => lang::get('Select full species lists'),
        'helpText' => lang::get('If you want to restrict the alerts to records of any ' .
            'species within a species list, then select the list here.'),
        'fieldname' => 'species_alert:taxon_list_id',
        'blankText' => lang::get('<Select a species list>'),
        'table' => 'taxon_list',
        'valueField' => 'id',
        'captionField' => 'title',
        'extraParams' => $auth['read'] + [
          'id' => $args['full_lists'],
          'orderby' => 'title',
        ],
        'sharing' => 'reporting',
        'class' => 'control-width-4',
      ]);
    }
    if (empty($args['location_control']) || $args['location_control'] === 'autocomplete') {
      $form .= data_entry_helper::location_autocomplete([
        'label' => lang::get('Select location'),
        'helpText' => lang::get('If you want to restrict the alerts to records within a certain boundary, search for it it here.'),
        'fieldname' => 'species_alert:location_id',
        'id' => 'imp-location',
        'valueField' => 'id',
        'captionField' => 'name',
        'extraParams' => $auth['read'] + [
          'query' => json_encode([
            'in' => ["location_type_id", $args['location_type_id']],
          ]),
          'orderby' => 'name',
        ],
        'class' => 'control-width-4',
      ]);
    }
    else {
      $form .= data_entry_helper::location_select([
        'label' => lang::get('Select location'),
        'helpText' => lang::get('If you want to restrict the alerts to records within a certain boundary, select it here.'),
        'fieldname' => 'species_alert:location_id',
        'id' => 'imp-location',
        'blankText' => lang::get('<Select boundary>'),
        'extraParams' => $auth['read'] + [
          'location_type_id' => $args['location_type_id'],
          'orderby' => 'name',
        ],
        'class' => 'control-width-4',
      ]);
    }
    $form .= data_entry_helper::checkbox([
      'label' => lang::get('Alert on initial entry'),
      'helpText' => lang::get('Tick this box if you want to receive a notification when the record is first input into the system.'),
      'fieldname' => 'species_alert:alert_on_entry',
    ]);
    $form .= data_entry_helper::checkbox([
      'label' => lang::get('Alert on verification as correct'),
      'helpText' => lang::get('Tick this box if you want to receive a notification when the record has been verified as correct.'),
      'fieldname' => 'species_alert:alert_on_verify',
    ]);
    $form .= "</fieldset>\n";
    $form .= '<input type="Submit" value="Subscribe" />';
    $form .= '</form>';
    data_entry_helper::enable_validation('entry_form');
    iform_load_helpers(['map_helper']);
    $mapOptions = iform_map_get_map_options($args, $auth['read']);
    $map = map_helper::map_panel($mapOptions);
    global $indicia_templates;
    return str_replace(['{col-1}', '{col-2}', '{attr}'], [$form, $map, ''], $indicia_templates['two-col-50']);
  }

  /**
   * Saves the subscription details to the warehouse when the form saved.
   *
   * @param array $args
   *   Form arguments.
   * @param array $auth
   *   Authorisation tokens.
   *
   * @throws \exception
   */
  private static function subscribe(array $args, array $auth) {
    $url = data_entry_helper::$base_url . 'index.php/services/species_alerts/register?';
    $params = [
      'auth_token' => $auth['write_tokens']['auth_token'],
      'nonce' => $auth['write_tokens']['nonce'],
      'first_name' => $_POST['first_name'],
      'surname' => $_POST['surname'],
      'email' => $_POST['email'],
      'website_id' => $args['website_id'],
      'alert_on_entry' => $_POST['species_alert:alert_on_entry'] ? 't' : 'f',
      'alert_on_verify' => $_POST['species_alert:alert_on_verify'] ? 't' : 'f',
    ];
    if (!empty($_POST['species_alert:id'])) {
      $params['id'] = $_POST['species_alert:id'];
    }
    if (!empty($_POST['species_alert:taxon_list_id'])) {
      $params['taxon_list_id'] = $_POST['species_alert:taxon_list_id'];
    }
    if (!empty($_POST['species_alert:location_id'])) {
      $params['location_id'] = $_POST['species_alert:location_id'];
    }
    if (!empty($_POST['user_id'])) {
      $params['user_id'] = $_POST['user_id'];
    }
    if (!empty($_POST['taxa_taxon_list_id'])) {
      // We've got a taxa_taxon_list_id in the post data. But, it is better to
      // subscribe via a taxon meaning ID, or even better, the external key.
      $taxon = data_entry_helper::get_population_data([
        'table' => 'taxa_taxon_list',
        'extraParams' => $auth['read'] + [
          'id' => $_POST['taxa_taxon_list_id'],
          'view' => 'cache',
        ],
      ]);
      if (count($taxon) !== 1) {
        throw new exception('Unable to find unique taxon when attempting to subscribe');
      }
      $taxon = $taxon[0];
      if (!empty($taxon['external_key'])) {
        $params['external_key'] = $taxon['external_key'];
      }
      else {
        $params['taxon_meaning_id'] = $taxon['taxon_meaning_id'];
      }
    }
    elseif (!empty($_POST['species_alert:external_key'])) {
      $params['external_key'] = $_POST['species_alert:external_key'];
    }
    elseif (!empty($_POST['species_alert:taxon_meaning_id'])) {
      $params['taxon_meaning_id'] = $_POST['species_alert:taxon_meaning_id'];
    }
    $url .= data_entry_helper::array_to_query_string($params, TRUE);
    $result = data_entry_helper::http_post($url);
    if ($result['result']) {
      hostsite_show_message(lang::get('Your subscription has been saved.'));
    }
    else {
      hostsite_show_message(lang::get('There was a problem saving your subscription.'));
      if (function_exists('watchdog')) {
        watchdog('iform', 'Species alert error on save: ' . print_r($result, TRUE));
      }
    }
  }

}
