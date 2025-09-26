<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Miniorange\Idp\Domain\Repository\UserGroup;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

abstract class AbstractUserGroupRepository
{
    /**
     * Helper method to execute database queries with TYPO3 v12/v13 compatibility
     */
    protected function executeQuery($queryBuilder, $isSelect = true)
    {
        // Check if the new methods exist (TYPO3 v13+)
        if (method_exists($queryBuilder, 'executeQuery') && method_exists($queryBuilder, 'executeStatement')) {
            return $isSelect ? $queryBuilder->executeQuery() : $queryBuilder->executeStatement();
        } else {
            // TYPO3 v12 and earlier - use legacy methods
            return $queryBuilder->execute();
        }
    }

    /**
     * Helper method to fetch data with TYPO3 v12/v13 compatibility
     */
    protected function fetchData($result, $method = 'fetchAll')
    {
        // Check if the new methods exist (TYPO3 v13+)
        if (method_exists($result, 'fetchAssociative') && method_exists($result, 'fetchAllAssociative')) {
            switch ($method) {
                case 'fetch':
                    return $result->fetchAssociative();
                case 'fetchAll':
                    return $result->fetchAllAssociative();
                case 'fetchOne':
                    return $result->fetchOne();
                default:
                    return $result->fetchAllAssociative();
            }
        } else {
            // TYPO3 v12 and earlier - use legacy methods
            return $result->$method();
        }
    }

    protected $tableName;

    public function __construct()
    {
        $this->setTableName();
    }

    abstract protected function setTableName(): void;

    public function findAll(): array
    {
        return $this->fetchData(
            $this->executeQuery($this->getQueryBuilder()->select('*')->from($this->tableName), true),
            'fetchAll'
        );
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
    }
}
