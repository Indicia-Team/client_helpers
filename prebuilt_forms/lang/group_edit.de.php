<?php

/**
 * @file
 * Language terms for group edit page in German.
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
 * @link https://github.com/Indicia-Team/client_helpers
 */

global $custom_terms;

/**
 * Language terms for the group_edit form.
 */
$custom_terms = array(
  "group's" => "Aktivit&auml;ten",
  'groups' => 'Gruppe',
  'group' => 'Gruppe',
  '{1} type' => 'Art der {1}',
  'What sort of {1} is it?' => 'Welcher Art ist die {1} ?',
  'Fill in details of your {1} below' => 'Geben Sie unten Details zu ihrer {1} an.',
  '{1} name' => 'Name der {1}',
  'Provide the full title of the {1}' => 'Geben Sie bitte den vollständigen Titel der {1} an.',
  'Code' => 'Code/Kürzel',
  'Provide a code or abbreviation identifying the {1}' => 'Geben Sie bitte (optional) einen Code oder ein Kürzel für Ihre {1} an.',
  '{1} description' => 'Beschreibung der {1}',
  '{1} parent' => 'Übergeordnete {1}',
  'LANG_Filter_Instruct' => 'Ihre {1} hat möglicherweise nur an bestimmten Daten Interesse, '
    . 'z.B. Beobachtungen aus einem Schutzgebiet, Beobachtungen zu einer speziellen Artengruppe oder '
    . 'Beobachtungen innerhalb eines bestimmten Zeitraums. Wenn dies der Fall ist, können Sie die folgenden '
    . 'Filter verwenden, um zu definieren, welche Daten für die Mitglieder der {1} von Interesse sind, und '
    . 'in den Berichtsseiten der {1} angezeigt werden sollen. Wenn sie zum Beispiel die Arten '
    . 'oder Artgruppen angeben möchten, die für Ihre {1} relevant sind, verwenden Sie die Option '
    . '<strong>Was</strong>, wenn Ihre {1} ausschließlich in einer bestimmten geografische Region aktiv ist, '
    . 'verwenden sie die Option <strong>Wo</strong>.',
  'LANG_Pages_Instruct' => 'Verwenden Sie die folgende Tabelle, um Seiten zu definieren, die Sie '
    . 'für die Nutzung durch {1} -Mitglieder verfügbar machen wollen. Diese Seiten erscheinen dann '
    . 'in den Link-Spalten in der Liste der {2}. Sie brauchen einen Link-Titel nur dann anzugeben, '
    . 'wenn Sie den Vorgabe-Seitennamen überschreiben möchten, wenn Sie Zugriff über Ihr {1} haben.'
    . 'Berücksichtigen Sie, dass Sie zumindest eine Eingabeform zu der {1} verlinken müssen, '
    . 'wenn Sie Ihren Mitgliedern erlauben möchten, explizit Daten in das {1} zu speichern. '
    . 'Sie müssen mindestens eine Berichtsseite verlinken, damit Ihre Mitglieder Zugriff auf die Daten der {1} haben.',
  'LANG_Record_Inclusion_Instruct_1' => 'Diese Option bestimmt, wie Beobachtungsdaten gespeichert werden, '
    . 'um sie für Berichtsausgaben unter {2} zu berücksichtigen.',
  'LANG_Record_Inclusion_Instruct_Sensitive' => 'Beachten Sie, dass einige Funktionen, wie '
    . 'etwa die Erlaubnis, sensible Daten mit voller Genauigkeit zu sichten, '
    . 'davon abhängt, ob die Daten über {1} eingegeben wurden. ',
  'LANG_Record_Inclusion_Instruct_2' => 'Wenn Sie angeben, dass Daten, die einer {1} zugeordnet werden sollen, über '
    . 'eine {1} -Eingabeform eingegeben werden müssen, stellen Sie sicher, dass '
    . 'Sie zumindest eine Eingabeform im Abschnitt <strong>Seiten der {2} </strong>unten auswählen. '
    . 'Andernfalls haben Gruppen-Mitglieder keine Möglichkeit, Daten für die {1} einzugeben.',
  'LANG_Description_Field_Instruct' => 'Beschreibung und Hinweise zur {1}. '
    . 'Diese wird in der Übersichtsliste zu Seiten der {1} angezeigt, damit andere Anwender Ihre {1} finden können.',
  'LANG_From_Field_Instruct' => 'Wenn Ihre Gruppe nur eine begrenzte Zeit aktiv ist, geben Sie bitte ein Start- und ein Enddatum an.',
  'LANG_To_Field_Instruct' => 'Geben Sie hier an, bis wann Ihre Gruppe voraussichtlich aktiv sein wird.',
  'LANG_Admins_Field_Instruct' => 'Suchen Sie nach weiteren Mitgliedern, um Sie der Gruppe der '
    . 'Administratoren dieser {1} hinzuzufügen. Geben Sie dazu die ersten Buchstaben des Mitglieds ein, das System versucht dann den Namen '
    . 'zu vervollständigen. Klicken Sie danach auf den Button Hinzufügen. '
    . 'Nur registrierte Anwender können als Administrator hinzugefügt werden.',
  'LANG_Members_Field_Instruct' => 'Suchen Sie nach Anwendern, die Sie der Kartiergruppe als Mitglied '
    . 'hinzufügen möchten . Geben Sie dazu die ersten Buchstaben des Mitglieds ein, das System sucht dann nach Entsprechungen in der Liste der registrierten Anwender '
    . 'und vervollständigt die Eingabe. Klicken Sie danach auf den Button Hinzufügen.'
    . 'Nur registrierte Anwender können als Mitglied der Kartierguppe hinzugefügt werden.',
   'Create {1}' => '{1} erstellen',
   'Update {1} settings' => 'Einstellungen zur {1} aktualisieren',
   '{1} pages' => 'Seiten der {1}',
   'How users join this {1}' => 'Wie können sich Anwender an dieser {1} beteiligen?',
   'Who can access the page?' => 'Berechtigung zur Seite',
   'Available to anyone' => 'Verfügbar für Jedermann',
   'Available only to group members' => 'Verfügbar nur für Gruppenmitglieder',
   'Available only to group admins' => 'Verfügbar nur für Admins',
   'Other {1} members' => 'Andere {1}-Mitglieder',
   '{1} administrators' => 'Administratoren der {1}',
   'If the {1} will only be active for a limited period of time (e.g. an event or bioblitz) ' .
          'then please fill in the start and or end date of this period in the controls below. This helps to prevent people joining after ' .
          'the {2}.'  => 'Wenn die {1} nur über einen bestimmten Zeitraum besteht, können Sie diesen hier angeben.',
    '{1} active from' => '{1} aktiv von ',
    'active to' => 'Aktiv bis ',
    'Show records at full precision' => 'Zeige Beobachtungen mit voller Genauigkeit',
    'Any sensitive records added to the system are normally shown blurred to a lower grid reference precision. If this box ' .
            'is checked, then group members can see sensitive records explicitly posted for the {1} at full precision.' => 'Sensible Daten werden normalerweise nur mit verringerter Genauigkeit dargestellt. Aktivieren Sie das Kästchen, wenn die Gruppenmitglieder auch sensible Daten mit voller Genauigkeit sehen sollen.',
    'Records are private' => 'Daten sind nicht-öffentlich',
    'Tick this box if you want to withold the release of the records from this {1} until a ' .
          'later point in time, e.g. when a project is completed.' => 'Aktivieren Sie das Kästchen, wenn die Daten ihrer Gruppe bis zu einem späteren Zeitpunkt zurückgehalten werden sollen, bis sie veröffentlicht werden.',
    'You are about to release the records belonging to this group. Do not proceed unless you intend to do this!' => 'Möchten Sie die Daten dieser Gruppe öffentlich machen? Wenn Sie unsicher sind, sollten Sie diese Aktion nicht fortsetzen.',
    'Filter for user group' => 'Filter nach Anwendergruppe',
    'Link caption' => 'Linkbezeichnung',
    'How to decide which records to include in the {1} reports' => 'Nach welchen Kriterien sollen Daten in Gruppen-Reports einbezogen werden?',
    'Include records on reports if' => 'Daten in Berichte einbeziehen, wenn',
    'they were posted by a group member and match the filter defined above ' .
            'and they were submitted via a {1} data entry form' => 'sie durch ein Gruppenmitglied über eine Eingabemaske der {1} eingegeben wurden und den Filterkriterien entsprechen.',
    'they were posted by a group member and match the filter defined above, ' .
            'but it doesn\'t matter which recording form was used' => 'sie den Filterkriterien entsprechen und durch ein Gruppenmitglied eingegeben wurden -unabhängig über welche Eingabeform-.',
    'they match the filter defined above but it doesn\'t matter who ' .
            'posted the record or via which form' => 'sie den Filterkriterien entsprechen -unabhängig davon, wer sie eingegeben hat-.',
    'No licence selected' => 'Keine Lizenz ausgewählt',
    'Licence for records' => 'Lizenzen für Beobachtungsdaten',
    'Choose a licence to apply to all records added explicitly to this {1}.' => 'Wählen Sie eine Lizenz, die für Daten dieser Gruppe gelten soll.',
    'Records that are of interest to the {1}' => 'Filter auf Datensätze, die für die {1} von Interesse sind',
    "Please ensure that the list of administrators and group members only includes each person once." => "Stellen Sie sicher, dass die Gruppenmitglieder nicht mehrfach ausgewählt werden.",
    "The group with ID $id could not be found." => "Die Gruppe mit der ID $id wurde nicht gefunden.",
    'You are trying to edit a group you don\'t have admin rights to.' => 'Sie versuchen eine Gruppe zu bearbeiten, zu der Sie keine Administrationsrechte haben.',
    'Survey datasets' => 'Projektdaten',
    'Input forms' => 'Eingabeform',
    'records from' => 'Daten von',
    'Advanced' => 'Erweitert',
    'Leave any list unticked to leave that list unfiltered.' => 'Wenn Sie keine Liste auswählen, bleiben sie ungefiltert',
    'Include' => 'Inklusive',
    'Exclude' => 'Exklusive'
);
