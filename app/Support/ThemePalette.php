<?php

namespace App\Support;

/**
 * Membuat skala warna 50–900 dari satu warna hex supaya seluruh utilitas Tailwind
 * (bg-brand-50, text-brand-700, dst.) ikut berubah ketika tema dipakai.
 * Warna tombol (600) & teks tautan (700) otomatis digelapkan bila kontras
 * terhadap putih terlalu rendah, supaya tetap terbaca (WCAG ≥ 4,5:1).
 */
class ThemePalette
{
    /** jarak campuran: negatif = dicampur putih, positif = dicampur hitam */
    private const STEPS = [50 => -0.94, 100 => -0.86, 200 => -0.72, 300 => -0.52, 400 => -0.28, 500 => 0, 600 => 0.14, 700 => 0.30, 800 => 0.46, 900 => 0.62];

    public const DEFAULT = ['#7c3aed', '#db2777', '#06b6d4'];

    public static function valid(?string $hex): bool
    {
        return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', (string) $hex);
    }

    /** @return array<string,string> */
    public static function variables(string $primary, string $secondary, ?string $accent = null): array
    {
        $vars = [];
        foreach (['brand' => $primary, 'accent' => $secondary, 'pop' => $accent ?: self::DEFAULT[2]] as $name => $hex) {
            foreach (self::scale($hex) as $step => $color) {
                $vars["--color-{$name}-{$step}"] = $color;
            }
        }

        return $vars;
    }

    /** @return array<int,string> */
    public static function scale(string $hex): array
    {
        $base = self::valid($hex) ? self::rgb($hex) : self::rgb(self::DEFAULT[0]);
        $out = [];
        foreach (self::STEPS as $step => $mix) {
            $out[$step] = self::hex(self::mix($base, $mix));
        }

        // kontras: 600 dipakai sebagai latar tombol (teks putih), 700 sebagai warna teks tautan
        foreach ([600 => 4.5, 700 => 5.5, 800 => 7] as $step => $min) {
            $mix = self::STEPS[$step];
            while (self::contrast(self::rgb($out[$step]), [255, 255, 255]) < $min && $mix < 0.9) {
                $mix += 0.06;
                $out[$step] = self::hex(self::mix($base, $mix));
            }
        }

        return $out;
    }

    public static function contrastWithWhite(string $hex): float
    {
        return round(self::contrast(self::rgb($hex), [255, 255, 255]), 2);
    }

    private static function mix(array $rgb, float $amount): array
    {
        $target = $amount < 0 ? 255 : 0;
        $t = abs($amount);

        return array_map(fn ($c) => (int) round($c + ($target - $c) * $t), $rgb);
    }

    private static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private static function hex(array $rgb): string
    {
        return sprintf('#%02x%02x%02x', ...array_map(fn ($c) => max(0, min(255, $c)), $rgb));
    }

    private static function luminance(array $rgb): float
    {
        $c = array_map(function ($v) {
            $v /= 255;

            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, $rgb);

        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }

    private static function contrast(array $a, array $b): float
    {
        $l1 = self::luminance($a);
        $l2 = self::luminance($b);

        return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
    }
}