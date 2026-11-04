<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Keys are camelCase on purpose: they match the Angular Settings page `Prefs` type. */
class SettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'theme' => $this->theme,
            'density' => $this->density,
            'desktopNotifications' => $this->desktop_notifications,
            'messagePreviews' => $this->message_previews,
            'whoCanMessage' => $this->who_can_message,
            'readReceipts' => $this->read_receipts,
            'showOnline' => $this->show_online_status,
        ];
    }
}
