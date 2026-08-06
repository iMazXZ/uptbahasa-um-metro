<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Office;
use App\Support\ImageTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Status absensi hari ini.
     */
    public function today(Request $request)
    {
        $today = Carbon::today();
        
        $attendance = Attendance::with('office:id,name,address')
            ->where('user_id', $request->user()->id)
            ->whereDate('clock_in', $today)
            ->first();

        if (! $attendance) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Belum absen hari ini',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $attendance->id,
                'office_id' => $attendance->office_id,
                'office' => $attendance->office,
                'clock_in' => $attendance->clock_in->toIso8601String(),
                'clock_out' => $attendance->clock_out?->toIso8601String(),
                'clock_in_lat' => $attendance->clock_in_lat,
                'clock_in_long' => $attendance->clock_in_long,
                'clock_in_photo' => $attendance->clock_in_photo ? url(Storage::url($attendance->clock_in_photo)) : null,
                'clock_out_lat' => $attendance->clock_out_lat,
                'clock_out_long' => $attendance->clock_out_long,
                'clock_out_photo' => $attendance->clock_out_photo ? url(Storage::url($attendance->clock_out_photo)) : null,
                'created_at' => $attendance->created_at->toIso8601String(),
                'updated_at' => $attendance->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Clock in.
     */
    public function clockIn(Request $request)
    {
        $request->validate([
            'office_id' => 'required|exists:offices,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'photo' => 'required|image|max:5120', // max 5MB
        ]);

        $user = $request->user();
        $today = Carbon::today();

        // Cek apakah sudah absen hari ini
        $existing = Attendance::where('user_id', $user->id)
            ->whereDate('clock_in', $today)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melakukan clock in hari ini',
            ], 422);
        }

        // Cek office aktif
        $office = Office::active()->find($request->office_id);
        if (! $office) {
            return response()->json([
                'success' => false,
                'message' => 'Lokasi tidak valid atau tidak aktif',
            ], 422);
        }

        // Validasi jarak
        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $office->latitude,
            $office->longitude
        );

        if ($distance > $office->radius) {
            return response()->json([
                'success' => false,
                'message' => "Anda berada di luar jangkauan lokasi absensi ({$distance}m dari lokasi, maksimal {$office->radius}m)",
                'distance' => $distance,
                'max_radius' => $office->radius,
            ], 422);
        }

        // Simpan foto dengan konversi ke WebP (ukuran lebih kecil)
        $photoResult = ImageTransformer::toWebpFromUploaded(
            $request->file('photo'),
            'public',
            'attendance/clock-in',
            quality: 75,
            maxWidth: 800,
        );
        $photoPath = $photoResult['path'];

        // Buat attendance record
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'office_id' => $office->id,
            'clock_in' => now(),
            'clock_in_lat' => $request->latitude,
            'clock_in_long' => $request->longitude,
            'clock_in_photo' => $photoPath,
        ]);

        $attendance->load('office:id,name,address');

        return response()->json([
            'success' => true,
            'message' => 'Clock in berhasil',
            'data' => [
                'id' => $attendance->id,
                'office' => $attendance->office,
                'clock_in' => $attendance->clock_in->toIso8601String(),
                'clock_in_photo' => url(Storage::url($attendance->clock_in_photo)),
            ],
        ]);
    }

    /**
     * Clock out.
     */
    public function clockOut(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'photo' => 'required|image|max:5120', // max 5MB
            'notes' => 'nullable|string|max:2000', // catatan aktivitas hari ini
        ]);

        $user = $request->user();
        $today = Carbon::today();

        // Cari attendance hari ini yang belum clock out
        $attendance = Attendance::with('office')
            ->where('user_id', $user->id)
            ->whereDate('clock_in', $today)
            ->whereNull('clock_out')
            ->first();

        if (! $attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada clock in yang perlu di-checkout atau sudah clock out',
            ], 422);
        }

        // Validasi jarak (gunakan office yang sama dengan clock in)
        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $attendance->office->latitude,
            $attendance->office->longitude
        );

        if ($distance > $attendance->office->radius) {
            return response()->json([
                'success' => false,
                'message' => "Anda berada di luar jangkauan lokasi absensi ({$distance}m dari lokasi, maksimal {$attendance->office->radius}m)",
                'distance' => $distance,
                'max_radius' => $attendance->office->radius,
            ], 422);
        }

        // Simpan foto dengan konversi ke WebP (ukuran lebih kecil)
        $photoResult = ImageTransformer::toWebpFromUploaded(
            $request->file('photo'),
            'public',
            'attendance/clock-out',
            quality: 75,
            maxWidth: 800,
        );
        $photoPath = $photoResult['path'];

        // Update attendance
        $attendance->update([
            'clock_out' => now(),
            'clock_out_lat' => $request->latitude,
            'clock_out_long' => $request->longitude,
            'clock_out_photo' => $photoPath,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Clock out berhasil',
            'data' => [
                'id' => $attendance->id,
                'clock_in' => $attendance->clock_in->toIso8601String(),
                'clock_out' => $attendance->clock_out->toIso8601String(),
                'work_duration' => $attendance->work_duration_formatted,
                'clock_out_photo' => url(Storage::url($attendance->clock_out_photo)),
                'notes' => $attendance->notes,
            ],
        ]);
    }

    /**
     * Update catatan absensi.
     */
    public function updateNotes(Request $request, $id)
    {
        $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        $attendance = Attendance::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $attendance->update([
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Catatan berhasil diperbarui',
            'data' => [
                'id' => $attendance->id,
                'notes' => $attendance->notes,
            ],
        ]);
    }

    /**
     * Riwayat absensi (paginated).
     */
    public function history(Request $request)
    {
        $perPage = min($request->input('per_page', 15), 50); // max 50

        $query = Attendance::with('office:id,name')
            ->where('user_id', $request->user()->id);

        if ($request->has('month')) {
            $query->whereMonth('clock_in', $request->month);
        }

        if ($request->has('year')) {
            $query->whereYear('clock_in', $request->year);
        }

        $attendances = $query->orderByDesc('clock_in')
            ->paginate($perPage);

        $data = $attendances->through(function ($attendance) {
            return [
                'id' => $attendance->id,
                'office' => $attendance->office,
                'clock_in' => $attendance->clock_in->toIso8601String(),
                'clock_out' => $attendance->clock_out?->toIso8601String(),
                'clock_in_lat' => $attendance->clock_in_lat,
                'clock_in_long' => $attendance->clock_in_long,
                'clock_in_photo' => $attendance->clock_in_photo ? url(Storage::url($attendance->clock_in_photo)) : null,
                'clock_out_lat' => $attendance->clock_out_lat,
                'clock_out_long' => $attendance->clock_out_long,
                'clock_out_photo' => $attendance->clock_out_photo ? url(Storage::url($attendance->clock_out_photo)) : null,
                'work_duration' => $attendance->hasClockOut() ? $attendance->work_duration_formatted : null,
                'notes' => $attendance->notes,
                'created_at' => $attendance->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data->items(),
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
            ],
        ]);
    }

    /**
     * Hitung jarak antara dua koordinat dalam meter (Haversine formula).
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meter

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon / 2) * sin($deltaLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c);
    }
}
