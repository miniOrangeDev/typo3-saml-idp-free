<?php

use Miniorange\Idp\Controller\BesamlController;

/**
 * Definitions for modules provided by EXT:examples
 */
return [
    'tools_besamlkey' => [
        'parent' => 'tools',
        'position' => ['after' => 'tools'],
        'access' => 'admin,user,group',
        'workspaces' => 'live',
        'iconIdentifier' => 'idp-plugin-bekey',
        'path' => 'module/tools/besamlkey',
        'labels' => 'LLL:EXT:idp/Resources/Private/Language/locallang_bekey.xlf',
        'extensionName' => 'idp',
        'controllerActions' => [
            BesamlController::class => 'request',
        ],
    ]
];