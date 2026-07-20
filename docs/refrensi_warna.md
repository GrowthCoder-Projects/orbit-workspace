# Dokumentasi Warna & Design Tokens — growthcoder.id

Dokumen ini mendokumentasikan sistem warna, variabel CSS, dan gradasi warna yang digunakan pada platform **growthcoder.id**. Seluruh nilai di bawah diselaraskan langsung dengan konfigurasi **Tailwind CSS v4** pada panel CMS admin untuk menjaga konsistensi identitas merek (brand identity).

---

## 1. Palet Warna Utama (Brand Colors)

Palet ini menggunakan warna navy pekat sebagai jangkar (anchor) utama, hijau emerald sebagai aksen interaktif, dan ungu terang sebagai varian pendukung.

| Nama Token | Kode HEX | Nilai HSL | Contoh Penggunaan |
| :--- | :--- | :--- | :--- |
| **Brand Primary** | `#2D2A6F` | `hsl(243, 45%, 30%)` | Warna primer, latar belakang Navbar, header utama. |
| **Brand Primary Hover**| `#3F3A94` | `hsl(243, 45%, 41%)` | State hover pada tombol primer. |
| **Brand Secondary** | `#5C59D9` | `hsl(241, 62%, 60%)` | Warna sekunder, warna teks utama di dark mode. |
| **Secondary Light** | `#E8E7FF` | `hsl(241, 100%, 95%)`| Latar belakang badge, hover state ringan. |
| **Brand Accent** | `#2BB673` | `hsl(151, 62%, 44%)` | Tombol sukses, badge status, link aktif, aksen. |
| **Brand Accent Hover** | `#24A566` | `hsl(151, 62%, 39%)` | State hover pada tombol aksen/sukses. |
| **Dark Neutral** | `#111827` | `hsl(221, 39%, 11%)` | Teks utama light mode, background di dark mode. |
| **Muted Grey** | `#6B7280` | `hsl(220, 9%, 46%)`  | Teks keterangan tambahan, ikon tidak aktif. |
| **Border Light** | `#E5E7EB` | `hsl(220, 13%, 91%)` | Border pemisah konten, garis tipis. |

---

## 2. Variabel CSS Global (`app/assets/css/main.css`)

Variabel CSS ini wajib didefinisikan pada root aplikasi agar dapat dikonsumsi secara konsisten oleh seluruh komponen Vue di Nuxt.

### A. Tema Terang (Light Mode - Default)
```css
:root {
    /* Latar Belakang & Teks */
    --background: hsl(0 0% 100%);
    --foreground: hsl(221 39% 11%); /* Dark Neutral #111827 */
    
    /* Kartu & Popover */
    --card: hsl(0 0% 100%);
    --card-foreground: hsl(221 39% 11%);
    --popover: hsl(0 0% 100%);
    --popover-foreground: hsl(221 39% 11%);
    
    /* Warna Aksi (Buttons & States) */
    --primary: hsl(243 45% 30%); /* Brand Primary #2D2A6F */
    --primary-foreground: hsl(0 0% 100%);
    --secondary: hsl(241 100% 95%); /* Brand Secondary Light #E8E7FF */
    --secondary-foreground: hsl(243 45% 30%);
    --accent: hsl(151 62% 44%); /* Brand Accent #2BB673 */
    --accent-foreground: hsl(0 0% 100%);
    --destructive: hsl(0 84.2% 60.2%);
    --destructive-foreground: hsl(0 0% 98%);
    
    /* Border & Focus Rings */
    --border: hsl(220 13% 91%); /* Border #E5E7EB */
    --input: hsl(220 13% 91%);
    --ring: hsl(243 45% 30%);
    --muted: hsl(220 9% 46%); /* Muted #6B7280 */
    --muted-foreground: hsl(220 9% 46%);
    
    /* Radius Sudut */
    --radius: 0.5rem;
    
    /* Brand Raw (Untuk Gradasi & Shadow Kustom) */
    --brand-primary: #2d2a6f;
    --brand-primary-hover: #3f3a94;
    --brand-accent: #2bb673;
    --brand-accent-hover: #24a566;
    --brand-secondary: #5c59d9;
    --brand-secondary-light: #e8e7ff;
}
```

### B. Tema Gelap (Dark Mode)
Diaktifkan dengan menambahkan class `.dark` pada tag `<html>`.
```css
.dark {
    /* Latar Belakang & Teks */
    --background: hsl(240 10% 3.9%);
    --foreground: hsl(0 0% 98%);
    
    /* Kartu & Popover */
    --card: hsl(240 10% 4.9%);
    --card-foreground: hsl(0 0% 98%);
    --popover: hsl(240 10% 4.9%);
    --popover-foreground: hsl(0 0% 98%);
    
    /* Warna Aksi (Aksesibilitas Ditingkatkan) */
    --primary: hsl(241 62% 60%); /* Secondary #5C59D9 untuk kontras yang lebih baik */
    --primary-foreground: hsl(0 0% 100%);
    --secondary: hsl(240 5% 15%);
    --secondary-foreground: hsl(0 0% 98%);
    --accent: hsl(151 62% 44%); /* Tetap Brand Accent #2BB673 */
    --accent-foreground: hsl(0 0% 100%);
    
    /* Border & Focus Rings */
    --border: hsl(240 5% 15%);
    --input: hsl(240 5% 15%);
    --ring: hsl(241 62% 60%);
    --muted: hsl(240 5% 65%);
    --muted-foreground: hsl(240 5% 65%);
}
```

---

## 3. Rumus Gradasi & Efek Visual Premium

Gunakan kode CSS berikut untuk merender elemen gradasi yang premium pada latar belakang Hero section dan tombol aksi.

### A. Gradasi Background Hero (Hero Gradient background)
Memberikan efek pendar cahaya yang lembut pada bagian header halaman:
```css
.hero-gradient {
    background: radial-gradient(
        circle at 50% -20%, 
        rgba(92, 89, 217, 0.15) 0%, 
        rgba(43, 182, 115, 0.05) 50%, 
        transparent 100%
    );
}
```

### B. Gradasi Border Teks (Text Gradient)
Digunakan pada teks judul utama (H1) untuk menonjolkan poin fokus:
```css
.text-brand-gradient {
    background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.dark .text-brand-gradient {
    background: linear-gradient(135deg, var(--brand-secondary) 0%, var(--brand-accent) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
```

### C. Efek Kaca Transparan (Glassmorphism Card)
Gunakan kombinasi border transparan dan blur latar belakang:
```css
.glass-panel {
    background-color: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(229, 231, 235, 0.5);
}

.dark .glass-panel {
    background-color: rgba(20, 20, 25, 0.8);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(63, 63, 70, 0.4);
}
```
