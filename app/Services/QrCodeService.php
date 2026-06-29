<?php

namespace App\Services;

use App\Models\Playlist;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

class QrCodeService
{
    /**
     * Generate (or regenerate) the QR PNG for a playlist's public URL,
     * store it on the public disk, and persist the path.
     *
     * Uses endroid/qr-code with the GD-backed PngWriter — GD is present on
     * Hostinger shared hosting, unlike imagick (which simple-qrcode needs for PNG).
     */
    public function generate(Playlist $playlist): string
    {
        $url = route('audio.show', $playlist);

        $qr = new QrCode(
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 600,
            margin: 16,
            foregroundColor: new Color(32, 54, 28),   // brand green #20361c
            backgroundColor: new Color(255, 255, 255),
        );

        $result = (new PngWriter())->write($qr);

        $path = "qrcodes/{$playlist->slug}.png";
        Storage::disk('public')->put($path, $result->getString());

        $playlist->update(['qr_path' => $path]);

        return $path;
    }
}
