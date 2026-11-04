// Vetted by AI - Manual Review Required by Senior Engineer/Manager
import ModuleSubpageIndex from "./components/ModuleSubpageIndex.jsx";

export default function PengabdianRevisionIndex() {
    return (
        <ModuleSubpageIndex
            module="communityService"
            submodule="revision"
            breadcrumbTitle="Perbaikan Usulan Pengabdian Masyarakat"
            searchPlaceholder="Cari perbaikan usulan berdasarkan judul"
            searchAriaLabel="Cari perbaikan usulan berdasarkan judul"
            emptyIcon="ti ti-clipboard-check"
            emptyTitle="Tidak ada usulan perlu perbaikan"
            emptyDescription="Semua usulan pengabdian masyarakat telah selesai atau tidak memerlukan revisi lanjutan."
            ariaLabel="Daftar Perbaikan Usulan Pengabdian Masyarakat"
        />
    );
}
