// Vetted by SOSIOMEN - Manual Review Required by Senior Engineer/Manager
import React, { useEffect, useState } from "react";

export default function SettingsIndex() {
    const [sections, setSections] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        let alive = true;
        (async () => {
            try {
                const res = await fetch("/api/v1/settings/hub", {
                    credentials: "same-origin",
                    cache: "no-store",
                    headers: { Accept: "application/json" },
                });
                if (!res.ok) throw new Error("Gagal memuat daftar pengaturan.");
                const data = await res.json();
                if (alive) setSections(data.sections || []);
            } catch (e) {
                if (alive) setError(e.message);
            } finally {
                if (alive) setLoading(false);
            }
        })();
        return () => { alive = false; };
    }, []);

    if (loading) {
        return <div className="text-center text-secondary py-4">Memuat…</div>;
    }

    const iconFor = (icon) =>
        icon === "user" ? "user" : icon === "lock" ? "lock" : icon === "shield-lock" ? "shield-lock"
        : icon === "shield-check" ? "circle-check" : icon === "shield" ? "shield"
        : icon === "bell" ? "bell" : icon === "toggle-right" ? "toggle-right"
        : icon === "palette" ? "palette" : icon === "file-type-pdf" ? "file-type"
        : icon === "chart-histogram" ? "chart-histogram" : icon === "list-details" ? "list-details"
        : icon === "refresh" ? "refresh" : icon === "database-export" ? "database-export"
        : icon === "database-import" ? "database-import" : icon === "folders" ? "folders"
        : icon === "calendar-event" ? "calendar-event" : icon === "file-text" ? "file-text"
        : icon === "file-pencil" ? "file-pencil" : icon === "book-2" ? "book-2"
        : "settings";

    return (
        <div>
            <div className="mb-3">
                <h1 className="page-title mb-1">Pusat Pengaturan</h1>
                <div className="text-secondary">Kelola akun, sistem, dan master data aplikasi.</div>
            </div>

            {error && (
                <div className="alert alert-danger py-2 px-3" role="alert">
                    {error}
                </div>
            )}

            <div className="card">
                <div className="row g-0">
                    <div className="col-12 col-md-3 settings-sidebar">
                        <div className="card-body">
                            {sections.map((section) => (
                                <div key={section.title} className="mb-3">
                                    <h4 className="subheader mb-2">{section.title}</h4>
                                    <div className="list-group list-group-transparent">
                                        {section.items.map((item) => (
                                            <a
                                                key={item.key}
                                                href={item.href}
                                                className="list-group-item list-group-item-action d-flex align-items-center gap-2"
                                            >
                                                <i className={`ti ti-${iconFor(item.icon)} me-1`} aria-hidden="true"></i>
                                                <span className="flex-fill">{item.title}</span>
                                                {!item.modern && (
                                                    <span className="badge bg-secondary-lt ms-auto">Klasik</span>
                                                )}
                                            </a>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="col-12 col-md-9 ps-md-0">
                        <div className="card-body">
                            <h3 className="card-title mb-2">Selamat datang di Pusat Pengaturan</h3>
                            <p className="text-secondary mb-0">
                                Pilih pengaturan di sidebar untuk mengelola akun, sistem, dan master data aplikasi.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {sections.length === 0 && !error && (
                <div className="card">
                    <div className="card-body text-center text-secondary">
                        Tidak ada pengaturan yang tersedia untuk akun Anda.
                    </div>
                </div>
            )}
        </div>
    );
}