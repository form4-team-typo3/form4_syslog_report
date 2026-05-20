<?php

$EM_CONF[$_EXTKEY] = array(
    'title' => 'form4 SysLog Report',
    'description' => 'Sends e-mail reports about sys_log entries.',
    'category' => 'be',
    'author' => 'form4 GmbH',
    'author_company' => 'form4 GmbH',
    'author_email' => 'typo3@form4.de',
    'state' => 'stable',
    'version' => '13.4.1',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
);
