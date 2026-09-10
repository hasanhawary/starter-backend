<?php

namespace Tests\Feature\Global;

use App\Http\Controllers\API\BaseController;
use App\Models\BaseModel;
use App\Trait\Global\HasFileActionsMethods;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Attachment extends BaseModel
{
    protected $table = 'test_attachments';

    protected $fillable = ['name', 'path'];

    public $timestamps = false;
}

class AttachmentController extends BaseController
{
    use HasFileActionsMethods;

    public function __construct()
    {
        parent::__construct();

        $this->setModel(Attachment::class)
            ->setStoragePath('attachments')
            ->enableFilePolicy(false);
    }

    public function destroyFile(): JsonResponse
    {
        return $this->deleteFile();
    }

    public function swapFile(): JsonResponse
    {
        return $this->replaceFile();
    }
}

class GuardedAttachmentController extends AttachmentController
{
    public function __construct()
    {
        parent::__construct();

        // Only an attachment whose name says it is draft may be deleted.
        $this->setFileGuard('delete', fn (Attachment $file) => str_contains($file->name, 'draft'));
    }
}

class FileActionsTest extends TestCase
{
    use RefreshDatabase;

    private string $disk;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path');
        });

        // MediaManager writes to media-manager.disk, falling back to the
        // filesystem default; fake exactly that one.
        $this->disk = config('media-manager.disk') ?? config('filesystems.default');
        Storage::fake($this->disk);

        Route::prefix('api')->middleware('api')->group(function () {
            Route::delete('_test/attachments/{attachment}', [AttachmentController::class, 'destroyFile']);
            Route::post('_test/attachments/{attachment}/replace', [AttachmentController::class, 'swapFile']);
            Route::delete('_test/guarded-attachments/{attachment}', [GuardedAttachmentController::class, 'destroyFile']);
        });
    }

    private function storedAttachment(string $name = 'draft.txt'): Attachment
    {
        $path = Media::from(UploadedFile::fake()->createWithContent($name, 'original'))
            ->to('attachments')
            ->store();

        return Attachment::create(['name' => $name, 'path' => $path]);
    }

    public function test_delete_file_removes_the_record_and_the_stored_file(): void
    {
        $attachment = $this->storedAttachment();
        $storedPath = $attachment->getRawOriginal('path');

        Storage::disk($this->disk)->assertExists($storedPath);

        $this->deleteJson("/api/_test/attachments/{$attachment->id}")
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertDatabaseMissing('test_attachments', ['id' => $attachment->id]);
        Storage::disk($this->disk)->assertMissing($storedPath);
    }

    public function test_replace_file_swaps_the_stored_file_and_updates_the_record(): void
    {
        $attachment = $this->storedAttachment('old.txt');
        $oldPath = $attachment->getRawOriginal('path');

        $this->postJson("/api/_test/attachments/{$attachment->id}/replace", [
            'file' => UploadedFile::fake()->createWithContent('new.txt', 'replacement'),
        ])->assertOk();

        $attachment->refresh();

        $this->assertSame('new.txt', $attachment->name);
        $this->assertNotSame($oldPath, $attachment->getRawOriginal('path'));
        Storage::disk($this->disk)->assertExists($attachment->getRawOriginal('path'));
        $this->assertSame('replacement', Storage::disk($this->disk)->get($attachment->getRawOriginal('path')));
    }

    public function test_a_failing_guard_blocks_the_delete(): void
    {
        $attachment = $this->storedAttachment('final.txt');

        $this->deleteJson("/api/_test/guarded-attachments/{$attachment->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('test_attachments', ['id' => $attachment->id]);
    }

    public function test_a_passing_guard_allows_the_delete(): void
    {
        $attachment = $this->storedAttachment('draft.txt');

        $this->deleteJson("/api/_test/guarded-attachments/{$attachment->id}")->assertOk();

        $this->assertDatabaseMissing('test_attachments', ['id' => $attachment->id]);
    }

    public function test_a_missing_record_is_reported_as_not_found(): void
    {
        $this->deleteJson('/api/_test/attachments/9999')->assertNotFound();
    }
}
