<?php
namespace Devlog\Devlog\Utility;

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

use Devlog\Devlog\Domain\Model\Entry;
use Devlog\Devlog\Domain\Model\ExtensionConfiguration;
use Devlog\Devlog\Writer\WriterInterface;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The main logging class.
 *
 * Calls the various writers to actually store the log entries somewhere.
 *
 * @author Francois Suter <typo3@cobweb.ch>
 */
class Logger implements SingletonInterface
{
    /**
     * Devlog extension configuration
     *
     * @var ExtensionConfiguration
     */
    protected $extensionConfiguration = null;

    /**
     * @var bool Flag used to turn logging off
     */
    protected $isLoggingEnabled = true;

    /**
     * @var string Unique ID of the current run
     */
    protected $runId;

    /**
     * @var int Counter for entries within the current run
     */
    protected $counter = 0;

    /**
     * @var array Mapping core severities to devlog severities
     */
    protected $severityMappings = [3, 3, 3, 3, 2, 1, 0, 0];

    public function __construct()
    {
        // Read the extension configuration
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);

        // Generate a unique ID, including the global timestamp
        $this->runId = $GLOBALS['EXEC_TIME'] . uniqid('.', true);
    }

    /**
     * @param LogRecord $record
     * @return Entry|null
     */
    public function getEntry(LogRecord $record)
    {
        // If logging is disabled, abort immediately
        if (!$this->isLoggingEnabled) {
            return null;
        }

        // Create an entry and fill it with data
        /** @var Entry $entry */
        $entry = GeneralUtility::makeInstance(Entry::class);
        $entry->setIp(GeneralUtility::getIndpEnv('REMOTE_ADDR')?:'');
        $entry->setExtkey($this->getExtensionKeyFromComponent($record->getComponent()));
        $entry->setSeverity($this->severityMappings[$record->getLevel()]);

        // If the log entry doesn't pass the basic filters, exit early doing nothing
        if (!$this->isEntryAccepted($entry)) {
            return null;
        }

        // Disable logging while inside the devlog, to avoid recursive calls
        $this->isLoggingEnabled = false;

        $entry->setRunId($this->runId);
        $entry->setSorting($this->counter);
        $this->counter++;
        $entry->setCrdate(time());
        $entry->setMessage($record->getMessage());
        $entry->setExtraData($record->getData());

        // Try to get a page id that makes sense
        $pid = 0;
        // In the FE context, this is obviously the current page
        if (TYPO3_MODE === 'FE') {
            $pid = $GLOBALS['TSFE']->id;

        // In other contexts, a global variable may be set with a relevant pid
        } elseif (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['debugData']['pid'])) {
            $pid = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['debugData']['pid'];
        }
        // Make sure that pid is not null
        if ($pid === null) {
            $pid = 0;
        }
        $entry->setPid($pid);

        $entry->setCruserId(
                (isset($GLOBALS['BE_USER']->user['uid'])) ? $GLOBALS['BE_USER']->user['uid'] : 0
        );

        // Get information about the place where this method was called from
        try {
            $callPlaceInfo = $this->getCallPlaceInfo();
            $entry->setLocation($callPlaceInfo['basename']);
            $entry->setLine($callPlaceInfo['line']);
        } catch (\OutOfBoundsException $e) {
            // Do nothing
        }

        $this->isLoggingEnabled = true;

        return $entry;
    }

    /**
     * Checks whether the given log data passes the filters or not.
     *
     * @param $entry Entry
     * @return bool
     */
    public function isEntryAccepted($entry)
    {
        // Skip entry if severity is below minimum level
        if ($entry->getSeverity() < $this->extensionConfiguration->getMinimumLogLevel()) {
            return false;
        }
        // Check excluded list only if included list is empty
        // (if included list is defined, it supersedes excluded list)
        $includedList = $this->extensionConfiguration->getIncludeKeys();
        if ($includedList === '') {
            if (GeneralUtility::inList($this->extensionConfiguration->getExcludeKeys(), $entry->getExtkey())) {
                return false;
            }
        } else {
            if (!GeneralUtility::inList($includedList, $entry->getExtkey())) {
                return false;
            }
        }
        // Skip entry if referrer does not match IP mask
        if (!$this->isIpAddressAccepted($entry->getIp())) {
            return false;
        }
        return true;
    }

    /**
     * Checks if given IP address is acceptable.
     *
     * @param string $ipAddress IP address to check
     * @return bool
     */
    public function isIpAddressAccepted($ipAddress)
    {
        $ipFilter = $this->extensionConfiguration->getIpFilter();
        // Re-use global IP mask if so defined
        if (strtolower($ipFilter) === 'devipmask') {
            $ipFilter = $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'];
        }
        return GeneralUtility::cmpIP($ipAddress, $ipFilter);
    }

    /**
     * Given a backtrace, this method tries to find the place where a "devLog" function was called
     * and returns info about that place.
     *
     * @return    array    information about the call place
     */
    protected function getCallPlaceInfo()
    {
        $backTrace = debug_backtrace();
        foreach ($backTrace as $entry) {
            if (in_array($entry['function'], ['log', 'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'])
                && $entry['class'] === \TYPO3\CMS\Core\Log\Logger::class
                && stripos($entry['file'], 'Logger.php') === false
            ) {
                $pathInfo = pathinfo($entry['file']);
                $pathInfo['line'] = $entry['line'];
                return $pathInfo;
            }
        }
        throw new \OutOfBoundsException(
                'No devLog() call found withing debug stack.',
                1414338781
        );
    }

    /**
     * Returns the extension's configuration.
     *
     * @return ExtensionConfiguration
     */
    public function getExtensionConfiguration()
    {
        return $this->extensionConfiguration;
    }

    /**
     * Sets the extension configuration.
     *
     * This should normally not be used. It is designed for unit testing.
     *
     * @param ExtensionConfiguration $extensionConfiguration
     * @return void
     */
    public function setExtensionConfiguration($extensionConfiguration)
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }

    /**
     * Returns the logging enabled flag.
     *
     * @return bool
     */
    public function isLoggingEnabled()
    {
        return $this->isLoggingEnabled;
    }

    /**
     * Sets the logging enabled flag.
     *
     * @param bool $flag
     * @return void
     */
    public function setIsLoggingEnabled($flag)
    {
        $this->isLoggingEnabled = (bool)$flag;
    }

    /**
     * @param string $component
     * @return string
     */
    protected function getExtensionKeyFromComponent($component) {
        $key = str_ireplace('TYPO3.CMS', 'TYPO3CMS' , strip_tags($component));
        $key = explode('.', $key, 3)[1];
        return GeneralUtility::camelCaseToLowerCaseUnderscored($key);
    }
}
