# Developer's Log

This is a 9 LTS compatible version of [cobwebch/devlog](https://github.com/cobwebch/devlog).
 
TYPO3 extension for logging calls form the [Logging Framework](https://docs.typo3.org/m/typo3/reference-coreapi/master/en-us/ApiOverview/Logging/Index.html) (instead of `GeneralUtility:devLog()`) and browsing and searching those entries.

If you want to use the FileWriter please add the following code:
~~~~
$GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'] = [
    \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
        'Devlog\\Devlog\\Writer\\FileWriter' => []
    ]
];
~~~~