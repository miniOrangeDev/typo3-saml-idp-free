<?php


use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

call_user_func(
    function () {
        $version = new Typo3Version();
        if (version_compare($version, '11.0.0', '>=')) {
            $extensionName = 'Idp';
        } else {
            $extensionName = 'Miniorange.Idp';
        }

        ExtensionUtility::registerPlugin(
            $extensionName,
            'Fesaml',
            'fesaml'
        );

    }
);
