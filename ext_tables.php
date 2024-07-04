<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Information\Typo3Version;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.backend.enforceContentSecurityPolicy'] = false;
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] = ['idp_name', 'RelayState', 'option', 'SAMLRequest', 'SAMLResponse', 'SigAlg', 'Signature', 'type', 'app', 'code', 'state', 'logintype'];

call_user_func(
    function () {

        $version = new Typo3Version();
        if (version_compare($version, '11.0.0', '>=')) {
            $extensionName = 'Idp';
            $cache_actions_fesaml = [Miniorange\Idp\Controller\FesamlController::class => 'request'];
            $cache_actions_besaml = [Miniorange\Idp\Controller\BesamlController::class => 'request'];
        } else {
            $extensionName = 'Miniorange.Idp';
            $cache_actions_fesaml = ['Fesaml' => 'request'];
            $cache_actions_besaml = ['Besaml' => 'request'];
        }

        ExtensionUtility::registerModule(
            $extensionName,
            'tools', // Make module a submodule of 'tools'
            'besamlkey', // Submodule key
            '4', // Position
            $cache_actions_besaml,
            [
                'access' => 'admin,user,group',
                'icon' => 'EXT:idp/Resources/Public/Icons/miniorange.png',
                'source' => 'EXT:idp/Resources/Public/Icons/miniorange.svg',
                'labels' => 'LLL:EXT:idp/Resources/Private/Language/locallang_bekey.xlf',
            ]
        );

        ExtensionUtility::registerPlugin(
            $extensionName,
            'Fesaml',
            'fesaml',
            'EXT:idp/Resources/Public/Icons/miniorange.svg'
        );
    }
);
