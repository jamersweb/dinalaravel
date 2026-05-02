<?php

namespace App\Console\Commands;

use App\Services\FlutterArbExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ExportTopSpokenArbFiles extends Command
{
    protected $signature = 'l10n:export-top-arbs
        {--output= : Output directory for .arb files (default: flutter_l10n_dir)}
        {--zip= : Also create a zip at this path (default: storage/app/l10n_top20_spoken.zip)}';

    protected $description = 'Write app_<locale>.arb for top spoken locales (EN + DB overrides; native language label).';

    public function handle(FlutterArbExportService $arb): int
    {
        $locales = config('localization.top_spoken_arb_locales', []);
        if (! is_array($locales) || $locales === []) {
            $this->error('config localization.top_spoken_arb_locales is empty.');

            return self::FAILURE;
        }

        if ($arb->loadTemplateStrings() === []) {
            $this->error('English template ARB missing or unreadable. Set FLUTTER_EN_ARB_PATH.');

            return self::FAILURE;
        }

        $outDir = $this->option('output');
        if (! is_string($outDir) || $outDir === '') {
            $outDir = config('localization.flutter_l10n_dir');
        }
        if (! is_string($outDir) || $outDir === '') {
            $this->error('No output directory.');

            return self::FAILURE;
        }

        if (! is_dir($outDir)) {
            $this->error('Output directory does not exist: '.$outDir);

            return self::FAILURE;
        }

        $written = [];
        foreach ($locales as $code => $label) {
            $code = strtolower(trim((string) $code));
            $merged = $arb->mergedForLocale($code, (string) $label);
            if ($merged === []) {
                $this->warn("Skipped {$code} (empty merge).");

                continue;
            }
            $path = rtrim($outDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'app_'.$code.'.arb';
            File::put($path, $arb->encodeArb($merged));
            $written[] = basename($path);
            $this->line($path);
        }

        $this->info('Wrote '.count($written).' ARB files.');

        $zipOpt = $this->option('zip');
        $zipPath = is_string($zipOpt) && $zipOpt !== ''
            ? $zipOpt
            : storage_path('framework/l10n_top20_spoken.zip');

        if (! class_exists(ZipArchive::class)) {
            $this->warn('php-zip (ZipArchive) not available; skipping zip.');

            return self::SUCCESS;
        }

        if (is_file($zipPath)) {
            @unlink($zipPath);
        }

        $zip = new ZipArchive;
        $open = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($open !== true) {
            $this->warn('Could not create zip ('.(string) $open.'): '.$zipPath.' — ARB files on disk are still valid.');

            return self::SUCCESS;
        }

        foreach ($locales as $code => $_) {
            $code = strtolower(trim((string) $code));
            $file = 'app_'.$code.'.arb';
            $full = rtrim($outDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file;
            if (is_readable($full)) {
                $body = file_get_contents($full);
                if ($body !== false) {
                    $zip->addFromString($file, $body);
                }
            }
        }
        $zip->close();
        $this->info('Zip: '.$zipPath);

        return self::SUCCESS;
    }
}
