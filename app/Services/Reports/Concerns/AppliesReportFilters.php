<?php

namespace App\Services\Reports\Concerns;

use App\Reports\Filters\ReportFilters;
use Illuminate\Database\Eloquent\Builder;

trait AppliesReportFilters
{
    protected function applySharedFilters(
        Builder $query,
        ReportFilters $filters,
        array $searchColumns = ['name', 'notes'],
        bool $supportsVenue = true,
        bool $requiresUserSelection = false
    ): Builder {
        $model = $query->getModel();

        if ($requiresUserSelection && ! $filters->userId) {
            $query->whereRaw('1 = 0');

            return $query;
        }

        if ($supportsVenue && $filters->venueId) {
            $query->where($model->qualifyColumn('venue_id'), $filters->venueId);
        }

        if ($filters->userId) {
            $query->where($model->qualifyColumn('user_id'), $filters->userId);
        }

        if ($filters->employeeRole) {
            $query->whereHas('user', function (Builder $userQuery) use ($filters) {
                $userQuery->where('role', $filters->employeeRole);
            });
        }

        if ($filters->dateFrom) {
            $query->whereDate($model->qualifyColumn('entry_date'), '>=', $filters->dateFrom);
        }

        if ($filters->dateTo) {
            $query->whereDate($model->qualifyColumn('entry_date'), '<=', $filters->dateTo);
        }

        if ($filters->search) {
            $search = '%'.$filters->search.'%';

            $query->where(function (Builder $searchQuery) use ($model, $searchColumns, $search) {
                foreach ($searchColumns as $index => $column) {
                    $qualified = str_contains($column, '.') ? $column : $model->qualifyColumn($column);
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $searchQuery->{$method}($qualified, 'like', $search);
                }
            });
        }

        return $query;
    }

    protected function applyFunctionEntryJoinFilters(Builder $query, ReportFilters $filters, bool $requiresUserSelection = false): Builder
    {
        if ($requiresUserSelection && ! $filters->userId) {
            $query->whereRaw('1 = 0');

            return $query;
        }

        if ($filters->venueId) {
            $query->where('function_entries.venue_id', $filters->venueId);
        }

        if ($filters->userId) {
            $query->where('function_entries.user_id', $filters->userId);
        }

        if ($filters->employeeRole) {
            $query->where('users.role', $filters->employeeRole);
        }

        if ($filters->dateFrom) {
            $query->whereDate('function_entries.entry_date', '>=', $filters->dateFrom);
        }

        if ($filters->dateTo) {
            $query->whereDate('function_entries.entry_date', '<=', $filters->dateTo);
        }

        if ($filters->search) {
            $search = '%'.$filters->search.'%';

            $query->where(function (Builder $searchQuery) use ($search) {
                $searchQuery
                    ->where('function_entries.name', 'like', $search)
                    ->orWhere('function_entries.notes', 'like', $search)
                    ->orWhere('function_packages.name_snapshot', 'like', $search);
            });
        }

        return $query;
    }
}
