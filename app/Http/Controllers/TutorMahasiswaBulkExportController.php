<?php

namespace App\Http\Controllers;

use App\Exports\TutorMahasiswaSelectedExport;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TutorMahasiswaBulkExportController extends Controller
{
    public function __invoke(Request $request)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Link unduhan tidak valid atau sudah kadaluarsa.');
        }

        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['Admin', 'tutor'])) {
            abort(403);
        }

        $ids = collect(explode(',', (string) $request->query('ids')))
            ->filter(fn ($id) => ctype_digit($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            abort(404, 'Tidak ada mahasiswa dipilih.');
        }

        $users = User::query()
            ->with([
                'prody',
                'basicListeningGrade',
                'basicListeningManualScores',
                'basicListeningAttempts' => fn ($q) => $q->whereNotNull('submitted_at'),
            ])
            ->whereIn('id', $ids)
            ->get()
            ->sortByDesc(fn ($u) => (int) $u->srn, SORT_REGULAR)
            ->values();

        $prodyName = optional($users->first()->prody)->name;

        return Excel::download(
            new TutorMahasiswaSelectedExport($users, null, $prodyName),
            'Nilai_BL_Selected.xlsx'
        );
    }
}
