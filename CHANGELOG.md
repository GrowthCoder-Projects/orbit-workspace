# 📋 Changelog

Semua perubahan penting pada proyek **Orbit** akan didokumentasikan dalam berkas ini.

Format ini didasarkan pada [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
dan proyek ini mengikuti [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

---

## [1.0.0] - 2026-07-26

### ✨ Added
- **Core Workspace & Dashboard**: Dashboard interaktif yang menampilkan metric Waktu Fokus (Focus Time), kalender kegiatan, ucapan harian (Daily Welcome), habit tracker, serta ringkasan tugas dan proyek.
- **REST API v1 & Sanctum Authentication**: Endpoint API v1 lengkap yang diamankan menggunakan Laravel Sanctum token-based authentication.
- **Manajemen Proyek & Milestones**: Katalog proyek perangkat lunak dengan pratinjau live site, pengelolaan URL staging/produksi, alamat IP server, serta grafik lini masa milestone.
- **Modul Keuangan (Finance)**: Suite keuangan pribadi dan bisnis lengkap:
  - Rekening/dompet digital multi-mata uang.
  - Tracking Aset dan Liabilitas.
  - Tagihan berulang (Recurring Bills).
  - Target anggaran bulanan (Budgets) & Tabungan (Savings).
  - Riwayat transaksi keuangan dan investasi.
  - Laporan keuangan otomatis.
- **Sistem Faktur & Invoicing**: Pembuatan faktur/invoice PDF dengan template yang dapat disesuaikan (*Classic*, *Minimalist*, *Modern*) dan pelacakan status pembayaran.
- **Manajemen Tugas & Kebiasaan (Tasks & Habits)**: Checklist tugas ber-tingkat, subtask, prioritas, habit streak harian, dan pencatatan progres bulanan.
- **Knowledge Base & Catatan**: Artikel dokumentasi dengan struktur pohon (tree node) berbasis Markdown serta pengelolaan catatan cepat (Notes).
- **Manajemen Dokumen & Bookmarks**: Pengelola bookmark URL otomatis dengan fetching metadata (judul & icon) serta repositori berkas dokumen.
- **Kalender & Log Aktivitas**: Tampilan kalender terpadu untuk event/milestone serta log audit riwayat aktivitas sistem.
- **Pengaturan & Keamanan**: Pengaturan tema (Dark/Light mode), autentikasi dua faktor (2FA), manajemen cadangan (backups), serta toggle fitur aplikasi.
- **Suite Pengujian Otomatis**: Feature & Unit test yang dibangun menggunakan Pest PHP.
