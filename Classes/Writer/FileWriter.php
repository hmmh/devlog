<?php

namespace Devlog\Devlog\Writer;

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

use Devlog\Devlog\Utility\Logger;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\AbstractWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Writes log entries to a given file
 */
class FileWriter extends AbstractWriter
{

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Handle to the log file.
     *
     * @var resource
     */
    protected $fileHandle;

    /**
     * Constructs this log writer
     *
     * @param array $options Configuration options - depends on the actual log writer
     * @throws \TYPO3\CMS\Core\Log\Exception\InvalidLogWriterConfigurationException
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->logger = GeneralUtility::makeInstance(Logger::class);
        $configuration = $this->logger->getExtensionConfiguration();
        $absoluteFilePath = GeneralUtility::getFileAbsFileName(
            $configuration->getLogFilePath()
        );

        // If the file path is valid, try opening the file
        $this->fileHandle = @fopen(
            $absoluteFilePath,
            'ab'
        );

    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        @fclose($this->fileHandle);
    }

    /**
     * Writes the log record
     *
     * @param LogRecord $record Log record
     * @return \TYPO3\CMS\Core\Log\Writer\WriterInterface $this
     * @throws \Exception
     */
    public function writeLog(LogRecord $record)
    {
        if (!$this->fileHandle) {
            return $this;
        }

        if ($entry = $this->logger->getEntry($record)) {
            $logLine = '';
            $logLine .= date('c', $entry->getCrdate());
            switch ($entry->getSeverity()) {
                case 0:
                    $severity = 'INFO';
                    break;
                case 1:
                    $severity = 'NOTICE';
                    break;
                case 2:
                    $severity = 'WARNING';
                    break;
                case 3:
                    $severity = 'ERROR';
                    break;
                default:
                    $severity = 'OK';
            }
            $logLine .= ' [' . $severity . ']';
            $logLine .= ' ' . $entry->getMessage();
            $logLine .= ' (' . $entry->getLocation() . ' ' . $entry->getLine() . ')';
            $logLine .= "\n";
            @fwrite(
                $this->fileHandle,
                $logLine
            );
        }
        return $this;
    }

}
