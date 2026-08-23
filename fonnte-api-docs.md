# Dokumentasi API Fonnte (WA Gateway)

**Base URL:** `https://api.fonnte.com/`
**Auth Header:** `Authorization: {FONNTE_TOKEN}`

## Endpoint: Send Message
`POST /send`

**Headers:**
- `Authorization`: Token API Fonnte.

**Form Data / JSON Body:**
- `target`: (String) Nomor tujuan (pisahkan dengan koma jika jamak). Wajib kode negara, cth: `628123456789`.
- `message`: (String) Isi pesan teks WhatsApp.
- `url`: (Opsional) URL gambar/file.

**Contoh Response Sukses:**
```json
{
  "status": true,
  "detail": "pesan sedang di proses",
  "process": "1 messages",
  "target": [
    "628123456789"
  ]
}
```

## Rate Limiting & Error Handling
- Batas rate limit tergantung paket Fonnte (disarankan dikirim melalui sistem Queue/Job di Laravel agar tidak memblokir thread HTTP).
- Jika nomor tidak valid, Fonnte akan mengembalikan error message pada log mereka. Sistem lokal sebaiknya mengecek panjang digit awalan `08` dan mengkonversi menjadi awalan kode negara `628`.
