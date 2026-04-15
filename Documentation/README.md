# Solr Dashboard Widgets for TYPO3

A TYPO3 extension providing 6 backend dashboard widgets for Apache Solr (EXT:solr).

## Compatibility

| Extension | TYPO3       | PHP        | EXT:solr    |
|-----------|-------------|------------|-------------|
| 1.x       | 12.4 – 14.x | 8.2 – 8.5  | 12.x – 13.x |

## Installation

```bash
composer require konradmichalik/solr-dashboard-widgets
```

## Requirements

- EXT:solr installed and configured
- `typo3/cms-dashboard` installed
- For Widget 6 (Search Statistics): `plugin.tx_solr.statistics = 1` set in TypoScript

## Widgets

### Widget 1: Solr Connection Status

Shows the ping status for each configured Solr core, giving a quick overview of which cores are reachable.

![Solr Connection Status](docs/screenshots/solr-connection-status.png)

### Widget 2: Index Queue Status

Displays a donut chart showing the ratio of indexed, pending, and failed items in the index queue.

![Index Queue Status](docs/screenshots/index-queue-status.png)

### Widget 3: Index Queue Errors

Lists recent indexing errors in a table and provides a reset button to clear failed queue items.

![Index Queue Errors](docs/screenshots/index-queue-errors.png)

### Widget 4: Documents in Index

Renders a bar chart showing the document count per configured Solr core.

![Documents in Index](docs/screenshots/documents-in-index.png)

### Widget 5: Last Indexing Run

Displays the timestamp of the last scheduler execution along with a status indicator.

![Last Indexing Run](docs/screenshots/last-indexing-run.png)

### Widget 6: Search Statistics

Shows top search terms, no-hit queries, and search volume trends based on Solr statistics.
Requires `plugin.tx_solr.statistics = 1` in TypoScript.

![Search Statistics](docs/screenshots/search-statistics.png)

## Dashboard Preset

The extension ships with a **"Solr Overview"** dashboard preset that includes all 6 widgets, providing an instant Solr monitoring dashboard out of the box.

## Notes

The `ext_emconf.php` file is deprecated as of TYPO3 v14 but is kept in this extension for compatibility with TYPO3 v12 and v13.

## License

GPL-2.0-or-later

## Author

Konrad Michalik
