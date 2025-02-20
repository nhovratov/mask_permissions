<?php


defined('TYPO3') or die();

$typo3VersionArray = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionStringToArray(
    \TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version()
);
if (version_compare($typo3VersionArray['version_main'], 12, '<')) {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'MaskPermissions',
        'tools',
        'mask_permissions',
        'bottom',
        [
            \HOV\MaskPermissions\Controller\PermissionController::class => 'index,update',
        ],
        [
            'access' => 'admin',
            'icon' => 'EXT:mask_permissions/Resources/Public/Icons/Extension.svg',
            'labels' => 'LLL:EXT:mask_permissions/Resources/Private/Language/locallang.xlf',
        ]
    );
}
