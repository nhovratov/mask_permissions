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

use MASK\Mask\Definition\TableDefinitionCollection;
use MASK\Mask\Enumeration\FieldType;
use MASK\Mask\Utility\AffixUtility;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MaskPermissions
{
    protected TableDefinitionCollection $tableDefinitionCollection;

    public function __construct(TableDefinitionCollection $tableDefinitionCollection)
    {
        $this->tableDefinitionCollection = $tableDefinitionCollection;
    }

    protected array $defaultExcludeFields = [
        'sys_language_uid',
        'starttime',
        'endtime',
        'l10n_parent',
        'hidden',
        'fe_group',
        'editlock'
    ];

    public function update(int $groupUid = 0): bool
    {
        $maskConfig = $this->getMaskConfig();
        if ($maskConfig === []) {
            return false;
        }

        if ($groupUid !== 0) {
            $groups = [$groupUid];
        } else {
            $groups = $this->getBeUserGroups();
        }

        foreach ($groups as $group) {
            $result = $this->getPermissions($group);

            // Update non_exclude_fields
            $nonExcludeFields = $result['non_exclude_fields'];
            $nonExcludeFields = GeneralUtility::trimExplode(',', $nonExcludeFields);
            $nonExcludeFields = array_merge(
                $nonExcludeFields,
                $this->getMaskFields(),
                $this->getMaskAdditionalTableModify()
            );
            $nonExcludeFields = array_unique($nonExcludeFields);
            $nonExcludeFields = implode(',', $nonExcludeFields);

            $queryBuilder = $this->getQueryBuilder('be_groups');
            $queryBuilder
                ->update('be_groups')
                ->set('non_exclude_fields', $nonExcludeFields)
                ->where($queryBuilder->expr()->eq('uid', $group))
                ->executeStatement();

            // Update tables_modify
            $tablesModify = $result['tables_modify'];
            $tablesModify = GeneralUtility::trimExplode(',', $tablesModify);
            $tablesModify = array_merge($tablesModify, $this->getMaskCustomTables());
            $tablesModify = array_unique($tablesModify);
            $tablesModify = implode(',', $tablesModify);

            $queryBuilder
                ->update('be_groups')
                ->set('tables_modify', $tablesModify)
                ->where($queryBuilder->expr()->eq('uid', $group))
                ->executeStatement();

            // Update explicit_allowdeny
            $explicitAllowDeny = $result['explicit_allowdeny'];
            $explicitAllowDeny = GeneralUtility::trimExplode(',', $explicitAllowDeny);
            $explicitAllowDeny = array_merge($explicitAllowDeny, $this->getMaskExplicitAllow());
            $explicitAllowDeny = array_unique($explicitAllowDeny);
            $explicitAllowDeny = implode(',', $explicitAllowDeny);

            $queryBuilder
                ->update('be_groups')
                ->set('explicit_allowdeny', $explicitAllowDeny)
                ->where($queryBuilder->expr()->eq('uid', $group))
                ->executeStatement();
        }
        return true;
    }

    /**
     * Is an update necessary?
     *
     * Is used to determine whether a wizard needs to be run.
     * Check if data for migration exists.
     */
    public function updateNecessary(int $groupUid = 0): bool
    {
        if ($groupUid) {
            $groups = [$groupUid];
        } else {
            $groups = $this->getBeUserGroups();
        }

        foreach ($groups as $uid) {
            $result = $this->getPermissions($uid);

            $nonExcludeFields = $result['non_exclude_fields'];
            $nonExcludeFields = GeneralUtility::trimExplode(',', $nonExcludeFields);
            $nonExcludeFields = array_filter(
                $nonExcludeFields,
                static function ($item) {
                    return strpos($item, 'tx_mask') !== false;
                }
            );

            $fields = array_merge($this->getMaskFields(), $this->getMaskAdditionalTableModify());
            $fieldsToUpdate = array_diff($fields, $nonExcludeFields);

            $tablesModify = $result['tables_modify'];
            $tablesModify = GeneralUtility::trimExplode(',', $tablesModify);
            $tablesModify = array_filter(
                $tablesModify,
                static function ($item) {
                    return strpos($item, 'tx_mask') !== false;
                }
            );

            $tablesToUpdate = array_diff($this->getMaskCustomTables(), $tablesModify);

            $explicitAllowDeny = $result['explicit_allowdeny'];
            $explicitAllowDeny = GeneralUtility::trimExplode(',', $explicitAllowDeny);
            $explicitAllowDenyToUpdate = array_diff($this->getMaskExplicitAllow(), $explicitAllowDeny);

            if ($fieldsToUpdate || $tablesToUpdate || $explicitAllowDenyToUpdate) {
                return true;
            }
        }
        return false;
    }

    protected function getQueryBuilder(string $table): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder;
    }

    protected function getMaskConfig(): array
    {
        return $this->tableDefinitionCollection->toArray();
    }

    protected function getMaskFields(): array
    {
        $fields = [];
        $tt_content = $this->tableDefinitionCollection->getTable('tt_content');
        foreach ($tt_content->elements as $element) {
            foreach ($element->columns as $column) {
                if ($this->tableDefinitionCollection->getFieldType($column, 'tt_content', $element->key)->equals(FieldType::PALETTE)) {
                    foreach ($tt_content->palettes->getPalette($column)->showitem as $item) {
                        $fields = $this->addField($fields, $item);
                    }
                } else {
                    $fields = $this->addField($fields, $column);
                }
            }
        }
        return $fields;
    }

    protected function addField(array $fields, string $column): array
    {
        if (strpos($column, 'tx_mask') !== false) {
            $fields[] = 'tt_content:' . $column;
        }
        return $fields;
    }

    protected function getMaskCustomTables(): array
    {
        $customTables = [];
        foreach ($this->tableDefinitionCollection->getCustomTables() as $tableDefinition) {
            $customTables[] = $tableDefinition->table;
        }
        return $customTables;
    }

    protected function getMaskAdditionalTableModify(): array
    {
        $additionalTableModify = [];
        foreach ($this->tableDefinitionCollection->getCustomTables() as $tableDefinition) {
            foreach ($tableDefinition->tca as $tcaField) {
                $additionalTableModify[] = $tableDefinition->table . ':' . $tcaField->fullKey;
            }
            foreach ($this->defaultExcludeFields as $default) {
                $additionalTableModify[] = $tableDefinition->table . ':' . $default;
            }
        }
        return $additionalTableModify;
    }

    protected function getMaskExplicitAllow(): array
    {
        $explicitAllow = [];
        foreach ($this->tableDefinitionCollection->getTable('tt_content')->elements as $elementDefinition) {
            $explicitAllow[] = 'tt_content:CType:' . AffixUtility::addMaskCTypePrefix($elementDefinition->key) . ':ALLOW';
        }
        return $explicitAllow;
    }

    protected function getPermissions(int $uid): array
    {
        $queryBuilder = $this->getQueryBuilder('be_groups');
        return $queryBuilder
            ->select('non_exclude_fields', 'tables_modify', 'explicit_allowdeny')
            ->from('be_groups')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();
    }

    protected function getBeUserGroups(): array
    {
        $uids = [];
        $backendUserGroups = GeneralUtility::makeInstance(BackendUserGroupRepository::class)->findAll();
        foreach ($backendUserGroups as $group) {
            $uids[] = $group->getUid();
        }
        return $uids;
    }
}
