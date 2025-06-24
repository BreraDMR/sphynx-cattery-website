<?php

declare(strict_types=1);

namespace App;

require_once __DIR__ . '/../config/env.php';

/**
 * Best-effort push to sphynx-cats-crm-bot when a new request comes in
 * through the public contact form -- the bot's notify endpoint forwards it
 * to the owner + every approved admin in Telegram, so a request doesn't
 * only sit in admin_requests.php waiting to be noticed.
 *
 * Deliberately fire-and-forget: NOTIFY_URL is optional (no-op if unset),
 * and any failure (timeout, bot down, network) is swallowed -- a visitor
 * submitting the public contact form must never see an error because the
 * bot happened to be offline.
 */
final class BotNotifier
{
    private const TIMEOUT_SECONDS = 3;

    public static function notifyNewRequest(RequestRecord $request): void
    {
        $url = env('NOTIFY_URL');
        $apiKey = env('BOT_API_KEY');

        if (!$url || !$apiKey) {
            return;
        }

        $payload = json_encode([
            'id' => $request->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'age' => $request->age,
            'color' => $request->color,
            'message' => $request->message,
            'created_at' => $request->createdAt,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-API-Key: ' . $apiKey],
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        @curl_exec($ch);
        curl_close($ch);
    }
}
