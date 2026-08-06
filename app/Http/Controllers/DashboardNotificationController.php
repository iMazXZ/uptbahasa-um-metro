<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardNotificationController extends Controller
{
    public function open(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        if ($record->read_at === null) {
            $record->markAsRead();
        }

        $targetUrl = data_get($record->data, 'url');

        return redirect()->to(
            filled($targetUrl) ? $targetUrl : route('dashboard.pendaftar')
        );
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        return back();
    }

    public function destroy(Request $request, string $notification): RedirectResponse
    {
        $request->user()
            ->notifications()
            ->whereKey($notification)
            ->delete();

        return back();
    }

    public function destroyRead(Request $request): RedirectResponse
    {
        $request->user()
            ->readNotifications()
            ->delete();

        return back();
    }
}
