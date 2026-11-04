// Vetted by AI - Manual Review Required by Senior Engineer/Manager
import ModuleSubpageIndex from "./components/ModuleSubpageIndex.jsx";

export default function ResearchRevisionIndex() {
    return (
        <ModuleSubpageIndex
            module="research"
            submodule="revision"
            breadcrumbTitle="Perbaikan Usulan Penelitian"
            searchPlaceholder="Cari perbaikan usulan berdasarkan judul"
            searchAriaLabel="Cari perbaikan usulan berdasarkan judul"
            emptyIcon="ti ti-clipboard-check"
            emptyTitle="Tidak ada usulan perlu perbaikan"
            emptyDescription="Semua usulan penelitian telah selesai atau tidak memerlukan revisi lanjutan."
            ariaLabel="Daftar Perbaikan Usulan Penelitian"
        />
    );
}
