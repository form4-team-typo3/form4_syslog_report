<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "form4_syslog_report".
 *
 * Auto generated 23-06-2021 15:19
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
  'title' => 'form4 SysLog Report',
  'description' => 'Sends e-mail reports about sys_log entries.',
  'category' => 'be',
  'author' => 'form4 GmbH & Co. KG',
  'author_company' => 'form4 GmbH & Co. KG',
  'author_email' => 'typo3@form4.de',
  'state' => 'stable',
  'uploadfolder' => false,
  'createDirs' => '',
  'clearCacheOnLoad' => 0,
  'version' => '11.0.0',
  'constraints' => 
  array (
    'depends' => 
    array (
      'typo3' => '11.5',
      'belog' => '11.5',
    ),
    'conflicts' => 
    array (
    ),
    'suggests' => 
    array (
    ),
  ),
  'clearcacheonload' => false,
);

