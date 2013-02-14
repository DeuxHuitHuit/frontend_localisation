<?php

	$about = array(
		'name' => 'Français',
		'author' => array(
			'name' => 'Jonathan Mellish',
			'email' => 'jonathan.mellish@gmail.com',
			'website' => ''
		),
		'release-date' => '2013-02-10'
	);

	/**
	 * Frontend Localisation
	 */
	$dictionary = array(

		'%1$s %2$s at %3$s. <a href="%4$s" accesskey="c">Create another?</a> <a href="%5$s" accesskey="a">%6$s</a>' => 
		'%1$s %2$s la %3$s. <a href="%4$s" accesskey="c">Créer nouveau?</a> <a href="%5$s" accesskey="a">%6$s</a>',

		'<code>%1$s</code>: Column `translations` for `tbl_pages` already exists. Uninstall extension and re-install it after.' => 
		'<code>%1$s</code>: La colonne `traductions` pour `tbl_pages` existe déjà. Désinstallez l\'extension puis reinstallez-la.',

		'<code>%1$s</code>: Failed to remove <code>%2$s</code> folder.' => 
		'<code>%1$s</code>: Erreur lors de la suppression du dossier <code>%2$s</code>.',

		'<code>%1$s</code>: MySQL error %d occured when adding column `translation` to `tbl_pages`. Installation aborted.' => 
		'<code>%1$s</code>: Une erreur MySQL %d est survenu lors de l\'ajout de la colonne `traduction` dans la table `tbl_pages`. Installation annulée.',

		'<code>%1$s</code>: No languages have been set on Preferences page. Please <a href="%2$s">review them</a>.' => 
		'<code>%1$s</code>: Aucune langue n\'a été sélectionné sur la page préférences. <a href="%2$s">Voir</a>.',

		'<code>%1$s</code>: Translation folders not found.' => 
		'<code>%1$s</code>: Dossiers de traduction introuvables',

		'An error occurred while processing this form. <a href="#error">See below for details.</a>' => 
		'Une erreur est survenu lors du traitement de ce formulaire. <a href="#error">Voir ci-dessous pour les détails.</a>',

		'Are you sure you want to delete the selected translations?' => 
		'Êtes-vous sûr de vouloir supprimer les traductions sélectionnées?',

		'Comma separated list of supported language codes.' => 
		'Liste des langues supportés séparé d\'une virgule.',

		'Consolidate translations' => 
		'Consolider les traductions',

		'Context: ' => 
		'Contexte: ',

        'Delete' =>
        'Supprimer',
        
        'Add a translation' =>
        'Ajouter une traduction',
        
        'Add a context' =>
        'Ajoute un contexte',
        
        'Delete a context' =>
        'Supprimer un contexte',
        
		'Create Translation' => 
		'Créer un traduction',

		'Create a new translation file' => 
		'Créer un fichier de traduction',

		'Default storage format' => 
		'Format de stockage par défaut',

		'Frontend Translations' => 
		'Traduction "Frontend"',

		'Handle is a required field' => 
		'Handle est un champ obligatoire',

		'Handle is a required field.' => 
		'Handle est un champ obligatoire',

		'In FLPageManager it died trying to get a list of Pages from Database. Poor fellow.' => 
		'Erreur Fatale lorsque FLPageManager à tenter de récupérer une liste de pages sur la Base de Données. Dommage.',

		'Language codes' => 
		'Codes des langues',

		'Main language' => 
		'Langue principale',

		'No Pages Found' => 
		'Aucune page trouvée',

		'No translations found. <a href="%s">Create new?</a>' => 
		'Aucune traduction. <a href="%s">Créer une traduction?</a>',

		'Normal' => 
		'Normal',

		'Page' => 
		'Page',

		'Please fill at least one valid language code.' => 
		'Veuillez saisir au moins un code de langue valide.',

		'Reference language' => 
		'Langue de référence',

		'Reference value' => 
		'Valeur de référence',

		'Save changes' => 
		'Sauvegarder les modifications',

		'Select the main language of the site.' => 
		'Sélectionnez la langue principale du site',

		'Set type of Translation. <b>%1$s</b> for Symphony Pages, <b>%2$s</b> otherwise.' => 
		'Choisissez le type de traduction. <b>%1$s</b> pour les pages Symphony, sinon <b>%2$s</b>.',

		'Storage format' => 
		'Format de stockage',

		'Storage format to use for translations.' => 
		'Format de stockage utilisé pour les traductions.',

		'The translation file you requested to edit does not exist.' => 
		'Le fichier de traduction que vous tentez de modifier n\'existe pas.',

		'Translation' => 
		'Traduction',

		'Translation could not be created.' => 
		'La traduction n\'a pas pu être créée.',

		'Translation not found' => 
		'Traduction introuvable',

		'Translations' => 
		'Traductions',

		'Translations synchronisation failed. Please contact site administrator.' => 
		'La synchronisation des traductions à échouée. Veuillez contacter l\'administrateur du site.',

		'Unknown Lang' => 
		'Langue inconnue',

		'Update Translations' => 
		'Mettre à jour les traductions',

		'View all Translations' => 
		'Visualiser toutes les traductions',

		'You asked to create a Translation but there are not languages set by your Language Driver.' => 
		'Vous avez demandé de créer une traduction hors il n\'y a pas le langue assigné par votre Pilote de langues.',

		'created' => 
		'créé',

		'updated' => 
		'mis à jour',

		'<code>%1$s</code>: Failed to remove `translation` column from `tbl_pages`. Perhaps it didn\'t existed at all.' =>
		'<code>%1$s</code>: La colonne `traductions` n\'a pas pu être supprimé de la table `tbl_pages`. Il est possible que cette colonne n\'existait pas auparavant.',

		'<code>%1$s</code>: Reference language code <code>%2$s</code> is not supported.' => 
		'<code>%1$s</code>: Le code <code>%2$s</code> de la langue de référence n\'est pas supporté.',

		'Please review settings' => 
		'Veuillez revoir vos paramètres',

		'<code>%1$s</code>: Translation folder couldn\'t be created at <code>%2$s</code>.' => 
		'<code>%1$s</code>: Le dossier de traduction n\'a pas pu être créé dans <code>%2$s</code>.',

		'<code>%1$s</code>: Storage directory <code>%2$s</code> for <code>%3$s</code> storage format doesn\'t exist.' => 
		'<code>%1$s</code>: Le répertoire de stockage <code>%2$s</code> pour <code>%3$s</code> n\'existe pas.',

		'Language translations that will be used as reference when updating other languages\' translations.' => 
		'Langue qui sera utilisé comme référence lorsque vous mettrez à jour d\'autres traductions',

		'Check this to preserve Translations for removed languages.' => 
		'Cochez cette case pour conserver les traductions pour les langues supprimées.',

		'Invalid language code.' => 
		'Code de langue invalide.',

		'Invalid storage format.' => 
		'Format de stockage invalide.',

	);
