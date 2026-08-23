# Spesifikasi Hosting (Shared Hosting)

Berikut spesifikasi minimum server CPanel / Shared Hosting yang menjadi target produksi:

- **Web Server:** LiteSpeed / Apache.
- **PHP Version:** PHP 8.3 (sesuai target Laravel 13).
- **Ekstensi PHP Wajib:**
  - `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `curl`.
- **Database:** MySQL 8.x atau MariaDB 10.x.
- **NodeJS/NPM:** Biasanya tidak tersedia bebas di Shared Hosting. Oleh karenanya, aset CSS/JS wajib di-*build* (menggunakan Vite) di lingkungan *local* (mesin dev) sebelum file *production* di-*upload* ke hosting.
- **Background Jobs (Queue):**
  - Shared hosting biasanya tidak mengizinkan `supervisor`.
  - Sistem *queue worker* Laravel (`queue:work`) harus diakali menggunakan **Cron Job** (`php artisan schedule:run` atau custom command yang menjalankan *queue processing* satu siklus tiap menit).

*Catatan: Sangat penting memastikan keamanan environment file (`.env`), di mana root folder public di cPanel diletakkan terpisah dari folder sistem (aplikasi Laravel), atau setidaknya dibatasi dengan file `.htaccess` jika di-*deploy* langsung di `public_html`.*
