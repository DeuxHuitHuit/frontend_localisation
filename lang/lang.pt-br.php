<?php

	$about = array(
		'name' => 'Portuguese (Brazil)',
		'author' => array(
			'name' => 'Marcio Toledo',
			'email' => 'mt@marciotoledo.com',
			'website' => 'http://www.marciotoledo.com'
		),
		'release-date' => '2012-12-07'
	);

	/**
	 * Frontend Localisation
	 */
	$dictionary = array(

		'%1$s %2$s at %3$s. <a href="%4$s" accesskey="c">Create another?</a> <a href="%5$s" accesskey="a">%6$s</a>' => 
		'%1$s %2$s em %3$s. <a href="%4$s" accesskey="c">Criar outro?</a> <a href="%5$s" accesskey="a">%6$s</a>',

		'<code>%1$s</code>: Column `translations` for `tbl_pages` already exists. Uninstall extension and re-install it after.' => 
		'<code>%1$s</code>: Coluna `translations` para `tbl_pages` já existe. Desinstale a extensão e reinstale em seguida.',

		'<code>%1$s</code>: Failed to remove <code>%2$s</code> folder.' => 
		'<code>%1$s</code>: Falha ao remover <code>%2$s</code> pasta.',

		'<code>%1$s</code>: MySQL error %d occured when adding column `translation` to `tbl_pages`. Installation aborted.' => 
		'<code>%1$s</code>: Erro de MySQL %d ocorreu quando adicionando coluna `translation` em `tbl_pages`. Instalação abortada.',

		'<code>%1$s</code>: No languages have been set on Preferences page. Please <a href="%2$s">review them</a>.' => 
		'<code>%1$s</code>: Nenhum idioma foi configurado nas Preferências. Por favor <a href="%2$s">verificar isso</a>.',

		'<code>%1$s</code>: Translation folders not found.' => 
		'<code>%1$s</code>: Pasta Translation não encontrada.',

		'An error occurred while processing this form. <a href="#error">See below for details.</a>' => 
		'Um erro ocorreu enquando processava este formulário. <a href="#error">Veja mais detalhes abaixo.</a>',

		'Are you sure you want to delete the selected translations?' => 
		'Você tem certeza que quer excluir a tradução selecionada?',

		'Comma separated list of supported language codes.' => 
		'Lista separada por vírgula dos códigos de idioma suportados.',

		'Consolidate translations' => 
		'Traduções consolidadas',

		'Context: ' => 
		'Contexto: ',

        'Delete' =>
        'Excluir',
        
        'Add a translation' =>
        'Adicionar uma tradução',
        
        'Add a context' =>
        'Adicionar um contexto',
        
        'Delete a context'=>
        'Excluir um contexto',
        
		'Create Translation' => 
		'Criar Tradução',

		'Create a new translation file' => 
		'Criar um novo arquivo de tradução',

		'Default storage format' => 
		'Formato de armazenamento padrão',

		'Frontend Translations' => 
		'Traduções Frontend',

		'Handle is a required field' => 
		'Tratamento de URL é um campo necessário',

		'Handle is a required field.' => 
		'Tratamento de URL é um campo necessário.',

		'In FLPageManager it died trying to get a list of Pages from Database. Poor fellow.' => 
		'FLPageManager morreu tentando obter um lista de Páginas da Base de Dados. Ó coitado.',

		'Language codes' => 
		'Códigos de idioma',

		'Main language' => 
		'Idioma principal',

		'No Pages Found' => 
		'Nenhuma Página Encontrada',

		'No translations found. <a href="%s">Create new?</a>' => 
		'Nenhuma tradução encontrada. <a href="%s">Criar nova?</a>',

		'Normal' => 
		'Normal',

		'Page' => 
		'Página',

		'Please fill at least one valid language code.' => 
		'Por favor preencha pelo menos um código de idioma válido.',

		'Reference language' => 
		'Idioma referência',

		'Reference value' => 
		'Valor referência',

		'Save changes' => 
		'Salvar alterações',

		'Select the main language of the site.' => 
		'Selecione o idioma principal do site.',

		'Set type of Translation. <b>%1$s</b> for Symphony Pages, <b>%2$s</b> otherwise.' => 
		'Configure o tipo de Tradução. <b>%1$s</b> para Páginas do Symphony, caso contrário <b>%2$s</b>.',

		'Storage format' => 
		'Formato de armazenamento',

		'Storage format to use for translations.' => 
		'Formato de armazenamento para usar nas traduções.',

		'The translation file you requested to edit does not exist.' => 
		'Os arquivos de tradução que você solicitou editar não existem.',

		'Translation' => 
		'Tradução',

		'Translation could not be created.' => 
		'Tradução não poderia ser criada.',

		'Translation not found' => 
		'Tradução não encontrada',

		'Translations' => 
		'Traduções',

		'Translations synchronisation failed. Please contact site administrator.' => 
		'Sincronização das traduções falhou. Por favor contate o administrador do site.',

		'Unknown Lang' => 
		'Lang desconhecido',

		'Update Translations' => 
		'Atualize Traduções',

		'View all Translations' => 
		'Ver todas as Traduções',

		'You asked to create a Translation but there are not languages set by your Language Driver.' => 
		'Você pediu para criar uma Tradução mas não há idiomas configurados para seu Language Driver.',

		'created' => 
		'criado',

		'updated' => 
		'atualizado',

		'<code>%1$s</code>: Failed to remove `translation` column from `tbl_pages`. Perhaps it didn\'t existed at all.' =>
		'<code>%1$s</code>: Falhou ao remover coluna `translations` de `tbl_pages`. Talvez ele nunca existiu.',

		'<code>%1$s</code>: Reference language code <code>%2$s</code> is not supported.' => 
		'<code>%1$s</code>: Referência de código de idioma <code>%2$s</code> não é suportado.',

		'Please review settings' => 
		'Por favor revisar configurações',

		'<code>%1$s</code>: Translation folder couldn\'t be created at <code>%2$s</code>.' => 
		'<code>%1$s</code>: Pasta de tradução não pode ser criado em <code>%2$s</code>.',

		'<code>%1$s</code>: Storage directory <code>%2$s</code> for <code>%3$s</code> storage format doesn\'t exist.' => 
		'<code>%1$s</code>: Diretório de armazenamento <code>%2$s</code> para padrão <code>%3$s</code> não existe.',

		'Language translations that will be used as reference when updating other languages\' translations.' => 
		'Traduções de idioma que serão usados como referência quando atualizando outros idiomas.',

		'Check this to preserve Translations for removed languages.' => 
		'Marque esta opção para preservar Traduções para idiomas removidos.',

		'Invalid language code.' => 
		'Código de idioma inválido.',

		'Invalid storage format.' => 
		'Formato de armazenamento inválido.',

	);