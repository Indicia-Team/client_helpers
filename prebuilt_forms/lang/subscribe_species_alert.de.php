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

global $custum_terms;

/**
 * Provides a list of default localisable terms used by the lang class.
 */
$custom_terms = array(
  'You cannot modify a species alert subscription created by someone else' => 'Sie können nur Abonnements zu Artmeldungen ändern, die Sie selbst erstellt haben.',
  'Your details' => 'Angaben zum Anwender',
  'First name' => 'Vorname',
  'Last name' => 'Nachname',
  'Email' => 'Email-Adresse',
  'Alert criteria' => 'Kriterien für die Meldung',
  'Alert species' => 'Taxa für die Meldung',
  'Select the species you are interested in receiving alerts in ' .
          'relation to if you want to receive alerts on a single species.' => 'Wenn Sie die Meldung von Artbeobachtungen auf eine einzelne Art innerhalb eines Gebietes beschränken möchten, wählen Sie die Art aus.',
  '<Select a species list>' => 'Artreferenz wählen',
  'Select full species lists' => 'Alle Arten einer Artenliste wählen',
  'If you want to restrict the alerts to records of any ' .
            'species within a species list, then select the list here.' => 'Wenn Sie die Meldungen zu allen Arten einer Artreferenz abonnieren möchten, wählen Sie bitte die Liste aus.',
  'Select location' => 'Raumbezug wählen',
  'If you want to restrict the alerts to records within a certain boundary, select it here.' => 'Wenn Sie die Meldungen auf einen bestimmten Raum einschränken möchten, wählen Sie diesen bitte aus. Sie haben verschiedene Ortstypen zur Auswahl.',
  '<Select boundary>' => 'Ort auswählen',
  'Alert on initial entry' => 'Meldung bei Erstfund',
  'Tick this box if you want to receive a notification when the record is first input into the system.' => 'Klicken Sie hier, wenn eine Benachrichtigung über einen Erstfund zu dieser Art in diesem Gebiet erstellt werden soll.',
  'Alert on verification as correct' => 'Meldung nach erfolgter Datenprüfung',
  'Tick this box if you want to receive a notification when the record has been verified as correct.' => 'Klicken Sie hier, wenn eine Benachrichtigung nach erfolgter Datenprüfung erstellt werden soll. Diese wird nur erstellt, sofern die Beobachtung als korrekt akzeptiert wurde.',
  "Subscribe" => "Abonnement erstellen",
  'Your subscription has been saved.' => 'Ihr Abonnement wurde erstellt',
  'There was a problem saving your subscription.' => 'Ihr Abonnement konnte leider nicht erstellt werden'

);
