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

use Devlog\Devlog\Domain\Repository\EntryRepository;
use Devlog\Devlog\Utility\Logger;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\AbstractWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Writes log entries to a database table.
 */
class DatabaseWriter extends AbstractWriter
{

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var EntryRepository
     */
    protected $entryRepository;

    /**
     * Constructs this log writer
     *
     * @param array $options Configuration options - depends on the actual log writer
     * @throws \TYPO3\CMS\Core\Log\Exception\InvalidLogWriterConfigurationException
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        try {
            $this->logger = GeneralUtility::makeInstance(Logger::class);
            $this->entryRepository = GeneralUtility::makeInstance(EntryRepository::class);
            $this->entryRepository->setExtensionConfiguration(
                $this->logger->getExtensionConfiguration()
            );
        }
        catch (\Exception $e) {
            throw new \UnexpectedValueException(
                    sprintf(
                            'Database writer is not available (Error: %s, Code: %s)',
                            $e->getMessage(),
                            $e->getCode()
                    ),
                    1518984907
            );
        }
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
        if ($entry = $this->logger->getEntry($record)) {
            $this->entryRepository->add($entry);
            $this->entryRepository->cleanUp();
        }
        return $this;
    }

}
