<?php

namespace App\Notifications;

use App\Models\Decree;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DecreeStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Decree $decree,
        public readonly string $fromStatus,
        public readonly string $toStatus,
        public readonly ?string $notes = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'decree_id' => $this->decree->id,
            'decree_number' => $this->decree->decree_number,
            'employee_name' => $this->decree->employee?->name,
            'from_status' => $this->fromStatus,
            'to_status' => $this->toStatus,
            'notes' => $this->notes,
            'url' => route('admin.decrees.show', $this->decree),
        ];
    }
}
