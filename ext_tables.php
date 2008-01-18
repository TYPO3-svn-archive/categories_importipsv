<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');



if (TYPO3_MODE=='BE')	{
	t3lib_extMgm::insertModuleFunction(
		'xMOD_txcategoriesImport',
		'tx_categoriesimportipsv',
		t3lib_extMgm::extPath($_EXTKEY).'class.tx_categoriesimportipsv.php',
		'IPSV (Integrated Public Sector Vocabulary)'
	);
}

t3lib_extMgm::addLLrefForTCAdescr('_MOD_txcategoriesimportipsv_modfunc','EXT:categories_importipsv/locallang_csh_importipsv.xml');









?>