<?php
namespace Devlog\Devlog\Controller;

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

use Psr\Log\LoggerAwareInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Psr\Log\LoggerAwareTrait;

/**
 * Dummy plugin controller, which generates test devlog entries.
 *
 * @package Devlog\Devlog\Controller
 */
class TestPluginController extends ActionController implements LoggerAwareInterface
{

    use LoggerAwareTrait;

    /**
     * Writes log entries and outputs confirmation sentence.
     *
     * @return void
     */
    public function indexAction()
    {
        $this->logger->emergency('Emergency test');
        $this->logger->alert('Alert test');
        $this->logger->critical('Critical test');
        $this->logger->error('Error test');
        $this->logger->warning('Warning test');
        $this->logger->notice('Notice test');
        $this->logger->info('Info test');
        $this->logger->debug('Debug test');

        $this->logger->warning('Escaping>=special "characters"', ['Special characters: < > & " \'']);
        $this->logger->warning('Logging object test', [$this]);

        $htmlObject = new \stdClass();
        $htmlObject->html = '<p>This is some HTML content, with <strong>wrong</strong> markups.</td></p>';
        $this->logger->warning('Logging <strong>HTML</strong>', [$htmlObject]);
    }
}
