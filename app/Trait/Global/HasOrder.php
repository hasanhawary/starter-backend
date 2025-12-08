<?php

namespace App\Trait\Global;

use Exception;
use Illuminate\Support\Facades\DB;

trait HasOrder
{
    /**
     * Change the order of a model instance.
     *
     * @param string $orderField
     * @param string $stepField
     * @param $request
     * @return void
     * @throws Exception
     */
    public function changeOrder(string $orderField, string $stepField, $request): void
    {
        try {
            $from = $this->{$orderField};
            $to = (int)$request->input($orderField);

            DB::beginTransaction();
            $this->updateOrderField($request, $orderField, $stepField, $from, $to);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /***
     * @param $request
     * @param string $orderField
     * @param string $stepField
     * @param int $from
     * @param int $to
     * @return void
     */
    protected function updateOrderField($request, string $orderField, string $stepField, int $from, int $to): void
    {
        $between = static::query()->where($stepField, $this->{$stepField})
            ->whereBetween($orderField, [min($from, $to), max($from, $to)])
            ->where('id', '!=', $this->id);

        if ($from < $to) {
            $between->decrement($orderField);
        } elseif ($to < $from) {
            $between->increment($orderField);
        }

        $this->update([$orderField => $to, $stepField => $request->{$stepField}]);
    }
}
