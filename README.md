<div align="center">

![Extension icon](Resources/Public/Icons/Extension.svg)

# TYPO3 extension `solr_dashboard_widgets`

[![Latest Stable Version](https://typo3-badges.dev/badge/solr_dashboard_widgets/version/shields.svg)](https://extensions.typo3.org/extension/solr_dashboard_widgets)
[![Supported TYPO3 versions](https://typo3-badges.dev/badge/solr_dashboard_widgets/typo3/shields.svg)](https://extensions.typo3.org/extension/solr_dashboard_widgets)
[![License](https://poser.pugx.org/konradmichalik/solr-dashboard-widgets/license)](LICENSE.md)

</div>

This extension adds a ready-to-use [**Solr Overview**](#-dashboard-preset) dashboard to the TYPO3 backend and ships a set of widgets that surface the most relevant information about your [EXT:solr](https://extensions.typo3.org/extension/solr) installation at a glance:

- **Connection & Health** — ping status per site/core and JVM/query metrics
- **Indexing** — queue status, errors, last and next scheduler run
- **Search** — volume trend, top queries, queries without results
- **Index content** — documents per type across all cores
- **Caching** — Solr cache hit rates (optional)

> [!NOTE]
> Most of the existing EXT:solr backend modules answer *"what's wrong right now?"* only after you open them. The goal of this extension is to move the answer onto the TYPO3 dashboard: one glance, one page, all cores and all sites.

## 🔥 Installation

### Requirements

* TYPO3 >= 13.4
* PHP 8.2+
* [EXT:solr](https://extensions.typo3.org/extension/solr) >= 13.0 (or 14.0‑alpha)
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

That's it — open **Dashboard** in the TYPO3 backend, click the **+** in a tab strip and pick **Solr Overview** from the presets.

## 🧰 Widgets

All widgets live in a dedicated **Apache Solr** group in the "Add widget" dialog. Each widget draws its data from Solr and/or EXT:solr directly — no persistent state of its own is stored.

### [Solr Connection Status](Classes/Widgets/ConnectionStatusWidget.php)

One card per configured TYPO3 site, listing every Solr core belonging to that site with a live ping (`OK` / `offline`). Reaches out to Solr on every refresh.

Footer button: jumps to the TYPO3 **Site Configuration** module.

### [Solr Health](Classes/Widgets/SolrHealthWidget.php)

Combined node-level health check:

- **Solr version** + link to the upstream project page
- **JVM heap** donut (used / max) with traffic-light color shift at 70 % / 85 %
- **Mean / p95 response time** and **requests per minute** aggregated across all cores

Data is pulled from Solr's `/admin/metrics` and `/admin/info/system` endpoints in a single HTTP round-trip per host, cached for the request.

Footer button: opens the **Solr Admin UI** in a new tab.

### [Last Indexing Run](Classes/Widgets/LastIndexingRunWidget.php)

Shows the most recent execution of any Solr-related scheduler task (e.g. `IndexQueueWorkerTask`) with a human-readable "N minutes ago" and a status badge (OK / warning / error, based on age thresholds). Also lists the next scheduled run so the widget is useful even when nothing has run yet.

Footer button: jumps to the **Scheduler** module (falls back cleanly between the v13 `scheduler_manage` and v14 `scheduler` route identifiers).

### [Index Queue Status](Classes/Widgets/IndexQueueStatusWidget.php)

Doughnut chart of the `tx_solr_indexqueue_item` table grouped into *Indexed* / *Pending* / *Failed*.

Footer button: jumps to EXT:solr's **Index Queue** module.

### [Index Queue Errors](Classes/Widgets/IndexQueueErrorsWidget.php)

Table of the most recent queue entries with non-empty `errors`, including record type, uid, truncated error message (full text on hover) and timestamp.

Footer button: jumps to EXT:solr's **Index Queue** module.

### [Documents in Index by Type](Classes/Widgets/DocumentsInIndexWidget.php)

Bar chart of document counts **per `type` field value**, aggregated across all reachable cores. Uses Solr's facet API (`facet.field=type`) so the data reflects what's actually in the index — not what's waiting in the TYPO3 queue.

Footer button: jumps to the **Solr Info** module.

### [Search Volume (last 14 days)](Classes/Widgets/SearchVolumeWidget.php)

Line chart of daily search volume over the last 14 days, read from `tx_solr_statistics`. Requires statistics logging to be enabled in TypoScript:

```
plugin.tx_solr.statistics = 1
```

Footer button: jumps to the **Solr Info** module.

### [Search Terms](Classes/Widgets/SearchTermsWidget.php)

Two stacked lists: **Top Queries** (top 5 by count) and **Queries Without Results**. Same data source and activation requirement as Search Volume.

Footer button: jumps to the **Solr Info** module.

### [Cache Hit Rates](Classes/Widgets/CacheHitRatesWidget.php)

*Not part of the default preset — available in the widget picker.*

Aggregated hit ratios for the three Solr searcher caches (`filterCache`, `queryResultCache`, `documentCache`) shown as progress bars. Helpful to spot tuning opportunities.

Footer button: jumps to the **Solr Info** module.

## 🎯 Dashboard preset

The extension ships a **Solr Overview** preset (`solrOverview`) that arranges the eight default widgets in a sensible order: *Connection Status · Solr Health · Last Indexing Run · Search Terms · Index Queue Status · Search Volume · Documents in Index · Index Queue Errors*.

New dashboards → **+** → **Solr Overview**.

## 🌓 Dark mode

All widgets use theme-adaptive styling: badges are driven by `currentColor` + `color-mix()`, charts use a mid-saturation palette that stays legible in both light and dark TYPO3 backends. No `prefers-color-scheme` media-query gymnastics required.

## ⭐ License

This project is licensed under [GNU General Public License 2.0 (or later)](LICENSE.md).
