<?php

namespace Tests\Feature\Global;

use App\Http\Resources\DataEntry\CountryResource;
use App\Models\Country;
use App\Rules\UniqueCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A translatable value collides per language, so the message must name the
 * language that is actually taken — "The Name in Arabic already exists" — not
 * the field alone.
 *
 * The label is composed from the field's own label plus the language name
 * (`validation.attribute_in_language`), so every translatable field gets a
 * per-language message without needing two lang keys of its own.
 */
class UniqueCheckLanguageTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $name): array
    {
        return [
            'name' => $name,
            'nationality' => ['en' => 'Exampleish', 'ar' => 'مثالية'],
            'code' => strtoupper(fake()->unique()->lexify('??')),
            'phone_code' => '+900',
            'phone_length' => 9,
        ];
    }

    public function test_it_names_the_arabic_value_when_only_the_arabic_name_is_taken(): void
    {
        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Translatable uniqueness is MySQL-only (JSON_UNQUOTE).');
        }

        $this->withHeader('Accept-Language', 'en');
        $this->actingAsUserWithPermissions(['create-country']);

        Country::factory()->create(['name' => ['en' => 'Freedonia', 'ar' => 'فريدونيا']]);

        $response = $this->postJson('api/countries', $this->payload(['en' => 'Elsewhere', 'ar' => 'فريدونيا']));

        $response->assertStatus(422);
        $this->assertSame(['The Name in Arabic already exists'], $response->json('errors.name'));
    }

    public function test_it_names_the_english_value_in_arabic_when_the_locale_is_arabic(): void
    {
        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Translatable uniqueness is MySQL-only (JSON_UNQUOTE).');
        }

        $this->withHeader('Accept-Language', 'ar');
        $this->actingAsUserWithPermissions(['create-country']);

        Country::factory()->create(['name' => ['en' => 'Freedonia', 'ar' => 'فريدونيا']]);

        $response = $this->postJson('api/countries', $this->payload(['en' => 'Freedonia', 'ar' => 'مكان آخر']));

        $response->assertStatus(422);
        $this->assertSame(['الاسم بالإنجليزية موجود بالفعل'], $response->json('errors.name'));
    }

    public function test_it_reports_both_languages_when_both_collide(): void
    {
        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Translatable uniqueness is MySQL-only (JSON_UNQUOTE).');
        }

        $this->withHeader('Accept-Language', 'en');
        $this->actingAsUserWithPermissions(['create-country']);

        Country::factory()->create(['name' => ['en' => 'Freedonia', 'ar' => 'فريدونيا']]);

        $response = $this->postJson('api/countries', $this->payload(['en' => 'Freedonia', 'ar' => 'فريدونيا']));

        $response->assertStatus(422);
        $this->assertSame([
            'The Name in Arabic already exists',
            'The Name in English already exists',
        ], $response->json('errors.name'));
    }

    public function test_a_field_without_per_language_labels_still_names_the_language(): void
    {
        app()->setLocale('en');

        $rule = new UniqueCheck(Country::class, CountryResource::class);

        $method = new \ReflectionMethod($rule, 'translatedLanguageAttribute');
        $method->setAccessible(true);

        // `nationality` has no `nationality.ar` label, so the message composes one.
        $this->assertSame('Nationality in Arabic', $method->invoke($rule, 'nationality', 'ar'));

        app()->setLocale('ar');
        $this->assertSame('الجنسية بالعربية', $method->invoke($rule, 'nationality', 'ar'));
    }
}
