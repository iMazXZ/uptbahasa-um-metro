<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\BasicListeningGrade;
use App\Models\InteractiveClassScore;
use App\Models\Prody;
use App\Support\BlGrading;
use App\Support\InteractiveClassScores;
use App\Support\LegacyBasicListeningScores;
use Illuminate\Support\Facades\Storage;
use App\Support\ImageTransformer;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class BasicListeningProfileController extends Controller
{
    /**
     * List of Islamic study programs that require Interactive Bahasa Arab
     */
    private const PRODI_ISLAM = [
        'Komunikasi dan Penyiaran Islam',
        'Pendidikan Agama Islam',
        'Pendidikan Islam Anak Usia Dini',
    ];

    /**
     * Check if the selected prodi is Pendidikan Bahasa Inggris and year <= 2024
     */
    private function isPendidikanBahasaInggris($request): bool
    {
        $year = (int) $request->input('year');
        if ($year > 2024) return false;

        $prodyId = $request->input('prody_id');
        if (!$prodyId) return false;

        $prody = \App\Models\Prody::find($prodyId);
        return $prody && $prody->name === 'Pendidikan Bahasa Inggris';
    }

    /**
     * Check if the selected prodi is one of 3 Islamic study programs and year <= 2024
     */
    private function isProdiIslam($request): bool
    {
        $year = (int) $request->input('year');
        if ($year > 2024) return false;

        $prodyId = $request->input('prody_id');
        if (!$prodyId) return false;

        $prody = \App\Models\Prody::find($prodyId);
        return $prody && in_array($prody->name, self::PRODI_ISLAM);
    }

    private function needsLegacyBasicListening(Request $request): bool
    {
        $year = (int) $request->input('year');
        $prodyName = LegacyBasicListeningScores::resolveProdyName(
            $request->input('prody_id') ? (int) $request->input('prody_id') : null
        );

        return LegacyBasicListeningScores::requiresLegacyScore($year, $prodyName);
    }

    private function minimumSrnDigitsRule(): callable
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $digits = preg_replace('/\D+/', '', (string) $value);

            if (mb_strlen((string) $digits) < 8) {
                $fail('NPM minimal 8 digit.');
            }
        };
    }

    private function resolveLegacyScoreFromRequest(Request $request): ?float
    {
        $record = LegacyBasicListeningScores::findByIdentity(
            srn: $request->input('srn'),
            name: $request->input('name'),
            year: (int) $request->input('year'),
        );

        return $record && is_numeric($record->score)
            ? (float) $record->score
            : null;
    }

    private function resolveExistingStoredLegacyScore(User $user, array $data): ?float
    {
        if (! is_numeric($user->nilaibasiclistening)) {
            return null;
        }

        if ((int) ($user->prody_id ?? 0) !== (int) ($data['prody_id'] ?? 0)) {
            return null;
        }

        if ((int) ($user->year ?? 0) !== (int) ($data['year'] ?? 0)) {
            return null;
        }

        if (
            LegacyBasicListeningScores::normalizeSrn($user->srn)
            !== LegacyBasicListeningScores::normalizeSrn($data['srn'] ?? null)
        ) {
            return null;
        }

        return (float) $user->nilaibasiclistening;
    }

    /** @return array<int, int> */
    private function resolveInteractiveScoresBySrn(null|string|int $srn, string $track): array
    {
        $normalizedSrn = InteractiveClassScores::normalizeSrn($srn);
        if ($normalizedSrn === null) {
            return [];
        }

        $track = InteractiveClassScores::normalizeTrack($track);

        return InteractiveClassScore::query()
            ->where('track', $track)
            ->where('srn_normalized', $normalizedSrn)
            ->whereBetween('semester', [1, InteractiveClassScores::maxSemester($track)])
            ->orderBy('semester')
            ->get(['semester', 'score'])
            ->filter(fn (InteractiveClassScore $row): bool => is_numeric($row->score))
            ->mapWithKeys(fn (InteractiveClassScore $row): array => [
                (int) $row->semester => (int) round((float) $row->score),
            ])
            ->all();
    }

    /** @return array<int, int> */
    private function resolveInteractiveScoresFromRequest(Request $request, string $track): array
    {
        return $this->resolveInteractiveScoresBySrn($request->input('srn'), $track);
    }

    /** @return array<int, int> */
    private function resolveExistingStoredInteractiveScores(User $user, null|string|int $srn, string $track): array
    {
        if (
            InteractiveClassScores::normalizeSrn($user->srn)
            !== InteractiveClassScores::normalizeSrn($srn)
        ) {
            return [];
        }

        $track = InteractiveClassScores::normalizeTrack($track);
        $scores = [];
        for ($semester = 1; $semester <= InteractiveClassScores::maxSemester($track); $semester++) {
            $field = InteractiveClassScores::fieldName($track, $semester);
            if ($field === null) {
                continue;
            }
            if (is_numeric($user->{$field})) {
                $scores[$semester] = (int) round((float) $user->{$field});
            }
        }

        return $scores;
    }

    /** @return array<int, int> */
    private function effectiveInteractiveClassScoresForUser(User $user): array
    {
        $prodyName = $user->prody?->name;
        $year = (int) ($user->year ?? 0);

        if ($year > 2024 || $prodyName !== 'Pendidikan Bahasa Inggris') {
            return [];
        }

        $imported = $this->resolveInteractiveScoresBySrn($user->srn, InteractiveClassScore::TRACK_ENGLISH);

        return $imported !== [] ? $imported : $this->resolveExistingStoredInteractiveScores(
            $user,
            $user->srn,
            InteractiveClassScore::TRACK_ENGLISH,
        );
    }

    /** @return array<int, int> */
    private function effectiveInteractiveArabicScoresForUser(User $user): array
    {
        $prodyName = $user->prody?->name;
        $year = (int) ($user->year ?? 0);

        if ($year > 2024 || ! in_array($prodyName, self::PRODI_ISLAM, true)) {
            return [];
        }

        $imported = $this->resolveInteractiveScoresBySrn($user->srn, InteractiveClassScore::TRACK_ARABIC);

        return $imported !== [] ? $imported : $this->resolveExistingStoredInteractiveScores(
            $user,
            $user->srn,
            InteractiveClassScore::TRACK_ARABIC,
        );
    }

    public function updateGroupNumber(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'nomor_grup_bl' => ['required','integer','between:1,4'],
        ], [
            'nomor_grup_bl.required' => 'Nomor grup wajib diisi.',
            'nomor_grup_bl.integer'  => 'Nomor grup harus berupa angka.',
            'nomor_grup_bl.between'  => 'Nomor grup harus antara 1 sampai 4.',
        ]);

        $user->update([
            'nomor_grup_bl' => $validated['nomor_grup_bl'],
        ]);

        return back()->with('success', 'Nomor grup berhasil disimpan.');
    }

    public function showCompleteForm(Request $request)
    {
        $user = $request->user();
        $next = $request->query('next', route('bl.index'));

        $prodis = Prody::query()->orderBy('name')->get(['id','name']);

        return view('bl.complete_profile', [
            'user'   => $user,
            'next'   => $next,
            'prodis' => $prodis,
        ]);
    }

    public function submitCompleteForm(Request $request)
    {
        $user = $request->user();
        $next = $request->input('next', route('bl.index'));

        if ($this->isPendidikanBahasaInggris($request)) {
            $resolvedInteractiveScores = $this->resolveInteractiveScoresFromRequest($request, InteractiveClassScore::TRACK_ENGLISH);
            $storedInteractiveScores = $this->resolveExistingStoredInteractiveScores(
                $user,
                $request->input('srn'),
                InteractiveClassScore::TRACK_ENGLISH,
            );

            $merge = [];
            for ($semester = 1; $semester <= 6; $semester++) {
                $field = 'interactive_class_' . $semester;
                $merge[$field] = $resolvedInteractiveScores[$semester]
                    ?? $storedInteractiveScores[$semester]
                    ?? null;
            }

            if ($merge !== []) {
                $request->merge($merge);
            }
        }

        if ($this->isProdiIslam($request)) {
            $resolvedArabicScores = $this->resolveInteractiveScoresFromRequest($request, InteractiveClassScore::TRACK_ARABIC);
            $storedArabicScores = $this->resolveExistingStoredInteractiveScores(
                $user,
                $request->input('srn'),
                InteractiveClassScore::TRACK_ARABIC,
            );

            $request->merge([
                'interactive_bahasa_arab_1' => $resolvedArabicScores[1] ?? $storedArabicScores[1] ?? null,
                'interactive_bahasa_arab_2' => $resolvedArabicScores[2] ?? $storedArabicScores[2] ?? null,
            ]);
        }

        $data = $request->validate([
            // ===== Field akun (opsional, dipakai di dashboard biodata) =====
            'name'  => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            // ===== Field biodata inti =====
            'prody_id' => ['required', Rule::exists('prodies', 'id')],
            'srn'      => [
                'required',
                'string',
                'max:50',
                $this->minimumSrnDigitsRule(),
                Rule::unique('users', 'srn')->ignore($user->id),
            ],
            'year'     => [
                'required',
                'integer',
                'min:2015',
                'max:' . (int) now()->year + 1, // biar sama2 aman
            ],

            // ===== Nilai Basic Listening (conditional: angkatan <= 2024 DAN bukan S2 DAN bukan Pendidikan Bahasa Inggris) =====
            'nilaibasiclistening' => ['nullable', 'numeric', 'min:0', 'max:100'],

            // ===== Interactive Class (6 field) - KHUSUS Pendidikan Bahasa Inggris angkatan <= 2024 =====
            'interactive_class_1' => ['nullable', Rule::requiredIf($this->isPendidikanBahasaInggris($request)), 'numeric', 'min:0', 'max:100'],
            'interactive_class_2' => ['nullable', Rule::requiredIf($this->isPendidikanBahasaInggris($request)), 'numeric', 'min:0', 'max:100'],
            'interactive_class_3' => ['nullable', Rule::requiredIf($this->isPendidikanBahasaInggris($request)), 'numeric', 'min:0', 'max:100'],
            'interactive_class_4' => ['nullable', Rule::requiredIf($this->isPendidikanBahasaInggris($request)), 'numeric', 'min:0', 'max:100'],
            'interactive_class_5' => ['nullable', Rule::requiredIf($this->isPendidikanBahasaInggris($request)), 'numeric', 'min:0', 'max:100'],
            'interactive_class_6' => ['nullable', Rule::requiredIf($this->isPendidikanBahasaInggris($request)), 'numeric', 'min:0', 'max:100'],

            // ===== Interactive Bahasa Arab (2 field) - KHUSUS 3 Prodi Islam angkatan <= 2024 =====
            'interactive_bahasa_arab_1' => ['nullable', Rule::requiredIf($this->isProdiIslam($request)), 'numeric', 'min:0', 'max:100'],
            'interactive_bahasa_arab_2' => ['nullable', Rule::requiredIf($this->isProdiIslam($request)), 'numeric', 'min:0', 'max:100'],

            // ===== Nomor WhatsApp (opsional + validasi format jika diisi) =====
            'whatsapp' => [
                'nullable', 
                'string', 
                'max:20',
                function ($attribute, $value, $fail) {
                    if (empty($value)) return; // Skip jika kosong
                    $normalized = \App\Support\NormalizeWhatsAppNumber::normalize($value);
                    if (!$normalized) {
                        $fail('Format nomor WhatsApp tidak valid. Contoh: 085712345678');
                    }
                },
            ],

            // ===== Foto Profil (dikompres) =====
            'image'    => ['nullable', 'mimes:jpeg,jpg,png,webp', 'max:8192'], // 8MB
        ], [
            'prody_id.required' => 'Pilih Program Studi.',
            'prody_id.exists'   => 'Program Studi tidak valid.',

            'srn.required'      => 'NPM wajib diisi.',
            'srn.unique'        => 'NPM ini sudah terdaftar. Hubungi Admin jika ini memang NPM Anda.',

            'year.required'     => 'Tahun angkatan wajib diisi.',

            'name.required'     => 'Nama lengkap wajib diisi.',
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email ini sudah digunakan oleh pengguna lain.',

            'nilaibasiclistening.required' => 'Nilai Basic Listening wajib diisi untuk angkatan 2024 ke bawah.',
            'nilaibasiclistening.numeric'  => 'Nilai Basic Listening harus berupa angka.',
            'nilaibasiclistening.min'      => 'Nilai Basic Listening minimal 0.',
            'nilaibasiclistening.max'      => 'Nilai Basic Listening maksimal 100.',

            'interactive_class_1.required' => 'Nilai Interactive Class Semester 1 wajib diisi.',
            'interactive_class_2.required' => 'Nilai Interactive Class Semester 2 wajib diisi.',
            'interactive_class_3.required' => 'Nilai Interactive Class Semester 3 wajib diisi.',
            'interactive_class_4.required' => 'Nilai Interactive Class Semester 4 wajib diisi.',
            'interactive_class_5.required' => 'Nilai Interactive Class Semester 5 wajib diisi.',
            'interactive_class_6.required' => 'Nilai Interactive Class Semester 6 wajib diisi.',

            'interactive_bahasa_arab_1.required' => 'Nilai Interactive Bahasa Arab 1 wajib diisi.',
            'interactive_bahasa_arab_2.required' => 'Nilai Interactive Bahasa Arab 2 wajib diisi.',

            'image.mimes'       => 'Foto harus berupa JPG, PNG, atau WebP.',
            'image.max'         => 'Ukuran foto maksimal 8 MB.',

            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
        ]);

        // Normalisasi nama ke uppercase jika diisi
        if (array_key_exists('name', $data) && $data['name'] !== null) {
            $data['name'] = mb_strtoupper(trim($data['name']), 'UTF-8');
        }

        if ($this->needsLegacyBasicListening($request)) {
            $resolvedScore = $this->resolveLegacyScoreFromRequest($request);
            $storedScore = $this->resolveExistingStoredLegacyScore($user, $data);

            if ($resolvedScore !== null) {
                $data['nilaibasiclistening'] = $resolvedScore;
            } elseif ($storedScore !== null) {
                $data['nilaibasiclistening'] = $storedScore;
            } elseif (! is_numeric($data['nilaibasiclistening'] ?? null)) {
                throw ValidationException::withMessages([
                    'nilaibasiclistening' => 'Nilai Basic Listening tidak ditemukan untuk NPM ini. Hubungi admin agar data nilai manual diimport.',
                ]);
            }
        }

        // Normalisasi nomor WhatsApp
        if (array_key_exists('whatsapp', $data) && !empty($data['whatsapp'])) {
            $data['whatsapp'] = \App\Support\NormalizeWhatsAppNumber::normalize($data['whatsapp']);
        }

        // === FOTO PROFIL: kompres pakai ImageTransformer (union UploadedFile / TemporaryUploadedFile) ===
        if ($request->hasFile('image')) {
            $file = $request->file('image');

            // hapus foto lama kalau ada
            $old = $user->image;
            if (is_string($old) && $old !== '' && Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }

            // nama file sama konsepnya dengan Filament: avatar_000123.webp
            $base = 'avatar_' . Str::of($user->id)->padLeft(6, '0') . '.webp';

            $result = ImageTransformer::toWebpFromUploaded(
                uploaded:   $file,
                targetDisk: 'public',
                targetDir:  'profile_pictures',
                quality:    82,
                maxWidth:   600,
                maxHeight:  600,
                basename:   $base
            );

            $data['image'] = $result['path'];
        }

        $user->forceFill($data)->save();

        if ((int) ($user->year ?? 0) <= 2024 && is_numeric($user->nilaibasiclistening)) {
            $grade = BasicListeningGrade::firstOrCreate([
                'user_id' => $user->id,
                'user_year' => $user->year,
            ]);

            $grade->final_numeric_cached = round((float) $user->nilaibasiclistening, 2);
            $grade->final_letter_cached = BlGrading::letter((float) $user->nilaibasiclistening);
            $grade->save();
        }

        return redirect($next)->with('success', 'Biodata berhasil diperbarui.');
    }

    public function deletePhoto(Request $request)
    {
        $user = $request->user();

        if ($user->image && Storage::disk('public')->exists($user->image)) {
            Storage::disk('public')->delete($user->image);
        }

        $user->image = null;
        $user->save();

        return back()->with('success', 'Foto profil berhasil dihapus.');
    }

    public function showDashboardBiodata(Request $request)
    {
        $user   = $request->user();
        $prodis = Prody::query()->orderBy('name')->get(['id', 'name']);
        $legacyAutoScore = LegacyBasicListeningScores::effectiveScoreForUser($user);

        // bisa pakai input number biasa, jadi tidak wajib kirim list years
        return view('dashboard.biodata', [
            'user' => $user,
            'prodis' => $prodis,
            'legacyAutoScore' => $legacyAutoScore,
            'interactiveAutoScores' => $this->effectiveInteractiveClassScoresForUser($user),
            'interactiveArabicAutoScores' => $this->effectiveInteractiveArabicScoresForUser($user),
        ]);
    }

    public function lookupLegacyScore(Request $request)
    {
        $data = $request->validate([
            'srn' => ['required', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:2015', 'max:' . ((int) now()->year + 1)],
            'prody_id' => ['nullable', Rule::exists('prodies', 'id')],
            'allow_existing_user_score' => ['nullable', 'boolean'],
        ]);

        $prodyName = LegacyBasicListeningScores::resolveProdyName(isset($data['prody_id']) ? (int) $data['prody_id'] : null);
        $legacyApplicable = LegacyBasicListeningScores::requiresLegacyScore(
            isset($data['year']) ? (int) $data['year'] : null,
            $prodyName,
        );
        $interactiveApplicable = $this->isPendidikanBahasaInggris($request);
        $interactiveArabicApplicable = $this->isProdiIslam($request);

        $normalizedSrn = preg_replace('/\D+/', '', (string) $data['srn']);
        if (mb_strlen((string) $normalizedSrn) < 8) {
            return response()->json([
                'success' => true,
                'applicable' => $legacyApplicable,
                'found' => false,
                'score' => null,
                'grade' => null,
                'message' => $legacyApplicable
                    ? 'Lengkapi NPM terlebih dahulu untuk mendeteksi nilai Basic Listening.'
                    : 'Nilai manual tidak diperlukan untuk kombinasi angkatan dan prodi ini.',
                'interactive_class_applicable' => $interactiveApplicable,
                'interactive_class_found' => false,
                'interactive_class_scores' => [],
                'interactive_class_message' => $interactiveApplicable
                    ? 'Lengkapi NPM terlebih dahulu untuk mendeteksi nilai Interactive Class.'
                    : 'Nilai Interactive Class tidak diperlukan untuk kombinasi angkatan dan prodi ini.',
                'interactive_arabic_applicable' => $interactiveArabicApplicable,
                'interactive_arabic_found' => false,
                'interactive_arabic_scores' => [],
                'interactive_arabic_message' => $interactiveArabicApplicable
                    ? 'Lengkapi NPM terlebih dahulu untuk mengecek nilai Bahasa Arab 1 dan 2.'
                    : 'Nilai Interactive Bahasa Arab tidak diperlukan untuk kombinasi angkatan dan prodi ini.',
            ]);
        }

        $record = $legacyApplicable
            ? LegacyBasicListeningScores::findByIdentity(
                srn: $data['srn'],
                name: $data['name'] ?? null,
                year: isset($data['year']) ? (int) $data['year'] : null,
            )
            : null;

        $storedScore = ($data['allow_existing_user_score'] ?? false) && $legacyApplicable && $record === null
            ? $this->resolveExistingStoredLegacyScore($request->user(), $data)
            : null;

        $interactiveScores = [];
        $interactiveStoredScores = [];
        $interactiveMessage = $interactiveApplicable
            ? 'Nilai Interactive Class belum ditemukan. Silakan ke kantor Lembaga Bahasa.'
            : 'Nilai Interactive Class tidak diperlukan untuk kombinasi angkatan dan prodi ini.';
        $interactiveArabicScores = [];
        $interactiveArabicStoredScores = [];
        $interactiveArabicMessage = $interactiveArabicApplicable
            ? 'Nilai Bahasa Arab 1 dan 2 belum ditemukan di arsip. Jika Anda sudah mengikuti kelas, silakan ke kantor Lembaga Bahasa.'
            : 'Nilai Interactive Bahasa Arab tidak diperlukan untuk kombinasi angkatan dan prodi ini.';

        if ($interactiveApplicable && mb_strlen((string) $normalizedSrn) >= 8) {
            $interactiveScores = $this->resolveInteractiveScoresFromRequest($request, InteractiveClassScore::TRACK_ENGLISH);

            if ($interactiveScores === []) {
                $interactiveStoredScores = $this->resolveExistingStoredInteractiveScores(
                    $request->user(),
                    $data['srn'] ?? null,
                    InteractiveClassScore::TRACK_ENGLISH,
                );
            }

            $effectiveInteractiveScores = $interactiveScores !== [] ? $interactiveScores : $interactiveStoredScores;

            if ($effectiveInteractiveScores !== []) {
                $missingSemesters = array_values(array_diff(range(1, 6), array_keys($effectiveInteractiveScores)));
                $interactiveMessage = $missingSemesters === []
                    ? 'Nilai Interactive Class berhasil ditemukan otomatis untuk semua semester.'
                    : 'Nilai ditemukan untuk semester ' . implode(', ', array_keys($effectiveInteractiveScores)) . '. Semester ' . implode(', ', $missingSemesters) . ' belum ada.';
            }
        } elseif ($interactiveApplicable) {
            $interactiveMessage = 'Lengkapi NPM terlebih dahulu untuk mendeteksi nilai Interactive Class.';
        }

        if ($interactiveArabicApplicable && mb_strlen((string) $normalizedSrn) >= 8) {
            $interactiveArabicScores = $this->resolveInteractiveScoresFromRequest($request, InteractiveClassScore::TRACK_ARABIC);

            if ($interactiveArabicScores === []) {
                $interactiveArabicStoredScores = $this->resolveExistingStoredInteractiveScores(
                    $request->user(),
                    $data['srn'] ?? null,
                    InteractiveClassScore::TRACK_ARABIC,
                );
            }

            $effectiveArabicScores = $interactiveArabicScores !== [] ? $interactiveArabicScores : $interactiveArabicStoredScores;

            if ($effectiveArabicScores !== []) {
                $missingArabic = array_values(array_diff([1, 2], array_keys($effectiveArabicScores)));
                $interactiveArabicMessage = $missingArabic === []
                    ? 'Bahasa Arab 1 dan 2 ditemukan. Nilai sudah terisi otomatis.'
                    : 'Sebagian nilai ditemukan. Bahasa Arab ' . implode(', ', array_keys($effectiveArabicScores)) . ' sudah tersedia, Bahasa Arab ' . implode(', ', $missingArabic) . ' belum ada di arsip.';
            }
        } elseif ($interactiveArabicApplicable) {
            $interactiveArabicMessage = 'Lengkapi NPM terlebih dahulu untuk mengecek nilai Bahasa Arab 1 dan 2.';
        }

        return response()->json([
            'success' => true,
            'applicable' => $legacyApplicable,
            'found' => $record !== null || $storedScore !== null,
            'score' => $record?->score !== null
                ? (int) round((float) $record->score)
                : ($storedScore !== null ? (int) round($storedScore) : null),
            'grade' => $record?->grade,
            'source' => $record !== null
                ? 'legacy_import'
                : ($storedScore !== null ? 'existing_user_manual' : null),
            'message' => ! $legacyApplicable
                ? 'Nilai manual tidak diperlukan untuk kombinasi angkatan dan prodi ini.'
                : (($record !== null || $storedScore !== null)
                ? null
                : 'Nilai Basic Listening belum tersedia di arsip. Jika Anda sudah mengikuti kelas, silakan ke kantor Lembaga Bahasa.'),
            'interactive_class_applicable' => $interactiveApplicable,
            'interactive_class_found' => ($interactiveScores !== [] || $interactiveStoredScores !== []),
            'interactive_class_scores' => $interactiveScores !== [] ? $interactiveScores : $interactiveStoredScores,
            'interactive_class_message' => $interactiveMessage,
            'interactive_arabic_applicable' => $interactiveArabicApplicable,
            'interactive_arabic_found' => ($interactiveArabicScores !== [] || $interactiveArabicStoredScores !== []),
            'interactive_arabic_scores' => $interactiveArabicScores !== [] ? $interactiveArabicScores : $interactiveArabicStoredScores,
            'interactive_arabic_message' => $interactiveArabicMessage,
        ]);
    }
}
