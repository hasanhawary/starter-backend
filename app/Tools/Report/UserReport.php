<?php

namespace App\Tools\Report;

use App\Enum\User\UserGenderEnum;
use App\Models\User;
use HasanHawary\ReportBuilder\BaseReport;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UserReport extends BaseReport
{
    public string $table;

    public function __construct(array $filter)
    {
        parent::__construct($filter);
        $this->table = 'users';
    }

    /*
    |--------------------------------------------------------------------------
    | Main Queries [ charts - cards ]
    |--------------------------------------------------------------------------
    */
    public function getCards(): array
    {
        $cards = (array) DB::table($this->table)
            ->join('model_has_roles', "$this->table.id", '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_type', '=', User::class)
            ->where(fn ($q) => $this->applyFilters($q))
            ->select([
                $this->getUserRoleRaw('root'),
                $this->getUserRoleRaw('admin'),
            ])->first();

        return $this->cardResponse($cards);
    }

    public function getRegisteredUsersByDate(): array
    {
        $data = DB::table($this->table)
            ->select(
                DB::raw("DATE_FORMAT($this->table.created_at,'{$this->guessDateFormat()}') as report_date"),
                DB::raw('COUNT(*) as users_count')
            )->where(fn ($q) => $this->applyFilters($q))
            ->groupBy('report_date')
            ->orderBy('created_at', 'ASC')
            ->get()
            ->toArray();

        return $this->chartResponse('report_date', $data);
    }

    public function getUserByGender(): array
    {
        $data = collect((array) DB::table($this->table)
            ->where(fn ($q) => $this->applyDateFilter($q))
            ->where(fn ($q) => $this->checkSoftDelete($q))
            ->select(
                DB::raw("SUM(CASE WHEN gender = '".UserGenderEnum::Male->value."' THEN 1 ELSE 0 END) as male"),
                DB::raw("SUM(CASE WHEN gender = '".UserGenderEnum::Female->value."' THEN 1 ELSE 0 END) as female"))
            ->first(),
        )->map(fn ($v, $k) => ['gender' => UserGenderEnum::resolve($k), 'count' => $v])->toArray();

        return $this->chartResponse('gender', $data);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    public function applyFilters($q): void
    {
        $this->checkSoftDelete($q);
        $this->applyDateFilter($q);
        $this->applyAdvancedFilters($q);
    }

    private function applyAdvancedFilters($q): void
    {
        if (! empty($this->filter['advanced'])) {
            collect($this->filter['advanced'])->each(function ($filter) use ($q) {
                try {
                    $q->whereIn($filter->key, Arr::wrap($filter->value));
                } catch (\Exception $e) {
                    logError($e);
                }
            });
        }
    }

    private function getUserRoleRaw(string $role): Expression
    {
        return DB::raw("COUNT(DISTINCT CASE WHEN roles.name = '".$role."' THEN 1 END) AS ".$role.'_users_count');
    }
}
