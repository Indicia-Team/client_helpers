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

$default_terms['import2fileSelectFormIntro'] = <<<TXT
Choose a *.xls, *.xlsx or *.csv file containing the data you would like to import then click Next
step.
TXT;

$default_terms['import2globalValuesFormIntro'] = <<<TXT
Select options for importing records. These options will be applied to all records in the import file.
TXT;

$default_terms['import2mappingsFormIntro'] = <<<TXT
Select which database field each of your import file's columns should be mapped to. Any columns in
your file that do not need to be imported, or that have no matches, should be set to
“- not imported -“.<br><br>
By default this page shows all the “standard” attributes that will be sufficient for typical
biological record formats. If needed you can add “advanced”  attributes to the list - these provide
additional options but are not always straightforward to use.

TXT;

$default_terms['import2lookupMatchingFormIntro'] = <<<TXT
For data values that need to be mapped to an exact term (e.g. a species name, or a term from a list
of options), the importer will automatically match the imported term to the equivalent in the
database where possible. If an exact match cannot be found, then once the background processing is
complete a form will appear below which will allow you to search for or select the correct value to
use from the database for each unmatched term or species name. Data values found in your import
file which don't exactly match one of the available options in the database, or values that match
multiple potential options in the database, are listed in the column on the left and the terms you
can select from for each value are in the column on the right. You must match all the values and
save them before proceeding with the import.
TXT;

$default_terms['import2validationFormIntro'] = <<<TXT
The following validation issues have been detected.
TXT;

$default_terms['import2summaryPageIntro'] = <<<TXT
Here is a summary of the import that will be carried out. Please check the settings – if anything
needs changing you can go back to previous steps in the import process. Once you are happy that the
details are correct scroll down and click "Start importing records".
TXT;

$default_terms['import2preprocessPageIntro'] = <<<TXT
Preparing to import your data.
TXT;

$default_terms['import2doImportPageIntro'] = <<<TXT
Please wait while your data are imported.
TXT;

$default_terms['import2requiredFieldsIntro'] = <<<TXT
The following database fields are required for the selected survey dataset. Please ensure they are all
mapped to columns in your import file before proceeding.
TXT;

$default_terms['optionGroup-occurrence'] = 'Occurrence';
$default_terms['optionGroup-occurrence_medium'] = 'Occurrence photos and other media';
$default_terms['optionGroup-occAttr'] = 'Occurrence attributes for selected survey';
$default_terms['optionGroup-sample'] = 'Sample';
$default_terms['optionGroup-sample_medium'] = 'Sample photos and other media';
$default_terms['optionGroup-smpAttr'] = 'Sample attributes for selected survey';

$default_terms['optionGroup-occurrence-shortLabel'] = 'Occurrence';
$default_terms['optionGroup-occurrence_medium-shortLabel'] = 'Occurrence media';
$default_terms['optionGroup-occAttr-shortLabel'] = 'Occurrence attributes';
$default_terms['optionGroup-sample-shortLabel'] = 'Sample';
$default_terms['optionGroup-sample_medium-shortLabel'] = 'Sample media';
$default_terms['optionGroup-smpAttr-shortLabel'] = 'Sample attributes';
// Improved field name captions.
$default_terms['occurrence:comment'] = 'Occurrence comment';
$default_terms['occurrence:external_key'] = 'External record ID';
$default_terms['location:external_key'] = 'External location ID';
$default_terms['sample:comment'] = 'Sample comment';
$default_terms['sample:external_key'] = 'External sample ID';
$default_terms['sample:record_status'] = 'Sample verification status';

$default_terms['Deleted'] = 'Deleted (for existing records)';
$default_terms['Id'] = 'ID (primary key for existing records)';
$default_terms['Taxa taxon list (lookup in database)'] = 'Species or taxon name';
