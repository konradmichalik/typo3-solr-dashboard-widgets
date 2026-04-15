-- Demo fixture data for EXT:solr dashboard widgets
-- Creates: sys_template with Solr TypoScript, index queue items, search statistics

-- Root-level sys_template with EXT:solr static includes
DELETE FROM sys_template WHERE pid = 1;
INSERT INTO sys_template
  (pid, tstamp, crdate, title, sitetitle, hidden, starttime, endtime, root, clear, include_static_file, constants, config, sorting, deleted)
VALUES
  (1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'Solr Demo Root', 'Solr Dashboard Widgets',
   0, 0, 0, 1, 3,
   'EXT:solr/Configuration/TypoScript/Solr/,EXT:solr/Configuration/TypoScript/Examples/System/',
   'plugin.tx_solr.solr {\r\n  scheme = http\r\n  host = solr\r\n  port = 8983\r\n  path = /solr/core_en/\r\n}',
   'plugin.tx_solr.statistics = 1\r\nplugin.tx_solr.logging.indexing = 1',
   256, 0);

-- Sample index queue items: 150 indexed, 30 pending, 5 failed
DELETE FROM tx_solr_indexqueue_item WHERE root = 1;

-- 150 indexed items (pages)
INSERT INTO tx_solr_indexqueue_item (root, item_type, item_uid, indexing_configuration, has_indexing_properties, indexing_priority, changed, indexed, errors)
SELECT 1, 'pages', n, 'pages', 0, 0, UNIX_TIMESTAMP() - 3600, UNIX_TIMESTAMP() - 1800, ''
FROM (
  SELECT a.N + b.N * 10 + c.N * 100 + 1 AS n
  FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
  CROSS JOIN (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
  CROSS JOIN (SELECT 0 AS N UNION SELECT 1) c
) numbers WHERE n <= 150;

-- 30 pending items (tt_content)
INSERT INTO tx_solr_indexqueue_item (root, item_type, item_uid, indexing_configuration, has_indexing_properties, indexing_priority, changed, indexed, errors)
SELECT 1, 'tt_content', n + 200, 'tt_content', 0, 0, UNIX_TIMESTAMP() - 600, 0, ''
FROM (
  SELECT a.N + b.N * 10 + 1 AS n
  FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
  CROSS JOIN (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2) b
) numbers WHERE n <= 30;

-- 5 failed items
INSERT INTO tx_solr_indexqueue_item (root, item_type, item_uid, indexing_configuration, has_indexing_properties, indexing_priority, changed, indexed, errors) VALUES
  (1, 'pages', 501, 'pages', 0, 0, UNIX_TIMESTAMP() - 300, 0, 'SolrServerException: Connection refused at /solr/core_en/update'),
  (1, 'pages', 502, 'pages', 0, 0, UNIX_TIMESTAMP() - 420, 0, 'SolrServerException: HTTP 400 Bad Request - invalid field: demoField'),
  (1, 'tt_content', 503, 'tt_content', 0, 0, UNIX_TIMESTAMP() - 900, 0, 'UnexpectedValueException: Could not resolve record type for tt_content uid 503'),
  (1, 'tt_content', 504, 'tt_content', 0, 0, UNIX_TIMESTAMP() - 1500, 0, 'RuntimeException: Indexer for type tt_content is not registered'),
  (1, 'pages', 505, 'pages', 0, 0, UNIX_TIMESTAMP() - 1800, 0, 'SolrClientException: Timeout after 30 seconds');

-- Scheduler task entry pretending to be the Solr indexer (for Last Indexing Run widget)
DELETE FROM tx_scheduler_task WHERE serialized_task_object LIKE '%Solr%';
INSERT INTO tx_scheduler_task (tstamp, nextexecution, lastexecution_time, lastexecution_failure, lastexecution_context, serialized_task_object, disable, description, task_group)
VALUES (UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 3600, UNIX_TIMESTAMP() - 600, '', 'CLI',
        'O:39:"ApacheSolrForTypo3\\\\Solr\\\\Task\\\\IndexQueueWorkerTask":0:{}', 0, 'Solr Index Queue Worker (demo)', 0);

-- Search statistics over the last 30 days
DELETE FROM tx_solr_statistics WHERE root_pid = 1;

-- Top search terms (with hits)
INSERT INTO tx_solr_statistics (pid, root_pid, tstamp, keywords, num_found, language) VALUES
  (1, 1, UNIX_TIMESTAMP() - 86400 * 1, 'typo3', 42, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 1, 'typo3', 42, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 2, 'typo3', 42, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 3, 'typo3', 42, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 3, 'typo3', 42, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 4, 'solr', 18, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 4, 'solr', 18, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 5, 'solr', 18, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 2, 'dashboard', 7, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 3, 'dashboard', 7, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 5, 'extension', 12, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 6, 'extension', 12, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 7, 'search', 35, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 8, 'search', 35, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 9, 'fluid', 3, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 10, 'content blocks', 9, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 11, 'content blocks', 9, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 12, 'backend', 5, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 13, 'upgrade', 2, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 14, 'routing', 4, 0);

-- No-hit queries
INSERT INTO tx_solr_statistics (pid, root_pid, tstamp, keywords, num_found, language) VALUES
  (1, 1, UNIX_TIMESTAMP() - 86400 * 2, 'foobar', 0, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 3, 'foobar', 0, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 4, 'foobar', 0, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 2, 'xyzzy', 0, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 5, 'xyzzy', 0, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 6, 'lorem ipsum', 0, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 7, 'lorem ipsum', 0, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 1, 'missing feature', 0, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 8, 'nothing here', 0, 0),
  (1, 1, UNIX_TIMESTAMP() - 86400 * 9, '404', 0, 0);

-- Spread daily search volume over the last 14 days for the line chart
INSERT INTO tx_solr_statistics (pid, root_pid, tstamp, keywords, num_found, language)
SELECT 1, 1, UNIX_TIMESTAMP() - 86400 * n, 'volume-fill', 1, 0
FROM (
  SELECT a.N + b.N * 10 + 1 AS n
  FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
  CROSS JOIN (SELECT 0 AS N UNION SELECT 1) b
) numbers WHERE n <= 14;
