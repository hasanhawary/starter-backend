<?php

namespace Modules\Showcase\app\Tools\Status;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Modules\Showcase\app\Enum\ShowcaseStatusEnum;
use Modules\Showcase\app\Tools\Status\Strategies\ArchivedStatus;
use Modules\Showcase\app\Tools\Status\Strategies\DraftStatus;
use Modules\Showcase\app\Tools\Status\Strategies\InReviewStatus;
use Modules\Showcase\app\Tools\Status\Strategies\PublishedStatus;

/**
 * The one place a status value becomes a strategy. Every supported value is
 * mapped explicitly; anything else fails loudly rather than silently doing
 * nothing. Validate the selector before calling this.
 */
class ShowcaseStatusFactory
{
    public static function guess(int|string $status, ?Model $model = null, ?User $user = null): ShowcaseStatus
    {
        return match ($status) {
            ShowcaseStatusEnum::Draft->value => new DraftStatus($model, $user),
            ShowcaseStatusEnum::InReview->value => new InReviewStatus($model, $user),
            ShowcaseStatusEnum::Published->value => new PublishedStatus($model, $user),
            ShowcaseStatusEnum::Archived->value => new ArchivedStatus($model, $user),
            default => throw new InvalidArgumentException("Unknown showcase status: {$status}"),
        };
    }
}
