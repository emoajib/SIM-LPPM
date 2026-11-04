// Vetted by AI - Manual Review Required by Senior Engineer/Manager
import ModuleSubpageIndex from "./components/ModuleSubpageIndex.jsx";

export default function PengabdianDailyNoteIndex() {
    return (
        <ModuleSubpageIndex
            module="communityService"
            submodule="daily-note"
            breadcrumbTitle="Realisasi Anggaran Pengabdian Masyarakat"
            searchPlaceholder="Cari catatan harian / realisasi anggaran pengabdian"
            searchAriaLabel="Cari catatan harian / realisasi anggaran pengabdian"
            emptyIcon="ti ti-receipt"
            emptyTitle="Belum ada catatan realisasi anggaran"
            emptyDescription="Belum ada catatan harian kegiatan atau belanja dana pengabdian."
            ariaLabel="Daftar Realisasi Anggaran Pengabdian Masyarakat"
            isRealisasi
        />
    );
}
