<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PermintaanPembelianNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $permintaanPembelianId,
        private readonly string $nomorPermintaan,
        private readonly string $event,
        private readonly string $message,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'permintaan_pembelian',
            'event' => $this->event,
            'permintaan_pembelian_id' => $this->permintaanPembelianId,
            'nomor_permintaan' => $this->nomorPermintaan,
            'message' => $this->message,
            'url' => route('permintaan-pembelian.show', $this->permintaanPembelianId),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
