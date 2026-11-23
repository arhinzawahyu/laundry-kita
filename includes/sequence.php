<?php
declare(strict_types=1);

/* ----------------------------------------------------------------------------
   4. Hero sequence auto discovery. Duplicate encodings of one frame are
      collapsed by frame number, WebP wins because it is roughly 15x smaller.
   ------------------------------------------------------------------------- */
$sequenceDir = $projectRoot . '/' . trim((string) $siteConfig['sequence_folder'], '/');
$sequenceUrl = trim((string) $siteConfig['sequence_folder'], '/');
$frames      = [];

if (is_dir($sequenceDir)) {
    $priority = ['webp' => 0, 'jpg' => 1, 'jpeg' => 1, 'png' => 2];
    $picked   = [];

    foreach ($priority as $ext => $rank) {
        $matches = glob($sequenceDir . '/*.' . $ext) ?: [];
        foreach ($matches as $path) {
            if (!is_file($path)) {
                continue;
            }
            $base = pathinfo($path, PATHINFO_FILENAME);
            if (preg_match('/(\d+)/', $base, $number) === 1) {
                $key = 'f' . str_pad((string) ((int) $number[1]), 6, '0', STR_PAD_LEFT);
            } else {
                $key = 'n' . strtolower($base);
            }
            if (!isset($picked[$key]) || $rank < $picked[$key]['rank']) {
                $picked[$key] = [
                    'rank' => $rank,
                    'file' => $base . '.' . $ext,
                ];
            }
        }
    }

    ksort($picked, SORT_NATURAL);
    foreach ($picked as $item) {
        $frames[] = $sequenceUrl . '/' . rawurlencode($item['file']);
        if (count($frames) >= (int) $siteConfig['sequence_limit']) {
            break;
        }
    }
}

$firstFrame = $frames[0] ?? null;
