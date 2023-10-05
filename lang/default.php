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

global $default_terms;
/**
 * Provides a list of default localisable terms used by the lang class.
 */
$default_terms = [
  'add' => 'Add',
  'add row' => 'Add Row',
  'back' => 'Back',
  'click here' => 'Click here',
  'close' => 'Close',
  'email' => 'Email',
  'enter additional species' => 'Enter additional species',
  'error loading control' => 'Error loading control',
  'file too big for webserver' => 'The image file cannot be uploaded because it is bigger than the size limit allowed by the server which this data entry page is running on.',
  'file too big for warehouse' => 'The image file cannot be uploaded because it is larger than the maximum file size allowed.',
  'first name' => 'First Name',
  'loading' => 'Loading',
  'locked tool-tip' => 'Click here to unlock the control\'s value for editing.',
  'metaFields' => 'Other Fields',
  'next step' => 'Next Step',
  'phone number' => 'Phone Number',
  'prev step' => 'Previous Step',
  'save' => 'Save',
  'search' => 'Search',
  'search for place on map' => 'Search for a place on the map',
  'spatial ref' => 'Spatial ref.',
  'species_checklist.species' => 'Species',
  'species_checklist.present' => 'Present',
  'species_checklist.absent' => 'Absent',
  'submit ok but file failed' => 'Your record was successfully submitted. However, an error occurred when saving an image file. The error was:',
  'surname' => 'Surname',
  'unlocked tool-tip' => 'Click here to lock the control\'s current value so that it is reused the next time you input data using this form.',
  'upload error' => 'An error occurred uploading the file.',
  'upload_not_available' => 'The uploaded file is no longer available. Please try uploading again.',
  'validation_required' => 'Please enter a value for this field',
  'validation_email' => 'Please enter a valid email address',
  'validation_url' => 'Please enter a valid URL',
  'validation_dateISO' => 'Please enter a valid date in yyyy-mm-dd format',
  'validation_date' => 'Please enter a valid date',
  'validation_time' => 'Please enter a valid 24Hr Time in HH:MM format',
  'validation_digit' => 'Please enter only digits',
  'validation_integer' => 'Please enter a whole number',
  // Default labels for various database fields.
  'occurrence:taxa_taxon_list_id' => 'Species',
  'sample:date' => 'Date',
  'sample:entered_sref' => 'Spatial reference',
  'sample:entered_sref_system' => 'Spatial reference system',

  // Spatial reference systems.
  'sref:OSGB' => 'British National Grid',
  'sref:OSIE' => 'Irish Grid',
  'sref:utm30ed50' => 'Channel Islands Grid (UTM ED50)',
  'sref:4326' => 'WGS84 (decimal lat,long)',
  'sref:2169' => 'LUREF (x,y)',
  'sref:3006' => 'SWEREF99 TM',
  'sref:3021' => 'Swedish Grid (RT90 2.5 gon v)',

  // Define the captions for fields in the data dictionary.
  'dd:occurrence:fk_taxa_taxon_list' => 'Species or taxon name',
  'dd:occurrence:all_info_in_determinations' => 'Use determinations table for identification information (Y/N)',
  // Same for associated occurrences.
  'dd:occurrence_2:fk_taxa_taxon_list' => 'Second species or taxon name',
  'dd:occurrence_2:all_info_in_determinations' => 'Use determinations table for identification information in second occurrence (Y/N)',
  'dd:sample:entered_sref' => 'Grid ref or other spatial ref',
  'dd:sample:entered_sref_system' => 'Spatial reference system',

  // Import model name/field name prefix overrides.
  'smpAttr' => 'Sample custom attributes',
  'occAttr' => 'Occurrence custom attributes',
  'locAttr' => 'Location custom attributes',
  'taxAttr' => 'Taxon custom attributes',
  'psnAttr' => 'Person custom attributes',
  'fkFilter' => 'Lookup filters',

  'Click to Filter What' => 'Select a list of species or species groups to include',
  'Click to Filter Where' => 'Define the geographic area, site or map reference to include',
  'Click to Filter When' => 'Define a date range for records to include',
  'Click to Filter Who' => 'Define whose records to include',
  'Click to Filter Occ_id' => 'Select records by record ID',
  'Click to Filter Quality' => 'Select records based on quality criteria such as verification status or presence of photos',
  'Click to Filter Source' => 'Select records based on source website, survey or input form',
];

// Some bigger bits of text better handled with HEREDOC.
$default_terms['import_settings_instructions'] = <<<TEXT
Before proceeding with the import, please specify the following settings that will apply to every record in the import
file. Any settings that you do not specify here can be supplied in the import file on a row by row basis by mapping the
setting to the appropriate column in the next step.
TEXT;
$default_terms['column_mapping_instructions'] = <<<TEXT
Please map each column in the CSV file you are uploading to the associated attribute in the database. We've tried to
match your columns to the available attributes where possible so check any automatically selected attributes in the
<strong>Maps to attribute</strong> column before proceeding. If you plan to repeat imports from similar spreadsheets in
future you can use the tickboxes to remember your choices.
TEXT;
$default_terms['partial_commits_download_error_file_instructions'] = <<<TEXT
As part of your upload was unsuccessful, download the generated CSV file which holds the rows of data that did not
upload. Find the column called "Problem" which lists the associated error for each row. Correct the error, and then
re-upload the CSV file containing <strong>only</strong> the rows of data which did not upload correctly. Please do not
re-upload the whole dataset again.
TEXT;
$default_terms['no_commits_download_error_file_instructions'] = <<<TEXT
Problems have been found in your upload file. Please download the generated CSV file
which holds the rows of data that did not upload and find the column called "Problem" which lists the associated
error for each row. Once you have corrected all the errors in the <strong>original file</strong>, please re-upload the original file again.
No rows will be uploaded to the database until all errors have been corrected.
TEXT;

$default_terms['gps_precision_instructions'] = <<<TEXT
This is the precision (normally given as a radius in metres) reported by most GPS devices or phones. This field is
only enabled for 10-figure OS grid references. These represent 1 m accuracy, however, this is normally associated with
some degree of error, which is the GPS precision recorded here. Precise grid references reported to a lower accuracy
than 10-figures are assumed to represent the corresponding OS square (e.g. a 100 m or 10 m square), and so additional
precision information is not required.
TEXT;
