<?php


use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

call_user_func(
    function () {
        $version = new Typo3Version();
        if (version_compare($version, '10.0.0', '>=')) {
            $extensionName = 'idp';
            $cache_actions_besaml = [Miniorange\Idp\Controller\BesamlController::class => 'request'];
        } else {
            $extensionName = 'Miniorange.Idp';
            $cache_actions_besaml = ['Besaml' => 'request'];
        }


    }
);
