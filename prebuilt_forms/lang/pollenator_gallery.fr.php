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
 * @package	Client
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

global $custom_terms;

/**
 * Language terms for the pollenator insect form.
 *
 * @package	Client
 */
$custom_terms = array(
	'LANG_Invocation_Error' => "Avertissement: GET valide les param�tres dans l'URL"
	,'LANG_Insufficient_Privileges' => "Vous n'avez pas de privil�ges suffisants pour acc�der � cette page."
	,'LANG_Please_Refresh_Page' => "Une erreur s'est produite. S'il vous pla�t, actualisez la page."
	
	,'LANG_Main_Title' => 'Les Collections'
	,'LANG_Enter_Filter_Name' => 'Entrer un nom pour ce filtre'
	,'LANG_Save_Filter_Button' => 'Enregistrer'
	,'LANG_Collection' => 'Retour � la Collection'
	,'LANG_Previous' => 'Pr�c�dent'
	,'LANG_Next' => 'Suivant'
	,'LANG_Add_Preferred_Insect' => 'enregistrer dans mes insectes prefer�s'
	,'LANG_Validate' => 'Valider'
	,'LANG_Add_Preferred_Collection'  => 'Enregistrer dans mes collection prefer�s'
	,'LANG_List' => 'Retour � la Liste'
	,'LANG_No_Collection_Results' => 'Aucune collection ne correspond � cette recherche.'
	,'LANG_No_Insect_Results' => 'Aucun insecte ne correspond � cette recherche.'

	,'LANG_Indentification_Title' => 'Identification'
	,'LANG_Doubt' => "�mettre un doute"
	,'LANG_Doubt_Comment' => 'Commentez votre doute :'
	,'LANG_Default_Doubt_Comment' => "J'ai exprim� un doute sur cette identification parce que..."
	,'LANG_New_ID' => 'Proposer une nouvelle identification'
	,'LANG_Launch_ID_Key' => "Lancer la cl� d'identification"
	,'LANG_Cancel_ID' => "Abandonner la cl� d'identification"
	,'LANG_Taxa_Returned' => "Taxons retourn� par la cl� d'identification:"
	,'LANG_ID_Unrecognised' => 'Les suivants ne sont pas reconnus: '
	,'LANG_Taxa_Unknown_In_Tool' => 'Taxon inconnu de la cl�'
	,'LANG_Det_Type_Label' => 'Identification'
	,'LANG_Det_Type_C' => 'Correct, valid�'
	,'LANG_Det_Type_X' => 'Non identifi�'
	,'LANG_Choose_Taxon' => "Choisissez un taxon dans la liste"
	,'LANG_Identify_Insect' => 'Indiquer le nom de cet insecte:'
	,'LANG_More_Precise' => 'D�nomination pr�cise'
	,'LANG_ID_Comment' => 'Commentez �ventuellement votre identification :'
	,'LANG_Default_ID_Comment' => "Precisions sur ma nouvelle identification..."
	,'LANG_Flower_Species' => "Nom de la Fleur"
	,'LANG_Flower_Name' => "Nom de la Fleur"
	,'LANG_Insect_Species' => "Nom de l'insecte"
	,'LANG_Insect_Name' => "Nom de l'insecte"
	,'LANG_History_Title' => 'Ancienne identification'
	,'LANG_Last_ID' => 'Derni�re(s) identification(s)'
	,'LANG_Display' => 'Afficher'
	,'LANG_No_Determinations' => 'Aucun identification enregistr�e.'
	,'LANG_No_Comments' => 'Aucun commentaire enregistr�.'
	
	,'LANG_Filter_Title' => 'Filtres'
	,'LANG_Name_Filter_Title' => 'Pseudo'
	,'LANG_Name' => "Pseudo"
	,'LANG_Date_Filter_Title' => 'Date'
	,'LANG_Flower_Filter_Title' => 'Fleur'
	,'LANG_ID_Status' => "Statut de l'identification"
	,'LANG_ID_Status_Choose' => "Choisissez un statut dans la liste"
	,'LANG_ID_Status_Unidentified' => "non identifi�"
	,'LANG_ID_Status_Initial' => "identifi� par l'auteur"
	,'LANG_ID_Status_Doubt' => "doute �mis sur l'identification"
	,'LANG_ID_Status_Validated' => "valid� par un expert"
	,'LANG_ID_Type' => "Type de l'identification"
	,'LANG_ID_Type_Choose' => "Choisissez un type dans la liste"
	,'LANG_ID_Type_Single' => "un seul taxon"
	,'LANG_ID_Type_Multiple' => "multi-taxon"
	,'LANG_Insect_Filter_Title' => 'Insecte'
	,'LANG_Conditions_Filter_Title' => "Conditions d'observation"
	,'LANG_Location_Filter_Title' => 'Localisation'
	,'LANG_Georef_Label' => '<strong>Localiser la fleur</strong> : utiliser les champs et/ou la carte ci-dessous'
	,'LANG_Georef_Notes' => "(Le nom d'un village, d'une ville, d'une r�gion, d'un d�partement ou un code postal.)"
    ,'msgGeorefSelectPlace' => "S�lectionnez dans les endroits suivants qui correspondent � vos crit�res de recherche, puis cliquez sur la carte pour indiquer l'emplacement exact"
    ,'msgGeorefNothingFound' => "Aucune ville portant ce nom n'a �t� trouv�e. Essayez le nom d'une ville proche."
	,'LANG_INSEE' => "No/nom de INSEE/D�partement/R�gion"
	,'LANG_Search'=>'Chercher'
	,'LANG_For'=>'pour'
	,'LANG_NO_INSEE' => "Il n'ya pas de zone qui r�pond � ce crit�re de recherche."
	,'LANG_Max_INSEE_Features' => 'Vous avez atteint le nombre maximum de zones (<>) qui peut �tre retourn� par cette recherche. La liste est abr�g�e. Pr�cisez �ventuellement votre recherche.'
	,'LANG_INSEE_Search_Limit' => 'Vous ne pouvez pas restreindre une recherche � plus de <> zones.'
	,'LANG_Search_Insects' => 'Rechercher des Insectes'
	,'LANG_Search_Collections' => 'Rechercher des collections'
	,'LANG_Insects_Search_Results' => 'Insectes'
	,'LANG_Collections_Search_Results' => 'Collections'
	,'LANG_Validate_Page' => "Valider l'identification de l'ensemble des photos de cette page"
	,'LANG_Bulk_Validation_Error' => 'An error has occurred during this bulk validation.'
	,'LANG_Bulk_Page_Validation_Completed' => "Votre validation a bien �t� prise en compte."
	,'LANG_Confirm_Bulk_Page_Validation'=>'Voulez-vous valider toutes les identifications de cette page?'
	,'LANG_Validate_Taxon' => "Valider l'ensemble des identifications pour ce taxon"
	,'LANG_Bulk_Taxon_Validation_Error' => 'An error has occurred during this bulk validation.'
	,'LANG_Bulk_Taxon_Validation_Completed' => "Votre validation a bien �t� prise en compte."
	,'LANG_Confirm_Bulk_Taxon_Validation'=>'Voulez-vous valider toutes les identifications de ce taxon?'
	,'LANG_Bulk_Validation_Comment'=>"Cette validation a �t� effectu�e dans le cadre d'une validation par un expert en vrac."
	,'LANG_Bulk_Page_Nothing_To_Do'=>"Il n'y a aucune identification � valider dans cette page."
	,'LANG_Bulk_Taxon_Nothing_To_Do'=>"Il n'y a aucune identification � valider pour ce taxon."
	,'LANG_Cancel' => 'Annuler'
	,'LANG_Bulk_Validation_Canceled'=>'Cette validation a �t� annul�e mi-chemin. Certains changements peuvent avoir d�j� �t� appliqu�e � la base de donn�es.'
	,'LANG_ClearTooltip' => 'Effacer polygones'
	,'LANG_User_Link' => 'Toutes ses collections dans les Galeries'
	,'LANG_Additional_Info_Title' => 'Informations Compl�mentaires'
	,'LANG_Date' => 'Date'
	,'LANG_Time' => 'Heure'
	,'LANG_To' => ' � '
	,'Sky' => 'Ciel : couverture nuageuse '
	,'Temperature' => 'Temp�rature '
	,'Wind' => 'Vent '
	,'Shade' => "Fleur � l'ombre "
	,'Flower Type' => "Il s'agit d'une fleur "
	,'Habitat' => "Il s'agit d'un habitat "
	,'Foraging'=> "L'insecte a �t� photographi� ailleurs que sur la fleur"
	
	,'LANG_Comments_Title' => 'COMMENTAIRES DES INTERNAUTES'
	,'LANG_New_Comment' => 'Ajouter un commentaire'
	,'LANG_Username' => 'Pseudo'
	,'LANG_Email' => 'EMAIL'
	,'LANG_Comment' => 'Commentaire'
	,'LANG_Submit_Comment' => 'Ajouter'
	,'LANG_Comment_By' => "par : "
	,'LANG_Reset_Filter' => 'R�initialiser'
	,'LANG_Submit_Location' => 'Modifier'
	
	,'validation_required' => "Ce champ est obligatoire"
	,'Yes' => 'Oui'
	,'No' => 'Non'
	,'close'=>'Fermer'	
  	,'search'=>'Chercher'
  	,'click here'=>'Cliquez ici'
	,'LANG_Unknown' => '?'
	,'LANG_Dubious' => '!'
	,'LANG_Confirm_Express_Doubt' => 'Etes-vous s�r de vouloir �mettre un doute au sujet de cette identification?'
	,'LANG_Doubt_Expressed' => "Cette personne a exprim� des doutes au sujet de cette identification."
	,'LANG_Determination_Valid' => 'Cette identification a �t� effectu�e par un expert. Elle est consid�r�e comme valide.'
	,'LANG_Determination_Incorrect' => 'Cette identification a �t� signal�e comme incorrecte.'
	,'LANG_Determination_Unconfirmed' => 'Cette identification a �t� marqu�e comme non confirm�es.'
	,'LANG_Determination_Unknown' => "Le taxon n'est pas connu de la cl� d'identification."
	,'LANG_Max_Collections_Reached' => "Du fait du grand nombre de collections enregistr�es sur le site du SPIPOLL, seules les 1000 derni�res collections saisies vous sont pr�sent�es. Utilisez la g�olocalisation et/ou le filtre date pour voir l'ensemble des collections au sein d'une zone et/ou d'une p�riode d'observation donn�es."
	,'LANG_Max_Insects_Reached' => "Du fait du grand nombre de collections enregistr�es sur le site du SPIPOLL, seules les 1000 derni�res insectes saisies vous sont pr�sent�es. Utilisez la g�olocalisation et/ou le filtre date pour voir l'ensemble des insectes au sein d'une zone et/ou d'une p�riode d'observation donn�es."
	,'LANG_General' => 'G�n�ral'
	,'LANG_Created_Between' => 'Cr�� entre'
	,'LANG_And' => 'et'
	,'LANG_Or' => 'ou'
	,'LANG_Indicia_Warehouse_Error' => 'Erreur renvoy�e par Indicia Warehouse'
	,'loading' => 'Chargement'
	,'LANG_INSEE_Localisation' => 'Localisation'
	,'LANG_Localisation_Confirm' => 'Etes-vous s�r de vouloir modifier la g�olocalisation de votre collection?'
	,'LANG_Localisation_Desc' => "Si la g�olocalisation de votre collection est incorrecte, vous pouvez la modifier en cliquant sur la carte ou en modifiant ses coordonn�es."
	,'LANG_Front Page' => "Inclure cette collection � la page d'accueil"
	,'LANG_Submit_Front_Page' => 'Enregistrer'
	,'LANG_Included_In_Front_Page' => "Cette collection a �t� inclue � la page d'accueil."
	,'LANG_Removed_From_Front_Page' => "Cette collection a �t� retir� de la page d'accueil"
	,'LANG_Number_In_Front_Page' => "Nombre de collections dans la liste page d'accueil: "
	,'LANG_Location_Updated' => 'La localisation de cette collection a �t� mise � jour.'
	,'LANG_Locality_Commune' => 'Commune'
	,'LANG_Locality_Department' => 'D�partement'
	,'LANG_Locality_Region' => 'R�gion'
	
	,'LANG_Bad_Collection_ID' => "Vous avez essay� de charger une session comme une collection: ce ID n'est pas une collection."
	,'LANG_Bad_Insect_ID' => "Vous avez essay� de charger une fleur comme un insecte: cette ID n'est pas un insect."
	,'LANG_Bad_Flower_ID' => "Vous avez essay� de charger un insecte comme une fleur: cet ID n'est pas une fleur."
	
);