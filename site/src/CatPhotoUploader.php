<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

/**
 * Resizes an uploaded cat photo and re-encodes it to WebP -- the same
 * pipeline applied by hand to the seed images (see assets/images/*.webp),
 * now run automatically for photos that come in through the Telegram bot.
 * Keeps api/cats.php focused on HTTP concerns rather than GD calls.
 */
final class CatPhotoUploader
{
    private const MAX_BYTES = 8 * 1024 * 1024; // 8 MB upload cap
    private const MAX_WIDTH = 1000;
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(private readonly string $uploadDir)
    {
    }

    /**
     * @param array{tmp_name: string, size: int, error: int} $file One entry of $_FILES
     * @return string Relative path (from the site root) to store in cats.photo_path
     */
    public function store(array $file, string $slug): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Файл не завантажено (код помилки ' . $file['error'] . ').');
        }

        if ($file['size'] > self::MAX_BYTES) {
            throw new RuntimeException('Файл занадто великий (максимум 8 МБ).');
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            throw new RuntimeException('Непідтримуваний формат файлу. Дозволені: JPEG, PNG, WebP.');
        }

        $source = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
            'image/png' => imagecreatefrompng($file['tmp_name']),
            'image/webp' => imagecreatefromwebp($file['tmp_name']),
        };

        if ($source === false) {
            throw new RuntimeException('Не вдалося обробити зображення.');
        }

        $width = imagesx($source);
        $height = imagesy($source);

        if ($width > self::MAX_WIDTH) {
            $newWidth = self::MAX_WIDTH;
            $newHeight = (int) round($height * ($newWidth / $width));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($source);
            $source = $resized;
        }

        if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0755, true) && !is_dir($this->uploadDir)) {
            throw new RuntimeException('Не вдалося створити директорію для завантажень.');
        }

        $filename = $slug . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.webp';
        $fullPath = rtrim($this->uploadDir, '/') . '/' . $filename;

        if (!imagewebp($source, $fullPath, 82)) {
            imagedestroy($source);
            throw new RuntimeException('Не вдалося зберегти WebP-файл.');
        }

        imagedestroy($source);

        return 'assets/images/cats/' . $filename;
    }
}
