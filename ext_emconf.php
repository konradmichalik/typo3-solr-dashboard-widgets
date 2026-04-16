<?php

// Deprecated since TYPO3 v14. Kept for compatibility with TYPO3 v13.
// This file can be removed once support for v13 is dropped.

$EM_CONF[$_EXTKEY] = [
    'title' => 'Solr Dashboard Widgets',
    'description' => 'Dashboard widgets for Apache Solr in TYPO3 Backend.',
    'category' => 'be',
    'author' => 'Konrad Michalik',
    'author_email' => 'hej@konradmichalik.dev',
    'state' => 'beta',
    'version' => '0.1.0',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.5.99',
            'typo3' => '12.4.0-14.3.99',
            'dashboard' => '13.4.0-14.3.99',
            'solr' => '13.0.0-14.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
