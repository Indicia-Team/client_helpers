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
Provide a list of global field values which apply to every row in the import file.
TXT;

$default_terms['import2mappingsFormIntro'] = <<<TXT
Select which database field each of your import file's columns should be mapped to.
TXT;

$default_terms['import2lookupMatchingFormIntro'] = <<<TXT
For data values that need to be mapped to an exact term (e.g. a species name, or a term from a list
of options), please ensure the values are correctly matched using the controls below. Data values
found in your import file which don't exactly match one of the available options in the database,
or values that match multiple potential options in the database, are listed in the column on the
left and the terms you can select from for each value are in the column on the right. You must
match all the values and save them before proceeding with the import.
TXT;

$default_terms['import2validationFormIntro'] = <<<TXT
The following validation issues have been detected.
TXT;

$default_terms['import2summaryPageIntro'] = <<<TXT
Please check the following settings before proceeding with the import.
TXT;

$default_terms['import2preprocessPageIntro'] = <<<TXT
Preparing to import your data.
TXT;

$default_terms['import2doImportPageIntro'] = <<<TXT
Please wait while your data are imported.
TXT;

$default_terms['import2requiredFieldsIntro'] = <<<TXT
The following database fields are required for the selected dataset. Please ensure they are all
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

$default_terms['Deleted'] = 'Deleted (for existing records)';
$default_terms['External key'] = 'External key (your reference)';
$default_terms['Id'] = 'ID (primary key for existing records)';
