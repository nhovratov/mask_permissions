<?php

return [
    'mask_permission_module' => [
        'parent' => 'tools',
        'position' => ['after' => 'mask_module'],
        'access' => 'admin',
        'icon' => 'EXT:mask_permissions/Resources/Public/Icons/Extension.svg',
        'path' => '/module/mask_permissions',
        'labels' => 'LLL:EXT:mask_permissions/Resources/Private/Language/locallang.xlf',
        'extensionName' => 'MaskPermissions',
        'controllerActions' => [
            \HOV\MaskPermissions\Controller\PermissionController::class => [
                'index',
                'update',
            ],
        ],
    ],
];
