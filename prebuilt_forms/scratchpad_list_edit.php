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
use IForm\prebuilt_forms\PrebuiltFormInterface;

/**
 * Form for editing a scratchpad list.
 *
 * A list of pointers to entities in the database, e.g. a list of species or
 * locations.
 */
class iform_scratchpad_list_edit implements PrebuiltFormInterface {

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_scratchpad_list_edit_definition() {
    return [
      'title' => 'Enter a scratchpad list',
      'category' => 'Data entry forms',
      'description' => <<<TXT
        Form for creating or editing an existing scratchpad list.
        This allows creation of a list of pointers to entities in the database,
        e.g. a list of species or locations.
      TXT,
      'recommended' => TRUE,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function getPageType(): PageType {
    return PageType::Utility;
  }

  /**
   * Get the list of parameters for this form.
   *
   * @return array
   *   List of parameters that this form requires.
   */
  public static function get_parameters() {
    return [
      [
        'name' => 'entity',
        'caption' => 'Type of data to create a list for',
        'description' => 'Select the type of data the scratchpad list will contain. Currently only species or other taxa are supported.',
        'type' => 'select',
        'options' => [
          'taxa_taxon_list' => 'Species or other taxa',
        ],
        'required' => TRUE,
      ],
      [
        'name' => 'scratchpad_type_id',
        'caption' => 'Scratchpad type',
        'description' => 'Select the type or category of scratchpad that new scratchpads will be saved as.',
        'type' => 'select',
        'table' => 'termlists_term',
        'captionField' => 'term',
        'valueField' => 'id',
        'extraParams' => [
          'termlist_external_key' => 'indicia:scratchpad_list_types',
        ],
        'required' => FALSE,
      ],
      [
        'name' => 'location_type_id',
        'caption' => 'Location types (optional)',
        'description' => 'Optionally select one or more location types to enable a location list editor on this form allowing the scratchpad list to be associated with one or more locations. The available locations will be filtered to the selected types.',
        'type' => 'checkbox_group',
        'table' => 'termlists_term',
        'captionField' => 'term',
        'valueField' => 'id',
        'extraParams' => [
          'termlist_external_key' => 'indicia:location_types',
        ],
        'required' => FALSE,
      ],
      [
        'name' => 'group_type_id',
        'caption' => 'Group types (optional)',
        'description' => 'Optionally select one or more group types to enable a group list editor on this form allowing the scratchpad list to be associated with one or more groups. The available groups will be filtered to the selected types.',
        'type' => 'checkbox_group',
        'table' => 'termlists_term',
        'captionField' => 'term',
        'valueField' => 'id',
        'extraParams' => [
          'termlist_external_key' => 'indicia:group_types',
        ],
        'required' => FALSE,
      ],
      [
        'name' => 'duplicates',
        'caption' => 'Duplicate handling',
        'description' => 'Select how duplicates in the scratchpad list should be handled.',
        'type' => 'select',
        'options' => [
          'allow' => 'Allow duplicates',
          'highlight' => 'Allow duplicates but highlight them',
          'warn' => 'Allow duplicates but warn when they occur',
          'disallow' => 'Disallow duplicates',
        ],
        'default' => 'highlight',
        'required' => TRUE,
      ],
      [
        'name' => 'filters',
        'caption' => 'Filters for search query',
        'description' => 'Additional filters to apply to the search query, e.g. taxon_list_id=&lt;n&gt; to limit to a single list. Key=value pairs, one per line',
        'type' => 'textarea',
        'required' => FALSE,
      ],
      [
        'name' => 'metadata_properties',
        'caption' => 'Entry metadata properties',
        'description' => 'Define metadata properties that can be stored against each list entry. One per line in the format machine_name|Caption|datatype|lookup_values. Datatype must be one of integer, float, text, lookup. For lookup, provide comma-separated lookup values as the 4th part. Example: stage|Life stage|lookup|egg,larva,pupa,adult',
        'type' => 'textarea',
        'required' => FALSE,
      ],
    ];
  }

  /**
   * Return the generated form output.
   *
   * @param array $args
   *   List of parameter values passed through to the form depending on how
   *   the form has been configured. This array always contains a value for
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
    data_entry_helper::add_resource('fancybox');
    data_entry_helper::add_resource('jquery_form');
    $conn = iform_get_connection_details($nid);
    $auth = data_entry_helper::get_read_write_auth($conn['website_id'], $conn['password']);
    $filters = data_entry_helper::explode_lines_key_value_pairs($args['filters']);
    $options = self::buildScratchpadOptions($args, $filters);
    $checkLabel = lang::get('Check');
    $removeDuplicatesLabel = lang::get('Remove duplicates');
    $saveLabel = lang::get('Save');
    $cancelLabel = lang::get('Cancel');
    $reloadPath = self::getReloadPath();
    $defaultList = '';
    $defaultEntryMetadata = [];
    $locationTypeIds = self::normaliseIdList($args['location_type_id'] ?? []);
    $groupTypeIds = self::normaliseIdList($args['group_type_id'] ?? []);
    $locationListEnabled = !empty($locationTypeIds);
    $groupListEnabled = !empty($groupTypeIds);
    $locationDefaults = ['linkIds' => [], 'defaultsForControl' => []];
    $groupDefaults = ['linkIds' => [], 'defaultsForControl' => []];
    $metadataProperties = self::parseMetadataPropertiesConfig($args['metadata_properties'] ?? '');
    $r = "<form method=\"post\" id=\"entry_form\" action=\"$reloadPath\" enctype=\"multipart/form-data\">\n";
    data_entry_helper::enable_validation('entry_form');
    if (!empty($args['scratchpad_type_id'])) {
      $r .= data_entry_helper::hidden_text([
        'fieldname' => 'scratchpad_list:scratchpad_type_id',
        'default' => $args['scratchpad_type_id'],
      ]);
    }
    $scratchpadListId = self::getScratchpadListIdFromRequest();
    if (!empty($scratchpadListId)) {
      $defaultList = self::getDefaultListHtmlForScratchpadList($auth['read'], $scratchpadListId, $defaultEntryMetadata);
      $r .= self::buildScratchpadListIdHidden($scratchpadListId);

      if ($locationListEnabled) {
        $locationDefaults = self::loadLocationDefaultsForScratchpadList($auth['read'], $scratchpadListId);
      }
      if ($groupListEnabled) {
        $groupDefaults = self::loadGroupDefaultsForScratchpadList($auth['read'], $scratchpadListId);
      }
    }
    $r .= data_entry_helper::hidden_text([
      'fieldname' => 'website_id',
      'default' => $conn['website_id'],
    ]);
    $r .= $auth['write'];
    $r .= data_entry_helper::text_input([
      'fieldname' => 'scratchpad_list:title',
      'label' => lang::get('List title'),
      'helpText' => lang::get('Provide a title that will help you remember what the list is for when you access it in future'),
      'class' => 'control-width-6',
      'validation' => ['required'],
    ]);
    $r .= data_entry_helper::textarea([
      'fieldname' => 'scratchpad_list:description',
      'label' => lang::get('List description'),
      'class' => 'control-width-6',
    ]);
    global $indicia_templates;
    $langListOk = lang::get('List OK');
    $langTotal = lang::get('Total');
    $langMatched = lang::get('Matched');
    $langQueried = lang::get('Queried');
    $langUnmatched = lang::get('Unmatched');
    $indicia_templates['scratchpad_input'] = <<<DIV
      <div id="scratchpad-container">
      <div contenteditable="true" id="{id}"{class}>{default}</div>
      <div id="scratchpad-stats" class="ui-helper-clearfix ui-helper-hidden">
        <div id="scratchpad-stats-total"><span class="all-done">$langListOk</span> $langTotal: <span class="stat">0</span></div>
        <div id="scratchpad-stats-matched">$langMatched: <span class="stat">0</span></div>
        <div id="scratchpad-stats-queried">$langQueried: <span class="stat">0</span></div>
        <div id="scratchpad-stats-unmatched">$langUnmatched: <span class="stat">0</span></div>
      </div>
    </div>
    DIV;
    $r .= data_entry_helper::apply_template('scratchpad_input', [
      'id' => 'scratchpad-input',
      'label' => lang::get('Enter the list of items'),
      'helpText' => lang::get(
        'Type in or paste items separated by commas or on separate lines.'
      ),
      'default' => $defaultList,
    ]);
    // Keep the metadata editor close to the scratchpad input. The input can
    // become very long, so the CSS constrains it to a scrollable region.
    $r .= '<div id="scratchpad-entry-metadata" class="scratchpad-entry-metadata" style="display:none"></div>';
    $r .= data_entry_helper::hidden_text([
      'id' => 'hidden-entries-list',
      'fieldname' => 'metaFields:entries',
    ]);
    $r .= data_entry_helper::hidden_text([
      'id' => 'hidden-entries-metadata',
      'fieldname' => 'metaFields:entries_metadata',
    ]);

    if ($locationListEnabled) {
      $r .= data_entry_helper::hidden_text([
        'id' => 'hidden-location-link-ids-list',
        'fieldname' => 'metaFields:location_link_ids',
        'default' => implode(';', $locationDefaults['linkIds']),
      ]);
    }
    if ($groupListEnabled) {
      $r .= data_entry_helper::hidden_text([
        'id' => 'hidden-group-link-ids-list',
        'fieldname' => 'metaFields:group_link_ids',
        'default' => implode(';', $groupDefaults['linkIds']),
      ]);
    }
    $r .= data_entry_helper::hidden_text([
      'fieldname' => 'scratchpad_list:entity',
      'default' => $args['entity'],
    ]);

    if ($locationListEnabled) {
      $r .= self::buildLocationFieldset($auth['read'], $locationTypeIds, $locationDefaults['defaultsForControl']);
    }

    if ($groupListEnabled) {
      $r .= self::buildGroupFieldset($auth['read'], $groupTypeIds, $groupDefaults['defaultsForControl']);
    }
    $r .= <<<HTML
      <button id="scratchpad-check" class="$indicia_templates[buttonHighlightedClass]" type="button">$checkLabel</button>
      <button id="scratchpad-remove-duplicates" class="$indicia_templates[buttonDefaultClass]" type="button" style="display: none">$removeDuplicatesLabel</button>
      <button id="scratchpad-save" type="submit" class="$indicia_templates[buttonDefaultClass]" disabled="disabled">$saveLabel</button>
    HTML;
    if (!empty($args['redirect_on_success'])) {
      $r .= "\n<button id=\"scratchpad-cancel\" type=\"button\">$cancelLabel</button>\n";
    }
    $r .= "</form>\n";
    data_entry_helper::$indiciaData['scratchpadSettings'] = $options;
    data_entry_helper::$indiciaData['ajaxUrl'] = hostsite_get_url('iform/ajax/scratchpad_list_edit');
    data_entry_helper::$indiciaData['scratchpadLocationListEnabled'] = $locationListEnabled;
    data_entry_helper::$indiciaData['scratchpadGroupListEnabled'] = $groupListEnabled;
    data_entry_helper::$indiciaData['scratchpadMetadataProperties'] = $metadataProperties;
    data_entry_helper::$indiciaData['scratchpadEntryMetadata'] = $defaultEntryMetadata;
    return $r;
  }

  /**
   * Handles the construction of a submission array from a set of form values.
   *
   * @param array $values
   *   Associative array of form data values.
   * @param array $args
   *   Iform parameters.
   *
   * @return array
   *   Submission structure.
   */
  public static function get_submission($values, $args) {
    $structure = [
      'model' => 'scratchpad_list',
      'metaFields' => ['entries', 'entries_metadata'],
    ];

    $submission = submission_builder::build_submission($values, $structure);
    // Optional linked locations list.
    $locationTypeIds = self::normaliseIdList($args['location_type_id'] ?? []);
    if (!empty($locationTypeIds)) {
      self::addJoinTableLinksToSubmission(
        $submission,
        'locations_scratchpad_list',
        'scratchpad_list_id',
        'location_id',
        $values['metaFields:location_ids'] ?? [],
        $values['metaFields:location_link_ids'] ?? ''
      );
    }

    // Optional linked groups list.
    $groupTypeIds = self::normaliseIdList($args['group_type_id'] ?? []);
    if (!empty($groupTypeIds)) {
      self::addJoinTableLinksToSubmission(
        $submission,
        'groups_scratchpad_list',
        'scratchpad_list_id',
        'group_id',
        $values['metaFields:group_ids'] ?? [],
        $values['metaFields:group_link_ids'] ?? ''
      );
    }
    return $submission;
  }

  /**
   * Builds the settings passed to the client-side scratchpad JS.
   *
   * @param array $args
   *   IForm configuration parameters.
   * @param array $filters
   *   Additional filters parsed from the configuration.
   *
   * @return array
   *   Scratchpad options for data_entry_helper::$indiciaData.
   */
  protected static function buildScratchpadOptions(array $args, array $filters): array {
    return [
      'entity' => $args['entity'],
      'extraParams' => [],
      'duplicates' => $args['duplicates'],
      'filters' => $filters,
      'ajaxProxyUrl' => iform_ajaxproxy_url(NULL, 'scratchpad_list'),
      'websiteId' => $args['website_id'],
      'returnPath' => hostsite_get_url($args['redirect_on_success']),
    ];
  }

  /**
   * Returns the scratchpad list ID from the request, if present.
   *
   * @return int|null
   *   The scratchpad list ID.
   */
  protected static function getScratchpadListIdFromRequest() {
    if (!empty($_GET['scratchpad_list_id'])) {
      return (int) $_GET['scratchpad_list_id'];
    }
    return NULL;
  }

  /**
   * Builds the hidden control containing the scratchpad list ID.
   *
   * @param int $scratchpadListId
   *   The existing scratchpad list ID.
   *
   * @return string
   *   HTML for the hidden input.
   */
  protected static function buildScratchpadListIdHidden(int $scratchpadListId): string {
    return data_entry_helper::hidden_text([
      'fieldname' => 'scratchpad_list:id',
      'default' => $scratchpadListId,
    ]);
  }

  /**
   * Loads an existing scratchpad list and returns the default HTML for the UI.
   *
   * Also populates data_entry_helper::$entity_to_load with title/description.
   * Optionally loads the per-entry metadata JSON.
   *
   * @param array $readAuth
   *   Read authentication token array.
   * @param int $scratchpadListId
   *   Scratchpad list ID.
   * @param array $entryMetadataById
   *   Output array keyed by entry_id containing decoded metadata arrays.
   *
   * @return string
   *   Default HTML to populate the scratchpad input.
   */
  protected static function getDefaultListHtmlForScratchpadList(array $readAuth, int $scratchpadListId, array &$entryMetadataById = []): string {
    $entryMetadataById = [];
    $defaultList = '';

    $list = data_entry_helper::get_population_data([
      'table' => 'scratchpad_list',
      'extraParams' => $readAuth + ['id' => $scratchpadListId],
      'caching' => FALSE,
    ]);
    $entries = data_entry_helper::get_population_data([
      'table' => 'scratchpad_list_entry',
      'extraParams' => $readAuth + [
        'scratchpad_list_id' => $scratchpadListId,
        'orderby' => 'id',
      ],
      'caching' => FALSE,
    ]);

    $sortedTaxa = [];
    $batchIds = [];
    $taxa = [];
    foreach ($entries as $entry) {
      if (!empty($entry['entry_id'])) {
        $batchIds[] = $entry['entry_id'];
        $sortedTaxa["ttlId:$entry[entry_id]"] = NULL;
        if (isset($entry['metadata']) && $entry['metadata'] !== '' && $entry['metadata'] !== NULL) {
          $decoded = json_decode($entry['metadata'], TRUE);
          if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $entryMetadataById[] = [
              'entry_id' => (int) $entry['entry_id'],
              'metadata' => $decoded,
            ];
          }
        }
      }
      // Grab the taxon list in batches of 50.
      if (count($batchIds) > 50) {
        $taxa = array_merge($taxa, data_entry_helper::get_population_data([
          'table' => 'taxa_taxon_list',
          'extraParams' => $readAuth + [
            'view' => 'cache',
            'query' => json_encode(['in' => ['id' => $batchIds]]),
          ],
        ]));
        $batchIds = [];
      }
    }
    // Grab the final batch.
    if (count($batchIds)) {
      $taxa = array_merge($taxa, data_entry_helper::get_population_data([
        'table' => 'taxa_taxon_list',
        'extraParams' => $readAuth + [
          'view' => 'cache',
          'query' => json_encode(['in' => ['id' => $batchIds]]),
        ],
      ]));
    }
    // Assign taxa to sorted array.
    foreach ($taxa as $taxon) {
      $sortedTaxa["ttlId:$taxon[id]"] = $taxon;
    }

    if (!empty($list[0])) {
      data_entry_helper::$entity_to_load = [
        'scratchpad_list:title' => $list[0]['title'],
        'scratchpad_list:description' => $list[0]['description'],
      ];
    }

    foreach ($sortedTaxa as $taxonInList) {
      // The sorted list contains NULL placeholders for IDs which fail to load.
      if (is_array($taxonInList) && isset($taxonInList['id']) && isset($taxonInList['taxon'])) {
        $id = $taxonInList['id'];
        $taxon = $taxonInList['taxon'];
        $defaultList .= "<span class=\"matched\" data-id=\"$id\">$taxon</span><br/>\n";
      }
    }

    return $defaultList;
  }

  /**
   * Parses the metadata property configuration.
   *
   * Each non-empty line should be:
   * machine_name|Caption|datatype|lookup_values.
   *
   * @param string $raw
   *   Raw textarea contents.
   *
   * @return array
   *   Array of property definitions with keys: name, caption, datatype,
   *   lookupValues.
   */
  protected static function parseMetadataPropertiesConfig(string $raw): array {
    $raw = trim($raw);
    if ($raw === '') {
      return [];
    }
    $lines = preg_split("/\r\n|\r|\n/", $raw);
    $props = [];
    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '' || str_starts_with($line, '#')) {
        continue;
      }
      $parts = array_map('trim', explode('|', $line));
      $name = $parts[0] ?? '';
      $caption = $parts[1] ?? '';
      $datatype = strtolower($parts[2] ?? 'text');
      $lookupRaw = $parts[3] ?? '';
      if ($name === '' || $caption === '') {
        continue;
      }
      if (!preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
        continue;
      }
      if (!in_array($datatype, ['integer', 'float', 'text', 'lookup'], TRUE)) {
        continue;
      }
      $lookupValues = [];
      if ($datatype === 'lookup' && $lookupRaw !== '') {
        $lookupValues = array_values(array_filter(array_map('trim', explode(',', $lookupRaw)), function ($v) {
          return $v !== '';
        }));
      }
      $props[] = [
        'name' => $name,
        'caption' => $caption,
        'datatype' => $datatype,
        'lookupValues' => $lookupValues,
      ];
    }
    return $props;
  }

  /**
   * Loads linked locations for a scratchpad list for display in the sub_list.
   *
   * @param array $readAuth
   *   Read authentication token array.
   * @param int $scratchpadListId
   *   Scratchpad list ID.
   *
   * @return array
   *   Array containing:
   *   - linkIds: IDs of join rows (used for deletion on save)
   *   - defaultsForControl: Default list rows for data_entry_helper::sub_list.
   */
  protected static function loadLocationDefaultsForScratchpadList(array $readAuth, int $scratchpadListId): array {
    $defaultLocationIds = [];
    $defaultLocationLinkIds = [];
    $defaultLocationsForControl = [];

    // Load any existing linked locations for this scratchpad list.
    // Stored via the warehouse join table locations_scratchpad_list.
    $linkedLocations = data_entry_helper::get_population_data([
      'table' => 'locations_scratchpad_list',
      'extraParams' => $readAuth + [
        'scratchpad_list_id' => $scratchpadListId,
        'orderby' => 'id',
      ],
      'caching' => FALSE,
    ]);
    foreach ($linkedLocations as $link) {
      if (!empty($link['id'])) {
        $defaultLocationLinkIds[] = $link['id'];
      }
      if (!empty($link['location_id'])) {
        $defaultLocationIds[] = $link['location_id'];
      }
    }

    // Load location names for display in the UI.
    if (!empty($defaultLocationIds)) {
      $locations = data_entry_helper::get_population_data([
        'table' => 'location',
        'extraParams' => $readAuth + [
          'query' => json_encode([
            'in' => ['id' => array_values(array_unique($defaultLocationIds))],
          ]),
        ],
        'caching' => FALSE,
      ]);
      $locationsById = [];
      foreach ($locations as $loc) {
        $locationsById[$loc['id']] = $loc;
      }
      foreach ($defaultLocationIds as $locId) {
        if (!empty($locationsById[$locId])) {
          $defaultLocationsForControl[] = [
            'fieldname' => 'metaFields:location_ids[]',
            'caption' => htmlspecialchars($locationsById[$locId]['name']),
            'default' => $locId,
          ];
        }
      }
    }

    return [
      'linkIds' => $defaultLocationLinkIds,
      'defaultsForControl' => $defaultLocationsForControl,
    ];
  }

  /**
   * Loads linked groups for a scratchpad list for display in the sub_list.
   *
   * @param array $readAuth
   *   Read authentication token array.
   * @param int $scratchpadListId
   *   Scratchpad list ID.
   *
   * @return array
   *   Array containing:
   *   - linkIds: IDs of join rows (used for deletion on save)
   *   - defaultsForControl: Default list rows for data_entry_helper::sub_list.
   */
  protected static function loadGroupDefaultsForScratchpadList(array $readAuth, int $scratchpadListId): array {
    $defaultGroupIds = [];
    $defaultGroupLinkIds = [];
    $defaultGroupsForControl = [];

    // Load any existing linked groups for this scratchpad list.
    // Stored via the warehouse join table groups_scratchpad_lists.
    $linkedGroups = data_entry_helper::get_population_data([
      'table' => 'groups_scratchpad_list',
      'extraParams' => $readAuth + [
        'scratchpad_list_id' => $scratchpadListId,
        'orderby' => 'id',
      ],
      'caching' => FALSE,
    ]);
    foreach ($linkedGroups as $link) {
      if (!empty($link['id'])) {
        $defaultGroupLinkIds[] = $link['id'];
      }
      if (!empty($link['group_id'])) {
        $defaultGroupIds[] = $link['group_id'];
      }
    }

    // Load group titles for display in the UI.
    if (!empty($defaultGroupIds)) {
      $groupsById = [];
      foreach (array_values(array_unique($defaultGroupIds)) as $groupId) {
        $groupRows = data_entry_helper::get_population_data([
          'table' => 'group',
          'extraParams' => $readAuth + ['id' => $groupId],
          'caching' => FALSE,
        ]);
        if (!empty($groupRows[0])) {
          $groupsById[$groupId] = $groupRows[0];
        }
      }
      foreach ($defaultGroupIds as $groupId) {
        if (!empty($groupsById[$groupId])) {
          $defaultGroupsForControl[] = [
            'fieldname' => 'metaFields:group_ids[]',
            'caption' => htmlspecialchars($groupsById[$groupId]['title']),
            'default' => $groupId,
          ];
        }
      }
    }

    return [
      'linkIds' => $defaultGroupLinkIds,
      'defaultsForControl' => $defaultGroupsForControl,
    ];
  }

  /**
   * Builds the locations sub_list UI wrapped in a fieldset.
   *
   * @param array $readAuth
   *   Read authentication token array.
   * @param array $locationTypeIds
   *   List of location type term IDs that should be offered.
   * @param array $defaultLocationsForControl
   *   Default values for the sub_list.
   *
   * @return string
   *   HTML output.
   */
  protected static function buildLocationFieldset(array $readAuth, array $locationTypeIds, array $defaultLocationsForControl): string {
    $r = '<fieldset id="scratchpad-location-fieldset"><legend>' . lang::get('Locations') . '</legend>';
    $r .= '<p class="helpText">' . lang::get('Add one or more locations to link to this list.') . '</p>';
    $r .= data_entry_helper::sub_list([
      'id' => 'scratchpad-location-list',
      'fieldname' => 'metaFields:location_ids[]',
      'table' => 'location',
      'autocompleteControl' => 'location_autocomplete',
      'captionField' => 'name',
      'valueField' => 'id',
      'extraParams' => self::buildTypeFilterExtraParams($readAuth, 'location_type_id', $locationTypeIds),
      'default' => $defaultLocationsForControl,
    ]);
    $r .= '</fieldset>';
    return $r;
  }

  /**
   * Builds the groups sub_list UI wrapped in a fieldset.
   *
   * @param array $readAuth
   *   Read authentication token array.
   * @param array $groupTypeIds
   *   List of group type term IDs that should be offered.
   * @param array $defaultGroupsForControl
   *   Default values for the sub_list.
   *
   * @return string
   *   HTML output.
   */
  protected static function buildGroupFieldset(array $readAuth, array $groupTypeIds, array $defaultGroupsForControl): string {
    $r = '<fieldset id="scratchpad-group-fieldset"><legend>' . lang::get('Groups') . '</legend>';
    $r .= '<p class="helpText">' . lang::get('Add one or more groups to link to this list.') . '</p>';
    $r .= data_entry_helper::sub_list([
      'id' => 'scratchpad-group-list',
      'fieldname' => 'metaFields:group_ids[]',
      'table' => 'group',
      'captionField' => 'title',
      'valueField' => 'id',
      'extraParams' => self::buildTypeFilterExtraParams($readAuth, 'group_type_id', $groupTypeIds),
      'default' => $defaultGroupsForControl,
    ]);
    $r .= '</fieldset>';
    return $r;
  }

  /**
   * Builds extraParams for filtering a list by one or more type IDs.
   *
   * For a single type ID, uses a simple query string parameter.
   * For multiple IDs, uses the warehouse JSON query "in" filter.
   *
   * @param array $readAuth
   *   Read authentication token array.
   * @param string $typeField
   *   Field name to filter on (e.g. location_type_id, group_type_id).
   * @param array $typeIds
   *   List of type term IDs.
   *
   * @return array
   *   Extra params array suitable for passing to controls or
   *   get_population_data.
   */
  protected static function buildTypeFilterExtraParams(array $readAuth, string $typeField, array $typeIds): array {
    $typeIds = array_values($typeIds);
    if (count($typeIds) === 1) {
      return $readAuth + [$typeField => $typeIds[0]];
    }
    return $readAuth + [
      'query' => json_encode([
        'in' => [$typeField => $typeIds],
      ]),
    ];
  }

  /**
   * Adds join-table subModels to a scratchpad list submission.
   *
   * Deletes any existing join rows (using a hidden list of join row IDs)
   * then re-adds join rows for the current selections.
   *
   * @param array $submission
   *   Submission array being built.
   * @param string $joinModel
   *   Warehouse model name for the join table.
   * @param string $fkId
   *   Foreign key field name used by the join model.
   * @param string $entityIdField
   *   Field name for the linked entity ID (e.g. location_id).
   * @param mixed $entityIdsRaw
   *   Raw entity IDs from the form (array or delimiter-separated string).
   * @param mixed $joinRowIdsRaw
   *   Raw join row IDs from the form (array or delimiter-separated string).
   */
  protected static function addJoinTableLinksToSubmission(
    array &$submission,
    string $joinModel,
    string $fkId,
    string $entityIdField,
    $entityIdsRaw,
    $joinRowIdsRaw
  ): void {
    $entityIds = self::normaliseIdList($entityIdsRaw);
    $joinRowIds = self::normaliseIdList($joinRowIdsRaw);

    if (empty($entityIds) && empty($joinRowIds)) {
      return;
    }

    if (!isset($submission['subModels'])) {
      $submission['subModels'] = [];
    }

    // When editing an existing list, remove existing join rows first.
    foreach ($joinRowIds as $joinRowId) {
      $submission['subModels'][] = [
        'fkId' => $fkId,
        'model' => submission_builder::wrap([
          'id' => $joinRowId,
          'deleted' => 't',
        ], $joinModel),
      ];
    }

    // Add join rows for current selections.
    foreach ($entityIds as $entityId) {
      $submission['subModels'][] = [
        'fkId' => $fkId,
        'model' => submission_builder::wrap([
          $entityIdField => $entityId,
        ], $joinModel),
      ];
    }
  }

  /**
   * Normalises a list of IDs from the form into a simple array.
   *
   * @param mixed $raw
   *   Raw value. Either an array (from [] fieldnames) or a delimiter-separated
   *   string.
   *
   * @return array
   *   List of non-empty trimmed IDs.
   */
  protected static function normaliseIdList($raw): array {
    if (is_array($raw)) {
      return array_values(array_filter(array_map('trim', $raw), function ($id) {
        return $id !== '';
      }));
    }
    if (is_string($raw)) {
      $raw = trim($raw);
      if ($raw === '') {
        return [];
      }
      return array_values(array_filter(array_map('trim', explode(';', $raw)), function ($id) {
        return $id !== '';
      }));
    }
    return [];
  }

  /**
   * Get the path for page reloading.
   *
   * @return string
   *   The reload path.
   */
  protected static function getReloadPath() {
    $reload = data_entry_helper::get_reload_link_parts();
    unset($reload['params']['sample_id']);
    unset($reload['params']['occurrence_id']);
    unset($reload['params']['location_id']);
    unset($reload['params']['new']);
    unset($reload['params']['newLocation']);
    $reloadPath = $reload['path'];
    if (count($reload['params'])) {
      // Decode params prior to encoding to prevent double encoding.
      foreach ($reload['params'] as $key => $param) {
        $reload['params'][$key] = urldecode($param);
      }
      $reloadPath .= '?' . http_build_query($reload['params']);
    }
    return $reloadPath;
  }

  /**
   * AJAX handler for the Check button's web request.
   *
   * Proxies to the warehouse and requests the list of provided names is
   * checked against the database.
   *
   * @param int $website_id
   *   Warehouse website ID.
   * @param string $password
   *   Warehouse website password.
   *
   * @return array
   *   Response from the warehouse for the check request.
   */
  public static function ajax_check($website_id, $password) {
    iform_load_helpers(['data_entry_helper']);
    if (empty($_POST['params'])) {
      return ['error' => 'Report parameters not provided'];
    }
    $auth = data_entry_helper::get_read_auth($website_id, $password);
    $url = data_entry_helper::$base_url . 'index.php/services/report/requestReport?' .
      data_entry_helper::array_to_query_string($_GET);
    $params = array_merge($_POST, $auth);
    $response = data_entry_helper::http_post($url, $params);
    return json_decode($response['output'], TRUE);
  }

}
