# Changelog

> Dihasilkan otomatis oleh `php artisan changelog:build` pada 2026-11-16.
> Sumber: conventional commit. Jangan edit manual — jalankan ulang command.

## Fitur

- **observability** [dfb4e8d8](https://github.com/emoajib/SIM-LPPM/commit/dfb4e8d81bb8dadedbdc8a6b51af7fbd4b9656ce) — feat(observability): digest mingguan error/CI/fix tanpa test
- **tooling** [1520c00e](https://github.com/emoajib/SIM-LPPM/commit/1520c00e4f07423df2fae834938f006c2e8ddbfc) — feat(tooling): status kesehatan registry regresi read-only
- **tooling** [bbe2b44e](https://github.com/emoajib/SIM-LPPM/commit/bbe2b44ebb4f84ee49e214c4a9c069fa83b75edb) — feat(tooling): auto changelog dari conventional commit
- **observability** [365dca79](https://github.com/emoajib/SIM-LPPM/commit/365dca794b79ae2895a7976c314890a75860e880) — feat(observability): history:sync, dev:record, logs:prune, dan wiring Sentry
- **observability** [3f8355cc](https://github.com/emoajib/SIM-LPPM/commit/3f8355cc1d17a858555801f27c0a8b17df677dcd) — feat(observability): error_signatures, dev_events, dan bug:capture
- **history** [1f019873](https://github.com/emoajib/SIM-LPPM/commit/1f0198730aaa99b217ff1027704210b7944df54b) — feat(history): codebase:map untuk peta struktur kodebase
- **history** [af80b262](https://github.com/emoajib/SIM-LPPM/commit/af80b2624980fbe0742664934a5d7eb333e26e1a) — feat(history): perintah history:brief untuk konteks area kerja
- **modern** [acd849ca](https://github.com/emoajib/SIM-LPPM/commit/acd849ca811049ef4352b97e6ec8798be1d4d185) — feat(modern): paritas filter sub-modul + status laporan akhir
- [374e5f4b](https://github.com/emoajib/SIM-LPPM/commit/374e5f4bd3bf40ead7b7f80adcd74d44aa2665b9) — feat: module-aware daily-note API + rejection banner (P1e) [BC-03]
- [3181a443](https://github.com/emoajib/SIM-LPPM/commit/3181a443b200adbaabeaa7e0761c41cf7a84c35a) — feat: native approve/reject in modern detail (P1c) [BC-04]
- [fd75d34d](https://github.com/emoajib/SIM-LPPM/commit/fd75d34dcda126e103ce7341936be0a4b2c7e4eb) — feat: native submit proposal in modern detail (P1b) [BC-04]
- [7e58a191](https://github.com/emoajib/SIM-LPPM/commit/7e58a191372d044d4fa58c5c21688e10d211158b) — feat: theme:check command + ThemeParityTest (BC-01)
- [24729109](https://github.com/emoajib/SIM-LPPM/commit/24729109371596d137d3ebf199b7518f75107dbf) — feat: action panel in modern proposal detail (link to classic) [BC-08]
- [cd2dc2b9](https://github.com/emoajib/SIM-LPPM/commit/cd2dc2b9da11be2d78c7ae5e72eda2573d24621e) — feat: default theme to classic on login; fix dashboard test for theme default
- [ccd12a35](https://github.com/emoajib/SIM-LPPM/commit/ccd12a352db16c570548241f860de0d451741c54) — feat: approve LPJ button in modern daily note (gap closure)
- [05cd2cfe](https://github.com/emoajib/SIM-LPPM/commit/05cd2cfebb8a06590ee77b246f2a816e4d1118c6) — feat: API endpoint request amendment (P2-9 infra)
- [82375d68](https://github.com/emoajib/SIM-LPPM/commit/82375d68fa2c801c4b58088b2266ce6d80c08cf5) — feat: React amendment list+decide via API (P2-9 partial)
- [451efbf6](https://github.com/emoajib/SIM-LPPM/commit/451efbf6ee0392ea67ace5e67794511cfd75dc46) — feat: React amendment list + approve/reject in modern daily note
- [d6ec9ab1](https://github.com/emoajib/SIM-LPPM/commit/d6ec9ab1af2f39fc4093e586abd08c84e62a4b5f) — feat: pending amendment indicator in modern daily note
- [b8bd22f8](https://github.com/emoajib/SIM-LPPM/commit/b8bd22f8f769c067f33b94be89dff920d6fd4f11) — feat: activity log LPJ/amandemen + artisan audit budget-caps
- [b0b70dd3](https://github.com/emoajib/SIM-LPPM/commit/b0b70dd316c04adbe3a46721a5a4675a118c3704) — feat: paritas modern Pengabdian, rektor read-only LPJ, badge mode surat, pagu deterministik
- [0c2a0ec1](https://github.com/emoajib/SIM-LPPM/commit/0c2a0ec17479ba5d832629f9d2325c831a95babb) — feat: Verifikasi Luaran - Input detail luaran untuk dosen
- [317f43f2](https://github.com/emoajib/SIM-LPPM/commit/317f43f29bc41677230d8dd6cc18ef0073004ce8) — feat: halaman modern Proposal Show (detail proposal) + API endpoint
- [bb038bb6](https://github.com/emoajib/SIM-LPPM/commit/bb038bb64e14dd1f1ccb5a1657c5734e5174fd68) — feat: upload bukti luaran (PDF+URL) + rank dropdown di Verifikasi Luaran (modern)
- [c1eb2edf](https://github.com/emoajib/SIM-LPPM/commit/c1eb2edf63d91e08d7230009e3fb1f803252e721) — feat: sinkronisasi data verifikasi luaran ke laporan-luaran (OutputReports)
- [75e0bc1a](https://github.com/emoajib/SIM-LPPM/commit/75e0bc1ae35474d23288c693104ac62e703abe7d) — feat: tambah tombol Preview & Download PDF di halaman Realisasi Anggaran (modern)
- [6951f147](https://github.com/emoajib/SIM-LPPM/commit/6951f1476b7f97a8abcfdb26c6540be3535a6ede) — feat: implementasi modul Realisasi Anggaran (Catatan Harian) di theme modern
- **modern** [34efffb3](https://github.com/emoajib/SIM-LPPM/commit/34efffb32a04d409212c595ecd35029b221b9625) — feat(modern): integrate real filter metadata (statuses) from availableFilters API
- **api** [20c701ab](https://github.com/emoajib/SIM-LPPM/commit/20c701ab0957454b8b7348a39246d9d7bcb864e8) — feat(api): add availableFilters endpoint + multi-column search + faculty/prodi filter
- **modern** [6ec6ce79](https://github.com/emoajib/SIM-LPPM/commit/6ec6ce797db6d896799862f480c1b4a94f5bab9d) — feat(modern): adopt surat-style card + sticky table + FAB for research & pengabdian

## Perbaikan

- **auth** [3481473b](https://github.com/emoajib/SIM-LPPM/commit/3481473b774f0f291a9432250db82fc481ee5d6c) — fix(auth): satukan otorisasi proposal modern/klasik/API [BC-06]
- **theme** [8618ed38](https://github.com/emoajib/SIM-LPPM/commit/8618ed38962866bcdd70ebd918890f164d102e4c) — fix(theme): hotfix URL kanonik, breadcrumb, dan artefak copy AI [BC-01][BC-09]
- [6e4aad9b](https://github.com/emoajib/SIM-LPPM/commit/6e4aad9b17d7b69f08c6789f01fd4325fe6195f2) — fix: hide modern module-tabs bar in classic theme (BC-01)
- [dd73c240](https://github.com/emoajib/SIM-LPPM/commit/dd73c2409c72031831fb56e48c1ade642063d617) — fix: render HTML subtitle in kpi-widget (was escaped)
- [a66159e7](https://github.com/emoajib/SIM-LPPM/commit/a66159e7e082bb86d3fa1a6efc854bb9c4001d5d) — fix: default theme cookie to classic in modern route redirects
- [db876653](https://github.com/emoajib/SIM-LPPM/commit/db8766532c7feb44125bd22d90832ae584ff8a80) — fix: warning banner + clearer guidance for blocked amendment LPJ
- [a44e3547](https://github.com/emoajib/SIM-LPPM/commit/a44e3547ace86253782f3919fb2c6d3217a976ee) — fix: clearer amendment error guidance + reject button prominence for admin lppm
- [b7b01c4a](https://github.com/emoajib/SIM-LPPM/commit/b7b01c4af249189600731c2929400f34839de424) — fix: redirect modern detail routes to classic when theme is classic
- [1d35e175](https://github.com/emoajib/SIM-LPPM/commit/1d35e17561925e1a7ea80a4659a8ecd2b79ffb19) — fix: pint import order + phpstan audit command
- [d8ad5a4c](https://github.com/emoajib/SIM-LPPM/commit/d8ad5a4c0ebc63003e2bf4f2945ea3611cf7a3e5) — fix: budget caps tolerance/copy, daily-note amendment banner, remove dead lettering flag
- [592a595c](https://github.com/emoajib/SIM-LPPM/commit/592a595c73640f2f72985ecdc4cf11ffef9cb6a2) — fix: blank modern detail, superadmin scoping, verifikasi luaran team/role, classic redirect, LPJ-amandemen, budget caps UI, letter flag sync
- **settings** [9b8d9724](https://github.com/emoajib/SIM-LPPM/commit/9b8d97241b28c2dca36cf5b871ef749f845ba02d) — fix(settings): mysqldump via defaults-file + redirect-to-file in BackupData
- **dashboard** [730ab84b](https://github.com/emoajib/SIM-LPPM/commit/730ab84bf19fb720bd50bea69720b7c697e10a5c) — fix(dashboard): kolom Skema pakai data master, Peran & Status nyata, margin +1 cm/sisi
- **settings** [1c5c878a](https://github.com/emoajib/SIM-LPPM/commit/1c5c878aeca56bcbd78ff05b997d1ef29a9dc244) — fix(settings): repair broken TKT & science cluster tab markup, widen modern layout
- **settings** [e894376c](https://github.com/emoajib/SIM-LPPM/commit/e894376c2e58e95fa2ef00052816344ffb89b696) — fix(settings): accept valid .sql uploads on restore data page
- **dashboard+ci** [d55fdb78](https://github.com/emoajib/SIM-LPPM/commit/d55fdb783a6969f207f3ee04ea154a75c4d146aa) — fix(dashboard+ci): remove duplicate create button, fix MySQL column-order baseline
- **icons** [1aa3a90f](https://github.com/emoajib/SIM-LPPM/commit/1aa3a90f0d140c4ba496e68e28cb71ad11678ba7) — fix(icons): add missing icons to 51 empty action buttons across 31 files
- **dashboard** [18440347](https://github.com/emoajib/SIM-LPPM/commit/184403474894040535f91ef016ca5e73fceaebd9) — fix(dashboard): filter proposal table by scheme client-side\n\n- Add data-scheme attribute to each proposal row in Daftar Usulan Terdaftar table\n- Add filterProposalTable() JS function to hide/show rows based on scheme filter\n- Call filterProposalTable from refreshVisuals() and renderAll()\n- Re-attach scheme button event listeners in renderAll() for Livewire re-render safety\n- Scheme filter now affects both infographic charts AND proposal list table
- **dashboard** [1b9b68a6](https://github.com/emoajib/SIM-LPPM/commit/1b9b68a64b12b65b077ac5752638144668549f6c) — fix(dashboard): scheme filter robustness + remove inline onclick\n\n- Replace inline onclick with data-scheme attribute + event listener for scheme filter buttons (Semua Skema/Penelitian/Pengabdian)\n- This ensures sliSetScheme() is reliably called when user clicks filter buttons\n- All Laravel caches cleared\n- User must hard refresh browser (Ctrl+Shift+R / Cmd+Shift+R)
- **alpine** [46f44b6c](https://github.com/emoajib/SIM-LPPM/commit/46f44b6c239c6572ce07b512a154845ddeba59ac) — fix(alpine): fix countdownTimer x-data expression error
- **classic theme** [4f92f760](https://github.com/emoajib/SIM-LPPM/commit/4f92f760a18bafdceeb496b855d54186268da412) — fix(classic theme): add missing icons to empty action buttons in research pages
- **classic theme** [b1faa8de](https://github.com/emoajib/SIM-LPPM/commit/b1faa8de6d7e80e95cab4b37d9ed2dd52ce120e8) — fix(classic theme): add ti ti-* icons + d-flex flex classes to all Blade action buttons
- [e6fb0684](https://github.com/emoajib/SIM-LPPM/commit/e6fb068415c8f56c7867052bb21793645c42ef08) — fix: Dashboard modern tidak muncul - tambah 'dashboard' ke modernPageMap
- [2b550963](https://github.com/emoajib/SIM-LPPM/commit/2b55096309d65c95baff495c64ab507b590b4fbf) — fix: tampilkan tab Verifikasi Luaran untuk dosen di theme klasik
- [8da87116](https://github.com/emoajib/SIM-LPPM/commit/8da87116063fe097e6334830ebd18468e80e929a) — fix: Livewire only supports one root element - move module-tabs inside root div
- [965452ea](https://github.com/emoajib/SIM-LPPM/commit/965452ea552eb4e8180d85a27b1350a48faff0b4) — fix: IkuVerification role-based filtering + module-tabs verifikasi URL revert
- [67aac2d8](https://github.com/emoajib/SIM-LPPM/commit/67aac2d8c68dc1880a3807ee939138b1754f3f12) — fix: rapikan form Detail Output Luaran + fieldset disabled untuk verifier
- [ebfbc9a5](https://github.com/emoajib/SIM-LPPM/commit/ebfbc9a5bc3dacb8bb0225fd808fde79e27e8cb8) — fix: Verifikasi Luaran - active role check + read-only modal untuk verifier
- [64d4ea06](https://github.com/emoajib/SIM-LPPM/commit/64d4ea06a4e5efef78e6f4e5220c3d884e131948) — fix: Dashboard modern — filter tipe usulan (Penelitian/Pengabdian) + active role check
- [e36e1b9a](https://github.com/emoajib/SIM-LPPM/commit/e36e1b9a59943486719896c5bacc2c24a810d14e) — fix: Verifikasi Luaran — data sesuai role user (ketua + anggota tim)
- [48ae1195](https://github.com/emoajib/SIM-LPPM/commit/48ae119574d49927f695079bf5885de8dc009d22) — fix: Verifikasi Luaran tetap dalam konteks /research dan /community-service
- [d6124812](https://github.com/emoajib/SIM-LPPM/commit/d61248121c3cd5b4a56e7e296d2670741ef6882c) — fix: Realisasi Anggaran — pagu anggaran dari approved_budget_snapshot + tabs tunggal
- [22c46c60](https://github.com/emoajib/SIM-LPPM/commit/22c46c600bd8b479e2c7d91a16a5f8488a98b3ec) — fix: Undefined variable $slot — gunakan null coalescing operator
- [3ea83750](https://github.com/emoajib/SIM-LPPM/commit/3ea83750790283980c7665f5f0fc1685ff741bf5) — fix: Undefined variable $slot di daily-note.blade.php
- [06131dcb](https://github.com/emoajib/SIM-LPPM/commit/06131dcbfbea65a9279612f446bc7d29e98a8392) — fix: navigasi Realisasi Anggaran ke URL /research/daily-note untuk semua role
- [a2c7435b](https://github.com/emoajib/SIM-LPPM/commit/a2c7435ba3073e3dbd70da7932a4de487023d74f) — fix: implementasi 5 tahap Alur Manajemen Penelitian & Pengabdian di theme modern
- **proposal-filter** [7df11086](https://github.com/emoajib/SIM-LPPM/commit/7df110869315920a4deeb387fe7e812c33c0b2c2) — fix(proposal-filter): 'Sebagai Anggota' menampilkan usulan sendiri
- **modern** [06973b41](https://github.com/emoajib/SIM-LPPM/commit/06973b41d0b6fe46dfd1a8df34a0a0365c20e5de) — fix(modern): navbar menu invisible — Tailwind .collapse utility hid Tabler navbar
- **modern** [062d99f8](https://github.com/emoajib/SIM-LPPM/commit/062d99f8cee9f7721f6bdbdd8fda69e145ae29d7) — fix(modern): inline style force navbar-collapse display (last resort)
- **modern** [e936df07](https://github.com/emoajib/SIM-LPPM/commit/e936df07aac290b22880024da2c9b9d80f6542ff) — fix(modern): force navbar menu always visible (no collapse) in modern theme
- **modern** [c7dbe010](https://github.com/emoajib/SIM-LPPM/commit/c7dbe010856c91c7dfa3a06d54cfa06c4c1a6b72) — fix(modern): expand navbar-collapse on md+ (root cause of disappearing menu)
- **modern** [eae60264](https://github.com/emoajib/SIM-LPPM/commit/eae60264dae999c85c321bd36cb0f8025f06d633) — fix(modern): remove global Tailwind Preflight + scoped reset + remove header persist (navbar fix)
- **modern** [9c34460e](https://github.com/emoajib/SIM-LPPM/commit/9c34460e07c5cf696bdd2bea6236133dfaaf74cc) — fix(modern): restore stable header persist + Blade fallback for navbar menu
- **modern** [6ac75b36](https://github.com/emoajib/SIM-LPPM/commit/6ac75b36cf2a3ca9d71ca9565a7aa75608e9bc70) — fix(modern): ensure navbar menu never disappears (dynamic persist key + cache invalidate + fallback + css)
- **modern** [cc30520e](https://github.com/emoajib/SIM-LPPM/commit/cc30520e87a8295d7c6638c447a00d524f8b5fa0) — fix(modern): disable menu collapse to 'Lainnya' so all navbar items stay visible
- **modern** [46d95d3a](https://github.com/emoajib/SIM-LPPM/commit/46d95d3a1ed8434734dada43c3f5488a4cc8223f) — fix(modern): make Penelitian & Pengabdian single links (like Surat module)

## Performa

- [17e78146](https://github.com/emoajib/SIM-LPPM/commit/17e78146b1fd45afd14c6595360d087b5b29dad7) — perf: cache eligibility & schedule status (60s) to speed up Info Eligibilitas

## Lainnya

- [d2f97583](https://github.com/emoajib/SIM-LPPM/commit/d2f975839f3a26bf1fcfb08181288e48ec6144f0) — ci: buat gate BC-07 adaptif terhadap perbedaan build lintas OS
- **deploy** [5ea5462f](https://github.com/emoajib/SIM-LPPM/commit/5ea5462f9a9d12a23572199b8b761cbf919351cc) — ci(deploy): hardening update_production.sh dan tegakkan BC-07
- **regressions** [3decd592](https://github.com/emoajib/SIM-LPPM/commit/3decd592727ad0db9195619442ab2eee83c7e8d5) — docs(regressions): hindari marker terlarang di BC-09 [BC-09]
- [a6a24d15](https://github.com/emoajib/SIM-LPPM/commit/a6a24d153013f1032436ab9d4d8fae076319f944) — docs: perbarui status proyek (Wave 2/3 selesai, Wave 4 sebagian)
- **deploy** [8eb8146e](https://github.com/emoajib/SIM-LPPM/commit/8eb8146e6bf318635778eaafe94976984088cb9a) — chore(deploy): checklist keamanan otomatis di akhir update_production.sh
- **api** [89a7f81b](https://github.com/emoajib/SIM-LPPM/commit/89a7f81bc88d20af95a5be43029e463c63adefcf) — test(api): kontrak payload detail proposal (React <-> API)
- **routes** [c9d3ed70](https://github.com/emoajib/SIM-LPPM/commit/c9d3ed70f659802da4cabc86542ba0d417579d29) — refactor(routes): ekstrak closure halaman modern ke ModernPageController
- **theme** [cb4da189](https://github.com/emoajib/SIM-LPPM/commit/cb4da1891a405368b9f893c42c94a68b3fb6676f) — refactor(theme): registry halaman modern + modern:check + enum Theme terpusat
- [5ef44619](https://github.com/emoajib/SIM-LPPM/commit/5ef4461911f881f34bf4677d8ef492b6314f9bb1) — docs: DoD wajib, template PR, dan arsip 5 dokumen root
- **theme** [0283e767](https://github.com/emoajib/SIM-LPPM/commit/0283e76766460857ff17d8a17a7251c960d468ce) — test(theme): matriks smoke 8 peran x 2 tema x 9 halaman
- [92ab91c3](https://github.com/emoajib/SIM-LPPM/commit/92ab91c3e2ee3825e54f2e0bc1acef764e024200) — docs: master plan urutan eksekusi + mitigasi (semua wave)
- [c6ac0a0f](https://github.com/emoajib/SIM-LPPM/commit/c6ac0a0f4e05781d8ec2a5a24fa5e0a382e790e4) — docs: gap analysis modern vs classic (usulan, revisi, laporan, realisasi, verifikasi)
- [ae2d3656](https://github.com/emoajib/SIM-LPPM/commit/ae2d3656178784ccfbd6e6014c8906caa968dcc8) — ci: make build-verify step informational (BC-07)
- [a051a60b](https://github.com/emoajib/SIM-LPPM/commit/a051a60bff18a76618a96da4d843153596fd8855) — ci: verify committed build matches source (BC-07)
- [f6ca1b80](https://github.com/emoajib/SIM-LPPM/commit/f6ca1b806b9252cadb97ce7edf76be844b7eac4c) — docs: add Bug Class Registry (BC-01..BC-08) + regression:check command (P3-H1)
- [551ac595](https://github.com/emoajib/SIM-LPPM/commit/551ac59515a2c01d2d28344f69c3a032a8970e37) — refactor: introduce App\Enums\Theme and use it in is_modern_theme (BC-01)
- [71c9adca](https://github.com/emoajib/SIM-LPPM/commit/71c9adcac5cbde144ef875d32954e74de933cd77) — chore: ignore local report docs and deploy script variants
- [0c834bd9](https://github.com/emoajib/SIM-LPPM/commit/0c834bd9874c70e4826f84f019f8f0a2fceb18ae) — style: pint eof blank line
- [9f6c39eb](https://github.com/emoajib/SIM-LPPM/commit/9f6c39ebc056c6fcc2147adb49f2ccd70da2342e) — test: letter module gating (flag/mode/route)
- [4e1f6c07](https://github.com/emoajib/SIM-LPPM/commit/4e1f6c076e5e7c0934679ef060f9b57d63302047) — test: LPJ terblokir saat amandemen pending
- [b461f22c](https://github.com/emoajib/SIM-LPPM/commit/b461f22c5b4c585d69423b4b3366a84e92139c7f) — revert: remove 'dashboard' from modernPageMap
- **theme** [40e2d889](https://github.com/emoajib/SIM-LPPM/commit/40e2d889385f06e1e30984d0fed803363f53900f) — test(theme): guard classic theme against modern bundle regression
- [292b690f](https://github.com/emoajib/SIM-LPPM/commit/292b690f7662356d0838f5635c98741d6518c01d) — chore: remove debug test file
