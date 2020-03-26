<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_devlog_domain_model_entry');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
    'tx_devlog_domain_model_entry',
    'EXT:devlog/Resources/Private/Language/locallang_csh_txdevlog.xlf'
);

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Devlog.Devlog',
        'system',
        'devlog',
        'after:BelogLog',
        [
            'ListModule' => 'index,delete'
        ],
        [
            'access' => 'admin',
            'icon' => 'EXT:devlog/Resources/Public/Images/ModuleIcon.svg',
            'labels' => 'LLL:EXT:devlog/Resources/Private/Language/Module.xlf'
        ]
    );
}

// Register sprite icons for loading spinner
/** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
    'tx_devlog-loader',
    \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
    [
        'name' => 'spinner',
        'spinning' => true
    ]
);
