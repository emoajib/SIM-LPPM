// Vetted by AI - Manual Review Required by Senior Engineer/Manager
import ModuleSubpageIndex from "./components/ModuleSubpageIndex.jsx";

export default function ResearchDailyNoteIndex() {
    return (
        <ModuleSubpageIndex
            module="research"
            submodule="daily-note"
            breadcrumbTitle="Realisasi Anggaran Penelitian"
            searchPlaceholder="Cari catatan harian / realisasi anggaran penelitian"
            searchAriaLabel="Cari catatan harian / realisasi anggaran penelitian"
            emptyIcon="ti ti-receipt"
            emptyTitle="Belum ada catatan realisasi anggaran"
            emptyDescription="Belum ada catatan harian kegiatan atau belanja dana riset."
            ariaLabel="Daftar Realisasi Anggaran Penelitian"
            isRealisasi
        />
    );
}
