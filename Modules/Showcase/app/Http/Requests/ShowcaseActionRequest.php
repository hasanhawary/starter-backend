<?php

namespace Modules\Showcase\app\Http\Requests;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Enum;
use InvalidArgumentException;
use Modules\Showcase\app\Enum\ShowcaseStatusEnum;
use Modules\Showcase\app\Tools\Status\ShowcaseStatusContext;
use Modules\Showcase\app\Tools\Status\ShowcaseStatusFactory;

/**
 * The action payload: the target status, plus whatever that specific transition
 * needs. The strategy's own rules are spread alongside the base selector rule,
 * never in place of it.
 */
class ShowcaseActionRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', new Enum(ShowcaseStatusEnum::class)],
            ...$this->statusRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return __('showcase::attributes');
    }

    /**
     * The selected strategy's extra rules. Resolved with the selector alone —
     * no model, no actor — because only `validateRules()` is needed here.
     *
     * An unmapped selector yields no extra rules so the base enum rule above
     * produces a validation error, never a 500 from the Factory.
     *
     * @return array<string, mixed>
     */
    private function statusRules(): array
    {
        try {
            return (new ShowcaseStatusContext)
                ->setStatus(ShowcaseStatusFactory::guess((string) $this->input('status')))
                ->validateRules();
        } catch (InvalidArgumentException) {
            return [];
        }
    }
}
