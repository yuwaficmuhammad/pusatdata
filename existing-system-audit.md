# Audit Sistem Presensi Lama (dist-easyadms)

Sistem presensi lama yang digunakan adalah aplikasi berbasis Laravel bernama `dist-easyadms`.

## Ringkasan Fitur dan Temuan:
1. **Arsitektur:** Menggunakan Laravel framework.
2. **Koneksi Mesin:** Sistem lama berfungsi sebagai ADMS server, di mana mesin (Solution X902/ZKTeco) melakukan POST data log dan GET perintah ke sistem melalui URL `/iclock/cdata` dan `/iclock/getrequest`.
3. **Pengelolaan Data:**
   - **Log Kehadiran (ATTLOG):** Menerima baris data dipisahkan spasi/tab yang berisi ID Pengguna, timestamp kehadiran, dll.
   - **Log Operasi (OPERLOG):** Menerima operasi seperti sinkronisasi User dan Sidik Jari/Wajah. Contoh: `USER PIN=1079 Name=Hendri...`
4. **Alur Webhook:** Sistem lama memiliki fitur Webhook yang meneruskan data (forward) attendance log ke URL lain.
5. **Kekurangan/Pain Points untuk Proyek Baru:**
   - Logika penanganan diletakkan langsung di dalam Controller (`CdataPostController.php`), tidak dipisah ke dalam *Service Class* seperti arsitektur baru yang diminta di PRD.
   - Routing untuk ADMS API dan Web Application masih bercampur di `web.php` dan belum sepenuhnya dipisah antara REST API dengan standar response JSON dan endpoint webhook khusus ADMS.

## Kesimpulan:
Proyek baru akan mengadopsi mekanisme penerimaan string dari mesin ADMS (ATTLOG) namun akan diproses menggunakan **AttendanceService** yang bersih sesuai dengan PRD Fase 4 (Sistem Pusat Data Terpadu), serta diarahkan melalui rute `api.php` sebagai `/api/v1/attendance/push` alih-alih `iclock/cdata`.
