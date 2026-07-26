# Dokumentasi REST API GrowthCoder Workspace (v1)

Dokumentasi ini menjelaskan cara menggunakan dan mengintegrasikan REST API v1 GrowthCoder Workspace dengan aplikasi pihak ketiga seperti **Bot Telegram**, **WhatsApp Bot**, **Browser Extension**, **Script Otomatisasi (Python/n8n/Make/Zapier)**, atau aplikasi mobile.

---

## 1. Otentikasi API (Authentication)

REST API dilindungi menggunakan **Laravel Sanctum Personal Access Tokens**.

### Cara Membuat API Token:
1. Buka aplikasi web di browser dan masuk ke menu **Settings** -> **API Tokens** (`/app/settings/api-tokens`).
2. Masukkan nama token (contoh: `Telegram Bot`, `Chrome Extension`, `Script Otomatisasi`).
3. Klik **Generate Token**.
4. Salin string API Token yang muncul (contoh: `1|v87A9sXk...`).

### Format HTTP Request Headers:
Setiap HTTP Request ke endpoint `/api/...` wajib menyertakan HTTP Header berikut:

```http
Authorization: Bearer <API_TOKEN_ANDA>
Content-Type: application/json
Accept: application/json
```

---

## 2. Standar Format Respon JSON

### Respon Sukses (200 OK / 201 Created)
```json
{
  "success": true,
  "message": "Note created successfully",
  "data": {
    "id": 1,
    "title": "Beli barang",
    "content": "Susu, Roti",
    "folder_id": null,
    "is_favorite": false,
    "is_archived": false,
    "created_at": "2026-07-21T23:30:00+07:00",
    "updated_at": "2026-07-21T23:30:00+07:00"
  }
}
```

### Respon Error / Gagal (400 Bad Request / 401 Unauthorized / 422 Validation Error)
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

---

## 3. Daftar Lengkap Endpoint API (API Reference)

### A. Authentication & User Profile
* **`GET /api/user`**
  * **Deskripsi**: Memeriksa informasi profil dan autentikasi user saat ini.
  * **Headers**: `Authorization: Bearer <token>`

---

### B. Modul Catatan (`Notes`)

* **`GET /api/v1/notes`**
  * **Deskripsi**: Mengambil daftar catatan.
  * **Query Parameters (Opsional)**:
    * `q` (string): Kata kunci pencarian judul/konten.
    * `folder_id` (integer): Filter berdasarkan ID folder.
    * `favorites` (boolean): `true` untuk hanya catatan favorit.
    * `per_page` (integer): Jumlah data per halaman (default: 15).
    * `page` (integer): Nomor halaman.

* **`POST /api/v1/notes`**
  * **Deskripsi**: Membuka/membuat catatan baru (*Quick Capture*).
  * **Body JSON**:
    ```json
    {
      "title": "Catatan Rapat Hari Ini",
      "content": "Pembahasan fitur baru REST API",
      "folder_id": null,
      "is_favorite": false
    }
    ```

* **`GET /api/v1/notes/{id}`**
  * **Deskripsi**: Menampilkan detail catatan beserta folder & backlinks.

* **`PATCH /api/v1/notes/{id}`**
  * **Deskripsi**: Perbarui catatan tertentu.

* **`DELETE /api/v1/notes/{id}`**
  * **Deskripsi**: Menghapus catatan.

---

### C. Modul Tugas (`Tasks`)

* **`GET /api/v1/tasks`**
  * **Deskripsi**: Mengambil daftar tugas.
  * **Query Parameters (Opsional)**:
    * `status` (string): `pending` / `completed`.
    * `priority` (string): `low` / `medium` / `high` / `urgent`.
    * `project_id` (integer): Filter berdasarkan proyek.

* **`POST /api/v1/tasks`**
  * **Deskripsi**: Menambah tugas baru.
  * **Body JSON**:
    ```json
    {
      "title": "Perbaiki bug di production",
      "description": "Cek log di server",
      "priority": "high",
      "due_date": "2026-07-25 17:00:00"
    }
    ```

* **`GET /api/v1/tasks/{id}`**
  * **Deskripsi**: Detail tugas beserta checklist item-nya.

* **`PATCH /api/v1/tasks/{id}`**
  * **Deskripsi**: Update status atau detail tugas.

* **`DELETE /api/v1/tasks/{id}`**
  * **Deskripsi**: Menghapus tugas.

---

### D. Modul Kebiasaan (`Habits`)

* **`GET /api/v1/habits`**
  * **Deskripsi**: Mengambil daftar kebiasaan harian aktif.
  * **Query Parameters**:
    * `archived` (boolean): `true` untuk menyertakan habit diarsipkan.

* **`POST /api/v1/habits`**
  * **Deskripsi**: Menambah kebiasaan baru.
  * **Body JSON**:
    ```json
    {
      "name": "Olahraga Pagi 30 Menit",
      "description": "Lari santai di kompleks",
      "frequency_type": "daily"
    }
    ```

* **`POST /api/v1/habits/{id}/toggle`**
  * **Deskripsi**: Toggle/Check-in penyelesaian habit pada tanggal tertentu.
  * **Body JSON**:
    ```json
    {
      "date": "2026-07-21"
    }
    ```

* **`DELETE /api/v1/habits/{id}`**
  * **Deskripsi**: Menghapus kebiasaan.

---

### E. Modul Keuangan (`Finance Transactions`)

* **`GET /api/v1/finance/transactions`**
  * **Deskripsi**: Mengambil riwayat transaksi keuangan.
  * **Query Parameters**:
    * `type` (string): `income` / `expense` / `transfer`.
    * `account_id` (integer): Filter akun dompet/bank.
    * `category_id` (integer): Filter kategori pengeluaran.

* **`POST /api/v1/finance/transactions`**
  * **Deskripsi**: Mencatat transaksi pemasukan/pengeluaran baru.
  * **Body JSON**:
    ```json
    {
      "amount": 45000,
      "type": "expense",
      "description": "Beli Bensin Pertamax",
      "account_id": 1,
      "category_id": 2,
      "date": "2026-07-21"
    }
    ```

* **`DELETE /api/v1/finance/transactions/{id}`**
  * **Deskripsi**: Menghapus transaksi keuangan.

---

### F. Modul Bookmark (`Bookmarks`)

* **`GET /api/v1/bookmarks`**
  * **Deskripsi**: Mengambil daftar simpanan link bookmark.
  * **Query Parameters**:
    * `q` (string): Cari judul atau URL.
    * `favorite` (boolean): Filter bookmark favorit.

* **`POST /api/v1/bookmarks`**
  * **Deskripsi**: Menyimpan link URL baru.
  * **Body JSON**:
    ```json
    {
      "title": "Laravel Documentation",
      "url": "https://laravel.com/docs",
      "description": "Panduan resmi Framework Laravel"
    }
    ```

* **`DELETE /api/v1/bookmarks/{id}`**
  * **Deskripsi**: Menghapus bookmark.

---

### G. Modul Kalender & Acara (`Calendar Events`)

* **`GET /api/v1/calendar/events`**
  * **Deskripsi**: Mengambil acara kalender.
  * **Query Parameters**:
    * `start_date` (string format ISO/date): Filter mulai dari tanggal.
    * `end_date` (string format ISO/date): Filter sampai tanggal.

* **`POST /api/v1/calendar/events`**
  * **Deskripsi**: Membuat jadwal event baru.
  * **Body JSON**:
    ```json
    {
      "title": "Meeting Sync Tim Developer",
      "start_at": "2026-07-22T09:00:00Z",
      "end_at": "2026-07-22T10:00:00Z",
      "location": "Google Meet"
    }
    ```

* **`DELETE /api/v1/calendar/events/{id}`**
  * **Deskripsi**: Menghapus event kalender.

---

## 4. Contoh Kode Integrasi Pihak Ketiga

### A. cURL Command Line
```bash
curl -X POST http://localhost:8000/api/v1/notes \
  -H "Authorization: Bearer 1|TOKEN_ANDA" \
  -H "Content-Type: application/json" \
  -d '{"title": "Catatan Cepat", "content": "Kirim file besok"}'
```

### B. Python Script (Bot / Automation)
```python
import requests

API_TOKEN = "1|TOKEN_ANDA"
BASE_URL = "http://localhost:8000/api/v1"

headers = {
    "Authorization": f"Bearer {API_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json"
}

def add_expense(amount, description, account_id=1):
    payload = {
        "amount": amount,
        "type": "expense",
        "description": description,
        "account_id": account_id
    }
    res = requests.post(f"{BASE_URL}/finance/transactions", json=payload, headers=headers)
    return res.json()

# Contoh Penggunaan
result = add_expense(35000, "Makan malam Nasi Goreng")
print(result)
```

### C. JavaScript / Node.js (Fetch)
```javascript
const API_TOKEN = '1|TOKEN_ANDA';

async function createNote(title, content) {
  const response = await fetch('http://localhost:8000/api/v1/notes', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${API_TOKEN}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({ title, content })
  });

  const data = await response.json();
  console.log(data);
}

createNote('Ide Aplikasi', 'Membuat modul AI Assistant');
```
