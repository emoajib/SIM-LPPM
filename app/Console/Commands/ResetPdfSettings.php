<?php

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

namespace App\Console\Commands;

use App\Constants\PdfConstants;
use App\Models\Setting;
use Illuminate\Console\Command;

class ResetPdfSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdf:reset-settings
        {--force : Force reset ALL settings including module-specific overrides}
        {--module= : Reset overrides for a specific module only (e.g. surat-tugas)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset PDF and Export styling settings to kanonik/default values';

    /**
     * Get all recognised module keys from config (single source of truth).
     */
    private function getModuleKeys(): array
    {
        return array_column(config('pdf-modules.list', []), 'key');
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->resetBaseSettings();

        $module = $this->option('module');
        $force = $this->option('force');

        if ($module) {
            $this->resetModuleOverrides($module);
        } elseif ($force) {
            if ($this->confirmResetAll()) {
                $this->resetAllModuleOverrides();
            }
        } else {
            $this->line('');
            $this->warn('Tip: Gunakan --module={key} untuk reset modul tertentu, atau --force untuk reset SEMUA module overrides.');
            $this->line('  Contoh: php artisan pdf:reset-settings --module=surat-tugas');
            $this->line('  Contoh: php artisan pdf:reset-settings --force');
        }

        $this->info('✓ PDF settings successfully reset to kanonik/defaults.');

        return 0;
    }

    /**
     * Reset base PDF settings (always runs).
     */
    private function resetBaseSettings(): void
    {
        $this->info('Resetting base PDF settings...');

        Setting::set(PdfConstants::GLOBAL_MARGIN_TOP, '0', 'string');
        Setting::set(PdfConstants::GLOBAL_MARGIN_RIGHT, '2', 'string');
        Setting::set(PdfConstants::GLOBAL_MARGIN_BOTTOM, '0.5', 'string');
        Setting::set(PdfConstants::GLOBAL_MARGIN_LEFT, '2', 'string');
        Setting::set(PdfConstants::GLOBAL_FONT_FAMILY, "'Times New Roman', Times, serif", 'string');
        Setting::set(PdfConstants::GLOBAL_BODY_FONT_SIZE, 11, 'integer');
        Setting::set(PdfConstants::GLOBAL_LINE_HEIGHT, '1', 'string');
        Setting::set(PdfConstants::GLOBAL_PARAGRAPH_SPACING, '6', 'string');
        Setting::set(PdfConstants::GLOBAL_PARAGRAPH_INDENT, '0', 'string');
        Setting::set(PdfConstants::GLOBAL_LOGO_SIZE, '110', 'string');
        Setting::set(PdfConstants::GLOBAL_SHOW_LOGO, true, 'boolean');
        Setting::set(PdfConstants::GLOBAL_PAGE_MARGIN, 'normal', 'string');

        Setting::set(PdfConstants::REPORT_FONT_FAMILY, 'Arial, Helvetica, sans-serif', 'string');
        Setting::set(PdfConstants::REPORT_FONT_SIZE, 9, 'integer');
        Setting::set(PdfConstants::REPORT_LINE_HEIGHT, '1.1', 'string');

        Setting::set(PdfConstants::GLOBAL_PAPER_SIZE, 'a4', 'string');
        Setting::set(PdfConstants::GLOBAL_LAYOUT_COMPACT, false, 'boolean');
        Setting::set(PdfConstants::GLOBAL_LOGO_POSITION, 'left', 'string');
    }

    /**
     * Reset module-specific overrides for a single module.
     */
    private function resetModuleOverrides(string $module): void
    {
        $keys = $this->getModuleKeys();
        if (! in_array($module, $keys, true)) {
            $this->error("Module '{$module}' tidak dikenal. Gunakan salah satu: ".implode(', ', $keys));

            return;
        }

        $this->warn("Resetting overrides for module: {$module}");

        Setting::where('key', 'LIKE', PdfConstants::PREFIX_CONTENT."{$module}_%")->delete();
        Setting::where('key', 'LIKE', PdfConstants::PREFIX_OVERRIDE."{$module}_%")->delete();

        $this->line("  ✓ Overrides for '{$module}' deleted.");
    }

    /**
     * Reset ALL module-specific overrides for all 15 modules.
     */
    private function resetAllModuleOverrides(): void
    {
        $this->warn('Resetting ALL module-specific overrides...');

        $keys = $this->getModuleKeys();
        foreach ($keys as $module) {
            Setting::where('key', 'LIKE', PdfConstants::PREFIX_CONTENT."{$module}_%")->delete();
            Setting::where('key', 'LIKE', PdfConstants::PREFIX_OVERRIDE."{$module}_%")->delete();
        }

        $this->line('  ✓ All module-specific overrides deleted ('.count($keys).' modules).');
    }

    /**
     * Prompt for confirmation before destroying all overrides.
     */
    private function confirmResetAll(): bool
    {
        $keys = $this->getModuleKeys();
        $count = count($keys);
        $overridesCount = $count * 9; // 9 override keys per module

        $this->line('');
        $this->warn("⚠️  PERINGATAN: Ini akan menghapus {$overridesCount} module-specific overrides dari {$count} modul!");
        $this->warn('   Semua kustomisasi font, margin, ukuran kertas, intro/outro per-modul akan hilang.');
        $this->line('');

        return $this->confirm('Lanjutkan reset semua module overrides?', false);
    }
}
