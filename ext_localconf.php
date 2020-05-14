<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

// Register test plugin
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Devlog.Devlog',
    'TestPlugin',
    ['TestPlugin' => 'index'],
    ['TestPlugin' => 'index']
);

$GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'] = [
    \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
        // add Devlog Writers
        \Devlog\Devlog\Writer\DatabaseWriter::class => [],
        //'Devlog\\Devlog\\Writer\\FileWriter' => []
    ]
];
