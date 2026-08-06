@php
    /** @var \App\Models\User $user */
    $grade = $user->basicListeningGrade;
    $lastAttempt = $user->basicListeningAttempts()->latest('updated_at')->first();
    $lastDate = $lastAttempt?->submitted_at ?? $lastAttempt?->updated_at;
    
    $gradeColor = match($grade?->final_letter_cached) {
        'A','A-','B+','B' => 'success',
        'B-','C+','C' => 'warning',
        'C-','D','E' => 'danger',
        default => 'gray',
    };
@endphp

<div class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <h3 class="text-sm font-medium text-gray-950 dark:text-white mb-3">Ringkasan Basic Listening</h3>
    
    <div class="grid grid-cols-5 gap-4">
        <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400">Attendance</dt>
            <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                {{ $grade?->attendance ?? '—' }}
            </dd>
        </div>
        
        <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400">Final Test</dt>
            <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                {{ $grade?->final_test ?? '—' }}
            </dd>
        </div>
        
        <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400">Final Score</dt>
            <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                {{ $grade?->final_numeric_cached !== null ? number_format((float)$grade->final_numeric_cached, 2) : '—' }}
            </dd>
        </div>
        
        <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400">Grade</dt>
            <dd class="mt-1">
                @if($grade?->final_letter_cached)
                    <span @class([
                        'inline-flex items-center justify-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                        'bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30' => $gradeColor === 'success',
                        'bg-warning-50 text-warning-600 ring-warning-600/10 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30' => $gradeColor === 'warning',
                        'bg-danger-50 text-danger-600 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30' => $gradeColor === 'danger',
                        'bg-gray-50 text-gray-600 ring-gray-600/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/30' => $gradeColor === 'gray',
                    ])>
                        {{ $grade->final_letter_cached }}
                    </span>
                @else
                    <span class="text-sm text-gray-500">—</span>
                @endif
            </dd>
        </div>
        
        <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400">Attempt Terakhir</dt>
            <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                {{ $lastDate ? $lastDate->timezone(config('app.timezone', 'Asia/Jakarta'))->format('d M Y H:i') : '—' }}
            </dd>
        </div>
    </div>
</div>
