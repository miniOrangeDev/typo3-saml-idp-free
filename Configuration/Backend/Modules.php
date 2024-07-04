<?php

use Miniorange\Idp\Controller\BesamlController;

/**
 * Definitions for modules provided by EXT:examples
 */
return [
    'tools_sp' => [
        'parent' => 'tools',
        'position' => [],
        'access' => 'user,group',
        'workspaces' => 'live',
        'iconIdentifier' => 'idp-plugin-bekey',
        'path' => 'module/tools/besamlkey',
        'labels' => 'LLL:EXT:idp/Resources/Private/Language/locallang_bekey.xlf',
        'extensionName' => 'Idp',
        'controllerActions' => [
            BesamlController::class => 'request',
        ],
    ]
];