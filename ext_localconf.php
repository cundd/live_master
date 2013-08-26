<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'Cundd.' . $_EXTKEY,
	'Livemaster',
	array(
		'Configuration' => 'list, show, new, create, edit, update, delete',
		
	),
	// non-cacheable actions
	array(
		'Configuration' => 'create, update, delete',
		
	)
);

?>