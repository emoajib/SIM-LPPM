<?php

namespace App\Http\Controllers;

use App\Exports\SintaCommunityServiceExport;
use App\Exports\SintaResearchExport;
use App\Support\AcademicPeriod;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SintaExportController extends Controller
{
    public function downloadResearch(Request $request)
    {
        try {
            $year = (string) $request->query('year', AcademicPeriod::current());
            $filename = 'SINTA_Penelitian_'.str_replace('/', '-', $year).'_'.now()->format('YmdHis').'.xlsx';

            while (ob_get_level() > 0) {
                @ob_end_clean();

            }

            return Excel::download(new SintaResearchExport($year), $filename);
        } catch (\Exception $e) {
            \Log::error('SINTA Research Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh Excel SINTA Penelitian: '.$e->getMessage());
        }
    }

    public function downloadPkm(Request $request)
    {
        try {
            $year = (string) $request->query('year', AcademicPeriod::current());
            $filename = 'SINTA_PengabdianMasyarakat_'.str_replace('/', '-', $year).'_'.now()->format('YmdHis').'.xlsx';

            while (ob_get_level() > 0) {
                @ob_end_clean();

            }

            return Excel::download(new SintaCommunityServiceExport($year), $filename);
        } catch (\Exception $e) {
            \Log::error('SINTA PKM Export Error: '.$e->getMessage());

            return back()->with('error', 'Gagal mengunduh Excel SINTA PKM: '.$e->getMessage());
        }
    }
}
