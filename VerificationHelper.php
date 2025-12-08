<?php

/**
 * @file
 * A helper class for Verification related code.
 *
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
 * @link https://github.com/indicia-team/client_helpers
 */

use IForm\IndiciaConversions;

 /**
  * A helper class for Verification related code.
  */
class VerificationHelper {

  /**
   * Determine if a user likely to see any notifications raised.
   *
   * Users who casually submit a record may never log in again to check their
   * notifications. This examines their notifications to determine if they can
   * be expected to check any future ones, to determine whether a notified
   * comment or a direct email is the best way of letting them know about an
   * action.
   *
   * @param array $readAuth
   *   Read authorisation tokens.
   * @param int $userId
   *   Warehouse user ID of the user to check.
   *
   * @return string
   *   Yes, no, maybe or unknown.
   */
  public static function doesUserSeeNotifications(array $readAuth, $userId) {
    iform_load_helpers(['report_helper']);
    // Get some summary stats about the user's existing notifications.
    $data = report_helper::get_report_data([
      'dataSource' => 'library/users/user_notification_response_likely',
      'readAuth' => $readAuth,
      'extraParams' => ['user_id' => $userId],
    ]);
    $acknowledged = 0;
    $unacknowledged = 0;
    $emailFrequency = FALSE;
    foreach ($data as $row) {
      if ($row['key'] === 'acknowledged') {
        $acknowledged = (int) $row['value'];
      }
      elseif ($row['key'] === 'unacknowledged') {
        $unacknowledged = (int) $row['value'];
      }
      elseif ($row['key'] === 'email_frequency') {
        $emailFrequency = $row['value'];
      }
    }
    if ($emailFrequency) {
      // If they receive emails for comment notifications, we can assume they
      // will see a comment.
      return 'yes';
    }
    elseif ($acknowledged + $unacknowledged > 0) {
      // Otherwise, we need some info on the ratio of acknowledged to
      // unacknowledged notifications over the last year.
      $ratio = $acknowledged / ($acknowledged + $unacknowledged);
      if ($ratio > 0.3) {
        return 'yes';
      }
      elseif ($ratio === 0) {
        return 'no';
      }
      else {
        return 'maybe';
      }
    }
    else {
      // They don't have notifications in database, so we can't say.
      return 'unknown';
    }
  }

  /**
   * Retrieves HTML to display a record's comments.
   *
   * @param array $readAuth
   *   Read authorisation tokens.
   * @param array $params
   *   Page parameters.
   * @param int $occurrenceId
   *   ID of the occurrence to load comments for.
   * @param bool $emailMode
   *   If true, then HTML simplified for insertion into an email.
   *
   * @return string
   *   HTML.
   */
  public static function getComments(array $readAuth, array $params, $occurrenceId, $emailMode = FALSE) {
    iform_load_helpers(['data_entry_helper', 'report_helper']);
    $options = [
      'dataSource' => 'reports_for_prebuilt_forms/verification_5/occurrence_comments_and_dets',
      'readAuth' => $readAuth,
      'sharing' => $params['sharing'],
      'extraParams' => ['occurrence_id' => $occurrenceId],
    ];
    $comments = report_helper::get_report_data($options);
    $imgPath = empty(report_helper::$images_path) ? report_helper::relative_client_helper_path() . "../media/images/" : report_helper::$images_path;
    $r = '';
    if (count($comments) === 0) {
      $r .= '<p id="no-comments">' . lang::get('No comments have been made.') . '</p>';
    }
    $r .= '<div id="comment-list">';
    foreach ($comments as $comment) {
      $r .= '<div class="comment">';
      $r .= '<div class="header">';
      if (!$emailMode) {
        $r .= IndiciaConversions::statusToIcons($comment['record_status'], $comment['record_substatus'], $imgPath);
        if ($comment['query'] === 't') {
          $hint = lang::get('This is a query');
          $r .= "<img width=\"12\" height=\"12\" src=\"{$imgPath}nuvola/dubious-16px.png\" title=\"$hint\" alt=\"$hint\"/>";
        }
      }
      $r .= "<strong>$comment[person_name]</strong> ";
      $commentTime = strtotime($comment['updated_on']);
      // Output the comment time. Skip if in future (i.e. server/client date settings don't match).
      if ($commentTime < time()) {
        $r .= IndiciaConversions::timestampToTimeAgoString($commentTime);
      }
      $r .= '</div>';
      $c = str_replace("\n", '<br/>', $comment['comment']);
      if ($emailMode) {
        $r .= "<div class=\"comment-body\">$c</div>";
      }
      else {
        $r .= '<div class="comment-body shrunk">' .
                '<a class="unshrink-comment" title="' . lang::get('Expand this comment block to show its full details.') . '">' .
                  lang::get('more...') .
                '</a>' .
                $c .
                '<a class="shrink-comment" title="' . lang::get('Shrink this comment block.') . '">' .
                  lang::get('less...') .
                '</a>' .
              '</div>';
        if (!empty($comment['correspondence_data'])) {
          $data = str_replace("\n", '<br/>', $comment['correspondence_data']);
          $correspondenceData = json_decode($data, TRUE);
          foreach ($correspondenceData as $type => $items) {
            $r .= '<h3>' . ucfirst($type) . '</h3>';
            foreach ($items as $item) {
              $r .= '<div class="correspondence shrunk">';
              $r .= '<a class="unshrink-correspondence" title="' .
                lang::get('Expand this correspondence block to show its full details.') . '">' .
                lang::get('more...') . '</a>';
              foreach ($item as $field => $value) {
                $field = $field === 'body' ? '' : '<span>' . ucfirst($field) . ':</span>';
                $r .= "<div>$field $value</div>";
              }
              $r .= '<a class="shrink-correspondence" title="' .
                lang::get('Shrink this correspondence block.') . '">' . lang::get('less...') . '</a>';
              $r .= '</div>';
            }
          }
        }
      }
      $r .= '</div>';
    }
    $r .= '</div>';
    return $r;
  }

  /**
   * Retrieves the media for a record as HTML.
   *
   * @param array $readAuth
   *   Read authentication tokens.
   * @param array $params
   *   Form configuration.
   * @param int $occurrenceId
   *   Occurrence ID to check for media.
   * @param int $sampleId
   *   Sample ID to check for media.
   * @param bool $includeInfo
   *   Should a data-media-info attribute be included contaioning extra
   *   information about the media file? Defaults to TRUE but set to FALSE for
   *   images to be included in emails.
   *
   * @return string
   *   HTML.
   */
  public static function getMedia(array $readAuth, array $params, $occurrenceId, $sampleId, $includeInfo = TRUE) {
    iform_load_helpers(['data_entry_helper']);
    // Retrieve occurrence media for record.
    $occ_media = data_entry_helper::get_population_data([
      'table' => 'occurrence_medium',
      'extraParams' => $readAuth + ['occurrence_id' => $occurrenceId],
      'nocache' => TRUE,
      'sharing' => $params['sharing'],
    ]);
    // Retrieve related sample media.
    $smp_media = data_entry_helper::get_population_data([
      'table' => 'sample_medium',
      'extraParams' => $readAuth + ['sample_id' => $sampleId],
      'nocache' => TRUE,
      'sharing' => $params['sharing'],
    ]);
    $r = '';
    if (count($occ_media) + count($smp_media) === 0) {
      $r .= lang::get('No media found for this record');
    }
    else {
      $r .= '<p>' . lang::get('Click on thumbnails to view full size') . '</p>';
      if (count($occ_media) > 0) {
        $r .= '<p class="header">' . lang::get('Record media') . '</p>';
        $r .= self::getMediaHtml('occurrence', $occ_media, $includeInfo);
      }
      if (count($smp_media) > 0) {
        $r .= '<p class="header">' . lang::get('Sample media') . '</p>';
        $r .= self::getMediaHtml('sample', $smp_media, $includeInfo);
      }
    }
    return $r;
  }

  /**
   * Build a list of media thumbnails as HTML.
   *
   * @param string $entity
   *   Root entity for the media, e.g. occurrence, sample.
   * @param array $media
   *   Media data loaded from the database.
   * @param bool $includeInfo
   *   Should a data-media-info attribute be included contaioning extra
   *   information about the media file? Defaults to TRUE but set to FALSE for
   *   images to be included in emails.
   *
   * @return string
   *   HTML.
   */
  private static function getMediaHtml($entity, array $media, $includeInfo = TRUE) {
    require_once 'prebuilt_forms/includes/report.php';
    $r = '<div class="media-gallery"><ul>';
    foreach ($media as $file) {
      $r .= iform_report_get_gallery_item($entity, $file, 'thumb', $includeInfo);
    }
    $r .= '</ul></div>';
    return $r;
  }

  /**
   * Finds the taxon meaning IDs which require fully logged communications.
   *
   * If the workflow_module is enabled then a verification page can call this
   * method to load the taxon meanin IDs for all taxa where
   * log_all_communications is set. These should store the contents of emails
   * sent in the occurrence_comments.
   *
   * Meaning IDs are added to $indiciaData.
   *
   * @param array $readAuth
   *   Read authorisation tokens.
   * @param string $sharing
   *   Record sharing mode, defaults to verification.
   */
  public static function fetchTaxaWithLoggedCommunications($readAuth, $sharing = 'verification') {
    // Allow caching.
    $wfMetadata = data_entry_helper::get_population_data([
      'table' => 'workflow_metadata',
      'extraParams' => $readAuth + [
        'log_all_communications' => 't',
        'entity' => 'occurrence',
        'view' => 'detail',
        'columns' => 'key,key_value',
      ],
      'cachePerUser' => FALSE,
    ]);
    if (!empty($wfMetadata)) {
      $workflowTaxonMeaningIDsLogAllComms = [];
      $externalKeys = [];
      foreach ($wfMetadata as $wfMeta) {
        switch ($wfMeta['key']) {
          case 'taxa_taxon_list_external_key':
            $externalKeys[] = $wfMeta['key_value'];
            break;

          default:
            hostsite_show_message("Unrecognised workflow_metadata key $wfMeta[key]");
            break;
        }
      }
      // Allow caching.
      $wkMIDs = data_entry_helper::get_population_data([
        'table' => 'cache_taxa_taxon_list',
        'extraParams' => $readAuth + [
          'query' => json_encode(['in' => ['external_key' => $externalKeys]]),
          'columns' => 'taxon_meaning_id',
        ],
        'sharing' => $sharing,
        'cachePerUser' => FALSE,
      ]);
      foreach ($wkMIDs as $wkMID) {
        $workflowTaxonMeaningIDsLogAllComms[] = $wkMID['taxon_meaning_id'];
      }
      data_entry_helper::$indiciaData['workflowEnabled'] = TRUE;
      data_entry_helper::$indiciaData['workflowTaxonMeaningIDsLogAllComms'] =
        array_values(array_unique($workflowTaxonMeaningIDsLogAllComms));
    }
  }

}
