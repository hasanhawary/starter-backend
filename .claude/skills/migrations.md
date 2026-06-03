# Skill: Migrations

## Rules

- Always additive — never modify existing columns in place (use `->change()` only in a new migration)
- Always reversible — `down()` must undo `up()` exactly
- Enum columns → `string` (never `enum` type)
- Index every FK, every filtered column, every sorted column
- Use `foreignIdFor(ModelClass::class)` for foreign keys
- `softDeletes()` for any model with restore/force-delete routes
- `timestamps()` on every table

---

## Create Table Template

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\RelatedModel;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xxxs', function (Blueprint $table) {
            $table->id();

            // FK — always index
            $table->foreignIdFor(RelatedModel::class)->constrained()->cascadeOnDelete();

            // Translatable fields — always mediumText (stores JSON)
            $table->mediumText('name');
            $table->mediumText('description')->nullable();

            // Enum column — always string
            $table->string('status')->default('active');

            // Media
            $table->string('image')->nullable();

            // Boolean flags
            $table->boolean('is_active')->default(true);

            // Audit
            $table->unsignedBigInteger('created_by')->nullable()->index();

            $table->softDeletes();
            $table->timestamps();

            // Indexes for filtered/sorted columns
            $table->index('status');
            $table->index('is_active');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xxxs');
    }
};
```

---

## Add Column to Existing Table

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xxxs', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::table('xxxs', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });
    }
};
```

---

## FK Patterns

```php
// With constraint (cascades)
$table->foreignIdFor(Category::class)->constrained()->cascadeOnDelete();

// Nullable FK
$table->foreignIdFor(Category::class)->nullable()->constrained()->nullOnDelete();

// Without constraint (manual index)
$table->unsignedBigInteger('category_id')->nullable()->index();
```

---

## Common Column Types

| Use case | Type |
|---|---|
| Enum value | `string` |
| Translatable field (JSON) | `mediumText` — never `string`, never `json` |
| Short text | `string` (varchar 255) |
| Long text | `text` or `mediumText` |
| JSON data | `json` |
| Boolean | `boolean` |
| File path | `string()->nullable()` |
| Money | `decimal(10, 2)` |
| Order/sort | `unsignedSmallInteger` |
| Flags/metadata | `json()->nullable()` |

---

## Do NOT

- Do NOT use `$table->enum()` — use `string` and cast in model
- Do NOT drop or rename columns without a separate compensating migration
- Do NOT add indexes inside a `create` call when using `constrained()` — it auto-indexes
- Do NOT omit `down()` — it must be the exact reverse of `up()`
