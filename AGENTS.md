# AGENTS.md

Guidance for coding agents working in this repository.

## Project overview

TYPO3 extension (`typo3_solr_dashboard_widgets`, `konradmichalik/typo3-solr-dashboard-widgets`) that ships a Solr Overview dashboard preset and nine widgets for EXT:solr. It is a thin adapter on the TYPO3 Dashboard: all domain knowledge lives in data providers, widgets assemble view data and the Chart.js config.

- Namespace: `KonradMichalik\SolrDashboardWidgets\`
- PHP: `~8.2 || ~8.3 || ~8.4 || ~8.5`
- TYPO3: `^13.0 || ^14.0` (with `apache-solr-for-typo3/solr` `^13.0 || ^14.0`)
- License: GPL-2.0-or-later

## Structure

- `Classes/Widgets/`: the nine widgets and `DashboardWidgetViewTrait`
- `Classes/Widgets/Provider/`: button providers. `ModuleButtonProvider` links to a backend module (accepts a string or an array as fallback chain), `SolrAdminUiButtonProvider` derives the Solr admin URL from the first connection
- `Classes/DataProvider/`: data sources (Solr connection status and metrics, index queue, last indexing run, search statistics, documents per type)
- `Configuration/Services.yaml`: widgets and button providers, tagged `dashboard.widget`, group `solr`
- `Configuration/Backend/`: `DashboardWidgetGroups.php` and `DashboardPresets.php` (`solrSearchInsights` and `solrOverview`)
- `Configuration/Icons.php`, `Resources/`: icons, Fluid templates, CSS
- `Tests/Unit/DataProvider/`: PHPUnit tests
- `Tests/Acceptance/Fixtures/`: dev-only seed data and scripts (`solr_seed.sh`, `scheduler_seed.sh`) consumed by the DDEV setup, no acceptance test framework runs them
- `Tests/CGL/`: isolated Composer project with the code style and analysis tooling
- `Documentation/`: images

### Widget pipeline

- A widget implements `WidgetInterface` and `RequestAwareWidgetInterface`. Chart widgets add `EventDataInterface` and `JavaScriptInterface`, widgets with custom CSS add `AdditionalCssInterface`
- A `ButtonProviderInterface` is injected via `Services.yaml` for the footer button
- Templates use `<f:layout name="Widget/Widget" />` from EXT:dashboard with `main` and `footer` sections
- Chart widgets return a `graphConfig` array from `getEventData()`
- All HTTP calls to Solr use short timeouts and swallow `Throwable`, so an unreachable Solr never breaks the dashboard
- No production code for demo-only concerns, seeding lives in the fixture scripts

## Development commands

Requires [DDEV](https://ddev.readthedocs.io/en/stable/). It provides per-version TYPO3 installs under `.Build/13/` and `.Build/14/` plus an Apache Solr container.

```bash
ddev start
ddev install all               # TYPO3 13 and 14, seeds Solr and fixtures
ddev install 13                # single version
ddev 13 typo3 cache:flush      # TYPO3 CLI for one version

ddev cgl lint                  # lint:composer, lint:editorconfig, lint:php
ddev cgl fix                   # auto-fix
ddev cgl sca                   # PHPStan
ddev cgl analyze               # composer-dependency-analyser
ddev cgl migration             # Rector
```

`Tests/CGL/composer.json` replaces `typo3/cms-core` and `typo3/cms-extbase` and sets `"lock": false`. Anything PHPStan needs beyond that must be added to its `require-dev`.

## Testing

```bash
ddev composer test             # PHPUnit, no coverage
ddev composer test:coverage    # PHPUnit with Xdebug coverage
ddev exec vendor/bin/phpunit -c phpunit.xml --filter <testName>
```

CI runs PHPUnit through a reusable workflow on PHP 8.2 to 8.5, TYPO3 13.4 and 14.3, with highest and lowest dependencies. It also runs CGL, a security workflow and OpenSSF Scorecard.

Test gotchas:
- The QueryBuilder mock must stub `createNamedParameter` with `willReturn(':p0')`. `willReturnArgument(0)` returns ints for timestamp parameters and violates the `: string` return type
- Percent-returning methods of `SearchStatisticsDataProvider` need both `fetchAllAssociative` and `fetchAssociative` mocked

## Code style and static analysis

- PHP CS Fixer with `konradmichalik/php-cs-fixer-preset`
- PHPStan with `konradmichalik/phpstan-typo3-preset`. Cognitive complexity caps: 40 per class, 10 per function-like, so extract helpers before adding branches
- Annotate `array` returns in PHPDoc (`@return array<string, mixed>`, `@return list<string>`), a bare `: array` trips `missingType.iterableValue`
- Rector and composer-dependency-analyser via `ddev cgl`

## Git workflow

- Branch from `main`, open a pull request
- Commit format: `<type>: <description>` with type one of feat, fix, refactor, docs, test, chore, perf, ci
- No co-author trailers
