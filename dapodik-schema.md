# Referensi Schema Dapodik (Entitas Siswa)

Berdasarkan format sinkronisasi Data Pokok Pendidikan (DAPODIK) Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi, berikut atribut standar yang diadaptasi:

- `nisn` (Nomor Induk Siswa Nasional): 10 digit angka, unik nasional.
- `nis` (Nomor Induk Sekolah): Unik di tingkat instansi sekolah.
- `nama`: Sesuai ijazah atau akta kelahiran.
- `jenis_kelamin`: 'L' (Laki-laki) atau 'P' (Perempuan).
- `tempat_lahir` & `tanggal_lahir`: Digunakan untuk verifikasi keabsahan data.
- `agama`: Sesuai data nasional (Islam, Kristen, Katolik, Hindu, Buddha, Khonghucu).
- `alamat`: Alamat tempat tinggal domisili saat ini.
- `nama_ayah`, `nama_ibu`, `nama_wali`: Data wali murid penanggung jawab.
- `no_telp_wali`: Nomor aktif untuk WhatsApp / Fonnte Gateway.

*Note: Skema ini sudah diakomodasi di dalam spesifikasi blueprint database tabel `student` pada PRD.*
