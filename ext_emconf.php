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
    'version' => '2.0.4',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-11.5.99',
            'mask' => '',
        ],
        'conflicts' => [],
    ],
];
