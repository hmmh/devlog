<?php
defined('TYPO3_MODE') or die();

call_user_func(function () {

    // Register test plugin
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'devlog',
        'TestPlugin',
        'LLL:EXT:devlog/Resources/Private/Language/locallang.xlf:test_plugin',
        'EXT:devlog/Resources/Public/Images/ModuleIcon.svg'
    );
});
