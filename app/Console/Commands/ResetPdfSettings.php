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
    protected $signature = 'pdf:reset-settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset all PDF and Export styling settings to their kanonik/default values';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Resetting PDF settings...');

        Setting::set('pdf_font_family', 'Times New Roman, Times, serif', 'string');
        Setting::set('pdf_body_font_size', 11, 'integer');
        Setting::set('pdf_layout_compact', false, 'boolean');
        Setting::set('pdf_show_logo', true, 'boolean');
        Setting::set('pdf_page_margin', 'normal', 'string');

        Setting::set('pdf_report_font_family', 'Arial, Helvetica, sans-serif', 'string');
        Setting::set('pdf_report_font_size', 9, 'integer');

        $this->info('✓ PDF settings successfully reset to kanonik/defaults.');

        return 0;
    }
}
