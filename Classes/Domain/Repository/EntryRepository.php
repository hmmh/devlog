<?php
namespace Devlog\Devlog\Domain\Repository;

/**
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

use Devlog\Devlog\Domain\Model\ExtensionConfiguration;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The repository for DevLog Entries.
 *
 * NOTE: this is not an Extbase repository as we don't need all the
 * sophistication here. We might as well avoid the overhead.
 *
 * @author Francois Suter <typo3@cobweb.ch>
 */
class EntryRepository implements SingletonInterface
{
    /**
     * Periods for log clearing intervals
     */
    const PERIOD_1YEAR = 31536000;
    const PERIOD_6MONTHS = 15768000;
    const PERIOD_3MONTHS = 7884000;
    const PERIOD_1MONTH = 2592000;
    const PERIOD_1WEEK = 604800;
    const PERIOD_1DAY = 86400;
    const PERIOD_1HOUR = 3600;

    /**
     * @var string Name of the database table used for logging
     */
    protected $databaseTable = 'tx_devlog_domain_model_entry';

    /**
     * @var ExtensionConfiguration Extension configuration
     */
    protected $extensionConfiguration = null;

    /**
     * @var integer Number of rows in the database (cached to avoid querying too often)
     */
    protected $numberOfRows = null;

    /**
     * EntryRepository constructor.
     *
     * @throws \UnexpectedValueException
     */
    public function __construct()
    {

    }

    /**
     * Returns all available records in the log table.
     *
     * By default records are sorted by descending creation date and
     * ascending order.
     *
     * @return array|null
     */
    public function findAll()
    {
        try {
            $entries = $this->getQueryBuilder()
                ->select('*')
                ->from($this->databaseTable)
                ->orderBy('crdate', 'DESC')
                ->addOrderBy('sorting', 'ASC')
                ->execute()->fetchAll();
        } catch (\Exception $e) {
            $entries = array();
        }
        return $this->expandEntryData($entries);
    }

    /**
     * Finds all entries at or after the given timestamp.
     *
     * @param int $timestamp Limit date/time for fetching entries
     * @return array
     */
    public function findAfterDate($timestamp)
    {
        try {
            $queryBuilder = $this->getQueryBuilder();
            $entries = $queryBuilder
                ->select('*')
                ->from($this->databaseTable)
                ->where($queryBuilder->expr()->gte('crdate', $queryBuilder->createNamedParameter($timestamp, \PDO::PARAM_INT)))
                ->orderBy('crdate', 'DESC')
                ->addOrderBy('sorting', 'ASC')
                ->execute()->fetchAll();
        } catch (\Exception $e) {
            $entries = array();
        }
        $entries = $this->expandEntryData($entries);
        return $entries;
    }

    /**
     * Finds all entries for a given key.
     *
     * @param string $key The key to look for
     * @return array
     */
    public function findByKey($key)
    {
        try {
            $queryBuilder = $this->getQueryBuilder();
            $entries = $queryBuilder
                ->select('*')
                ->from($this->databaseTable)
                ->where($this->getQueryBuilder()->expr()->eq('extkey', $queryBuilder->createNamedParameter($key)))
                ->orderBy('crdate', 'DESC')
                ->addOrderBy('sorting', 'ASC')
                ->execute()->fetchAll();
        } catch (\Exception $e) {
            $entries = array();
        }
        $entries = $this->expandEntryData($entries);
        return $entries;
    }

    /**
     * Returns the number of entries per key.
     *
     * @return array
     */
    public function countByKey()
    {
        $count = array();
        try {
            $rows = $this->getQueryBuilder()
                ->select('extkey')
                ->addSelectLiteral($this->getQueryBuilder()->expr()->count('uid', 'total'))
                ->from($this->databaseTable)
                ->groupBy('extkey')
                ->orderBy('extkey')
                ->execute()->fetchAll();
            if (is_array($rows)) {
                /** @var array $rows */
                foreach ($rows as $row) {
                    $count[$row['extkey']] = (int)$row['total'];
                }
            }
        } catch (\Exception $e) {
            // Let an empty array return
        }
        return $count;
    }

    /**
     * Returns the number of entries for predefined periods of time.
     *
     * @return array
     */
    public function countByPeriod()
    {
        $count = array(
                '1hour' => 0,
                '1day' => 0,
                '1week' => 0,
                '1month' => 0,
                '3months' => 0,
                '6months' => 0,
                '1year' => 0
        );
        try {
            $rows = $this->getQueryBuilder()
                ->select( 'crdate')
                ->from($this->databaseTable)
                ->execute()->fetchAll();

            if (is_array($rows)) {
                $now = time();
                /** @var array $rows */
                foreach ($rows as $row) {
                    $age = $now - $row['crdate'];
                    if ($age >= self::PERIOD_1YEAR) {
                        $count['1hour']++;
                        $count['1day']++;
                        $count['1week']++;
                        $count['1month']++;
                        $count['3months']++;
                        $count['6months']++;
                        $count['1year']++;
                    } elseif ($age >= self::PERIOD_6MONTHS) {
                        $count['1hour']++;
                        $count['1day']++;
                        $count['1week']++;
                        $count['1month']++;
                        $count['3months']++;
                        $count['6months']++;
                    } elseif ($age >= self::PERIOD_3MONTHS) {
                        $count['1hour']++;
                        $count['1day']++;
                        $count['1week']++;
                        $count['1month']++;
                        $count['3months']++;
                    } elseif ($age >= self::PERIOD_1MONTH) {
                        $count['1hour']++;
                        $count['1day']++;
                        $count['1week']++;
                        $count['1month']++;
                    } elseif ($age >= self::PERIOD_1WEEK) {
                        $count['1hour']++;
                        $count['1day']++;
                        $count['1week']++;
                    } elseif ($age >= self::PERIOD_1DAY) {
                        $count['1hour']++;
                        $count['1day']++;
                    } elseif ($age >= self::PERIOD_1HOUR) {
                        $count['1hour']++;
                    }
                }
            }
        } catch (\Exception $e) {
            // Let an empty array return
        }
        return $count;
    }

    /**
     * Adds a log entry to the database table.
     *
     * @param \Devlog\Devlog\Domain\Model\Entry $entry
     * @return boolean
     */
    public function add($entry)
    {
        $fields = array(
                'run_id' => $entry->getRunId(),
                'sorting' => $entry->getSorting(),
                'severity' => $entry->getSeverity(),
                'extkey' => $entry->getExtkey(),
                'message' => $entry->getMessage(),
                'location' => $entry->getLocation(),
                'line' => $entry->getLine(),
                'ip' => $entry->getIp(),
                'cruser_id' => $entry->getCruserId(),
                'crdate' => $entry->getCrdate(),
                'pid' => $entry->getPid(),
        );
        // Handle extra data
        $extraData = $entry->getExtraData();
        // NOTE: GeneralUtility::devLog() sends "false" if extra data is undefined
        if ($extraData) {
            try {
                $fields['extra_data'] = gzcompress(serialize($extraData));
                $extraDataSize = strlen($fields['extra_data']);
                $maximumExtraDataSize = $this->extensionConfiguration->getMaximumExtraDataSize();
                // If the entry's extra data is above the limit, replace it with a warning
                if (!empty($maximumExtraDataSize) && $extraDataSize > $maximumExtraDataSize) {
                    $fields['extra_data'] = gzcompress(serialize('Extra data too large, not saved.'));
                }
            } catch (\Exception $e) {
                $fields['extra_data'] = gzcompress(serialize('Extra data could not be serialized.'));
            }
        } else {
            $fields['extra_data'] = '';
        }
        return $this->getQueryBuilder()->insert($this->databaseTable)->values($fields)->execute();
    }

    /**
     * Enforces the limits set in the extension configuration to avoid that the DB tables gets out of hand.
     *
     * @return void
     */
    public function cleanUp()
    {
        // Get the total number of rows, if not already defined
        if ($this->numberOfRows === null) {
            $this->numberOfRows = $this->getQueryBuilder()
                ->count('uid')
                ->from($this->databaseTable)
                ->execute()
                ->fetchColumn(0);
        }

        // Check if number of rows is above the limit and clean up if necessary
        if ($this->numberOfRows > $this->extensionConfiguration->getMaximumRows()) {
            // Select the row from which to start cleaning up
            // To achieve this, order by creation date (so oldest comes first)
            // then offset by 10% of maximumRows and get the next record
            // This will return a timestamp that is used as a cut-off date
            $numberOfRowsToRemove = round(0.1 * $this->extensionConfiguration->getMaximumRows());
            $cutOffDate = $this->getQueryBuilder()
                ->select('crdate', 'uid')
                ->from($this->databaseTable)
                ->orderBy('crdate')
                ->setMaxResults(1)
                ->setFirstResult($numberOfRowsToRemove)
                ->execute()
                ->fetchColumn(0);

            // Delete all rows older or same age as previously found timestamp
            // This will probably delete a bit more than 10% of maximumRows, but will at least
            // delete complete log runs
            $queryBuilder = $this->getQueryBuilder();
            $numberOfRemovedRows =  $queryBuilder
                ->delete($this->databaseTable)
                ->where($queryBuilder->expr()->lte('crdate', $queryBuilder->createNamedParameter($cutOffDate)))
                ->execute();

            // Update (cached) number of rows
            $this->numberOfRows -= $numberOfRemovedRows;

        }

    }

    /**
     * Deletes all log entries in the database table.
     *
     * @return int
     */
    public function deleteAll()
    {
        return $this->getQueryBuilder()->delete($this->databaseTable)->execute();
    }

    /**
     * Deletes all log entries related to the given key.
     *
     * @param string $key The key to look for
     * @return int
     */
    public function deleteByKey($key)
    {
        $queryBuilder = $this->getQueryBuilder();
        return $queryBuilder->delete($this->databaseTable)
            ->where($queryBuilder->expr()->eq('extkey', $queryBuilder->createNamedParameter($key)))
            ->execute();
    }

    /**
     * Deletes all log entries older than the given period.
     *
     * @param int $period Age of log entries which should be deleted
     * @return int
     */
    public function deleteByPeriod($period)
    {
        $queryBuilder = $this->getQueryBuilder();
        return $queryBuilder->delete($this->databaseTable)
            ->where($queryBuilder->expr()->lte('crdate', time() - (int)$period))
            ->execute();
    }

    /**
     * Collects additional data or transforms data from entries for simpler handling during display.
     *
     * @param array $entries
     * @return array
     */
    protected function expandEntryData(array $entries)
    {
        $pageInformationCache = array();
        $numEntries = count($entries);
        if ($numEntries > 0) {
            $users = $this->findAllUsers();
            for ($i = 0; $i < $numEntries; $i++) {
                // Escape potentially malign data
                $entries[$i]['key'] = htmlspecialchars($entries[$i]['key']);
                $entries[$i]['message'] = htmlspecialchars($entries[$i]['message']);
                // Grab username instead of id
                $userId = (int)$entries[$i]['cruser_id'];
                if ($userId > 0 && isset($users[$userId])) {
                    $entries[$i]['username'] = $users[$userId]['username'];
                } else {
                    $entries[$i]['username'] = '';
                }
                // Grab page title
                $pid = (int)$entries[$i]['pid'];
                if ($pid > 0 && isset($pageInformationCache[$pid])) {
                    $entries[$i]['page'] = $pageInformationCache[$pid];
                } else {
                    $pageTitle = '';
                    $pageRecord = BackendUtility::getRecord(
                            'pages',
                            $pid
                    );
                    if (is_array($pageRecord)) {
                        $title = BackendUtility::getRecordTitle(
                                'pages',
                                $pageRecord
                        );
                        $pageTitle = $title . ' [' . $pid . ']';
                    }
                    $entries[$i]['page'] = $pageTitle;
                    $pageInformationCache[$pid] = $pageTitle;
                }
                // Process extra data (uncompress, dump and escape)
                if ($entries[$i]['extra_data'] === '') {
                    $extraData = '';
                } else {
                    try {
                        $extraData = gzuncompress($entries[$i]['extra_data']);
                        $extraData = htmlspecialchars(json_encode(unserialize($extraData), JSON_PRETTY_PRINT));
                    } catch (\Exception $e) {
                        $extraData = $e->getMessage();
                    }
                }
                $entries[$i]['extra_data'] = $extraData;
            }
            unset($pageInformationCache);
        }
        return $entries;
    }

    /**
     * Fetches the list of all BE users.
     *
     * @return array
     */
    protected function findAllUsers()
    {
        $users = [];
        try {
            $records = $this->getQueryBuilder('be_users')
                ->select('uid', 'username')
                ->from('be_users')
                ->execute()->fetchAll();
            foreach ($records as $record) {
                $users[$record['uid']] = $record;
            }
        } catch (\Exception $e) {
            // Let an empty array return
        }
        return $users;
    }

    /**
     * Sets the extension configuration.
     *
     * Used to pass the "devlog" configuration down to the entry repository.
     *
     * @param ExtensionConfiguration $configuration
     */
    public function setExtensionConfiguration($configuration)
    {
        $this->extensionConfiguration = $configuration;
    }

    /**
     * @param string $table
     * @return QueryBuilder
     */
    protected function getQueryBuilder($table = null)
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table?:$this->databaseTable);
    }
}
