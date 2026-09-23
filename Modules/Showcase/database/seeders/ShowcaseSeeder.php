<?php

namespace Modules\Showcase\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Showcase\app\Models\ShowcaseCategory;
use Modules\Showcase\app\Models\ShowcaseTag;

/**
 * Seeds the module's reference data — the categories and tags a fresh install
 * needs — and nothing else. Records themselves are user data.
 *
 * Idempotent: re-running updates the seeded rows in place and never touches
 * anything an administrator added.
 */
class ShowcaseSeeder extends Seeder
{
    /**
     * @var array<int, array{code: string, en: string, ar: string}>
     */
    private const CATEGORIES = [
        ['code' => 'GENERAL', 'en' => 'General', 'ar' => 'عام'],
        ['code' => 'INTERNAL', 'en' => 'Internal', 'ar' => 'داخلي'],
        ['code' => 'ARCHIVE', 'en' => 'Archive', 'ar' => 'الأرشيف'],
    ];

    /**
     * @var array<int, array{slug: string, en: string, ar: string, color: string}>
     */
    private const TAGS = [
        ['slug' => 'featured', 'en' => 'Featured', 'ar' => 'مميز', 'color' => '#2563EB'],
        ['slug' => 'needs-review', 'en' => 'Needs Review', 'ar' => 'يحتاج مراجعة', 'color' => '#F59E0B'],
        ['slug' => 'deprecated', 'en' => 'Deprecated', 'ar' => 'متوقف', 'color' => '#DC2626'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $index => $category) {
            ShowcaseCategory::updateOrCreate(
                ['code' => $category['code']],
                [
                    'name' => ['en' => $category['en'], 'ar' => $category['ar']],
                    'sort_order' => $index,
                    'is_active' => true,
                ],
            );
        }

        foreach (self::TAGS as $tag) {
            ShowcaseTag::updateOrCreate(
                ['slug' => Str::slug($tag['slug'])],
                [
                    'name' => ['en' => $tag['en'], 'ar' => $tag['ar']],
                    'color' => $tag['color'],
                    'is_active' => true,
                ],
            );
        }
    }
}
