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
    'version' => '3.0.2',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.5.99',
            'mask' => '7.2.0-8.99.99',
        ],
        'conflicts' => [],
    ],
];
