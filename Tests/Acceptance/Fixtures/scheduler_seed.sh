#!/usr/bin/env bash
#
# Seeds a demo Solr scheduler task ("Solr Index Queue Worker (demo)") so the
# Last Indexing Run dashboard widget has data. Idempotent.
#
# This script is dev-only; no equivalent ships with the extension runtime.
# It bootstraps TYPO3 via a tiny inline PHP snippet per installed version and
# writes the row directly using the Doctrine connection. Relies on
# GeneralUtility::makeInstance() to hydrate the IndexQueueWorkerTask (which
# needs the DI container for AbstractTask's parent constructor), then uses
# PHP's native serialize() so the class-name length header is correct.
#
# Usage (inside ddev web container):
#   bash Tests/Acceptance/Fixtures/scheduler_seed.sh

set -euo pipefail

for V in 13 14; do
    TYPO3_DIR="/var/www/html/.Build/${V}"
    [ -d "$TYPO3_DIR" ] || continue

    echo "Seeding demo Solr scheduler task for TYPO3 v${V}..."

    php -d memory_limit=512M -r "
        require '${TYPO3_DIR}/vendor/autoload.php';
        \$classLoader = require '${TYPO3_DIR}/vendor/autoload.php';
        \TYPO3\CMS\Core\Core\SystemEnvironmentBuilder::run(0, \TYPO3\CMS\Core\Core\SystemEnvironmentBuilder::REQUESTTYPE_CLI);
        \TYPO3\CMS\Core\Core\Bootstrap::init(\$classLoader);
        \$connection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getConnectionForTable('tx_scheduler_task');
        \$connection->executeStatement(
            'DELETE FROM tx_scheduler_task WHERE description = ?',
            ['Solr Index Queue Worker (demo)']
        );
        \$task = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\ApacheSolrForTypo3\Solr\Task\IndexQueueWorkerTask::class);
        \$now = time();
        \$row = [
            'crdate' => \$now,
            'disable' => 0,
            'description' => 'Solr Index Queue Worker (demo)',
            'task_group' => 0,
            'nextexecution' => \$now + 3600,
            'lastexecution_time' => \$now - 600,
            'lastexecution_failure' => '',
            'lastexecution_context' => 'CLI',
            'serialized_task_object' => serialize(\$task),
            'serialized_executions' => '',
        ];
        try {
            \$connection->fetchOne('SELECT tasktype FROM tx_scheduler_task LIMIT 1');
            \$row['tasktype'] = \ApacheSolrForTypo3\Solr\Task\IndexQueueWorkerTask::class;
        } catch (\Throwable) {}
        \$connection->insert('tx_scheduler_task', \$row);
        \$uid = (int)\$connection->lastInsertId();
        \$task->setTaskUid(\$uid);
        \$connection->update(
            'tx_scheduler_task',
            ['serialized_task_object' => serialize(\$task)],
            ['uid' => \$uid]
        );
        echo 'Created demo scheduler task with uid ' . \$uid . PHP_EOL;
    " || echo "Failed for v${V} (continuing)"
done

echo "Done."
