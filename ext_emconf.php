<?php

$EM_CONF[$_EXTKEY] = array(
    'title' => 'form4 SysLog Report',
    'description' => 'Sends e-mail reports about sys_log entries.',
    'category' => 'be',
    'author' => 'form4 GmbH',
    'author_company' => 'form4 GmbH',
    'author_email' => 'typo3@form4.de',
    'state' => 'stable',
    'version' => '12.4.3',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
);