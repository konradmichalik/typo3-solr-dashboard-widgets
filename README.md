<div align="center">

![Extension icon](Resources/Public/Icons/Extension.svg)

# TYPO3 extension `solr_dashboard_widgets`

[![Latest Stable Version](https://typo3-badges.dev/badge/solr_dashboard_widgets/version/shields.svg)](https://extensions.typo3.org/extension/solr_dashboard_widgets)
[![Supported TYPO3 versions](https://typo3-badges.dev/badge/solr_dashboard_widgets/typo3/shields.svg)](https://extensions.typo3.org/extension/solr_dashboard_widgets)
[![License](https://poser.pugx.org/konradmichalik/solr-dashboard-widgets/license)](LICENSE.md)

</div>

This extension adds a ready-to-use **Solr Overview** dashboard to the TYPO3 backend with a set of widgets that surface the most relevant information about your [EXT:solr](https://extensions.typo3.org/extension/solr) installation at a glance:

- **Connection & Health** — live ping status per site/core and JVM/query metrics
- **Indexing** — queue status, errors, last and next scheduler run
- **Search** — volume trend, top queries, queries without results
- **Index content** — document counts per type across all cores
- **Caching** — Solr cache hit rates (optional, not in default preset)

> [!NOTE]
> Most EXT:solr backend modules answer *"what's wrong right now?"* only after you open them. This extension moves the answer onto the TYPO3 dashboard — one glance, one page, all cores and all sites.

## 🔥 Installation

### Requirements

* TYPO3 13.4 or 14.x
* PHP 8.2+
* [EXT:solr](https://extensions.typo3.org/extension/solr) ^13.0 or ^14.0-alpha
* `typo3/cms-dashboard`

### Composer

[![Packagist](https://img.shields.io/packagist/v/konradmichalik/solr-dashboard-widgets?label=version&logo=packagist)](https://packagist.org/packages/konradmichalik/solr-dashboard-widgets)
[![Packagist Downloads](https://img.shields.io/packagist/dt/konradmichalik/solr-dashboard-widgets?color=brightgreen)](https://packagist.org/packages/konradmichalik/solr-dashboard-widgets)

```bash
composer require konradmichalik/solr-dashboard-widgets
```

### TER

[![TER version](https://typo3-badges.dev/badge/solr_dashboard_widgets/version/shields.svg)](https://extensions.typo3.org/extension/solr_dashboard_widgets)
[![TER downloads](https://typo3-badges.dev/badge/solr_dashboard_widgets/downloads/shields.svg)](https://extensions.typo3.org/extension/solr_dashboard_widgets)

Download the zip file from [TYPO3 extension repository (TER)](https://extensions.typo3.org/extension/solr_dashboard_widgets).

### Setup

```bash
vendor/bin/typo3 extension:setup --extension=solr_dashboard_widgets
```

Open **Dashboard** in the TYPO3 backend, click **+** in a tab strip and pick **Solr Overview** from the presets.

## 🧰 Widgets

All widgets appear in a dedicated **Apache Solr** group in the "Add widget" dialog. Each widget reads data from Solr and/or EXT:solr directly — no persistent state of its own is stored.

### [Connection Status](Classes/Widgets/ConnectionStatusWidget.php)

One card per configured TYPO3 site, listing every Solr core with a live ping result (`OK` / `offline`). Reaches out to Solr on every dashboard refresh.

Footer button: jumps to the TYPO3 **Site Configuration** module.

### [Solr Health](Classes/Widgets/SolrHealthWidget.php)

Combined node-level health check:

- **Solr version** with a link to the upstream project page
- **JVM heap** donut (used / max) with traffic-light color shift at 70 % / 85 %
- **Mean / p95 response time** and **requests per minute** aggregated across all cores

Data is pulled from Solr's `/admin/metrics` and `/admin/info/system` endpoints, cached for the request.

Footer button: opens the **Solr Admin UI** in a new tab.

### [Last Indexing Run](Classes/Widgets/LastIndexingRunWidget.php)

Shows the most recent execution of any Solr-related scheduler task (e.g. `IndexQueueWorkerTask`) with a human-readable "N minutes ago" and a status badge (OK / warning / error based on age thresholds). Also lists the next scheduled run.

> [!TIP]
> The footer button navigates to the **Scheduler** module. The widget resolves the correct route identifier for both TYPO3 v13 (`scheduler_manage`) and v14 (`scheduler`) automatically.

### [Index Queue Status](Classes/Widgets/IndexQueueStatusWidget.php)

Doughnut chart of `tx_solr_indexqueue_item` entries grouped into *Indexed* / *Pending* / *Failed*.

Footer button: jumps to EXT:solr's **Index Queue** module.

### [Index Queue Errors](Classes/Widgets/IndexQueueErrorsWidget.php)

Table of the most recent queue entries with a non-empty `errors` column, including record type, uid, truncated error message (full text on hover), and timestamp.

Footer button: jumps to EXT:solr's **Index Queue** module.

### [Documents in Index by Type](Classes/Widgets/DocumentsInIndexWidget.php)

Bar chart of document counts **per `type` field value**, aggregated across all reachable cores via Solr's facet API (`facet.field=type`). Reflects what is actually in the index, not what is waiting in the TYPO3 queue.

Footer button: jumps to the **Solr Info** module.

### [Search Volume (last 14 days)](Classes/Widgets/SearchVolumeWidget.php)

Line chart of daily search volume over the last 14 days, read from `tx_solr_statistics`.

> [!IMPORTANT]
> Statistics logging must be enabled in TypoScript for this widget to show data:
> ```
> plugin.tx_solr.statistics = 1
> ```

Footer button: jumps to the **Solr Info** module.

### [Search Terms](Classes/Widgets/SearchTermsWidget.php)

Two stacked lists: **Top Queries** (top 5 by count) and **Queries Without Results**. Uses the same `tx_solr_statistics` data source as Search Volume — the same TypoScript flag is required.

Footer button: jumps to the **Solr Info** module.

### [Cache Hit Rates](Classes/Widgets/CacheHitRatesWidget.php)

> [!NOTE]
> Not part of the default preset — available individually via the widget picker.

Aggregated hit ratios for the three Solr searcher caches (`filterCache`, `queryResultCache`, `documentCache`) shown as progress bars. Useful for spotting cache tuning opportunities.

Footer button: jumps to the **Solr Info** module.

## 🎯 Dashboard preset

The extension ships a **Solr Overview** preset (`solrOverview`) that arranges the eight default widgets in the following order:

*Connection Status · Solr Health · Last Indexing Run · Search Terms · Index Queue Status · Search Volume · Documents in Index · Index Queue Errors*

New dashboards → **+** → **Solr Overview**.

## 🌓 Dark mode

All widgets use theme-adaptive styling: badges are driven by `currentColor` and `color-mix()`, charts use a mid-saturation palette that remains legible in both light and dark TYPO3 backends.

> [!TIP]
> No `prefers-color-scheme` media-query overrides are needed — the widgets adapt automatically to the active TYPO3 color scheme.

## ⭐ License

This project is licensed under [GNU General Public License 2.0 (or later)](LICENSE.md).
