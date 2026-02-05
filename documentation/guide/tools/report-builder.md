---
title: Report Builder
description: Create dynamic, filterable reports with charts
---

# Report Builder

A flexible report builder for Laravel that helps you generate cards, charts, and tables from your data models with minimal effort.
Easily configurable via config/report.php, with support for HighCharts, dynamic filters, and multiple report pages.

-   For more information, visit [Dynamic CLI on Packagist](https://packagist.org/packages/hasanhawary/dynamic-cli).

### Features

-   Generate cards, charts, and tables with unified structure.
-   Out-of-the-box support for HighCharts.
-   Simple configuration using config/report.php.
-   Build multiple report pages (users, orders, etc.).
-   Extendable via your own Report classes.
-   JSON response format ready for any frontend (Vue, React, Inertia, Livewire...).

## Installation

The starter already includes the package (see `packages/report-builder/`). To update:

```bash
composer require hasanhawary/report-builder
```

## How it works in this project

-   Requests hit `/api/report` and are validated by `ReportRequest`.
-   `app/Http/Controllers/API/Global/Report/ReportController.php` enriches the validated filters (sets `page`, computes `apply_date`, and falls back to the default `prefer_chart` from `ReportChartTypeEnum`).
-   The controller instantiates `ReportBuilder`, which picks the correct report class under `App\Tools\Report` (for example `UserReport`) and returns a normalized payload of rows, pagination, cards, and charts.

Controller entrypoint:

```php
// app/Http/Controllers/API/Global/Report/ReportController.php
public function __invoke(ReportRequest $request): JsonResponse
{
	$report = new ReportBuilder($this->filters($request));

	return successResponse($report->response());
}
```

## Define reports

Reports extend `HasanHawary\ReportBuilder\BaseReport` and implement the slices (cards, charts, tables) that should be returned. Example from `App\Tools\Report\UserReport`:

```php
class UserReport extends BaseReport
{
	public string $table = 'users';

	public function getCards(): array
	{
		$cards = DB::table($this->table)
			// add your specific query
			->get() 

		return $this->cardResponse((array) $cards);
	}

	public function getRegisteredUsersByDate(): array
	{
		$data = DB::table($this->table)
			// add your specific query
			->orderBy('created_at', 'ASC')
			->get()
			->toArray();

		return $this->chartResponse('report_date', $data);
	}
}
```

Helpers like `applyFilters`, `applyDateFilter`, and `checkSoftDelete` live in the base class and ensure filters are reused across cards and charts.

## Calling the API

```bash
GET /api/report?model=user&start=2024-01-01&end=2024-01-31&prefer_chart=bar
```

Query parameters map directly to the report filter array. `prefer_chart` drives the default visualization when multiple chart types are available.

## Tips

-   Keep expensive aggregations inside database expressions (`DB::raw`) to avoid PHP loops.
-   Use `apply_date` to guard against missing date ranges and keep queries fast.
-   Pair with [Export Builder](/guide/tools/export-builder) when the same dataset needs to be downloaded.
