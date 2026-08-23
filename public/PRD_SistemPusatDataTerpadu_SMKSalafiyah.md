# PRD — Sistem Pusat Data Terpadu (Centralized Data System)
**Klien:** SMK Salafiyah | **Versi:** 1.0.0 | **Tanggal:** 2026-08-23
**Status:** Draft — Pending Socratic Clarification Sign-off

---

> **Cara Penggunaan Dokumen Ini (Antigravity PRD Phases)**
> Dokumen ini mengikuti 5 fase Antigravity PRD:
> 1. **Context Loading** — referensi file konteks via `@mention`
> 2. **Constitution & Rules** — aturan non-negotiable di `agents.md`
> 3. **Socratic Clarification** — pertanyaan tajam untuk menutup edge case
> 4. **Planning Mode** — `implementation_plan.md` wajib ada sebelum kode ditulis
> 5. **Task Breakdown** — task atomic, testable, dan paralel

---

## FASE 1 — Context Loading

### Referensi Dokumen Konteks

Sebelum memulai pengerjaan, agent/developer wajib memuat file berikut ke dalam project context:

| `@mention` | Isi | Status |
|---|---|---|
| `@company-goals.md` | Visi digitalisasi SMK Salafiyah, OKR tahun ajaran berjalan | ⚠️ Belum ada — wajib dibuat |
| `@existing-system-audit.md` | Dokumentasi sistem presensi lama (jika ada), pain points | ⚠️ Belum ada — wajib dibuat |
| `@dapodik-schema.md` | Referensi atribut standar DAPODIK untuk entitas Siswa | ⚠️ Unduh dari portal Kemendikbud |
| `@solution-x902-adms-spec.md` | Dokumentasi ADMS Server protocol mesin Solution X902 | ⚠️ Minta ke vendor |
| `@fonnte-api-docs.md` | Dokumentasi API Fonnte WA Gateway (endpoint, auth, rate limit) | ⚠️ Unduh dari fonnte.com |
| `@hosting-spec.md` | Spesifikasi MySQL Shared Hosting (versi MySQL, PHP, limit koneksi) | ⚠️ Minta ke provider hosting |

> **Rule:** Tidak ada agent yang boleh menulis kode sebelum semua `@mention` di atas tersedia dan di-review.

---

## FASE 2 — Constitution & Rules (`agents.md`)

> File ini adalah **hukum proyek**. Tidak ada pengecualian tanpa persetujuan tertulis dari Product Owner.

```markdown
# agents.md — Sistem Pusat Data Terpadu SMK Salafiyah

## NON-NEGOTIABLE CONSTRAINTS

### Database
- [LAW-DB-01] Penamaan tabel WAJIB bahasa Inggris singular (contoh: `student`, bukan `students`).
- [LAW-DB-02] DILARANG KERAS membuat tabel `admin`, `designer`, `desainer`, atau entitas serupa.
- [LAW-DB-03] Semua otentikasi (admin, siswa, wali kelas) WAJIB menggunakan satu tabel `user`.
- [LAW-DB-04] Setiap tabel WAJIB memiliki index pada kolom yang digunakan untuk JOIN dan WHERE.
- [LAW-DB-05] Kolom `created_at` dan `updated_at` WAJIB ada di setiap tabel.

### Security
- [LAW-SEC-01] Setiap endpoint API WAJIB memvalidasi bahwa resource yang diakses dimiliki oleh user yang autentikasi (anti-IDOR).
- [LAW-SEC-02] Semua form web WAJIB menggunakan CSRF token Laravel.
- [LAW-SEC-03] Semua output ke HTML WAJIB di-escape (Blade `{{ }}`, bukan `{!! !!}` kecuali ada justifikasi).
- [LAW-SEC-04] Kredensial (DB password, API key Fonnte, dsb.) WAJIB disimpan di `.env`, TIDAK BOLEH di-hardcode.
- [LAW-SEC-05] File `.env` dan `storage/` WAJIB masuk `.gitignore`.

### Architecture
- [LAW-ARCH-01] Logika bisnis WAJIB ada di Service Class, bukan di Controller.
- [LAW-ARCH-02] Web Controller dan API Controller WAJIB menggunakan Service Class yang sama (Logic Sharing).
- [LAW-ARCH-03] DILARANG query di dalam loop (pencegahan N+1) — wajib gunakan Eager Loading (`with()`).
- [LAW-ARCH-04] Semua response API WAJIB mengikuti format standar JSON yang telah ditetapkan.

### Mobile
- [LAW-MOB-01] Aplikasi Android native WAJIB menggunakan `HttpURLConnection` murni.
- [LAW-MOB-02] DILARANG menggunakan library jaringan pihak ketiga (Retrofit, OkHttp, Volley, dll.) di Android.

### Engineering Culture
- [LAW-ENG-01] Setiap PR wajib di-review minimal 1 developer lain.
- [LAW-ENG-02] Tidak ada feature yang merge ke `main` tanpa lolos automated test.
- [LAW-ENG-03] Setiap perubahan pada jadwal, presensi, atau data siswa WAJIB tercatat di `activity_log`.
```

---

## FASE 3 — Socratic Clarification

> Pertanyaan-pertanyaan ini WAJIB dijawab oleh Product Owner / Stakeholder SMK Salafiyah sebelum memasuki Planning Mode. Jawaban mengisi gap logika kritis yang tidak tercakup dalam brief awal.

### Blok A — Manajemen Siswa & Kelas

| ID | Pertanyaan | Mengapa Kritis | Jawaban PO |
|---|---|---|---|
| Q-A01 | Apakah seorang siswa bisa terdaftar di lebih dari satu kelas **aktif** secara bersamaan dalam satu semester? | Menentukan constraint `UNIQUE` di tabel `student_classroom`. | |
| Q-A02 | Ketika siswa mutasi pindah kelas di tengah semester, apakah data presensi lama ikut dipindah atau tetap di kelas asal? | Menentukan desain relasi `attendance` → `student_classroom`. | |
| Q-A03 | Apakah siswa yang sudah "lulus" atau "pindah sekolah" masih bisa login ke sistem? | Menentukan logika soft-delete vs. status flag di tabel `user`/`student`. | |
| Q-A04 | Siapa yang berwenang melakukan mutasi kelas — admin saja, atau wali kelas juga bisa mengajukan? | Menentukan role-based access control untuk fitur mutasi. | |

### Blok B — Presensi & Jadwal

| ID | Pertanyaan | Mengapa Kritis | Jawaban PO |
|---|---|---|---|
| Q-B01 | Jika mesin ADMS Server offline, apakah data presensi **di-queue** dan dikirim saat online kembali, atau **di-drop**? | Menentukan apakah dibutuhkan antrian (queue/job) di Laravel. | |
| Q-B02 | Apakah notifikasi WA tetap dikirim jika nomor wali murid **tidak terdaftar** di sistem? | Menentukan handling error pada integrasi Fonnte. | |
| Q-B03 | Siapa yang berwenang membuat `schedule_version` baru — hanya `admin`, atau `homeroom_teacher` juga? | Menentukan permission check di `ScheduleService`. | |
| Q-B04 | Berapa toleransi waktu antara jam masuk jadwal dengan jam absen yang masih dianggap **"tepat waktu"** (bukan terlambat)? | Menentukan logika kalkulasi status presensi (HADIR / TERLAMBAT / ALPHA). | |
| Q-B05 | Apakah hari libur nasional dan libur sekolah khusus perlu dikelola dalam sistem untuk memblokir presensi? | Menentukan apakah dibutuhkan entitas `holiday` atau `school_calendar`. | |
| Q-B06 | Bagaimana jika seorang siswa melakukan **tap dua kali** pada mesin di hari yang sama dalam satu sesi? Ambil yang pertama, terakhir, atau keduanya? | Menentukan logika de-duplikasi di `AttendanceService`. | |

### Blok C — Notifikasi & Integrasi

| ID | Pertanyaan | Mengapa Kritis | Jawaban PO |
|---|---|---|---|
| Q-C01 | Apakah notifikasi WA dikirim untuk **setiap** rekaman (datang + pulang) atau hanya salah satu? | Menentukan jumlah API call ke Fonnte per hari. | |
| Q-C02 | Apakah ada **rate limit** Fonnte yang perlu diperhatikan (misal: max X pesan/menit)? | Menentukan apakah notifikasi perlu di-queue atau bisa langsung dikirim. | |
| Q-C03 | Siapa yang menerima notifikasi jika siswa tidak memiliki wali murid terdaftar — admin, atau tidak ada notifikasi? | Menentukan fallback logic di `NotificationService`. | |

### Blok D — Activity Log & Akuntabilitas

| ID | Pertanyaan | Mengapa Kritis | Jawaban PO |
|---|---|---|---|
| Q-D01 | Apakah `activity_log` mencatat operasi **READ** (lihat data) atau hanya **WRITE** (tambah/ubah/hapus)? | Menentukan volume log dan strategi retensi data. | |
| Q-D02 | Berapa lama data `activity_log` disimpan? Apakah ada mekanisme **archiving** atau **purging** otomatis? | Mencegah tabel log membengkak dan memperlambat sistem. | |
| Q-D03 | Siapa yang bisa **melihat** activity log — semua admin, atau hanya super-admin tertentu? | Menentukan role permission untuk modul audit trail. | |

### Blok E — Scope & Bisnis

| ID | Pertanyaan | Mengapa Kritis | Jawaban PO |
|---|---|---|---|
| Q-E01 | Apakah sistem ini menggantikan sistem presensi lama **sepenuhnya** (big bang), atau **berjalan paralel** dulu? | Menentukan strategi migrasi data dan risiko downtime. | |
| Q-E02 | Apakah ada modul **nilai/rapor** yang akan diintegrasikan di fase berikutnya? | Menentukan apakah perlu desain schema yang mengakomodasi ini dari awal. | |
| Q-E03 | Berapa jumlah siswa aktif saat ini dan proyeksi 3 tahun ke depan? | Menentukan strategi indexing, pagination, dan kapasitas hosting. | |

> **Sign-off:** Semua pertanyaan di atas wajib dijawab dan ditandatangani oleh Product Owner sebelum lanjut ke Fase 4.

---

## FASE 4 — Planning Mode

> **ATURAN:** File `implementation_plan.md` WAJIB di-generate dan di-approve sebelum satu baris kode pun ditulis.

### 4.1 Executive Summary

**Visi Proyek:** Membangun sistem digital terpusat yang mengintegrasikan manajemen akademik, presensi biometrik real-time, dan notifikasi WhatsApp otomatis untuk SMK Salafiyah — menggantikan proses manual dengan platform berbasis Laravel yang aman, scalable, dan mudah dioperasikan oleh staf non-teknis.

#### 3 Poin Wajib

**A. Sumber Daya Manusia (SDM)**

| Peran | Jumlah | Tanggung Jawab Utama |
|---|---|---|
| Project Manager | 1 | Koordinasi sprint, komunikasi stakeholder, manajemen risiko |
| Backend Developer (Laravel) | 2 | API, Service Layer, ADMS Integration, Queue |
| Frontend Developer | 1 | Web Dashboard (jQuery, Swal, responsive) |
| Android Developer | 1 | Native Android app (`HttpURLConnection`) |
| Database Architect | 1 (merangkap Backend) | Schema design, indexing, query optimization |
| QA Engineer | 1 | Testing fungsional, keamanan (IDOR, XSS, CSRF), performa |
| DevOps / Sysadmin | 1 (part-time) | Deployment shared hosting, env management, .gitignore |
| **Total** | **~7 orang** | |

**B. Rencana Prototyping**

| Fase | Deliverable | Durasi | Milestone |
|---|---|---|---|
| **Prototype 0** | Wireframe low-fidelity (Figma/paper) seluruh modul | 1 minggu | Sign-off UX oleh PO |
| **Prototype 1** | Auth + manajemen user/siswa/kelas (web) | 2 minggu | Demo internal |
| **Prototype 2** | Integrasi ADMS + presensi masuk/pulang | 2 minggu | Test dengan mesin X902 nyata |
| **Prototype 3** | Notifikasi Fonnte WA + activity log | 1 minggu | Test end-to-end notifikasi |
| **Prototype 4** | Android native app (minimal viable) | 2 minggu | Test di device nyata |
| **Beta** | Semua modul terintegrasi, UAT bersama staf sekolah | 2 minggu | Sign-off PO |
| **Launch** | Deployment produksi + training staf | 1 minggu | Go-live |

**C. Estimasi Harga Pengembangan**

> Estimasi berbasis rate pasar Indonesia untuk software house, bukan freelancer lepas. Angka bersifat indikatif.

| Komponen | Estimasi Biaya |
|---|---|
| Backend Development (Laravel API + Web) | Rp 18.000.000 – Rp 25.000.000 |
| Frontend Development (Web Dashboard) | Rp 8.000.000 – Rp 12.000.000 |
| Android Native App | Rp 10.000.000 – Rp 15.000.000 |
| QA & Security Testing | Rp 5.000.000 – Rp 8.000.000 |
| Project Management & Documentation | Rp 4.000.000 – Rp 6.000.000 |
| **Total Estimasi** | **Rp 45.000.000 – Rp 66.000.000** |
| Maintenance tahunan (opsional) | Rp 8.000.000 – Rp 15.000.000/tahun |

---

### 4.2 System Architecture & Logic Sharing

#### Arsitektur Berbasis Controller (Laravel)

```
┌─────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                          │
│  Browser (Web Dashboard)    │    Android App (Native)        │
└──────────────┬──────────────┴──────────────┬────────────────┘
               │ HTTP/HTTPS                   │ HTTP/HTTPS
               ▼                              ▼
┌──────────────────────────────────────────────────────────────┐
│                     LARAVEL APPLICATION                       │
│                                                              │
│  ┌─────────────────────┐    ┌──────────────────────────┐    │
│  │   Web Controller    │    │     API Controller        │    │
│  │ (routes/web.php)    │    │  (routes/api.php)         │    │
│  └──────────┬──────────┘    └────────────┬─────────────┘    │
│             │                             │                   │
│             └──────────┬──────────────────┘                  │
│                        ▼                                      │
│              ┌─────────────────┐                             │
│              │  Service Layer  │  ← LOGIKA BISNIS TUNGGAL    │
│              │ (Logic Sharing) │                             │
│              └────────┬────────┘                             │
│                       │                                      │
│          ┌────────────┼────────────┐                         │
│          ▼            ▼            ▼                         │
│    ┌──────────┐ ┌──────────┐ ┌──────────────┐               │
│    │  Model   │ │  Queue   │ │  HTTP Client │               │
│    │(Eloquent)│ │  (Jobs)  │ │  (Guzzle)    │               │
│    └──────────┘ └──────────┘ └──────┬───────┘               │
│                                     │                        │
└─────────────────────────────────────┼────────────────────────┘
                                      │
               ┌──────────────────────┼──────────────────┐
               ▼                      ▼                   ▼
     ┌─────────────────┐   ┌─────────────────┐  ┌──────────────┐
     │  MySQL Database │   │   ADMS Server   │  │  Fonnte WA   │
     │  (Shared Host)  │   │ (Solution X902) │  │  Gateway API │
     └─────────────────┘   └─────────────────┘  └──────────────┘
```

#### Logic Sharing — Service Class Pattern

```php
// Contoh struktur Logic Sharing
// app/Services/AttendanceService.php
class AttendanceService
{
    public function recordAttendance(array $data): array
    {
        // Logika bisnis tunggal — digunakan Web & API Controller
    }
}

// app/Http/Controllers/Web/AttendanceController.php
class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $service) {}

    public function store(Request $request)
    {
        $result = $this->service->recordAttendance($request->validated());
        return redirect()->back()->with('success', $result['message']);
    }
}

// app/Http/Controllers/Api/AttendanceController.php
class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $service) {}

    public function store(Request $request)
    {
        $result = $this->service->recordAttendance($request->validated());
        return ApiResponse::success($result);  // Format JSON standar
    }
}
```

#### Pencegahan N+1 Query

```php
// ❌ DILARANG — N+1 Query
$schedules = Schedule::all();
foreach ($schedules as $schedule) {
    echo $schedule->subject->name;      // Query baru per iterasi
    echo $schedule->teacher->name;      // Query baru per iterasi
    echo $schedule->timeSlot->start;    // Query baru per iterasi
}

// ✅ WAJIB — Eager Loading
$schedules = Schedule::with([
    'subject',
    'teacher',
    'timeSlot',
    'scheduleVersion',
    'activeDay',
    'classroom.students' // Nested eager loading
])->get();
```

#### Prinsip SOLID dalam Services

| Prinsip | Implementasi |
|---|---|
| **S** — Single Responsibility | Satu Service untuk satu domain: `AttendanceService`, `ScheduleService`, `NotificationService` |
| **O** — Open/Closed | Service di-extend via interface, bukan modifikasi langsung |
| **L** — Liskov Substitution | `WaNotificationService implements NotificationInterface` — bisa diganti tanpa ubah Controller |
| **I** — Interface Segregation | `NotificationInterface` terpisah dari `AttendanceInterface` |
| **D** — Dependency Inversion | Controller bergantung pada Interface, bukan concrete class |

#### Format Standarisasi Response REST API

```json
// SUCCESS
{
  "status": "success",
  "code": 200,
  "message": "Data berhasil diambil",
  "data": { ... },
  "meta": {
    "page": 1,
    "per_page": 15,
    "total": 120,
    "last_page": 8
  }
}

// ERROR
{
  "status": "error",
  "code": 422,
  "message": "Validasi gagal",
  "errors": {
    "nis": ["NIS sudah terdaftar"],
    "email": ["Format email tidak valid"]
  },
  "data": null
}

// UNAUTHORIZED / IDOR
{
  "status": "error",
  "code": 403,
  "message": "Akses ditolak. Anda tidak memiliki izin untuk resource ini.",
  "data": null
}
```

---

### 4.3 Security Guidelines (Prioritas Utama)

#### Strategi Anti-IDOR

```php
// ❌ RAWAN IDOR — Menggunakan ID langsung
public function show($id)
{
    $student = Student::findOrFail($id);  // Siapapun bisa akses ID manapun
    return response()->json($student);
}

// ✅ AMAN — Validasi kepemilikan resource
public function show($id)
{
    $student = Student::findOrFail($id);

    // Untuk role homeroom_teacher: hanya bisa akses siswa di kelasnya
    if (auth()->user()->role === 'homeroom_teacher') {
        $this->authorize('view', $student); // Policy check
    }

    return ApiResponse::success($student);
}

// app/Policies/StudentPolicy.php
public function view(User $user, Student $student): bool
{
    if ($user->role === 'admin') return true;

    if ($user->role === 'homeroom_teacher') {
        return $student->classrooms()
            ->where('homeroom_teacher_id', $user->id)
            ->exists();
    }

    if ($user->role === 'student') {
        return $student->user_id === $user->id;
    }

    return false;
}
```

#### Standar Keamanan Lengkap

| Ancaman | Mitigasi |
|---|---|
| **IDOR** | Laravel Policy + Gate, validasi kepemilikan resource di setiap endpoint |
| **CSRF** | Middleware `VerifyCsrfToken` aktif untuk semua route web, token di setiap form |
| **XSS** | Blade `{{ }}` untuk semua output, Content Security Policy header, sanitasi input |
| **SQL Injection** | Eloquent ORM + Query Builder dengan parameter binding, hindari raw query |
| **Brute Force** | Rate limiting (`throttle:6,1` pada login), lockout setelah 5 gagal |
| **Mass Assignment** | `$fillable` eksplisit di setiap Model, hindari `$guarded = []` |
| **Credential Leak** | `.env` di `.gitignore`, rotate secret key secara berkala |
| **Session Hijacking** | `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true` di produksi |
| **Shared Hosting Risk** | Pastikan `public/` adalah document root, file `.env` di luar `public/` |

#### Strategi `.env` & `.gitignore`

```bash
# .gitignore — WAJIB DISERTAKAN
.env
.env.*
!.env.example
storage/
bootstrap/cache/
vendor/
node_modules/
*.log
*.key
```

```dotenv
# .env.example — Template aman yang di-commit ke repo
APP_NAME="Sistem Pusat Data SMK Salafiyah"
APP_ENV=production
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

ADMS_SERVER_URL=
ADMS_SECRET_KEY=

FONNTE_API_URL=https://api.fonnte.com/send
FONNTE_TOKEN=

QUEUE_CONNECTION=database
```

---

### 4.4 UI/UX & Frontend Implementation

#### Pedoman UI

| Aspek | Standar |
|---|---|
| **Warna** | Gunakan palet kontras tinggi (WCAG AA minimum: rasio 4.5:1 untuk teks normal) |
| **Tipografi** | Font tunggal, maksimal 2 ukuran heading utama, body 14–16px |
| **Konsistensi** | Satu desain tombol primer, satu desain tombol sekunder — tidak boleh campur-campur |
| **White Space** | Padding minimal 16px antar elemen, hindari kepadatan informasi berlebih |
| **Ikon** | Gunakan icon ringan gratis: Phosphor Icons atau Tabler Icons (SVG sprite) |

#### Pedoman UX & Aksesibilitas (WAI-ARIA)

```html
<!-- Contoh implementasi WAI-ARIA -->
<button
  id="btn-submit-attendance"
  aria-label="Simpan data presensi"
  aria-busy="false"
  class="btn-primary"
>
  Simpan
</button>

<!-- Saat loading -->
<button aria-busy="true" aria-label="Menyimpan data..." disabled>
  <span class="spinner" aria-hidden="true"></span>
  Menyimpan...
</button>

<!-- Tabel presensi aksesibel -->
<table role="grid" aria-label="Data presensi kelas X RPL 1">
  <thead>
    <tr>
      <th scope="col" aria-sort="ascending">Nama Siswa</th>
      <th scope="col">Jam Masuk</th>
      <th scope="col">Status</th>
    </tr>
  </thead>
</table>
```

#### Spesifikasi Teknis Frontend

```javascript
// Pattern: Button → Spinner saat submit
$('#form-attendance').on('submit', function(e) {
    e.preventDefault();
    const $btn = $(this).find('[type="submit"]');

    // Ganti tombol dengan spinner
    $btn.prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm"></span> Menyimpan...')
        .attr('aria-busy', 'true');

    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: $(this).serialize(),
        success: function(res) {
            Toast.fire({ icon: 'success', title: res.message });
            // Reset tombol
            $btn.prop('disabled', false)
                .html('Simpan')
                .attr('aria-busy', 'false');
        },
        error: function(xhr) {
            Swal.fire('Gagal', xhr.responseJSON.message, 'error');
            $btn.prop('disabled', false)
                .html('Simpan')
                .attr('aria-busy', 'false');
        }
    });
});

// Page loader saat perpindahan halaman
$(document).on('click', 'a[data-page-link]', function() {
    $('#page-loader').fadeIn(200);
});
$(window).on('load', function() {
    $('#page-loader').fadeOut(300);
});
```

| Fitur | Implementasi |
|---|---|
| Toast notification | SweetAlert2 Toast (posisi top-right, auto-dismiss 3 detik) |
| Konfirmasi hapus | SweetAlert2 modal konfirmasi dengan teks eksplisit |
| Tabel advanced | jQuery DataTables dengan server-side processing + advanced filter |
| Infinite scroll | Intersection Observer API atau jQuery plugin pada list view |
| Lazy loading | `loading="lazy"` pada gambar, dynamic import untuk modul besar |
| Responsive | CSS Grid + Flexbox, breakpoint mobile 768px, tablet 1024px |
| Page loader | Overlay fullscreen dengan spinner saat navigasi antar halaman |

---

### 4.5 Functional & Technical Constraints

#### Hak Akses (Role)

| Fitur | `admin` | `homeroom_teacher` | `student` |
|---|---|---|---|
| Manajemen user | ✅ Full | ❌ | ❌ |
| Manajemen siswa & kelas | ✅ Full | 👁️ Read (kelas sendiri) | 👁️ Read (data sendiri) |
| Mutasi siswa | ✅ Full | ❌ | ❌ |
| Jadwal & schedule version | ✅ Full | 👁️ Read | 👁️ Read |
| Data presensi | ✅ Full | 👁️ Read (kelas sendiri) | 👁️ Read (data sendiri) |
| Activity log | ✅ Full | ❌ | ❌ |
| Notifikasi WA (trigger) | ✅ Otomatis via sistem | ✅ Otomatis via sistem | ❌ |

#### Modul Presensi & Fonnte WA — Alur Data

```
Siswa tap kartu di X902
        │
        ▼
  ADMS Server (push ke endpoint Laravel)
        │
        ▼
  POST /api/v1/attendance/push  ← Endpoint khusus ADMS, auth via secret key
        │
        ▼
  AttendanceService::processAdmsPush()
   ├── Validasi data (NIS, timestamp, mesin ID)
   ├── Tentukan tipe: DATANG atau PULANG (berdasarkan schedule version aktif)
   ├── Hitung status: HADIR / TERLAMBAT / ALPHA
   ├── Simpan ke tabel `attendance`
   ├── Catat ke `activity_log`
   └── Dispatch Job: SendWaNotificationJob
              │
              ▼
        Queue Worker
              │
              ▼
        NotificationService::sendViaFonnte()
         ├── Ambil nomor wali murid dari tabel `student`
         ├── Build pesan WhatsApp
         └── POST ke api.fonnte.com/send
```

#### Penjadwalan Dinamis & Versioning

```
Tahun Ajaran (academic_year)
  └── Semester
        └── Schedule Version  ← VERSI AKTIF (bisa berganti mid-semester)
              └── Schedule
                    ├── Subject (Mata Pelajaran)
                    ├── Teacher (Guru)
                    ├── TimeSlot (Jam mulai–selesai)
                    ├── Classroom (Kelas)
                    └── Active Day (Senin–Sabtu)
```

Logika penentuan jam **PULANG** berdasarkan versi aktif:
1. Ambil `schedule_version` yang aktif pada tanggal presensi
2. Cari `schedule` untuk kelas siswa di hari tersebut
3. Ambil `time_slot` terakhir (jam pelajaran terakhir)
4. Jam pulang = `time_slot.end_time` + toleransi (konfigurasi)

---

## FASE 5 — Task Breakdown

### 5.1 `implementation_plan.md` Template

> File ini harus di-generate dan di-commit ke repo sebelum sprint pertama dimulai.

```markdown
# implementation_plan.md

## Sprint 0 — Foundation (Minggu 1-2)
### Paralel Track A: Infrastructure
- [ ] Setup repo Git + branch strategy (main, develop, feature/*)
- [ ] Konfigurasi .env.example dan .gitignore
- [ ] Setup Laravel 13 + PHP 8.3 di hosting
- [ ] Setup database MySQL + user permissions

### Paralel Track B: Design
- [ ] Wireframe low-fidelity semua modul (Figma)
- [ ] Jawaban final Socratic Clarification dari PO
- [ ] Finalisasi schema database + indexing

## Sprint 1 — Core Auth & Master Data (Minggu 3-4)
### Paralel Track A: Backend
- [ ] Tabel: user, student, classroom, student_classroom
- [ ] AuthService + JWT/Sanctum token
- [ ] CRUD API: student, classroom
- [ ] Role middleware (admin, homeroom_teacher, student)

### Paralel Track B: Frontend
- [ ] Layout web dashboard (sidebar, navbar, page loader)
- [ ] Halaman login + validasi
- [ ] Halaman manajemen siswa (tabel + advanced filter)
- [ ] Halaman manajemen kelas

## Sprint 2 — Jadwal & Schedule Versioning (Minggu 5-6)
- [ ] Tabel: academic_year, semester, subject, teacher, time_slot, schedule, schedule_version, active_day
- [ ] ScheduleService + versioning logic
- [ ] API + Web CRUD jadwal
- [ ] UI jadwal (kalender view / tabel view)

## Sprint 3 — Presensi & ADMS Integration (Minggu 7-8)
- [ ] Tabel: attendance
- [ ] Endpoint /api/v1/attendance/push (ADMS webhook)
- [ ] AttendanceService (logika datang/pulang/status)
- [ ] Test integrasi dengan mesin X902 nyata
- [ ] UI laporan presensi per kelas per hari

## Sprint 4 — Notifikasi & Activity Log (Minggu 9)
- [ ] Tabel: activity_log
- [ ] Queue + SendWaNotificationJob
- [ ] NotificationService + Fonnte integration
- [ ] ActivityLogService (observer pattern)
- [ ] UI activity log (filter by user, action, date)

## Sprint 5 — Android App (Minggu 7-10, paralel)
- [ ] Android project setup (tanpa library jaringan)
- [ ] HttpURLConnection wrapper class
- [ ] Screen: login, dashboard, presensi pribadi
- [ ] Test di device nyata

## Sprint 6 — QA, Security & Launch (Minggu 11-13)
- [ ] Security audit: IDOR, CSRF, XSS, SQL injection
- [ ] Performance test: load testing query jadwal kompleks
- [ ] UAT bersama staf SMK Salafiyah
- [ ] Training staf
- [ ] Go-live
```

---

### 5.2 Task Breakdown Atomic

#### Modul: Auth & User

| ID | Task | Assignee | Estimasi | Testable? |
|---|---|---|---|---|
| T-AUTH-01 | Buat migration tabel `user` dengan field role | Backend Dev 1 | 2 jam | ✅ Migration runs + rollback |
| T-AUTH-02 | Implementasi `AuthService::login()` + token Sanctum | Backend Dev 1 | 4 jam | ✅ Unit test login valid/invalid |
| T-AUTH-03 | Middleware `RoleMiddleware` untuk 3 role | Backend Dev 1 | 3 jam | ✅ Test akses dengan role berbeda |
| T-AUTH-04 | API endpoint POST `/api/v1/auth/login` | Backend Dev 1 | 2 jam | ✅ Postman test |
| T-AUTH-05 | Halaman login web + spinner | Frontend Dev | 3 jam | ✅ Visual + form submit |

#### Modul: Presensi

| ID | Task | Assignee | Estimasi | Testable? |
|---|---|---|---|---|
| T-ATT-01 | Migration tabel `attendance` + index | Backend Dev 2 | 2 jam | ✅ Migration runs |
| T-ATT-02 | `AttendanceService::processAdmsPush()` | Backend Dev 2 | 6 jam | ✅ Unit test semua kasus status |
| T-ATT-03 | Endpoint POST `/api/v1/attendance/push` + auth secret | Backend Dev 2 | 3 jam | ✅ Test dengan payload ADMS simulasi |
| T-ATT-04 | `SendWaNotificationJob` + queue dispatch | Backend Dev 1 | 4 jam | ✅ Queue processed + Fonnte mock |
| T-ATT-05 | UI laporan presensi harian per kelas | Frontend Dev | 5 jam | ✅ Data tampil, filter berfungsi |

#### Modul: Jadwal

| ID | Task | Assignee | Estimasi | Testable? |
|---|---|---|---|---|
| T-SCH-01 | Migration 7 tabel jadwal + foreign key + index | Backend Dev 2 | 3 jam | ✅ Migration runs |
| T-SCH-02 | `ScheduleService::getActiveVersion()` | Backend Dev 2 | 3 jam | ✅ Unit test dengan multiple versi |
| T-SCH-03 | Eager loading jadwal kompleks (N+1 prevention) | Backend Dev 2 | 2 jam | ✅ Laravel Debugbar: 0 N+1 |
| T-SCH-04 | API CRUD schedule_version | Backend Dev 2 | 4 jam | ✅ Postman test |
| T-SCH-05 | UI manajemen jadwal (tabel + form) | Frontend Dev | 6 jam | ✅ CRUD berfungsi |

---

## Lampiran A — Database & API Blueprint

### Schema Tabel Relasional

#### `user`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT UNSIGNED PK AI | |
| `name` | VARCHAR(100) | |
| `email` | VARCHAR(150) UNIQUE | Index |
| `password` | VARCHAR(255) | Bcrypt |
| `role` | ENUM('admin','student','homeroom_teacher') | Index |
| `is_active` | TINYINT(1) DEFAULT 1 | |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

#### `student`
> Atribut mengacu pada standar DAPODIK

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT UNSIGNED PK AI | |
| `user_id` | BIGINT UNSIGNED FK → user.id | Index |
| `nis` | VARCHAR(20) UNIQUE | Index — NIS lokal |
| `nisn` | VARCHAR(10) UNIQUE | Index — NISN Dapodik |
| `name` | VARCHAR(100) | |
| `gender` | ENUM('L','P') | |
| `birth_place` | VARCHAR(100) | |
| `birth_date` | DATE | |
| `religion` | VARCHAR(30) | |
| `address` | TEXT | |
| `phone` | VARCHAR(20) | |
| `parent_name` | VARCHAR(100) | Wali murid |
| `parent_phone` | VARCHAR(20) | Index — untuk notifikasi WA |
| `status` | ENUM('active','graduated','transferred','dropped') | Index |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

#### `classroom`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT UNSIGNED PK AI | |
| `name` | VARCHAR(50) | Contoh: "X RPL 1" |
| `grade` | TINYINT | 10, 11, atau 12 |
| `homeroom_teacher_id` | BIGINT UNSIGNED FK → teacher.id | Index |
| `academic_year_id` | BIGINT UNSIGNED FK → academic_year.id | Index |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

#### `student_classroom` (Pivot)
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT UNSIGNED PK AI | |
| `student_id` | BIGINT UNSIGNED FK → student.id | Index |
| `classroom_id` | BIGINT UNSIGNED FK → classroom.id | Index |
| `joined_at` | DATE | Tanggal masuk kelas |
| `left_at` | DATE NULL | Tanggal keluar (mutasi) |
| `mutation_reason` | ENUM('active','grade_up','transferred','graduated') | |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| **INDEX** | UNIQUE(`student_id`, `classroom_id`, `joined_at`) | Cegah duplikasi |

#### `academic_year`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT UNSIGNED PK AI | |
| `name` | VARCHAR(20) | Contoh: "2025/2026" |
| `start_date` | DATE | |
| `end_date` | DATE | |
| `is_active` | TINYINT(1) DEFAULT 0 | Index |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

#### `semester`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT UNSIGNED PK AI | |
| `academic_year_id` | BIGINT UNSIGNED FK → academic_year.id | Index |
| `name` | ENUM('ganjil','genap') | |
| `start_date` | DATE | |
| `end_date` | DATE | |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

#### `subject`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT UNSIGNED PK AI | |
| `name` | VARCHAR(100) | |
| `code` | VARCHAR(20) UNIQUE | |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

#### `teacher`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT UNSIGNED PK AI | |
| `user_id` | BIGINT UNSIGNED FK → user.id | Index |
| `nip` | VARCHAR(30) UNIQUE NULL | NIP ASN |
| `name` | VARCHAR(100) | |
| `phone` | VARCHAR(20) | |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

#### `time_slot`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT UNSIGNED PK AI | |
| `name` | VARCHAR(30) | Contoh: "Jam ke-1" |
| `start_time` | TIME | |
| `end_time` | TIME | |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

#### `schedule_version`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT UNSIGNED PK AI | |
| `semester_id` | BIGINT UNSIGNED FK → semester.id | Index |
| `name` | VARCHAR(100) | Contoh: "Jadwal Ganjil Rev.2" |
| `effective_date` | DATE | Index — tanggal mulai berlaku |
| `is_active` | TINYINT(1) DEFAULT 0 | Index |
| `created_by` | BIGINT UNSIGNED FK → user.id | |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

#### `active_day`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT UNSIGNED PK AI | |
| `schedule_version_id` | BIGINT UNSIGNED FK → schedule_version.id | Index |
| `day` | ENUM('monday','tuesday','wednesday','thursday','friday','saturday') | Index |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

#### `schedule`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT UNSIGNED PK AI | |
| `schedule_version_id` | BIGINT UNSIGNED FK → schedule_version.id | Index |
| `classroom_id` | BIGINT UNSIGNED FK → classroom.id | Index |
| `subject_id` | BIGINT UNSIGNED FK → subject.id | |
| `teacher_id` | BIGINT UNSIGNED FK → teacher.id | |
| `time_slot_id` | BIGINT UNSIGNED FK → time_slot.id | |
| `day` | ENUM('monday','tuesday','wednesday','thursday','friday','saturday') | Index |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| **INDEX** | COMPOSITE(`schedule_version_id`, `classroom_id`, `day`) | Query optimization |

#### `attendance`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT UNSIGNED PK AI | |
| `student_id` | BIGINT UNSIGNED FK → student.id | Index |
| `classroom_id` | BIGINT UNSIGNED FK → classroom.id | Index |
| `schedule_version_id` | BIGINT UNSIGNED FK → schedule_version.id | Index |
| `date` | DATE | Index |
| `check_in` | DATETIME NULL | |
| `check_out` | DATETIME NULL | |
| `status` | ENUM('present','late','absent','excused') | Index |
| `device_id` | VARCHAR(50) | ID mesin X902 |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| **INDEX** | COMPOSITE(`student_id`, `date`) | Query harian per siswa |
| **INDEX** | COMPOSITE(`classroom_id`, `date`) | Query harian per kelas |

#### `activity_log`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT UNSIGNED PK AI | |
| `user_id` | BIGINT UNSIGNED FK → user.id NULL | NULL jika sistem |
| `action` | VARCHAR(50) | Contoh: "create", "update", "delete" |
| `module` | VARCHAR(50) | Contoh: "student", "attendance", "schedule" |
| `record_id` | BIGINT UNSIGNED NULL | ID record yang diubah |
| `old_value` | JSON NULL | Nilai sebelum perubahan |
| `new_value` | JSON NULL | Nilai setelah perubahan |
| `ip_address` | VARCHAR(45) | |
| `user_agent` | VARCHAR(255) | |
| `created_at` | TIMESTAMP | Index |
| **INDEX** | `user_id` | |
| **INDEX** | COMPOSITE(`module`, `record_id`) | |

---

### API Endpoints Blueprint

#### Auth

| Method | Endpoint | Role | Deskripsi |
|---|---|---|---|
| POST | `/api/v1/auth/login` | Public | Login, return token |
| POST | `/api/v1/auth/logout` | All | Revoke token |
| GET | `/api/v1/auth/me` | All | Info user aktif |

#### Student

| Method | Endpoint | Role | Deskripsi |
|---|---|---|---|
| GET | `/api/v1/students` | admin | List semua siswa + filter + paginate |
| POST | `/api/v1/students` | admin | Tambah siswa baru |
| GET | `/api/v1/students/{id}` | admin, homeroom_teacher* | Detail siswa |
| PUT | `/api/v1/students/{id}` | admin | Update data siswa |
| DELETE | `/api/v1/students/{id}` | admin | Soft delete siswa |
| POST | `/api/v1/students/{id}/mutate` | admin | Mutasi naik kelas / pindah / lulus |

#### Classroom

| Method | Endpoint | Role | Deskripsi |
|---|---|---|---|
| GET | `/api/v1/classrooms` | admin, homeroom_teacher | List kelas |
| POST | `/api/v1/classrooms` | admin | Tambah kelas |
| GET | `/api/v1/classrooms/{id}/students` | admin, homeroom_teacher* | Daftar siswa di kelas |

#### Schedule

| Method | Endpoint | Role | Deskripsi |
|---|---|---|---|
| GET | `/api/v1/schedule-versions` | admin | List semua versi jadwal |
| POST | `/api/v1/schedule-versions` | admin | Buat versi jadwal baru |
| PUT | `/api/v1/schedule-versions/{id}/activate` | admin | Aktifkan versi jadwal |
| GET | `/api/v1/schedules` | All | Jadwal aktif saat ini |
| POST | `/api/v1/schedules` | admin | Tambah entry jadwal |

#### Attendance

| Method | Endpoint | Role | Deskripsi |
|---|---|---|---|
| POST | `/api/v1/attendance/push` | ADMS (secret key) | Push data dari mesin X902 |
| GET | `/api/v1/attendance` | admin, homeroom_teacher* | List presensi + filter tanggal/kelas |
| GET | `/api/v1/attendance/my` | student | Presensi pribadi siswa |
| GET | `/api/v1/attendance/report` | admin, homeroom_teacher* | Rekap presensi |

#### Activity Log

| Method | Endpoint | Role | Deskripsi |
|---|---|---|---|
| GET | `/api/v1/activity-logs` | admin | List log + filter user/module/tanggal |

> `*` = homeroom_teacher hanya bisa akses data kelas yang dia pegang (anti-IDOR via Policy)

---

*Dokumen ini adalah living document. Setiap perubahan wajib di-review oleh Product Owner dan dicatat dengan versi baru.*
