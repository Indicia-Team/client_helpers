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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/indicia-team/client_helpers
 */

class VerificationHelper {

  private static $statusTermsTranslated = FALSE;

  private static $statusTerms = array(
    'V' => 'Accepted',
    'R' => 'Not accepted',
    // Deprecated.
    'D' => 'Query',
    'I' => 'In progress',
    'T' => 'Test record',
    'C' => 'Not reviewed',
  );

  private static $substatusTerms = array(
    '1' => 'correct',
    '2' => 'considered correct',
    '3' => 'plausible',
    '4' => 'unable to verify',
    '5' => 'incorrect',
  );

  public static function doesUserSeeNotifications($readAuth, $userId) {
    iform_load_helpers(['report_helper']);
    $data = report_helper::get_report_data(array(
      'dataSource' => 'library/users/user_notification_response_likely',
      'readAuth' => $readAuth,
      'extraParams' => array('user_id' => $userId)
    ));
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
      // If they receive emails for comment notifications, we can assume they will see a comment.
      return 'yes';
    }
    elseif ($acknowledged + $unacknowledged > 0) {
      // otherwise, we need some info on the ratio of acknowledged to unacknowledged notifications over the last year
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

  public static function getComments($readAuth, $params, $emailMode = FALSE) {
    iform_load_helpers(array('data_entry_helper', 'report_helper'));
    $options = array(
      'dataSource' => 'reports_for_prebuilt_forms/verification_5/occurrence_comments_and_dets',
      'readAuth' => $readAuth,
      'sharing' => $params['sharing'],
      'extraParams' => array('occurrence_id' => $_GET['occurrence_id']),
    );
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
        $r .= self::getStatusIcons($comment['record_status'], $comment['record_substatus'], $imgPath);
        if ($comment['query'] === 't') {
          $hint = lang::get('This is a query');
          $r .= "<img width=\"12\" height=\"12\" src=\"{$imgPath}nuvola/dubious-16px.png\" title=\"$hint\" alt=\"$hint\"/>";
        }
      }
      $r .= "<strong>$comment[person_name]</strong> ";
      $commentTime = strtotime($comment['updated_on']);
      // Output the comment time. Skip if in future (i.e. server/client date settings don't match).
      if ($commentTime < time()) {
        $r .= helper_base::ago($commentTime);
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
              $r .= '<a class="unshrink-correspondence" title="'.lang::get('Expand this correspondence block to show its full details.').'">'.lang::get('more...').'</a>';
              foreach ($item as $field => $value) {
                $field = $field === 'body' ? '' : '<span>' . ucfirst($field) . ':</span>';
                $r .= "<div>$field $value</div>";
              }
              $r .= '<a class="shrink-correspondence" title="'.lang::get('Shrink this correspondence block.').'">'.lang::get('less...').'</a>';
              $r .= '</div>';
            }
          }
        }
      }
      $r .= '</div>';
    }
    $r .= '</div>';
    if (!$emailMode) {
      $r .= self::getCommentsForm();
    }
    return $r;
  }

  /**
   * Returns the HTML for a comments form.
   *
   * @return string
   *   Form HTML.
   */
  private static function getCommentsForm() {
    $allowConfidential = isset($_GET['allowconfidential']) && $_GET['allowconfidential'] === 'true';
    $r = '<form><fieldset><legend>' . lang::get('Add new comment') . '</legend>';
    if ($allowConfidential) {
      $r .= '<label><input type="checkbox" id="comment-confidential" /> ' . lang::get('Confidential?') . '</label><br>';
    }
    else {
      $r .= '<input type="hidden" id="comment-confidential" value="f" />';
    }
    $r .= data_entry_helper::textarea([
      'fieldname' => 'comment-text'
    ]);
    $r .= data_entry_helper::text_input([
      'label' => lang::get('External reference or other source'),
      'fieldname' => 'comment-reference'
    ]);
    $r .= '<button type="button" class="default-button" ' .
      'onclick="indiciaFns.saveComment(jQuery(\'#comment-text\').val(), jQuery(\'#comment-reference\').val(), jQuery(\'#comment-confidential\:checked\').length, false);">' . lang::get('Save') . '</button>';
    $r .= '</fieldset></form>';
    return $r;
  }

  public static function getMedia($readAuth, $params) {
    iform_load_helpers(['data_entry_helper']);
    // Retrieve occurrence media for record.
    $occ_media = data_entry_helper::get_population_data(array(
      'table' => 'occurrence_medium',
      'extraParams' => $readAuth + array('occurrence_id' => $_GET['occurrence_id']),
      'nocache' => TRUE,
      'sharing' => $params['sharing'],
    ));
    // Retrieve related sample media.
    $smp_media = data_entry_helper::get_population_data(array(
      'table' => 'sample_medium',
      'extraParams' => $readAuth + array('sample_id' => $_GET['sample_id']),
      'nocache' => TRUE,
      'sharing' => $params['sharing'],
    ));
    $r = '';
    if (count($occ_media) + count($smp_media) === 0) {
      $r .= lang::get('No media found for this record');
    }
    else {
      $r .= '<p>' . lang::get('Click on thumbnails to view full size') . '</p>';
      if (count($occ_media) > 0) {
        $r .= '<p class="header">' . lang::get('Record media') . '</p>';
        $r .= self::getMediaHtml($occ_media);
      }
      if (count($smp_media) > 0) {
        $r .= '<p class="header">' . lang::get('Sample media') . '</p>';
        $r .= self::getMediaHtml($smp_media);
      }
    }
    return $r;
  }

  private static function getMediaHtml($media) {
    require_once 'prebuilt_forms/includes/report.php';
    $path = helper_base::get_uploaded_image_folder();
    $r = '<div class="media-gallery"><ul >';
    foreach ($media as $file) {
      $r .= iform_report_get_gallery_item($file);
    }
    $r .= '</ul></div>';
    return $r;
  }

  /**
   * Converts a status and substatus into a readable label.
   *
   * E.g. "accepted", or "accepted:considered correct".
   *
   * @param string $status
   *   Status code from database (e.g. 'C').
   * @param integer $substatus
   *   Substatus value from database.
   * @param string $query
   *   Query valid for the record (null, Q or A).
   *
   * @return string
   *   Status label text.
   */
  public static function getStatusLabel($status, $substatus, $query) {
    $labels = array();
    self::translateStatusTerms();
    // Grab the term for the status. We don't need to bother with not reviewed status if
    // substatus is plausible.
    if (!empty(self::$statusTerms[$status]) && ($status !== 'C' || (int) $substatus !== 3)) {
      $labels[] = lang::get(self::$statusTerms[$status]);
    }
    elseif ((int) $substatus !== 3)
      $labels[] = lang::get('Unknown');
    if ($substatus && !empty(self::$substatusTerms[$substatus])) {
      $labels[] = lang::get(self::$substatusTerms[$substatus]);
    }
    switch ($query) {
      case 'Q':
        $labels[] = lang::get('Queried');
        break;

      case 'A':
        $labels[] = lang::get('Query answered');
        break;
    }
    return implode('::', $labels);
  }

  public static function getTranslatedStatusTerms() {
    self::translateStatusTerms();
    return array_merge(
      self::$statusTerms,
      [
        'V1' => self::getStatusLabel('V', '1', FALSE),
        'V2' => self::getStatusLabel('V', '2', FALSE),
        'C3' => self::getStatusLabel('C', '3', FALSE),
        'R4' => self::getStatusLabel('R', '4', FALSE),
        'R5' => self::getStatusLabel('R', '5', FALSE),
      ]
    );
  }

  private static function getStatusIcons($status, $substatus, $imgPath) {
    $r = '';
    if (!empty($status)) {
      $hint = self::getStatusLabel($status, $substatus, NULL);
      $images = array();
      if ($status === 'V') {
        $images[] = 'ok-16px';
      }
      elseif ($status === 'R') {
        $images[] = 'cancel-16px';
      }
      switch ($substatus) {
        case '1':
          $images[] = 'ok-16px';
          break;

        case '2':
          break;

        case '3':
          $images[] = 'quiz-22px';
          break;

        case '4':
          break;

        case '5':
          $images[] = 'cancel-16px';
          break;
      }
      if ($images) {
        foreach ($images as $image) {
          $r .= "<img width=\"12\" height=\"12\" src=\"{$imgPath}nuvola/$image.png\" title=\"$hint\" alt=\"$hint\"/>";
        }
      }
    }
    return $r;
  }

  /**
   * Convert the list of status terms and substatus terms into a translated version.
   */
  private static function translateStatusTerms() {
    if (!self::$statusTermsTranslated) {
      foreach (self::$statusTerms as &$term) {
        $term = lang::get($term);
      }
      foreach (self::$substatusTerms as &$term) {
        $term = lang::get($term);
      }
      self::$statusTermsTranslated = TRUE;
    }
  }

}