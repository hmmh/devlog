<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

// Register the logging method with the appropriate hook
//$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['devLog']['tx_devlog'] = \Devlog\Devlog\Utility\Logger::class . '->log';

// Register log writers
//$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['devlog']['writers']['db'] = \Devlog\Devlog\Writer\DatabaseWriter::class;
//$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['devlog']['writers']['file'] = \Devlog\Devlog\Writer\FileWriter::class;

// Register test plugin
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Devlog.' . $_EXTKEY,
    'TestPlugin',
    ['TestPlugin' => 'index'],
    ['TestPlugin' => 'index']
);

$GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'] = [
    \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
        // add Devlog Writers
        'Devlog\\Devlog\\Writer\\DatabaseWriter' => [],
        //'Devlog\\Devlog\\Writer\\FileWriter' => []
    ]
];
