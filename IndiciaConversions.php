<?php

namespace IForm;

/**
 * Class containing useful conversion functions.
 */
class IndiciaConversions {

  /**
   * Track if status labels have been translated so we only do it once.
   *
   * @var bool
   */
  private static $statusTermsTranslated = FALSE;

  /**
   * Record status term mappings.
   *
   * @var array
   */
  private static $statusTerms = [
    'V' => 'Accepted',
    'R' => 'Not accepted',
    'C' => 'Not reviewed',
    'I' => 'In progress',
    // Deprecated.
    'D' => 'Query',
    'T' => 'Test record',
  ];

  /**
   * Record substatus term mappings.
   *
   * @var array
   */
  private static $substatusTerms = [
    '1' => 'correct',
    '2' => 'considered correct',
    '3' => 'plausible',
    '4' => 'unable to verify',
    '5' => 'incorrect',
  ];

  /**
   * Returns an array of all translated status terms, keyed by code.
   *
   * @return array
   *   List of status terms keyed by code.
   */
  public static function getTranslatedStatusTerms() {
    self::translateStatusTerms();
    return array_merge(
      self::$statusTerms,
      [
        'V1' => self::statusToLabel('V', '1', FALSE),
        'V2' => self::statusToLabel('V', '2', FALSE),
        'C3' => self::statusToLabel('C', '3', FALSE),
        'R4' => self::statusToLabel('R', '4', FALSE),
        'R5' => self::statusToLabel('R', '5', FALSE),
      ]
    );
  }

  /**
   * Returns the icon HTML for a given status/substatus.
   *
   * @param string $status
   *   Record status code.
   * @param string $substatus
   *   Record substatus number.
   * @param string $imgPath
   *   Path to the media/images folder.
   *
   * @return string
   *   Icon HTML.
   */
  public static function statusToIcons($status, $substatus, $imgPath) {
    $r = '';
    if (!empty($status)) {
      $hint = self::statusToLabel($status, $substatus, NULL);
      $images = [];
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
          $r .= " :: <img width=\"12\" height=\"12\" src=\"{$imgPath}nuvola/$image.png\" title=\"$hint\" alt=\"$hint\"/>";
        }
      }
    }
    return $r;
  }

  /**
   * Converts a status and substatus into a readable label.
   *
   * E.g. "accepted", or "accepted:considered correct".
   *
   * @param string $status
   *   Status code from database (e.g. 'C').
   * @param int $substatus
   *   Substatus value from database.
   * @param string $query
   *   Query valid for the record (null, Q or A).
   *
   * @return string
   *   Status label text.
   */
  public static function statusToLabel($status, $substatus, $query) {
    $labels = [];
    self::translateStatusTerms();
    // Grab the term for the status. We don't need to bother with not reviewed status if
    // substatus is plausible.
    if (!empty(self::$statusTerms[$status]) && ($status !== 'C' || (int) $substatus !== 3)) {
      $labels[] = \lang::get(self::$statusTerms[$status]);
    }
    elseif ((int) $substatus !== 3) {
      $labels[] = \lang::get('Unknown');
    }
    if ($substatus && !empty(self::$substatusTerms[$substatus])) {
      $labels[] = \lang::get(self::$substatusTerms[$substatus]);
    }
    switch ($query) {
      case 'Q':
        $labels[] = \lang::get('Queried');
        break;

      case 'A':
        $labels[] = \lang::get('Query answered');
        break;
    }
    return implode('::', $labels);
  }

  /**
   * Ensure a provided value is boolean, not a string representation.
   *
   * Use when you are uncertain of the type or representation of a value that
   * should be boolean (e.g. a query string parameter from $_GET). Empty
   * strings are interpreted as NULL.
   *
   * @param mixed $value
   *   Provided value, e.g. 't', 'true', TRUE, 'f', 'false', FALSE.
   *
   * @return null|bool
   *   Boolean equivalent.
   */
  public static function toBool($value) {
    if (in_array($value, ['', 'null', 'NULL', NULL])) {
      return NULL;
    }
    elseif (in_array($value, ['t', 'f'])) {
      // Filter_var() doesn't handle 't' & 'f'.
      return $value === 't';
    }
    else {
      return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
  }

  /**
   * Convert a timestamp into readable format (... ago).
   *
   * @param int $timestamp
   *   The date time to convert.
   *
   * @return string
   *   The output string.
   */
  public static function timestampToTimeAgoString($timestamp) {
    $difference = time() - $timestamp;
    // Having the full phrase means that it is fully localisable if the
    // phrasing is different.
    $periods = [
      \lang::get("{1} second ago"),
      \lang::get("{1} minute ago"),
      \lang::get("{1} hour ago"),
      \lang::get("Yesterday"),
      \lang::get("{1} week ago"),
      \lang::get("{1} month ago"),
      \lang::get("{1} year ago"),
      \lang::get("{1} decade ago")
    ];
    $periodsPlural = [
      \lang::get("{1} seconds ago"),
      \lang::get("{1} minutes ago"),
      \lang::get("{1} hours ago"),
      \lang::get("{1} days ago"),
      \lang::get("{1} weeks ago"),
      \lang::get("{1} months ago"),
      \lang::get("{1} years ago"),
      \lang::get("{1} decades ago")
    ];
    $lengths = ['60', '60', '24', '7', '4.35', '12', '10'];
    for ($j = 0; (($difference >= $lengths[$j]) && ($j < 7)); $j++) {
      $difference /= $lengths[$j];
    }
    $difference = round($difference);
    if ($difference == 1) {
      $text = str_replace('{1}', $difference, $periods[$j]);
    }
    else {
      $text = str_replace('{1}', $difference, $periodsPlural[$j]);
    }
    return $text;
  }

  /**
   * Convert the list of status/substatus terms and into a translated version.
   */
  private static function translateStatusTerms() {
    if (!self::$statusTermsTranslated) {
      foreach (self::$statusTerms as &$term) {
        $term = \lang::get($term);
      }
      foreach (self::$substatusTerms as &$term) {
        $term = \lang::get($term);
      }
      self::$statusTermsTranslated = TRUE;
    }
  }

}
