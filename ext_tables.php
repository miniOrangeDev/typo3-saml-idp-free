<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Miniorange\Idp\Controller\FesamlController;
use Miniorange\Idp\Controller\BesamlController;

call_user_func(
    function () {
        $version = GeneralUtility::makeInstance(Typo3Version::class);
        $isV13OrHigher = version_compare($version, '13.0.0', '>=');

        $extensionName = $isV13OrHigher || version_compare($version, '10.0.0', '>=')? 'idp': 'Miniorange.idp';

        $cache_actions_fesaml = $isV13OrHigher || version_compare($version, '10.0.0', '>=')? [FesamlController::class => 'request']: ['Fesaml' => 'request'];

        $cache_actions_besaml = $isV13OrHigher || version_compare($version, '10.0.0', '>=')? [BesamlController::class => 'request']: ['Besaml' => 'request'];

        if (!$isV13OrHigher) {
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                $extensionName,
                'tools', // Main module
                'besamlkey', // Submodule key
                '4', // Position
                $cache_actions_besaml,
                [
                    'access' => 'admin,user,group',
                    'icon'   => 'EXT:idp/Resources/Public/Icons/Extension.png',
                    'labels' => 'LLL:EXT:idp/Resources/Private/Language/locallang_bekey.xlf',
                ]
            );
        } else {
            $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['idp']['BesamlModule'] = [
                'extensionName'   => $extensionName,
                'mainModuleName'  => 'tools',
                'subModuleName'   => 'besamlkey',
                'controllerActions' => $cache_actions_besaml,
                'access'          => 'admin,user,group',
                'iconIdentifier'  => 'idp-plugin-bekey',
                'labels'          => 'LLL:EXT:idp/Resources/Private/Language/locallang_bekey.xlf',
                'position'        => 'top',
            ];
        }

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extensionName,
            'Fesaml',
            'fesaml',
            'idp-plugin-bekey'
        );
    }
);
