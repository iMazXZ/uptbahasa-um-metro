<?php

namespace Tests\Unit;

use App\Models\EptGroup;
use App\Models\Post;
use App\Models\User;
use App\Support\EptSchedulePostSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EptSchedulePostSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Admin', 'web');
    }

    public function test_it_creates_schedule_post_from_group_schedule(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $group = EptGroup::create([
            'name' => 'Grup 04 E 25/26',
            'quota' => 20,
            'jadwal' => now()->setDate(2026, 4, 20)->setTime(8, 30),
            'lokasi' => 'Ruang Stanford',
        ]);

        $post = app(EptSchedulePostSyncService::class)->sync($group, $user->id);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertSame($group->id, $post->ept_group_id);
        $this->assertSame('schedule', $post->type);
        $this->assertSame('Ruang Stanford', $post->event_location);
        $this->assertTrue($post->is_published);
        $this->assertSame('Jadwal Tes EPT Grup 04 (Senin, 20 April 2026)', $post->title);
    }

    public function test_it_unpublishes_existing_schedule_post_when_group_schedule_removed(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $group = EptGroup::create([
            'name' => 'Grup 05 E 25/26',
            'quota' => 20,
            'jadwal' => now()->setDate(2026, 4, 21)->setTime(10, 0),
            'lokasi' => 'Ruang A',
        ]);

        $service = app(EptSchedulePostSyncService::class);
        $createdPost = $service->sync($group, $user->id);

        $group->update(['jadwal' => null]);
        $updatedPost = $service->sync($group->fresh(), $user->id);

        $this->assertSame($createdPost->id, $updatedPost?->id);
        $this->assertFalse((bool) $updatedPost?->is_published);
        $this->assertNull($updatedPost?->published_at);
    }

    public function test_it_detaches_and_unpublishes_schedule_post_when_group_is_deleted(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $group = EptGroup::create([
            'name' => 'Grup 06 E 25/26',
            'quota' => 20,
            'jadwal' => now()->setDate(2026, 4, 22)->setTime(13, 0),
            'lokasi' => 'Ruang B',
        ]);

        $createdPost = app(EptSchedulePostSyncService::class)->sync($group, $user->id);

        $group->delete();

        $updatedPost = Post::query()->find($createdPost->id);

        $this->assertNotNull($updatedPost);
        $this->assertNull($updatedPost?->ept_group_id);
        $this->assertFalse((bool) $updatedPost?->is_published);
        $this->assertNull($updatedPost?->published_at);
    }
}
