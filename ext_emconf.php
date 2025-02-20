<?php

$EM_CONF['mask_permissions'] = [
    'title' => 'Mask Permissions',
    'description' => 'Never configure mask permissions for editors manually ever again!',
    'category' => 'backend',
    'author' => 'Nikita Hovratov',
    'author_email' => 'entwicklung@nikita-hovratov.de',
    'author_company' => 'Nikita Hovratov',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'version' => '3.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'mask' => '8.0.0-9.99.99',
        ],
        'conflicts' => [],
    ],
];
