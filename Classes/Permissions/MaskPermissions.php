<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace HOV\MaskPermissions\Permissions;

use TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository;
use MASK\Mask\Domain\Repository\StorageRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MaskPermissions
{
    protected $defaultExcludeFields = [
        'sys_language_uid',
        'starttime',
        'endtime',
        'l10n_parent',
        'hidden',
        'fe_group',
        'editlock'
    ];

    /**
     * @param int $group
     * @return bool
     */
    public function update($group = 0)
    {
        $maskConfig = $this->getMaskConfig();
        if (!$maskConfig) {
            return false;
        }

        if ($group) {
            $groups = [$group];
        } else {
            $groups = $this->getBeUserGroups();
        }

        foreach ($groups as $group) {
            $result = $this->getPermissions($group);

            // Update non_exclude_fields
            $nonExludeFields = $result['non_exclude_fields'];
            $nonExludeFields = GeneralUtility::trimExplode(',', $nonExludeFields);
            $nonExludeFields = array_merge(
                $nonExludeFields,
                $this->getMaskFields($maskConfig),
                $this->getMaskAdditionalTableModify($maskConfig)
            );
            $nonExludeFields = array_unique($nonExludeFields);
            $nonExludeFields = implode(',', $nonExludeFields);

            $queryBuilder = $this->getQueryBuilder('be_groups');
            $queryBuilder
                ->update('be_groups')
                ->set('non_exclude_fields', $nonExludeFields)
                ->where($queryBuilder->expr()->eq('uid', $group))
                ->execute();

            // Update tables_modify
            $tablesModify = $result['tables_modify'];
            $tablesModify = GeneralUtility::trimExplode(',', $tablesModify);
            $tablesModify = array_merge($tablesModify, $this->getMaskCustomTables($maskConfig));
            $tablesModify = array_unique($tablesModify);
            $tablesModify = implode(',', $tablesModify);

            $queryBuilder
                ->update('be_groups')
                ->set('tables_modify', $tablesModify)
                ->where($queryBuilder->expr()->eq('uid', $group))
                ->execute();

            // Update explicit_allowdeny
            $explicitAllowDeny = $result['explicit_allowdeny'];
            $explicitAllowDeny = GeneralUtility::trimExplode(',', $explicitAllowDeny);
            $explicitAllowDeny = array_merge($explicitAllowDeny, $this->getMaskExplicitAllow($maskConfig));
            $explicitAllowDeny = array_unique($explicitAllowDeny);
            $explicitAllowDeny = implode(',', $explicitAllowDeny);

            $queryBuilder
                ->update('be_groups')
                ->set('explicit_allowdeny', $explicitAllowDeny)
                ->where($queryBuilder->expr()->eq('uid', $group))
                ->execute();
        }
        return true;
    }

    /**
     * Is an update necessary?
     *
     * Is used to determine whether a wizard needs to be run.
     * Check if data for migration exists.
     *
     * @param int $group
     * @return bool
     */
    public function updateNecessary($group = 0): bool
    {
        $maskConfig = $this->getMaskConfig();
        if (!$maskConfig) {
            return false;
        }

        if ($group) {
            $groups = [$group];
        } else {
            $groups = $this->getBeUserGroups();
        }

        foreach ($groups as $uid) {
            $result = $this->getPermissions($uid);

            $nonExcludeFields = $result['non_exclude_fields'];
            $nonExcludeFields = GeneralUtility::trimExplode(',', $nonExcludeFields);
            $nonExcludeFields = array_filter(
                $nonExcludeFields,
                function ($item) {
                    return strpos($item, 'tx_mask') !== false;
                }
            );

            $fields = array_merge($this->getMaskFields($maskConfig), $this->getMaskAdditionalTableModify($maskConfig));
            $fieldsToUpdate = array_diff($fields, $nonExcludeFields);

            $tablesModify = $result['tables_modify'];
            $tablesModify = GeneralUtility::trimExplode(',', $tablesModify);
            $tablesModify = array_filter(
                $tablesModify,
                function ($item) {
                    return strpos($item, 'tx_mask') !== false;
                }
            );

            $tablesToUpdate = array_diff($this->getMaskCustomTables($maskConfig), $tablesModify);

            $explicitAllowDeny = $result['explicit_allowdeny'];
            $explicitAllowDeny = GeneralUtility::trimExplode(',', $explicitAllowDeny);
            $explicitAllowDenyToUpdate = array_diff($this->getMaskExplicitAllow($maskConfig), $explicitAllowDeny);

            if ($fieldsToUpdate || $tablesToUpdate || $explicitAllowDenyToUpdate) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $table
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    protected function getQueryBuilder($table)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder;
    }

    /**
     * @return bool|mixed
     */
    protected function getMaskConfig()
    {
        if (!ExtensionManagementUtility::isLoaded('mask')) {
            return false;
        }
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $maskConfig = $storageRepository->load();
        if (!$maskConfig) {
            return false;
        }
        return $maskConfig;
    }

    /**
     * @param $maskConfig
     * @return array
     */
    protected function getMaskFields($maskConfig)
    {
        $elements = $this->getMaskElements($maskConfig);
        $fields = [];

        foreach ($elements as $element) {
            if (!key_exists('columns', $element)) {
                continue;
            }
            $columns = $element['columns'];
            foreach ($columns as $col) {
                if (strpos($col, 'tx_mask') !== false) {
                    $fields[] = 'tt_content:' . $col;
                }
            }
        }
        return $fields;
    }

    protected function getMaskCustomTables($maskConfig)
    {
        $keys = array_keys($maskConfig);
        return array_filter(
            $keys,
            function ($item) {
                return strpos($item, 'tx_mask') !== false;
            }
        );
    }

    protected function getMaskAdditionalTableModify($maskConfig)
    {
        $customTables = $this->getMaskCustomTables($maskConfig);
        $additionalTableModify = [];
        foreach ($customTables as $key) {
            foreach ($maskConfig[$key]['tca'] as $tcaField => $value) {
                $additionalTableModify[] = $key . ':' . $tcaField;
            }
            foreach ($this->defaultExcludeFields as $default) {
                $additionalTableModify[] = $key . ':' . $default;
            }
        }
        return $additionalTableModify;
    }

    protected function getMaskExplicitAllow($maskConfig)
    {
        $elements = $this->getMaskElements($maskConfig);
        $explicitAllow = [];
        foreach ($elements as $element => $value) {
            $explicitAllow[] = 'tt_content:CType:mask_' . $element . ':ALLOW';
        }
        return $explicitAllow;
    }

    protected function getPermissions($uid)
    {
        $queryBuilder = $this->getQueryBuilder('be_groups');
        return $queryBuilder
            ->select('non_exclude_fields', 'tables_modify', 'explicit_allowdeny')
            ->from('be_groups')
            ->where($queryBuilder->expr()->eq('uid', $uid))
            ->execute()
            ->fetch();
    }

    protected function getMaskElements($maskConfig)
    {
        return $maskConfig['tt_content']['elements'];
    }

    protected function getBeUserGroups()
    {
        $uids = [];
        $backendUserGroups = GeneralUtility::makeInstance(BackendUserGroupRepository::class)->findAll();
        foreach ($backendUserGroups as $group) {
            $uids[] = $group->getUid();
        }
        return $uids;
    }
}
