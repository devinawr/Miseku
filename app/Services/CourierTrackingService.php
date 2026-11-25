<?php

namespace App\Services;

use App\Models\CustomerOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Observasi kurir; tidak pernah menganggap paket tiba karena lama dikirim. */
class CourierTrackingService
{
    public function check(CustomerOrder $order): bool
    {
        if (! config('services.rajaongkir.tracking_enabled') || ! filled(config('services.rajaongkir.key'))
            || $order->order_status !== 'shipped' || $order->shipping_method !== 'courier' || $order->delivered_at) return false;
        $order->loadMissing('address');
        $recipient = trim((string) $order->address?->recipient_name);
        if (! $order->address_id || $recipient === '') return false;
        $awb = trim((string) $order->tracking_number);
        $label = trim((string) $order->courier);
        $name = mb_strtolower($label);
        $courier = match (true) {
            str_starts_with($name, 'pos') => 'pos',
            str_starts_with($name, 'tiki') => 'tiki',
            str_starts_with($name, 'sicepat') => 'sicepat',
            str_starts_with($name, 'j&t'), str_starts_with($name, 'jnt') => 'jnt',
            // JNE memerlukan 5 digit nomor penerima. Data alamat sekarang belum menyimpannya.
            default => null,
        };
        $base = rtrim((string) config('services.rajaongkir.base_url'), '/');
        if (! $courier || $awb === '' || ! str_starts_with($base, 'https://')) return false;
        try {
            $url = $base . '/track/waybill?' . http_build_query(['awb' => $awb, 'courier' => $courier]);
            $response = Http::withHeaders(['key' => config('services.rajaongkir.key')])->acceptJson()->timeout(10)->post($url);
            if (! $response->successful() || $response->json('data.delivered') !== true
                || strcasecmp(trim((string) $response->json('data.summary.waybill_number')), $awb) !== 0
                || strcasecmp(trim((string) $response->json('data.summary.courier_code')), $courier) !== 0
                || mb_strtolower(trim((string) $response->json('data.summary.receiver_name'))) !== mb_strtolower($recipient)) return false;
            return app(OrderService::class)->markDeliveredFromTracking($order->id, $awb, $label, $order->address_id, $recipient);
        } catch (\Throwable $e) {
            // Jangan catat resi, alamat, nomor telepon atau API key di log.
            Log::warning('Pengecekan status kurir gagal', ['order_id' => $order->id]);
            return false;
        }
    }
}
