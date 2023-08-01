<?php

/**
 * @file
 * Language utility functions.
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

/**
 * List of methods that assist with Indicia and Drupal language interactions.
 */

/**
 * Get an ISO 639-2 three-character language code.
 *
 * Either returns the code of the language in use on the host site or converts
 * from a supplied ISO 639-1 two-character code.
 *
 * @param string $lang
 *   An ISO 639-1 two-character code or NULL.
 *   If NULL, the code of the language in use on the host site is returned.
 *
 * @return string
 *   The three-character language code corresponding to the $lang parameter.
 *
 * @todo Complete the list.
 */
function iform_lang_iso_639_2($lang = NULL) {
  // @todo Check the following for Drupal 8 compatibility
  if (!$lang) {
    $lang = hostsite_get_user_field('language');
  }
  if (strlen($lang) === 3) {
    return $lang;
  }
  // If there is a sub-language, ignore it (e.g. en-GB becomes just en).
  // @todo may want to handle sub-languages
  $lang = explode('-', $lang);
  $lang = $lang[0];
  $list = [
    'bg' => 'bul', // Bulgarian.
    'ca' => 'cat', // Catalan.
    'hr' => 'hrv', // Croatian.
    'cs' => 'ces', // Czech Republic.
    'da' => 'dan', // Danish.
    'nl' => 'nld', // Dutch.
    'en' => 'eng', // English.
    'fi' => 'fin', // Finnish.
    'fr' => 'fra', // French.
    'gd' => 'gla', // Gaelic.
    'de' => 'deu', // German.
    'hu' => 'hun', // Hugarian.
    'ga' => 'gle', // Irish.
    'it' => 'ita', // Italian.
    'ja' => 'jpn', // Japanese.
    'la' => 'lat', // Latin.
    'lt' => 'lit', // Lithuanian.
    'lb' => 'ltz', // Luxembourgish.
    'no' => 'nor', // Norwegian.
    'pl' => 'pol', // Polish.
    'pt' => 'por', // Portuguese.
    'ru' => 'rus', // Russian.
    'sl' => 'slv', // Slovenian.
    'es' => 'spa', // Spanish.
    'sv' => 'swe', // Swedish.
    'tr' => 'tur', // Turkish.
    'cy' => 'cym', // Welsh.
  ];
  return $list[$lang];
}
