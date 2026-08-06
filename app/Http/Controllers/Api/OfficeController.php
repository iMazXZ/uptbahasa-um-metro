<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Office;

class OfficeController extends Controller
{
    /**
     * Daftar semua office yang aktif.
     */
    public function index()
    {
        $offices = Office::active()
            ->select(['id', 'name', 'address', 'latitude', 'longitude', 'radius'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $offices,
        ]);
    }
}
