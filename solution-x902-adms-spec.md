# Spesifikasi Protokol ADMS Mesin Solution X902

Dokumen ini mendeskripsikan mekanisme interaksi protokol Push Data ADMS berdasarkan cara kerja mesin Solution X902.

## Endpoint & HTTP Methods
Sistem ADMS bekerja menggunakan HTTP GET dan POST yang diinisiasi oleh mesin X902 (berfungsi sebagai HTTP Client).

1. **Init Koneksi / Handshake (GET `/iclock/cdata`)**
   Mesin akan melakukan request GET secara berkala atau saat pertama dihidupkan dengan URL parameter yang memuat `SN` (Serial Number) mesin dan parameter lainnya untuk otentikasi (seperti Stamp atau token rahasia).

2. **Push Data Presensi (POST `/iclock/cdata` / `/api/v1/attendance/push`)**
   Ketika ada siswa yang melakukan absen, mesin akan mengirimkan data *Attendance Log* via metode POST.
   - **Query Parameters:** `SN=[serial_number]&table=ATTLOG&Stamp=[timestamp]`
   - **Body (Text/Plain):** Berisi baris teks dipisahkan baris baru (`\n`). Setiap baris dipisahkan spasi/tab.
     - Contoh Body `ATTLOG`:
       ```
       [USER_ID] [DATE_TIME] [STATUS] [VERIFY_MODE]
       1001 2026-08-23 07:05:12 0 1
       1002 2026-08-23 07:05:15 0 1
       ```
     - Keterangan Data:
       - `USER_ID`: PIN atau User ID di mesin.
       - `DATE_TIME`: Waktu absensi.
       - `STATUS`: 0 (Masuk), 1 (Keluar), dst (Tergantung mode mesin).
       - `VERIFY_MODE`: 1 (Fingerprint), 4 (Card), 15 (Face), dll.

3. **Push Data Operasi (POST `/iclock/cdata` table `OPERLOG`)**
   Jika ada pendaftaran user baru, wajah baru, atau sidik jari di mesin, mesin melakukan push data via POST.
   - **Query Parameters:** `table=OPERLOG`
   - **Body:**
     ```
     USER PIN=1079 Name=Hendri Pri=14 Passwd=1234 Grp=1
     ```

## Format Response
Server wajib membalas (merespons) dengan `text/plain` berupa:
`OK: [jumlah_data_tersimpan]` (misalnya: `OK: 1` atau `OK: 2`).

## Catatan Implementasi di Laravel
Berdasarkan PRD yang baru, request webhook presensi ini akan diarahkan ke `POST /api/v1/attendance/push`. 
Endpoint tersebut akan membaca raw body menggunakan `$request->getContent()` lalu mem-parsing string `\n` dan spasi untuk mengekstrak array log, dan diolah secara aman oleh `AttendanceService`.
