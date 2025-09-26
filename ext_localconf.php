<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Information\Typo3Version;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.backend.enforceContentSecurityPolicy'] = false;
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] = ['sp_name', 'RelayState', 'option', 'SAMLRequest', 'SAMLResponse', 'SigAlg', 'Signature', 'type', 'app', 'code', 'state', 'logintype'];
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['enforceValidation'] = false;
call_user_func(
    function () {
        $pluginNameFesaml = 'Fesaml';
        $pluginNameBesaml = 'Besaml';

        $version = new Typo3Version();
        if (version_compare($version, '10.0.0', '>=')) {
            $extensionName = 'idp';
            $cache_actions_fesaml = [Miniorange\Idp\Controller\FesamlController::class => 'request'];
            $non_cache_actions_fesaml = [Miniorange\Idp\Controller\FesamlController::class => 'control'];
        } else {
            $extensionName = 'Miniorange.Idp';
            $cache_actions_besaml = ['Besaml' => 'request'];
            $cache_actions_fesaml = ['Fesaml' => 'request'];
            $non_cache_actions_fesaml = ['Fesaml' => 'control'];
        }

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            $extensionName,
            $pluginNameBesaml,
            [
                'Besaml' => 'request',
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            $extensionName,
            $pluginNameFesaml,
            $cache_actions_fesaml,
            // non-cacheable actions
            $non_cache_actions_fesaml
        );

        // wizards
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    "mod.wizards.newContentElement.wizardItems.plugins {
                elements {
            fesaml {
                iconIdentifier = idp-plugin-bekey
                title = Fesaml
                description = For Sending Request
                        tt_content_defValues {
                            CType = list
                    list_type = {$extensionName}_fesaml
                        }
                    }
                }
                show = *
    }"
        );

        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
        $iconRegistry->registerIcon(
            'idp-plugin-fesaml',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:idp/Resources/Public/Icons/Extension.png']
        );

        $iconRegistry->registerIcon(
            'idp-plugin-bekey',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:idp/Resources/Public/Icons/miniorange.png']
        );
    }
);
