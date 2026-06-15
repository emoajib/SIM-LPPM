<?php

// Vetted by AI - Manual Review Required by Senior Engineer/Manager

namespace App\Console\Commands;

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
     * All recognised module keys for module-specific overrides.
     */
    private const MODULE_KEYS = [
        'surat-tugas', 'surat-keterangan', 'surat-izin',
        'proposal-export', 'laporan-kemajuan', 'logbook',
        'evaluasi-reviewer', 'iku', 'penelitian', 'pengabdian',
        'output', 'mitra', 'monev-ba', 'monev', 'reviewer',
    ];

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

        Setting::set('pdf_margin_top', '0', 'string');
        Setting::set('pdf_margin_right', '2', 'string');
        Setting::set('pdf_margin_bottom', '0.5', 'string');
        Setting::set('pdf_margin_left', '2', 'string');
        Setting::set('pdf_font_family', "'Times New Roman', Times, serif", 'string');
        Setting::set('pdf_body_font_size', 11, 'integer');
        Setting::set('pdf_line_height', '1', 'string');
        Setting::set('pdf_paragraph_spacing', '6', 'string');
        Setting::set('pdf_paragraph_indent', '0', 'string');
        Setting::set('pdf_logo_size', '110', 'string');
        Setting::set('pdf_show_logo', true, 'boolean');
        Setting::set('pdf_page_margin', 'normal', 'string');

        Setting::set('pdf_report_font_family', 'Arial, Helvetica, sans-serif', 'string');
        Setting::set('pdf_report_font_size', 9, 'integer');
        Setting::set('pdf_report_line_height', '1.1', 'string');

        Setting::set('pdf_paper_size', 'a4', 'string');
        Setting::set('pdf_layout_compact', false, 'boolean');
        Setting::set('pdf_logo_position', 'left', 'string');
    }

    /**
     * Reset module-specific overrides for a single module.
     */
    private function resetModuleOverrides(string $module): void
    {
        if (! in_array($module, self::MODULE_KEYS, true)) {
            $this->error("Module '{$module}' tidak dikenal. Gunakan salah satu: ".implode(', ', self::MODULE_KEYS));

            return;
        }

        $this->warn("Resetting overrides for module: {$module}");

        Setting::where('key', 'LIKE', "pdf_content_{$module}_%")->delete();
        Setting::where('key', 'LIKE', "pdf_override_{$module}_%")->delete();

        $this->line("  ✓ Overrides for '{$module}' deleted.");
    }

    /**
     * Reset ALL module-specific overrides for all 15 modules.
     */
    private function resetAllModuleOverrides(): void
    {
        $this->warn('Resetting ALL module-specific overrides...');

        foreach (self::MODULE_KEYS as $module) {
            Setting::where('key', 'LIKE', "pdf_content_{$module}_%")->delete();
            Setting::where('key', 'LIKE', "pdf_override_{$module}_%")->delete();
        }

        $this->line('  ✓ All module-specific overrides deleted ('.count(self::MODULE_KEYS).' modules).');
    }

    /**
     * Prompt for confirmation before destroying all overrides.
     */
    private function confirmResetAll(): bool
    {
        $count = count(self::MODULE_KEYS);
        $overridesCount = $count * 9; // 9 override keys per module

        $this->line('');
        $this->warn("⚠️  PERINGATAN: Ini akan menghapus {$overridesCount} module-specific overrides dari {$count} modul!");
        $this->warn('   Semua kustomisasi font, margin, ukuran kertas, intro/outro per-modul akan hilang.');
        $this->line('');

        return $this->confirm('Lanjutkan reset semua module overrides?', false);
    }
}
