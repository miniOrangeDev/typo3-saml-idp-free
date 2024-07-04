<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addStaticFile('idp', 'Configuration/TypoScript', 'SAML IDP');

ExtensionManagementUtility::addLLrefForTCAdescr('tx_idp_domain_model_fesaml', 'EXT:idp/Resources/Private/Language/locallang_csh_tx_idp_domain_model_fesaml.xlf');
//ExtensionManagementUtility::allowTableOnStandardPages('tx_idp_domain_model_fesaml');

ExtensionManagementUtility::addLLrefForTCAdescr('tx_idp_domain_model_besaml', 'EXT:idp/Resources/Private/Language/locallang_csh_tx_idp_domain_model_besaml.xlf');
//ExtensionManagementUtility::allowTableOnStandardPages('tx_idp_domain_model_besaml');