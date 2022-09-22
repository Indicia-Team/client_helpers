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

/**
 * Extension class provides structured comments that can handle voting.
 */
class extension_voting_comments {

  /**
   * A form for submitting a vote or review.
   *
   * Currently for locations data only, but reports can be added to the
   * warehouse to extend for other entities.
   *
   * Can be added to a relevant details page or any page with <entity>_id in
   * the page's URL query. Options include:
   * * entity - occurrence, sample or location.
   * * textFields - associative array of key/title pairs for textarea fields
   *   that the user can complete when filling in a vote or review.
   * * voteFields - associative array of key/title pairs for star voting fields
   *   that the user can complete when filling in a vote or review.
   *
   * The saved information is saved as JSON text in the relevant comment table
   * on the warehouse.
   */
  public static function form($auth, $args, $tabalias, $options, $path) {
    self::validateOptions($options, 'form');
    helper_base::add_resource('font_awesome');
    iform_load_helpers(['data_entry_helper']);
    global $indicia_templates;
    $entityAbbr = [
      'occurrence' => 'occ',
      'sample' => 'smp',
      'location' => 'loc',
    ][$options['entity']];
    helper_base::$indiciaData['voteAjaxUrl'] = iform_ajaxproxy_url(NULL, "$entityAbbr-comment");
    helper_base::$indiciaData['entity'] = $options['entity'];
    helper_base::$indiciaData['entity_id'] = $_GET[$options['entity'] . '_id'];
    $lang = [
      'save' => lang::get('Save'),
    ];
    helper_base::addLanguageStringsToJs('votingCommentsForm', [
      'msgNothingToSave' => 'Please provide some information for your review before saving it.',
      'msgSaveFail' => 'An error occurred whilst saving the review.',
      'msgSaveSuccess' => 'Your review has been saved.',
    ]);
    $r = '<div class="voting-form">';
    $r .= !empty($options['title']) ? '<h3>' . $options['title'] . '</h3>' : '';
    foreach ($options['voteFields'] as $name => $title) {
      $r .= self::starInput($name, lang::get($title));
    }
    foreach ($options['textFields'] as $key => $title) {
      $r .= data_entry_helper::textarea([
        'fieldname' => $key,
        'label' => lang::get($title),
      ]);
    }
    $r .= "<button type=\"submit\" class=\"$indicia_templates[buttonHighlightedClass]\" id=\"vote-save\">$lang[save]</button>";
    // Following prevents visual glitch when solid star first loads.
    $r .= '<span style="opacity: 0"><i class="fas fa-star"></i></span>';
    $r .= '</div>';
    return $r;
  }

  /**
   * Outputs a summary of voting scores for the current data item.
   *
   * Includes a heading with the overall score and count, plus a collapsible
   * section with a breakdown by voting question. Options include:
   * * entity - occurrence, sample or location.
   * * textFields - associative array of key/title pairs for textarea fields
   *   that the user can complete when filling in a vote or review.
   * * voteFields - associative array of key/title pairs for star voting fields
   *   that the user can complete when filling in a vote or review.
   */
  public static function summary($auth, $args, $tabalias, $options, $path) {
    self::validateOptions($options, 'summary');
    if (empty($options['voteFields'])) {
      throw new exception("The [voting_comments.$control] control requires the voteFields option to be populated.");
    }
    $id = $_GET["$options[entity]_id"];
    $voteFields = htmlspecialchars(json_encode($options['voteFields']));
    return <<<HTML
<section class="vote-summary panel panel-default" data-id="$id" data-votefields="$voteFields" data-entity="$options[entity]">
  <div class="panel-heading">
    <a data-toggle="collapse" href="#vote-details-collapse"></a>
  </div>
  <div class="panel-collapse collapse" id="vote-details-collapse">
    <div class="panel-body"></div>
  </div>
</section>
HTML;
  }

  /**
   * Outputs a list of votes/reviews for the current data item.
   *
   * Options include:
   * * title - block heading title.
   * * entity - occurrence, sample or location.
   * * textFields - associative array of key/title pairs for textarea fields
   *   that the user can complete when filling in a vote or review.
   * * voteFields - associative array of key/title pairs for star voting fields
   *   that the user can complete when filling in a vote or review.
   */
  public static function votes_list($auth, $args, $tabalias, $options, $path) {
    self::validateOptions($options, 'votes_list');
    $options = array_merge([
      'title' => 'Reviews',
    ], $options);
    $id = $_GET["$options[entity]_id"];
    $voteFields = htmlspecialchars(json_encode($options['voteFields']));
    $textFields = htmlspecialchars(json_encode($options['textFields']));
    $lang = [
      'title' => lang::get($options['title']),
    ];
    return <<<HTML
<section class="vote-list panel panel-default" data-id="$id" data-offset="0" data-votefields="$voteFields" data-textfields="$textFields" data-entity="$options[entity]">
  <div class="panel-heading">$lang[title]</div>
  <div class="panel-body"></div>
</section>
HTML;
  }

  private static function validateOptions(array $options, $control) {
    $validEntities = ['occurrence', 'sample', 'location'];
    if (empty($options['entity']) || !in_array($options['entity'], $validEntities)) {
      throw new exception("The [voting_comments.$control] control requires an @entity option naming an entity that supports comments.");
    }
    if (empty($_GET["$options[entity]_id"])) {
      throw new exception("The [voting_comments.$control] control a $options[entity]_id parameter in the URL.");
    }
    if (empty($options['voteFields']) && empty($options['textFields'])) {
      throw new exception("The [voting_comments.$control] control requires at least one of the voteFields or textFields options to be populated.");
    }
    if (isset($options['voteFields']) && !is_array($options['voteFields'])) {
      throw new exception("The [voting_comments.$control] control @voteFields option must be a JSON object with field names and field titles as key/value pairs.");
    }
    if (isset($options['textFields']) && !is_array($options['textFields'])) {
      throw new exception("The [voting_comments.$control] control @textFields option must be a JSON object with field names and field titles as key/value pairs.");
    }
  }

  /**
   * Fetch the HTML required for a star voting input control.
   */
  private static function starInput($name, $title) {
    return <<<HTML
<section class="hover-ratings">
  <label>$title<br/>
    <input type="hidden" name="$name" class="clicked-rating" value="" />
    <i class="far fa-star hover-star" data-value="1"></i>
    <i class="far fa-star hover-star" data-value="2"></i>
    <i class="far fa-star hover-star" data-value="3"></i>
    <i class="far fa-star hover-star" data-value="4"></i>
    <i class="far fa-star hover-star" data-value="5"></i>
  </label>
</section>
HTML;
  }

}
