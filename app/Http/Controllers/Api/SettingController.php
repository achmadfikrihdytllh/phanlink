<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SettingResource;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(protected SettingService $settingService)
    {
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'settings' => new SettingResource($this->settingService->get($request->user())),
        ]);
    }

    /**
     * Accepts the same camelCase keys the Angular Settings page uses, so the
     * frontend can send its `Prefs` object as-is.
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'theme' => ['sometimes', 'in:phantom,midnight,paper'],
            'density' => ['sometimes', 'in:comfortable,compact'],
            'desktopNotifications' => ['sometimes', 'boolean'],
            'messagePreviews' => ['sometimes', 'boolean'],
            'whoCanMessage' => ['sometimes', 'in:contacts,everyone'],
            'readReceipts' => ['sometimes', 'boolean'],
            'showOnline' => ['sometimes', 'boolean'],
        ]);

        $columns = [
            'theme' => 'theme',
            'density' => 'density',
            'desktopNotifications' => 'desktop_notifications',
            'messagePreviews' => 'message_previews',
            'whoCanMessage' => 'who_can_message',
            'readReceipts' => 'read_receipts',
            'showOnline' => 'show_online_status',
        ];

        $mapped = [];
        foreach ($data as $key => $value) {
            $mapped[$columns[$key]] = $value;
        }

        return response()->json([
            'message' => 'Pengaturan disimpan.',
            'settings' => new SettingResource($this->settingService->update($request->user(), $mapped)),
        ]);
    }
}
