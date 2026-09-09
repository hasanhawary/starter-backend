<?php

namespace Tests\Feature\Global;

use App\Http\Controllers\API\BaseController;
use App\Models\BaseModel;
use App\Models\User;
use App\Trait\Global\HasPinMethods;
use App\Trait\Global\LdapOperations;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use LdapRecord\Models\ActiveDirectory\User as ActiveDirectoryLdapUser;
use LdapRecord\Models\OpenLDAP\User as OpenLdapUser;
use Tests\TestCase;

class Article extends BaseModel
{
    protected $table = 'test_articles';

    protected $fillable = ['name'];

    public $timestamps = false;

    public function pinUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'test_article_user', 'article_id', 'user_id');
    }
}

class ArticleController extends BaseController
{
    use HasPinMethods;

    protected string $model = Article::class;

    public function togglePin(): JsonResponse
    {
        return $this->pin();
    }
}

class LdapReader
{
    use LdapOperations;

    public function model(): object
    {
        return $this->getLdapModel();
    }
}

class PinAndLdapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_articles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('test_article_user', function (Blueprint $table) {
            $table->foreignId('article_id');
            $table->foreignId('user_id');
        });

        Route::prefix('api')->middleware('api')
            ->post('_test/articles/{article}/pin', [ArticleController::class, 'togglePin']);
    }

    /*
    |--------------------------------------------------------------------------
    | HasPinMethods
    |--------------------------------------------------------------------------
    */
    public function test_pin_attaches_the_current_user(): void
    {
        $user = $this->actingAsUserWithPermissions();
        $article = Article::create(['name' => 'Guide']);

        $this->postJson("/api/_test/articles/{$article->id}/pin")
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('test_article_user', [
            'article_id' => $article->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_pinning_twice_unpins(): void
    {
        $user = $this->actingAsUserWithPermissions();
        $article = Article::create(['name' => 'Guide']);

        $this->postJson("/api/_test/articles/{$article->id}/pin")->assertOk();
        $this->postJson("/api/_test/articles/{$article->id}/pin")->assertOk();

        $this->assertDatabaseMissing('test_article_user', [
            'article_id' => $article->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_pin_is_scoped_to_the_acting_user(): void
    {
        $owner = $this->actingAsUserWithPermissions();
        $other = $this->createUser();
        $article = Article::create(['name' => 'Guide']);

        $this->postJson("/api/_test/articles/{$article->id}/pin")->assertOk();

        $this->assertDatabaseHas('test_article_user', ['user_id' => $owner->id]);
        $this->assertDatabaseMissing('test_article_user', ['user_id' => $other->id]);
    }

    public function test_pin_reports_a_missing_record(): void
    {
        $this->actingAsUserWithPermissions();

        $this->postJson('/api/_test/articles/9999/pin')->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | LdapOperations
    |--------------------------------------------------------------------------
    */
    public function test_no_ldap_users_are_read_while_ldap_is_inactive(): void
    {
        config(['ldap.active' => false]);

        $this->assertSame([], (new LdapReader)->getLdapUsers(new Request(['search' => 'anything'])));
    }

    public function test_the_ldap_model_follows_the_local_flag(): void
    {
        config(['ldap.local' => true]);
        $this->assertInstanceOf(OpenLdapUser::class, (new LdapReader)->model());

        config(['ldap.local' => false]);
        $this->assertInstanceOf(ActiveDirectoryLdapUser::class, (new LdapReader)->model());
    }
}
