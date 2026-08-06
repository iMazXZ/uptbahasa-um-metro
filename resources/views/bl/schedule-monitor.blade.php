{{-- resources/views/bl/schedule-monitor.blade.php --}}
@extends('layouts.front')
@section('title', 'Jadwal Basic Listening')

@push('styles')
<style>
  /* Animasi Pulse untuk Indikator Live */
  @keyframes live-pulse {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
    70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
  }
  .live-indicator { animation: live-pulse 2s infinite; }
</style>
@endpush

@php
  use Carbon\Carbon;
  use App\Models\BasicListeningSchedule;

  $now  = isset($now) ? $now->copy() : Carbon::now('Asia/Jakarta');
  $dmap = [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'];
  $days = ['Senin','Selasa','Rabu','Kamis','Jumat'];

  // Ambil data mingguan
  $weekData = $weekData 
      ?? BasicListeningSchedule::with(['tutors:id,name','prody:id,name'])
            ->whereIn('hari', $days)
            ->orderBy('hari')->orderBy('jam_mulai')
            ->get();

  $byDay = $weekData->groupBy('hari');
  $todayName = $dmap[$now->dayOfWeekIso] ?? 'Minggu';
  $nowTime   = $now->format('H:i:s');

  // Helper Logic untuk menentukan Status Item
  $getStatus = function($item) use ($todayName, $nowTime, $now) {
      // 1. Cek Live
      if ($item->hari === $todayName && $item->jam_mulai <= $nowTime && $item->jam_selesai >= $nowTime) {
          return 'live';
      }
      // 2. Cek Upcoming (30 menit sebelum)
      if ($item->hari === $todayName) {
          $start = Carbon::createFromFormat('H:i:s', $item->jam_mulai, 'Asia/Jakarta');
          $diff  = $start->diffInMinutes($now, false);
          if ($diff < 0 && abs($diff) <= 30) {
              return 'upcoming';
          }
      }
      return 'normal';
  };

  // Helper Template Render Kartu Hari (Agar tidak perlu file partial terpisah)
  $renderDayCard = function($dayName) use ($byDay, $todayName, $getStatus, $now) {
      $items = $byDay->get($dayName, collect());
      $isToday = ($todayName === $dayName);
      
      // Styling Header Kartu
      $headerBg = $isToday ? 'bg-blue-600' : 'bg-white border-b border-slate-100';
      $headerText = $isToday ? 'text-white' : 'text-slate-800';
      $borderClass = $isToday ? 'ring-2 ring-blue-500/50 border-blue-500' : 'border-slate-200';

      // Output HTML via Echo
      echo '<div class="flex flex-col h-full bg-white rounded-2xl shadow-lg shadow-slate-200/50 border '.$borderClass.' overflow-hidden transition-transform hover:-translate-y-1 duration-300">';
      
      // Header
      echo '<div class="px-5 py-4 flex items-center justify-between '.$headerBg.' '.$headerText.'">';
          echo '<h3 class="font-bold text-lg">'.$dayName.'</h3>';
          if($isToday) {
              echo '<span class="px-2 py-0.5 rounded bg-white/20 text-[10px] font-bold uppercase tracking-wider border border-white/20">Hari Ini</span>';
          }
      echo '</div>';

      // Body List
      echo '<div class="p-4 space-y-3 flex-1 bg-slate-50/50">';
          
          if($items->isEmpty()) {
              echo '<div class="h-full flex flex-col items-center justify-center text-slate-400 py-8 text-center">';
                  echo '<i class="fa-regular fa-calendar-xmark text-3xl mb-2 opacity-30"></i>';
                  echo '<p class="text-xs font-medium">Tidak ada jadwal</p>';
              echo '</div>';
          } else {
              foreach($items as $item) {
                  $status = $getStatus($item);
                  
                  // Default Styles (Normal)
                  $cardBg = 'bg-white';
                  $cardBorder = 'border-slate-200';
                  $borderLeft = 'border-l-4 border-l-slate-300';
                  $timeColor = 'text-slate-500';
                  $badge = '';
                  
                  // Styles (Live)
                  if ($status === 'live') {
                      $cardBg = 'bg-emerald-50/50';
                      $cardBorder = 'border-emerald-200 shadow-sm';
                      $borderLeft = 'border-l-4 border-l-emerald-500';
                      $timeColor = 'text-emerald-700 font-bold';
                      $badge = '<span class="ml-auto px-2 py-0.5 rounded text-[9px] font-black uppercase bg-emerald-100 text-emerald-700 tracking-wide live-indicator">LIVE</span>';
                  } 
                  // Styles (Upcoming)
                  elseif ($status === 'upcoming') {
                      $cardBg = 'bg-amber-50/50';
                      $cardBorder = 'border-amber-200';
                      $borderLeft = 'border-l-4 border-l-amber-500';
                      $timeColor = 'text-amber-700 font-bold';
                      
                      $start = Carbon::createFromFormat('H:i:s', $item->jam_mulai, 'Asia/Jakarta');
                      $diff  = (int) abs($start->diffInMinutes($now, false));
                      $badge = '<span class="ml-auto px-2 py-0.5 rounded text-[9px] font-bold uppercase bg-amber-100 text-amber-700">'.$diff.' Menit Lagi</span>';
                  }

                  $tutors = $item->tutors?->pluck('name')->join(', ') ?: '—';

                  echo '<div class="group relative p-3 rounded-xl border '.$cardBg.' '.$cardBorder.' '.$borderLeft.' transition-all duration-200 hover:shadow-md flex flex-col gap-2">';
                      
                      // Baris 1: Jam & Badge
                      echo '<div class="flex items-center justify-between">';
                          echo '<div class="flex items-center gap-1.5 text-xs '.$timeColor.'">';
                              echo '<i class="fa-regular fa-clock"></i>';
                              echo substr($item->jam_mulai, 0, 5) . ' - ' . substr($item->jam_selesai, 0, 5);
                          echo '</div>';
                          echo $badge;
                      echo '</div>';

                      // Baris 2: Nama Prodi
                      echo '<h4 class="text-sm font-bold text-slate-900 leading-tight">'.($item->prody->name ?? 'Prodi Tidak Diketahui').'</h4>';

                      // Baris 3: Tutor
                      echo '<div class="flex items-start gap-1.5 text-xs text-slate-500 border-t border-slate-200/50 pt-2 mt-0.5">';
                          echo '<i class="fa-solid fa-chalkboard-user mt-0.5 text-slate-400"></i>';
                          echo '<span class="line-clamp-1 group-hover:line-clamp-none transition-all">'.$tutors.'</span>';
                      echo '</div>';

                  echo '</div>'; // End Item Card
              }
          }

      echo '</div>'; // End Body List
      echo '</div>'; // End Container
  };
@endphp

@section('content')
    {{-- 1. HERO SECTION --}}
    <div class="relative bg-slate-900 pt-10 pb-24 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-900 to-slate-900 opacity-90"></div>
        {{-- Background Decor --}}
        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-blue-600 rounded-full blur-3xl opacity-20"></div>
        <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-40 h-40 bg-emerald-500 rounded-full blur-3xl opacity-10"></div>

        <div class="relative max-w-7xl mx-auto px-4">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 backdrop-blur-sm mb-3">
                        <div class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </div>
                        <span class="text-xs font-semibold text-emerald-300 tracking-wide uppercase">Live Monitoring</span>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">Kalender Akademik</h1>
                    <p class="text-blue-200 text-sm md:text-base">
                        Jadwal mingguan kelas Basic Listening • <span class="font-semibold text-white">{{ $now->isoFormat('dddd, D MMMM Y') }}</span>
                    </p>
                </div>

                {{-- Legend --}}
                <div class="flex flex-wrap gap-3">
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 border border-white/10 backdrop-blur-sm">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 live-indicator"></span>
                        <span class="text-xs font-medium text-white">Sedang Berlangsung</span>
                    </div>
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 border border-white/10 backdrop-blur-sm">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        <span class="text-xs font-medium text-white">Segera Dimulai</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. CALENDAR GRID --}}
    <div class="relative z-10 -mt-16 pb-12 px-4 max-w-7xl mx-auto">
        
        {{-- BARIS 1: Senin - Rabu --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            @foreach (array_slice($days, 0, 3) as $dayName)
                {{ $renderDayCard($dayName) }}
            @endforeach
        </div>

        {{-- BARIS 2: Kamis - Jumat (Centered) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-5xl mx-auto">
            @foreach (array_slice($days, 3, 2) as $dayName)
                {{ $renderDayCard($dayName) }}
            @endforeach
        </div>

    </div>
@endsection