<?php

namespace App\Support;

use App\Models\EptGroup;
use App\Models\Post;
use Carbon\CarbonInterface;

class EptSchedulePostSyncService
{
    public function sync(EptGroup $group, ?int $authorId = null): ?Post
    {
        $post = $group->schedulePost()->first();

        if (! $group->jadwal instanceof CarbonInterface) {
            return $this->deactivate($group);
        }

        $authorId ??= auth()->id();
        $authorId ??= $post?->author_id;

        if (! $authorId) {
            return $post;
        }

        $post ??= new Post();

        $post->fill([
            'author_id' => $post->author_id ?: $authorId,
            'ept_group_id' => $group->getKey(),
            'type' => 'schedule',
            'title' => $this->buildTitle($group),
            'excerpt' => $this->buildExcerpt($group),
            'body' => $this->buildBody($group),
            'event_date' => $group->jadwal->toDateString(),
            'event_time' => $group->jadwal->format('H:i:s'),
            'event_location' => $group->lokasi,
            'is_published' => true,
            'published_at' => $post->published_at ?? now(),
        ]);

        $post->save();

        return $post->fresh();
    }

    public function deactivate(EptGroup $group): ?Post
    {
        $post = $group->schedulePost()->first();

        if (! $post) {
            return null;
        }

        $post->fill([
            'is_published' => false,
            'published_at' => null,
        ]);

        $post->save();

        return $post->fresh();
    }

    public function detachOnGroupDelete(EptGroup $group): ?Post
    {
        $post = Post::query()
            ->where('ept_group_id', $group->getKey())
            ->where('type', 'schedule')
            ->first();

        if (! $post) {
            return null;
        }

        $post->fill([
            'ept_group_id' => null,
            'is_published' => false,
            'published_at' => null,
        ]);

        $post->save();

        return $post->fresh();
    }

    protected function buildTitle(EptGroup $group): string
    {
        $formattedDate = $group->jadwal->translatedFormat('l, d F Y');
        $groupLabel = $this->extractGroupDisplayLabel($group);

        return "Jadwal Tes EPT Grup {$groupLabel} ({$formattedDate})";
    }

    protected function buildExcerpt(EptGroup $group): string
    {
        return 'Peserta agar memperhatikan jadwal, waktu, dan lokasi tes berikut.';
    }

    protected function buildBody(EptGroup $group): string
    {
        $eventDate = e($group->jadwal->translatedFormat('l, d F Y'));
        $eventTime = e($group->jadwal->format('H:i'));
        $location = e((string) $group->lokasi);

        return <<<HTML
<p>Peserta ujian agar memperhatikan jadwal ujian. Jika setelah pengumuman ini ditetapkan, YBS tidak hadir maka YBS dianggap gugur dan harus daftar ulang lagi.</p>
<h3>Perhatian Sebelum Ujian</h3>
<p>Saat ujian EPT atau memasuki Lab. Bahasa, peserta wajib:</p>
<ul>
  <li>Menonaktifkan HP atau alat elektronik lainnya.</li>
  <li>Menunjukkan kartu peserta ujian (print) dan Kartu Tanda Penduduk.</li>
  <li>Memakai pakaian yang sopan.</li>
</ul>
<ol>
  <li>Jadwal Ujian :
    <ol type="a">
      <li>Hari, Tanggal : {$eventDate}</li>
      <li>Pukul : {$eventTime} s/d selesai</li>
      <li>Ruang : {$location}</li>
    </ol>
  </li>
  <li>Hal yang kurang jelas dapat ditanyakan langsung pada bagian Pendaftaran EPT di Lembaga Bahasa UM Metro</li>
</ol>
<p><em>Geser ke kiri untuk melihat detail lengkap daftar peserta.</em></p>
HTML;
    }

    protected function extractGroupDisplayLabel(EptGroup $group): string
    {
        if (preg_match('/(\d+)/', (string) $group->name, $matches)) {
            return $matches[1];
        }

        return $group->name;
    }
}
