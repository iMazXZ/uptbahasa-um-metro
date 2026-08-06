{{-- resources/views/filament/pages/bl-survey-results.blade.php --}}
<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filter form --}}
        <x-filament::section heading="Filter & Kategori">
            {{ $this->form }}
        </x-filament::section>

        {{-- Stats Cards (inline dengan flexbox) --}}
        @php
            $stats = $this->getTopStats();
        @endphp
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            {{-- Total Responden --}}
            <div
                wire:click="showRespondentsDetail"
                role="button"
                tabindex="0"
                title="Klik untuk melihat daftar responden"
                style="flex: 1; min-width: 200px; background: white; border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05); cursor: pointer;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="padding: 0.75rem; background: #eff6ff; border-radius: 0.5rem;">
                        <x-heroicon-o-users style="width: 1.5rem; height: 1.5rem; color: #2563eb;" />
                    </div>
                    <div>
                        <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">Total Responden</p>
                        <p style="font-size: 1.5rem; font-weight: 700; color: #111827; margin: 0;">{{ number_format($stats['respondents']) }}</p>
                    </div>
                </div>
            </div>

            {{-- Rata-rata Skor --}}
            <div style="flex: 1; min-width: 200px; background: white; border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="padding: 0.75rem; background: #fef3c7; border-radius: 0.5rem;">
                        <x-heroicon-o-star style="width: 1.5rem; height: 1.5rem; color: #d97706;" />
                    </div>
                    <div>
                        <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">Rata-rata Skor</p>
                        <p style="font-size: 1.5rem; font-weight: 700; color: #111827; margin: 0;">{{ $stats['avg'] ?? '—' }}</p>
                    </div>
                </div>
            </div>

            {{-- Belum Isi (Nilai Lengkap) --}}
            @if($this->category === 'tutor' && $this->tutorId)
            <div
                wire:click="showMissingEligibleDetail"
                role="button"
                tabindex="0"
                title="Klik untuk melihat daftar mahasiswa yang belum mengisi"
                style="flex: 1; min-width: 220px; background: white; border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05); cursor: pointer;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="padding: 0.75rem; background: #fee2e2; border-radius: 0.5rem;">
                        <x-heroicon-o-exclamation-triangle style="width: 1.5rem; height: 1.5rem; color: #ef4444;" />
                    </div>
                    <div>
                        <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">Belum Isi (Nilai Lengkap)</p>
                        <p style="font-size: 1.5rem; font-weight: 700; color: #111827; margin: 0;">{{ number_format($this->getMissingEligibleCount()) }}</p>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Modal Daftar Responden --}}
        @if($this->showRespondentsModal)
        <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 50;" wire:click="closeRespondentsModal">
            <div style="background: white; border-radius: 1rem; padding: 1.5rem; max-width: 1000px; width: min(96vw, 1000px); max-height: 80vh; overflow: hidden; display: flex; flex-direction: column;" wire:click.stop>
                {{-- Header --}}
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                    <div>
                        <h3 style="font-size: 1.25rem; font-weight: 600; color: #111827; margin: 0;">Daftar Responden</h3>
                        <p style="font-size: 0.875rem; color: #6b7280; margin: 0.25rem 0 0 0;">
                            Menampilkan {{ count($this->respondents) }} dari {{ $this->respondentsTotal }} responden
                        </p>
                    </div>
                    <button type="button" wire:click="closeRespondentsModal" style="padding: 0.5rem; background: #f3f4f6; border-radius: 0.5rem; border: none; cursor: pointer;">
                        <x-heroicon-o-x-mark style="width: 1.25rem; height: 1.25rem; color: #6b7280;" />
                    </button>
                </div>

                {{-- Content --}}
                <div style="overflow-y: auto; flex: 1;">
                    @if(count($this->respondents) > 0)
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                        <thead>
                            <tr style="background: #f9fafb;">
                                <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-weight: 600;">Responden</th>
                                <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-weight: 600; white-space: nowrap;">NPM/SRN</th>
                                <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-weight: 600;">Prody</th>
                                <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-weight: 600; white-space: nowrap;">No. WA</th>
                                <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-weight: 600;">Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->respondents as $item)
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 0.75rem; vertical-align: top;">
                                    <div style="font-weight: 500; color: #111827;">{{ $item['name'] }}</div>
                                </td>
                                <td style="padding: 0.75rem; color: #6b7280; vertical-align: top; white-space: nowrap;">{{ $item['srn'] ?: '-' }}</td>
                                <td style="padding: 0.75rem; color: #6b7280; vertical-align: top;">{{ $item['prody_name'] ?: '-' }}</td>
                                <td style="padding: 0.75rem; color: #6b7280; vertical-align: top; white-space: nowrap;">{{ $item['whatsapp'] ?: '-' }}</td>
                                <td style="padding: 0.75rem; color: #6b7280; vertical-align: top;">{{ $item['email'] ?: '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- Load More Button --}}
                    @if(count($this->respondents) < $this->respondentsTotal)
                    <div style="text-align: center; margin-top: 1rem;">
                        <button type="button"
                                wire:click="loadMoreRespondents"
                                style="padding: 0.5rem 1.5rem; background: #2563eb; color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem; font-weight: 500;"
                                onmouseover="this.style.background='#1d4ed8'"
                                onmouseout="this.style.background='#2563eb'">
                            Muat Lebih Banyak ({{ $this->respondentsTotal - count($this->respondents) }} tersisa)
                        </button>
                    </div>
                    @endif
                    @else
                    <p style="text-align: center; color: #9ca3af; padding: 2rem;">Tidak ada data</p>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Modal Daftar Belum Isi --}}
        @if($this->showMissingEligibleModal)
        <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 50;" wire:click="closeMissingEligibleModal">
            <div style="background: white; border-radius: 1rem; padding: 1.5rem; max-width: 1100px; width: min(96vw, 1100px); max-height: 80vh; overflow: hidden; display: flex; flex-direction: column;" wire:click.stop>
                {{-- Header --}}
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                    <div>
                        <h3 style="font-size: 1.25rem; font-weight: 600; color: #111827; margin: 0;">Belum Mengisi Kuesioner (Nilai Lengkap)</h3>
                        <p style="font-size: 0.875rem; color: #6b7280; margin: 0.25rem 0 0 0;">
                            Menampilkan {{ count($this->missingEligibleUsers) }} dari {{ $this->missingEligibleTotal }} mahasiswa
                        </p>
                    </div>
                    <button type="button" wire:click="closeMissingEligibleModal" style="padding: 0.5rem; background: #f3f4f6; border-radius: 0.5rem; border: none; cursor: pointer;">
                        <x-heroicon-o-x-mark style="width: 1.25rem; height: 1.25rem; color: #6b7280;" />
                    </button>
                </div>

                {{-- Content --}}
                <div style="overflow-y: auto; flex: 1;">
                    @if(count($this->missingEligibleUsers) > 0)
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                        <thead>
                            <tr style="background: #f9fafb;">
                                <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-weight: 600;">Responden</th>
                                <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-weight: 600; white-space: nowrap;">NPM/SRN</th>
                                <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-weight: 600;">Prody</th>
                                <th style="padding: 0.75rem; text-align: right; border-bottom: 1px solid #e5e7eb; font-weight: 600; white-space: nowrap;">Nilai Akhir</th>
                                <th style="padding: 0.75rem; text-align: center; border-bottom: 1px solid #e5e7eb; font-weight: 600; white-space: nowrap;">Grade</th>
                                <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-weight: 600; white-space: nowrap;">No. WA</th>
                                <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-weight: 600;">Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->missingEligibleUsers as $item)
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 0.75rem; vertical-align: top;">
                                    <div style="font-weight: 500; color: #111827;">{{ $item['name'] }}</div>
                                </td>
                                <td style="padding: 0.75rem; color: #6b7280; vertical-align: top; white-space: nowrap;">{{ $item['srn'] ?: '-' }}</td>
                                <td style="padding: 0.75rem; color: #6b7280; vertical-align: top;">{{ $item['prody_name'] ?: '-' }}</td>
                                <td style="padding: 0.75rem; text-align: right; vertical-align: top; white-space: nowrap;">
                                    {{ is_numeric($item['final_numeric']) ? number_format($item['final_numeric'], 2) : '—' }}
                                </td>
                                <td style="padding: 0.75rem; text-align: center; vertical-align: top; white-space: nowrap;">
                                    @php
                                        $grade = $item['final_letter'] ?? null;
                                        $gradeColor = match ($grade) {
                                            'A', 'A-', 'B+', 'B' => 'success',
                                            'B-', 'C+', 'C' => 'warning',
                                            'C-', 'D', 'E' => 'danger',
                                            default => 'gray',
                                        };
                                    @endphp
                                    <x-filament::badge :color="$gradeColor" size="sm">
                                        {{ $grade ?: '—' }}
                                    </x-filament::badge>
                                </td>
                                <td style="padding: 0.75rem; color: #6b7280; vertical-align: top; white-space: nowrap;">{{ $item['whatsapp'] ?: '-' }}</td>
                                <td style="padding: 0.75rem; color: #6b7280; vertical-align: top;">{{ $item['email'] ?: '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- Load More Button --}}
                    @if(count($this->missingEligibleUsers) < $this->missingEligibleTotal)
                    <div style="text-align: center; margin-top: 1rem;">
                        <button type="button"
                                wire:click="loadMoreMissingEligible"
                                style="padding: 0.5rem 1.5rem; background: #2563eb; color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem; font-weight: 500;"
                                onmouseover="this.style.background='#1d4ed8'"
                                onmouseout="this.style.background='#2563eb'">
                            Muat Lebih Banyak ({{ $this->missingEligibleTotal - count($this->missingEligibleUsers) }} tersisa)
                        </button>
                    </div>
                    @endif
                    @else
                    <p style="text-align: center; color: #9ca3af; padding: 2rem;">Tidak ada data</p>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Likert Distribution Chart --}}
        @php
            $likertData = $stats['likertDistribution'] ?? [];
            $hasData = array_sum($likertData) > 0;
            $maxValue = $hasData ? max($likertData) : 1;
            $total = array_sum($likertData);
            $barColors = [1 => '#ef4444', 2 => '#f97316', 3 => '#eab308', 4 => '#84cc16', 5 => '#22c55e'];
            $labels = [1 => 'Sangat Tidak Setuju', 2 => 'Tidak Setuju', 3 => 'Netral', 4 => 'Setuju', 5 => 'Sangat Setuju'];
        @endphp

        @if($hasData)
        <div style="background: white; border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.05);">
            <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0 0 0.5rem 0;">Distribusi Jawaban Likert</h3>
            <p style="font-size: 0.75rem; color: #9ca3af; margin: 0 0 1rem 0;">Klik bar untuk melihat daftar responden</p>
            <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 0.5rem; height: 160px;">
                @for($i = 1; $i <= 5; $i++)
                    @php
                        $count = $likertData[$i] ?? 0;
                        $height = $maxValue > 0 ? ($count / $maxValue) * 100 : 0;
                        $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                    @endphp
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 0.75rem; font-weight: 500; color: #6b7280;">{{ $count }}</span>
                        <div style="width: 100%; display: flex; flex-direction: column-reverse; align-items: center; height: 100px;">
                            <button type="button"
                                    wire:click="showLikertDetail({{ $i }})"
                                    style="width: 100%; max-width: 60px; height: {{ max($height, 5) }}%; background-color: {{ $barColors[$i] }}; border-radius: 0.375rem 0.375rem 0 0; border: none; cursor: pointer; transition: opacity 0.2s;"
                                    onmouseover="this.style.opacity='0.8'" 
                                    onmouseout="this.style.opacity='1'"
                                    title="Klik untuk melihat detail {{ $labels[$i] }}: {{ $count }} jawaban">
                            </button>
                        </div>
                        <div style="text-align: center;">
                            <span style="font-size: 0.875rem; font-weight: 700; color: #111827;">{{ $i }}</span>
                            <span style="display: block; font-size: 0.75rem; color: #6b7280;">{{ $percentage }}%</span>
                        </div>
                    </div>
                @endfor
            </div>
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem; font-size: 0.75rem; color: #6b7280;">
                    @for($i = 1; $i <= 5; $i++)
                        <span style="display: flex; align-items: center; gap: 0.25rem;">
                            <span style="width: 0.75rem; height: 0.75rem; border-radius: 0.25rem; background-color: {{ $barColors[$i] }};"></span>
                            {{ $i }} = {{ $labels[$i] }}
                        </span>
                    @endfor
                </div>
            </div>
        </div>
        @endif

        {{-- Modal Detail Likert --}}
        @if($this->showLikertModal)
        <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 50;" wire:click="closeLikertModal">
            <div style="background: white; border-radius: 1rem; padding: 1.5rem; max-width: 600px; width: 90%; max-height: 80vh; overflow: hidden; display: flex; flex-direction: column;" wire:click.stop>
                {{-- Header --}}
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                    <div>
                        <h3 style="font-size: 1.25rem; font-weight: 600; color: #111827; margin: 0;">
                            Detail Nilai {{ $this->selectedLikertValue }}
                        </h3>
                        <p style="font-size: 0.875rem; color: #6b7280; margin: 0.25rem 0 0 0;">
                            {{ $labels[$this->selectedLikertValue] ?? '' }} - {{ count($this->likertRespondents) }} jawaban
                        </p>
                    </div>
                    <button type="button" wire:click="closeLikertModal" style="padding: 0.5rem; background: #f3f4f6; border-radius: 0.5rem; border: none; cursor: pointer;">
                        <x-heroicon-o-x-mark style="width: 1.25rem; height: 1.25rem; color: #6b7280;" />
                    </button>
                </div>
                
                {{-- Content --}}
                <div style="overflow-y: auto; flex: 1;">
                    @if(count($this->likertRespondents) > 0)
                    <p style="font-size: 0.75rem; color: #9ca3af; margin: 0 0 0.75rem 0;">
                        Menampilkan {{ count($this->likertRespondents) }} dari {{ $this->likertTotal }} data
                    </p>
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                        <thead>
                            <tr style="background: #f9fafb;">
                                <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-weight: 600;">Responden</th>
                                <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-weight: 600;">Pertanyaan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->likertRespondents as $item)
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 0.75rem; vertical-align: top;">{{ $item['respondent_name'] }}</td>
                                <td style="padding: 0.75rem; color: #6b7280;">{{ $item['question_text'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    
                    {{-- Load More Button --}}
                    @if(count($this->likertRespondents) < $this->likertTotal)
                    <div style="text-align: center; margin-top: 1rem;">
                        <button type="button" 
                                wire:click="loadMoreLikert"
                                style="padding: 0.5rem 1.5rem; background: #2563eb; color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem; font-weight: 500;"
                                onmouseover="this.style.background='#1d4ed8'" 
                                onmouseout="this.style.background='#2563eb'">
                            Muat Lebih Banyak ({{ $this->likertTotal - count($this->likertRespondents) }} tersisa)
                        </button>
                    </div>
                    @endif
                    @else
                    <p style="text-align: center; color: #9ca3af; padding: 2rem;">Tidak ada data</p>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Table --}}
        <x-filament::section heading="Ringkasan Hasil Kuesioner">
            {{ $this->table }}
        </x-filament::section>

        {{-- Kritik dan Saran (Text Feedback) --}}
        @php
            $feedbackList = $this->getTextFeedback();
            $hasFeedback = count($feedbackList) > 0;
        @endphp

        <x-filament::section heading="Kritik dan Saran" description="Respon teks dari responden">
            @if($hasFeedback)
            <div style="margin-bottom: 0.5rem;">
                <span style="font-size: 0.75rem; color: #6b7280;">
                    Menampilkan {{ count($feedbackList) }} dari {{ $this->feedbackTotal }} feedback
                </span>
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                    <thead>
                        <tr style="background: #f9fafb;">
                            <th style="padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-weight: 600; white-space: nowrap;">Responden</th>
                            @if($this->category === 'tutor')
                            <th style="padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-weight: 600; white-space: nowrap;">Untuk Tutor</th>
                            @elseif($this->category === 'supervisor')
                            <th style="padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-weight: 600; white-space: nowrap;">Untuk Supervisor</th>
                            @endif
                            <th style="padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-weight: 600;">Kritik/Saran</th>
                            <th style="padding: 0.5rem 0.75rem; text-align: right; border-bottom: 1px solid #e5e7eb; font-weight: 600; white-space: nowrap;">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($feedbackList as $item)
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 0.5rem 0.75rem; vertical-align: top; white-space: nowrap;">
                                <span style="font-weight: 500; color: #111827;">{{ $item['respondent_name'] }}</span>
                            </td>
                            @if($this->category === 'tutor')
                            <td style="padding: 0.5rem 0.75rem; vertical-align: top; white-space: nowrap;">
                                <span style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.125rem 0.5rem; background: #dbeafe; color: #1d4ed8; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 500;">
                                    {{ $item['tutor_name'] ?? '-' }}
                                </span>
                            </td>
                            @elseif($this->category === 'supervisor')
                            <td style="padding: 0.5rem 0.75rem; vertical-align: top; white-space: nowrap;">
                                <span style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.125rem 0.5rem; background: #fef3c7; color: #b45309; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 500;">
                                    {{ $item['supervisor_name'] ?? '-' }}
                                </span>
                            </td>
                            @endif
                            <td style="padding: 0.5rem 0.75rem; color: #374151; line-height: 1.4;">{{ $item['feedback'] }}</td>
                            <td style="padding: 0.5rem 0.75rem; vertical-align: top; text-align: right; white-space: nowrap; color: #9ca3af; font-size: 0.75rem;">{{ \Carbon\Carbon::parse($item['created_at'])->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Load More Button --}}
            @if(count($feedbackList) < $this->feedbackTotal)
            <div style="text-align: center; margin-top: 1rem;">
                <button type="button" 
                        wire:click="loadMoreFeedback"
                        style="padding: 0.5rem 1.5rem; background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; border-radius: 0.375rem; cursor: pointer; font-size: 0.8125rem; font-weight: 500; transition: background 0.2s;"
                        onmouseover="this.style.background='#e5e7eb'" 
                        onmouseout="this.style.background='#f3f4f6'">
                    Muat Lebih Banyak ({{ $this->feedbackTotal - count($feedbackList) }} lagi)
                </button>
            </div>
            @endif
            @else
            <div style="text-align: center; padding: 2rem; color: #9ca3af;">
                <x-heroicon-o-chat-bubble-bottom-center-text style="width: 2rem; height: 2rem; margin: 0 auto 0.5rem; color: #d1d5db;" />
                <p style="margin: 0; font-size: 0.8125rem;">Belum ada kritik dan saran</p>
            </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
