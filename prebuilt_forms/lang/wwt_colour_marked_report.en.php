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
 * @link https://github.com/indicia-team/client_helpers/
 */

global $custom_terms;

/**
 * Language terms for the wwt_colour_marked_report form.
 */
$custom_terms = [
  'LANG_Edit' => 'Edit',
  'LANG_Add_Sample' => 'Add New Sample',
  'LANG_Add_Sample_Single' => 'Add Single Occurrence',
  'LANG_Add_Sample_Grid' => 'Add List of Occurrences',

  'LANG_Tab_aboutyou' => 'About You',
  'LANG_Tab_Instructions_aboutyou' => '<strong>About You</strong><br/>Please tell us about yourself first.',
  // Can also add entries each of the attribute captions used in this tab.
  // Note these do not have LANG_ prefixes.

  'LANG_Tab_species' => 'What Did You See?',
  'LANG_Tab_Instructions_species' => '<strong>Species Selection</strong><br/>Please click on all the species that you observed, enter the relevant additional information, and then move on to the next stage.',
  // The species and presence column titles in the grid may be set by adding entries for 'species_checklist.species'
  // and 'species_checklist.present'. All the others are not translated, but taken directly from the attribute caption.
  'LANG_Sample_Comment_Label' => 'Description of Others field',

  'LANG_Tab_place' => 'Where Was It?',
  'LANG_Tab_Instructions_place' => '<strong>Place Selection</strong><br/>Please either enter the spatial reference of the observation if you know it, or click on the map to specify the place as accurately as you can.',
  'LANG_SRef_Label' => 'Spatial Ref',
  'LANG_Location_Label' => 'Location',
  'LANG_Location_Name' => 'Site Name',
  'LANG_Georef_Label' => 'Search for a place on the map',
  // The search button may be changed by adding an entry for 'search'

  'LANG_Other_Information_Tab' => 'Other Information',
  'LANG_Tab_Instructions _otherinformation' => '<strong>Other Information</strong><br/>Please tell us when the observation took place, the biotope, whether a voucher specimen was taken (in the case where identification was difficult, to allow verification), and whether the observation details have been completed.',
  'LANG_Date' => 'Date',
  // Below gives an example of setting the biotope and voucher attribute captions used in this tab.
  // Note these do not have LANG_ prefixes.
  'MNHNL Collaborators 1 Biotope' => 'Biotope',
  'Voucher' => 'Voucher Specimen taken?',
  // Can also add entries for 'Yes' and 'No' for the voucher attribute
  'LANG_Record_Status_Label' => 'Record Status',
  'LANG_Record_Status_I' => 'In Progress',
  'LANG_Record_Status_C' => 'Completed',
  'LANG_Record_Status_V' => 'Verified', // NB not used
  'LANG_Image_Label' => 'Upload Image',
  'LANG_Save' => 'Save',
  'validation_required' => 'Please enter a value for this field',
];