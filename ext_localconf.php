<?php

defined('TYPO3_MODE') or die();

// Add update wizard for mask permissions
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['maskPermissions'] = \HOV\MaskPermissions\Updates\MaskPermissions::class;
