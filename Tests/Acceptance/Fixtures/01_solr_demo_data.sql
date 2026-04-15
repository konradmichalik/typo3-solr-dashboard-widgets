-- Demo fixture data for EXT:solr dashboard widgets
-- All operations are guarded via information_schema so the file works on
-- TYPO3 v12, v13 and v14 — even if EXT:solr tables/columns differ or are missing.

-- ---------------------------------------------------------------------------
-- 1. sys_template (only TYPO3 v12/v13 — removed in v14)
-- ---------------------------------------------------------------------------
SET @has_sys_template = (SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sys_template');

SET @sql = IF(@has_sys_template > 0, 'DELETE FROM sys_template WHERE pid = 1', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(@has_sys_template > 0,
    "INSERT INTO sys_template (pid, tstamp, crdate, title, hidden, starttime, endtime, root, clear, include_static_file, constants, config, sorting, deleted) VALUES (1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'Solr Demo Root', 0, 0, 0, 1, 3, 'EXT:solr/Configuration/TypoScript/Solr/,EXT:solr/Configuration/TypoScript/Examples/System/', 'plugin.tx_solr.solr {\\r\\n  scheme = http\\r\\n  host = solr\\r\\n  port = 8983\\r\\n  path = /solr/core_en/\\r\\n}', 'page = PAGE\\r\\npage.10 = TEXT\\r\\npage.10.value = Solr Dashboard Widgets Dev Environment\\r\\nplugin.tx_solr.statistics = 1\\r\\nplugin.tx_solr.logging.indexing = 1', 256, 0)",
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 2. tx_solr_indexqueue_item — sample 150 indexed, 30 pending, 5 failed
-- ---------------------------------------------------------------------------
SET @has_indexqueue = (SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tx_solr_indexqueue_item');

SET @sql = IF(@has_indexqueue > 0, 'DELETE FROM tx_solr_indexqueue_item WHERE root = 1', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 150 indexed pages
SET @sql = IF(@has_indexqueue > 0,
    "INSERT INTO tx_solr_indexqueue_item (root, item_type, item_uid, indexing_configuration, has_indexing_properties, indexing_priority, changed, indexed, errors)
     SELECT 1, 'pages', n, 'pages', 0, 0, UNIX_TIMESTAMP() - 3600, UNIX_TIMESTAMP() - 1800, ''
     FROM (
       SELECT a.N + b.N * 10 + c.N * 100 + 1 AS n
       FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
       CROSS JOIN (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
       CROSS JOIN (SELECT 0 AS N UNION SELECT 1) c
     ) numbers WHERE n <= 150",
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 30 pending tt_content
SET @sql = IF(@has_indexqueue > 0,
    "INSERT INTO tx_solr_indexqueue_item (root, item_type, item_uid, indexing_configuration, has_indexing_properties, indexing_priority, changed, indexed, errors)
     SELECT 1, 'tt_content', n + 200, 'tt_content', 0, 0, UNIX_TIMESTAMP() - 600, 0, ''
     FROM (
       SELECT a.N + b.N * 10 + 1 AS n
       FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
       CROSS JOIN (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2) b
     ) numbers WHERE n <= 30",
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5 failed entries
SET @sql = IF(@has_indexqueue > 0,
    "INSERT INTO tx_solr_indexqueue_item (root, item_type, item_uid, indexing_configuration, has_indexing_properties, indexing_priority, changed, indexed, errors) VALUES
       (1, 'pages', 501, 'pages', 0, 0, UNIX_TIMESTAMP() - 300, 0, 'SolrServerException: Connection refused at /solr/core_en/update'),
       (1, 'pages', 502, 'pages', 0, 0, UNIX_TIMESTAMP() - 420, 0, 'SolrServerException: HTTP 400 Bad Request - invalid field: demoField'),
       (1, 'tt_content', 503, 'tt_content', 0, 0, UNIX_TIMESTAMP() - 900, 0, 'UnexpectedValueException: Could not resolve record type for tt_content uid 503'),
       (1, 'tt_content', 504, 'tt_content', 0, 0, UNIX_TIMESTAMP() - 1500, 0, 'RuntimeException: Indexer for type tt_content is not registered'),
       (1, 'pages', 505, 'pages', 0, 0, UNIX_TIMESTAMP() - 1800, 0, 'SolrClientException: Timeout after 30 seconds')",
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 3. tx_scheduler_task — Solr indexer scheduler entry for "Last Indexing Run"
-- ---------------------------------------------------------------------------
SET @has_scheduler = (SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tx_scheduler_task');

SET @sql = IF(@has_scheduler > 0, "DELETE FROM tx_scheduler_task WHERE serialized_task_object LIKE '%Solr%'", 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(@has_scheduler > 0,
    "INSERT INTO tx_scheduler_task (nextexecution, lastexecution_time, lastexecution_failure, lastexecution_context, serialized_task_object, disable, description, task_group)
     VALUES (UNIX_TIMESTAMP() + 3600, UNIX_TIMESTAMP() - 600, '', 'CLI',
             'O:39:\"ApacheSolrForTypo3\\\\\\\\Solr\\\\\\\\Task\\\\\\\\IndexQueueWorkerTask\":0:{}', 0, 'Solr Index Queue Worker (demo)', 0)",
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 4. tx_solr_statistics — top searches, no-hits, daily volume
-- ---------------------------------------------------------------------------
SET @has_stats = (SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tx_solr_statistics');

SET @sql = IF(@has_stats > 0, 'DELETE FROM tx_solr_statistics WHERE root_pid = 1', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Top search terms (with hits)
SET @sql = IF(@has_stats > 0,
    "INSERT INTO tx_solr_statistics (pid, root_pid, tstamp, keywords, num_found, language) VALUES
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
       (1, 1, UNIX_TIMESTAMP() - 86400 * 14, 'routing', 4, 0)",
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- No-hit queries
SET @sql = IF(@has_stats > 0,
    "INSERT INTO tx_solr_statistics (pid, root_pid, tstamp, keywords, num_found, language) VALUES
       (1, 1, UNIX_TIMESTAMP() - 86400 * 2, 'foobar', 0, 0),
       (1, 1, UNIX_TIMESTAMP() - 86400 * 3, 'foobar', 0, 0),
       (1, 1, UNIX_TIMESTAMP() - 86400 * 4, 'foobar', 0, 0),
       (1, 1, UNIX_TIMESTAMP() - 86400 * 2, 'xyzzy', 0, 0),
       (1, 1, UNIX_TIMESTAMP() - 86400 * 5, 'xyzzy', 0, 0),
       (1, 1, UNIX_TIMESTAMP() - 86400 * 6, 'lorem ipsum', 0, 0),
       (1, 1, UNIX_TIMESTAMP() - 86400 * 7, 'lorem ipsum', 0, 0),
       (1, 1, UNIX_TIMESTAMP() - 86400 * 1, 'missing feature', 0, 0),
       (1, 1, UNIX_TIMESTAMP() - 86400 * 8, 'nothing here', 0, 0),
       (1, 1, UNIX_TIMESTAMP() - 86400 * 9, '404', 0, 0)",
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Daily volume for the line chart (last 14 days)
SET @sql = IF(@has_stats > 0,
    "INSERT INTO tx_solr_statistics (pid, root_pid, tstamp, keywords, num_found, language)
     SELECT 1, 1, UNIX_TIMESTAMP() - 86400 * n, 'volume-fill', 1, 0
     FROM (
       SELECT a.N + b.N * 10 + 1 AS n
       FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
       CROSS JOIN (SELECT 0 AS N UNION SELECT 1) b
     ) numbers WHERE n <= 14",
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 5. be_dashboards — pre-configured "Solr Overview" dashboard for admin (uid 1)
-- ---------------------------------------------------------------------------
SET @has_dashboards = (SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'be_dashboards');
SET @has_cruser_col = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'be_dashboards' AND COLUMN_NAME = 'cruser_id');

SET @sql = IF(@has_dashboards > 0,
    "DELETE FROM be_dashboards WHERE identifier = 'solr-overview-demo'",
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @widgets_json = '{"a1":{"identifier":"solrDashboardWidgets.connectionStatus"},"a2":{"identifier":"solrDashboardWidgets.lastIndexingRun"},"a3":{"identifier":"solrDashboardWidgets.indexQueueStatus"},"a4":{"identifier":"solrDashboardWidgets.documentsInIndex"},"a5":{"identifier":"solrDashboardWidgets.indexQueueErrors"},"a6":{"identifier":"solrDashboardWidgets.searchStatistics"}}';

SET @sql = IF(@has_dashboards > 0 AND @has_cruser_col > 0,
    CONCAT("INSERT INTO be_dashboards (pid, tstamp, crdate, cruser_id, identifier, title, widgets) VALUES (0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1, 'solr-overview-demo', 'Solr Overview', '", @widgets_json, "')"),
    IF(@has_dashboards > 0,
        CONCAT("INSERT INTO be_dashboards (pid, tstamp, crdate, identifier, title, widgets) VALUES (0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'solr-overview-demo', 'Solr Overview', '", @widgets_json, "')"),
        'DO 0'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
