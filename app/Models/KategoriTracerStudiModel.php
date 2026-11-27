<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model Master Kategori Pertanyaan Tracer Study (The Graduate Web).
 *
 * Menyimpan daftar kategori terpusat untuk Bank Pertanyaan maupun Form Aktif Tracer Study Awal,
 * lengkap dengan validasi keunikan nama, sinkronisasi ubah nama (cascade), dan proteksi hapus
 * terhadap kategori yang masih dipakai oleh pertanyaan.
 */
class KategoriTracerStudiModel extends Model
{
    public const DEFAULT_KATEGORI = '';
    public const MAX_NAMA_LENGTH  = 80;
    public const MAX_DESC_LENGTH  = 500;

    protected $table            = 'kategori_tracer_studi';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'nama_kategori',
        'deskripsi',
        'is_default',
        'created_by',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    /**
     * Daftar seed kategori awal (selaras dengan PRESET_KATEGORI pada Bank Pertanyaan).
     *
     * @return array<int, array{nama_kategori: string, deskripsi: string, is_default: int}>
     */
    public static function defaultSeeds(): array
    {
        return [
            [
                'nama_kategori' => 'Status & Pekerjaan',
                'deskripsi'     => 'Pertanyaan mengenai status kelulusan, aktivitas saat ini, instansi, dan jalur memperoleh kerja.',
                'is_default'    => 0,
            ],
            [
                'nama_kategori' => 'Masa Tunggu & Gaji',
                'deskripsi'     => 'Pertanyaan terkait masa tunggu memperoleh pekerjaan pertama serta kisaran penghasilan.',
                'is_default'    => 0,
            ],
            [
                'nama_kategori' => 'Relevansi & Kompetensi',
                'deskripsi'     => 'Pertanyaan kesesuaian bidang kerja dengan program studi, kurikulum, dan kompetensi lulusan.',
                'is_default'    => 0,
            ],
            [
                'nama_kategori' => 'Evaluasi Layanan & Kurikulum',
                'deskripsi'     => 'Penilaian kepuasan layanan pembimbingan, administrasi akademik, serta dukungan karier prodi/fakultas.',
                'is_default'    => 0,
            ],
            [
                'nama_kategori' => 'Saran & Masukan',
                'deskripsi'     => 'Masukan terbuka dari calon wisudawan untuk pengembangan program studi dan layanan fakultas.',
                'is_default'    => 0,
            ],
        ];
    }

    /**
     * Memeriksa apakah tabel kategori_tracer_studi sudah tersedia di database.
     */
    public function isTableReady(): bool
    {
        try {
            return isset($this->db) && $this->db->tableExists($this->table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Merapikan spasi berlebih pada nama kategori tanpa mengubah huruf kapital.
     */
    public static function cleanNamaKategori(string $nama): string
    {
        return preg_replace('/\s+/u', ' ', trim($nama)) ?? trim($nama);
    }

    /**
     * Kunci pembanding nama kategori secara case-insensitive.
     */
    public static function normalizeKey(string $nama): string
    {
        $clean = self::cleanNamaKategori($nama);

        return function_exists('mb_strtolower')
            ? mb_strtolower($clean, 'UTF-8')
            : strtolower($clean);
    }

    /**
     * Tidak ada kategori yang dikunci permanen sebagai bawaan sistem; semua kategori mengikuti proteksi pemakaian.
     *
     * @param array<string, mixed> $row
     */
    public static function isDefaultKategoriRow(array $row): bool
    {
        return false;
    }

    /**
     * Mengambil daftar baris Master Kategori (atau fallback preset jika tabel belum dimigrasi).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDaftarKategori(?string $keyword = null): array
    {
        $keywordFilter = trim((string) $keyword);

        if (! $this->isTableReady()) {
            $rows = [];
            foreach (self::defaultSeeds() as $idx => $seed) {
                $row = [
                    'id'            => $idx + 1,
                    'nama_kategori' => $seed['nama_kategori'],
                    'deskripsi'     => $seed['deskripsi'],
                    'is_default'    => 0,
                    'created_by'    => 'Sistem',
                ];
                if ($keywordFilter !== '') {
                    $haystack = strtolower($row['nama_kategori'] . ' ' . $row['deskripsi']);
                    if (! str_contains($haystack, strtolower($keywordFilter))) {
                        continue;
                    }
                }
                $rows[] = $row;
            }

            return $rows;
        }

        $builder = $this->builder();
        if ($keywordFilter !== '') {
            $builder->groupStart()
                ->like('nama_kategori', $keywordFilter)
                ->orLike('deskripsi', $keywordFilter)
                ->groupEnd();
        }

        return $builder
            ->orderBy('nama_kategori', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Mengambil statistik pemakaian setiap kategori pada Bank Pertanyaan dan Form Pertanyaan Aktif.
     *
     * @return array{bank: array<string, int>, aktif: array<string, int>}
     */
    public function getUsageCountsMap(): array
    {
        $bankCounts  = [];
        $aktifCounts = [];

        if (! isset($this->db)) {
            return ['bank' => $bankCounts, 'aktif' => $aktifCounts];
        }

        if ($this->db->tableExists('bank_pertanyaan_tracer_studi') && $this->db->fieldExists('kategori', 'bank_pertanyaan_tracer_studi')) {
            $bankRows = $this->db->table('bank_pertanyaan_tracer_studi')
                ->select('kategori')
                ->get()
                ->getResultArray();

            foreach ($bankRows as $row) {
                $raw = trim((string) ($row['kategori'] ?? ''));
                if ($raw === '') {
                    continue;
                }
                $key              = self::normalizeKey($raw);
                $bankCounts[$key] = ($bankCounts[$key] ?? 0) + 1;
            }
        }

        if ($this->db->tableExists('pertanyaan_tracer_studi_awal') && $this->db->fieldExists('kategori', 'pertanyaan_tracer_studi_awal')) {
            $aktifRows = $this->db->table('pertanyaan_tracer_studi_awal')
                ->select('kategori')
                ->get()
                ->getResultArray();

            foreach ($aktifRows as $row) {
                $raw = trim((string) ($row['kategori'] ?? ''));
                if ($raw === '') {
                    continue;
                }
                $key               = self::normalizeKey($raw);
                $aktifCounts[$key] = ($aktifCounts[$key] ?? 0) + 1;
            }
        }

        return [
            'bank'  => $bankCounts,
            'aktif' => $aktifCounts,
        ];
    }

    /**
     * Mengambil daftar kategori beserta hitungan pemakaian di Form Aktif dan Bank Pertanyaan.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDaftarKategoriWithUsageCounts(?string $keyword = null): array
    {
        $rows   = $this->getDaftarKategori($keyword);
        $counts = $this->getUsageCountsMap();

        foreach ($rows as &$row) {
            $key          = self::normalizeKey((string) ($row['nama_kategori'] ?? ''));
            $jumlahBank   = (int) ($counts['bank'][$key] ?? 0);
            $jumlahAktif  = (int) ($counts['aktif'][$key] ?? 0);
            $totalDipakai = $jumlahBank + $jumlahAktif;

            $row['jumlah_bank']     = $jumlahBank;
            $row['jumlah_aktif']    = $jumlahAktif;
            $row['total_digunakan'] = $totalDipakai;
            $row['is_protected']    = false;
            $row['can_delete']      = $totalDipakai === 0;
        }
        unset($row);

        return $rows;
    }

    /**
     * Mengambil daftar nama kategori terurut untuk pilihan dropdown pada modul Tracer Study.
     *
     * @return string[]
     */
    public function getNamaKategoriList(bool $includeExistingOrphans = true): array
    {
        $categories = [];
        $seenKeys   = [];

        $appendUnique = static function (string $name) use (&$categories, &$seenKeys): void {
            $clean = self::cleanNamaKategori($name);
            if ($clean === '') {
                return;
            }
            $key = self::normalizeKey($clean);
            if (! isset($seenKeys[$key])) {
                $seenKeys[$key] = true;
                $categories[]   = $clean;
            }
        };

        if ($this->isTableReady()) {
            $rows = $this->builder()
                ->select('nama_kategori')
                ->orderBy('nama_kategori', 'ASC')
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $appendUnique((string) ($row['nama_kategori'] ?? ''));
            }
        } else {
            foreach (BankPertanyaanTracerStudiModel::PRESET_KATEGORI as $preset) {
                $appendUnique($preset);
            }
        }

        if ($includeExistingOrphans && isset($this->db)) {
            if ($this->db->tableExists('bank_pertanyaan_tracer_studi') && $this->db->fieldExists('kategori', 'bank_pertanyaan_tracer_studi')) {
                $bankRows = $this->db->table('bank_pertanyaan_tracer_studi')->select('kategori')->distinct()->get()->getResultArray();
                foreach ($bankRows as $row) {
                    $appendUnique((string) ($row['kategori'] ?? ''));
                }
            }

            if ($this->db->tableExists('pertanyaan_tracer_studi_awal') && $this->db->fieldExists('kategori', 'pertanyaan_tracer_studi_awal')) {
                $aktifRows = $this->db->table('pertanyaan_tracer_studi_awal')->select('kategori')->distinct()->get()->getResultArray();
                foreach ($aktifRows as $row) {
                    $appendUnique((string) ($row['kategori'] ?? ''));
                }
            }
        }

        return $categories;
    }

    /**
     * Mencari baris Master Kategori berdasarkan nama secara case-insensitive.
     *
     * @return ?array<string, mixed>
     */
    public function findByNama(string $nama): ?array
    {
        if (! $this->isTableReady()) {
            return null;
        }

        $targetKey = self::normalizeKey($nama);
        if ($targetKey === '') {
            return null;
        }

        foreach ($this->findAll() as $row) {
            if (self::normalizeKey((string) ($row['nama_kategori'] ?? '')) === $targetKey) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Memeriksa apakah nama kategori sudah terdaftar pada baris lain.
     */
    public function existsNamaKategori(string $nama, ?int $excludeId = null): bool
    {
        if (! $this->isTableReady()) {
            return false;
        }

        $targetKey = self::normalizeKey($nama);
        if ($targetKey === '') {
            return false;
        }

        foreach ($this->findAll() as $row) {
            $rowId = (int) ($row['id'] ?? 0);
            if ($excludeId !== null && $rowId === $excludeId) {
                continue;
            }
            if (self::normalizeKey((string) ($row['nama_kategori'] ?? '')) === $targetKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * Memvalidasi payload tambah/ubah kategori Tracer Study.
     *
     * @param array<string, mixed> $input
     *
     * @return array{errors: string[], data: array<string, mixed>}
     */
    public static function validateKategoriPayload(array $input): array
    {
        $errors       = [];
        $namaKategori = self::cleanNamaKategori((string) ($input['nama_kategori'] ?? ''));
        $deskripsi    = trim((string) ($input['deskripsi'] ?? ''));

        if ($namaKategori === '') {
            $errors[] = 'Nama kategori wajib diisi.';
        } elseif (mb_strlen($namaKategori) > self::MAX_NAMA_LENGTH) {
            $errors[] = sprintf('Nama kategori maksimal %d karakter.', self::MAX_NAMA_LENGTH);
        }

        if ($deskripsi !== '' && mb_strlen($deskripsi) > self::MAX_DESC_LENGTH) {
            $errors[] = sprintf('Deskripsi kategori maksimal %d karakter.', self::MAX_DESC_LENGTH);
        }

        return [
            'errors' => $errors,
            'data'   => [
                'nama_kategori' => $namaKategori,
                'deskripsi'     => $deskripsi !== '' ? $deskripsi : null,
            ],
        ];
    }

    /**
     * Menyimpan kategori Tracer Study baru.
     *
     * @param array<string, mixed> $input
     *
     * @return array{success: bool, errors: string[], id: ?int, data: ?array<string, mixed>}
     */
    public function createKategori(array $input, ?string $createdBy = null): array
    {
        $validation = self::validateKategoriPayload($input);
        if ($validation['errors'] !== []) {
            return [
                'success' => false,
                'errors'  => $validation['errors'],
                'id'      => null,
                'data'    => null,
            ];
        }

        if (! $this->isTableReady()) {
            return [
                'success' => false,
                'errors'  => ['Tabel kategori_tracer_studi belum tersedia. Jalankan migrasi terlebih dahulu.'],
                'id'      => null,
                'data'    => null,
            ];
        }

        $data = $validation['data'];
        if ($this->existsNamaKategori($data['nama_kategori'])) {
            return [
                'success' => false,
                'errors'  => [sprintf('Kategori "%s" sudah terdaftar.', $data['nama_kategori'])],
                'id'      => null,
                'data'    => null,
            ];
        }

        $data['is_default'] = 0;
        $data['created_by'] = $createdBy !== null && trim($createdBy) !== '' ? mb_substr(trim($createdBy), 0, 100) : null;

        $insertedId = $this->insert($data, true);
        if (! $insertedId) {
            return [
                'success' => false,
                'errors'  => ['Gagal menyimpan kategori Tracer Study baru.'],
                'id'      => null,
                'data'    => null,
            ];
        }

        $data['id'] = (int) $insertedId;

        return [
            'success' => true,
            'errors'  => [],
            'id'      => (int) $insertedId,
            'data'    => $data,
        ];
    }

    /**
     * Memperbarui kategori dan menyinkronkan perubahan nama ke Bank Pertanyaan maupun Form Aktif.
     *
     * @param array<string, mixed> $input
     *
     * @return array{success: bool, errors: string[], id: ?int, data: ?array<string, mixed>, notFound: bool, cascadeBankCount: int, cascadeAktifCount: int}
     */
    public function updateKategori(int $id, array $input): array
    {
        if (! $this->isTableReady()) {
            return [
                'success'           => false,
                'errors'            => ['Tabel kategori_tracer_studi belum tersedia. Jalankan migrasi terlebih dahulu.'],
                'id'                => null,
                'data'              => null,
                'notFound'          => false,
                'cascadeBankCount'  => 0,
                'cascadeAktifCount' => 0,
            ];
        }

        $existing = $id > 0 ? $this->find($id) : null;
        if (! is_array($existing)) {
            return [
                'success'           => false,
                'errors'            => ['Kategori tidak ditemukan atau sudah dihapus.'],
                'id'                => null,
                'data'              => null,
                'notFound'          => true,
                'cascadeBankCount'  => 0,
                'cascadeAktifCount' => 0,
            ];
        }

        $oldName    = (string) ($existing['nama_kategori'] ?? '');
        $validation = self::validateKategoriPayload($input);
        if ($validation['errors'] !== []) {
            return [
                'success'           => false,
                'errors'            => $validation['errors'],
                'id'                => $id,
                'data'              => null,
                'notFound'          => false,
                'cascadeBankCount'  => 0,
                'cascadeAktifCount' => 0,
            ];
        }

        $data = $validation['data'];

        if ($this->existsNamaKategori($data['nama_kategori'], $id)) {
            return [
                'success'           => false,
                'errors'            => [sprintf('Kategori "%s" sudah digunakan oleh data lain.', $data['nama_kategori'])],
                'id'                => $id,
                'data'              => null,
                'notFound'          => false,
                'cascadeBankCount'  => 0,
                'cascadeAktifCount' => 0,
            ];
        }

        $cascadeBankCount  = 0;
        $cascadeAktifCount = 0;
        $now               = date('Y-m-d H:i:s');

        $this->db->transStart();
        $this->update($id, $data);

        if ($oldName !== '' && $oldName !== $data['nama_kategori']) {
            $cascadeBankCount  = $this->cascadeRenameTable('bank_pertanyaan_tracer_studi', $oldName, $data['nama_kategori'], $now);
            $cascadeAktifCount = $this->cascadeRenameTable('pertanyaan_tracer_studi_awal', $oldName, $data['nama_kategori'], $now);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return [
                'success'           => false,
                'errors'            => ['Gagal memperbarui kategori Tracer Study.'],
                'id'                => $id,
                'data'              => null,
                'notFound'          => false,
                'cascadeBankCount'  => 0,
                'cascadeAktifCount' => 0,
            ];
        }

        $data['id']         = $id;
        $data['is_default'] = 0;

        return [
            'success'           => true,
            'errors'            => [],
            'id'                => $id,
            'data'              => $data,
            'notFound'          => false,
            'cascadeBankCount'  => $cascadeBankCount,
            'cascadeAktifCount' => $cascadeAktifCount,
        ];
    }

    /**
     * Menghapus kategori dengan proteksi apabila masih dipakai oleh pertanyaan di Bank atau Form Aktif.
     *
     * @return array{success: bool, error: ?string, deleted: ?array<string, mixed>, notFound: bool, blockedByUsage: bool}
     */
    public function deleteKategori(int $id): array
    {
        if (! $this->isTableReady()) {
            return [
                'success'        => false,
                'error'          => 'Tabel kategori_tracer_studi belum tersedia. Jalankan migrasi terlebih dahulu.',
                'deleted'        => null,
                'notFound'       => false,
                'blockedByUsage' => false,
            ];
        }

        $existing = $id > 0 ? $this->find($id) : null;
        if (! is_array($existing)) {
            return [
                'success'        => false,
                'error'          => 'Kategori tidak ditemukan atau sudah dihapus.',
                'deleted'        => null,
                'notFound'       => true,
                'blockedByUsage' => false,
            ];
        }

        $counts      = $this->getUsageCountsMap();
        $key         = self::normalizeKey((string) ($existing['nama_kategori'] ?? ''));
        $jumlahBank  = (int) ($counts['bank'][$key] ?? 0);
        $jumlahAktif = (int) ($counts['aktif'][$key] ?? 0);
        $totalUsed   = $jumlahBank + $jumlahAktif;

        if ($totalUsed > 0) {
            return [
                'success'        => false,
                'error'          => sprintf(
                    'Kategori "%s" tidak dapat dihapus karena masih digunakan pada %d pertanyaan aktif dan %d pertanyaan di Bank Pertanyaan.',
                    (string) ($existing['nama_kategori'] ?? ''),
                    $jumlahAktif,
                    $jumlahBank
                ),
                'deleted'        => null,
                'notFound'       => false,
                'blockedByUsage' => true,
            ];
        }

        if (! $this->delete($id)) {
            return [
                'success'        => false,
                'error'          => 'Gagal menghapus kategori Tracer Study.',
                'deleted'        => null,
                'notFound'       => false,
                'blockedByUsage' => false,
            ];
        }

        return [
            'success'        => true,
            'error'          => null,
            'deleted'        => $existing,
            'notFound'       => false,
            'blockedByUsage' => false,
        ];
    }

    /**
     * Memperbarui nama kategori secara massal pada tabel target saat nama kategori diubah.
     */
    private function cascadeRenameTable(string $tableName, string $oldName, string $newName, string $updatedAt): int
    {
        if (! $this->db->tableExists($tableName) || ! $this->db->fieldExists('kategori', $tableName)) {
            return 0;
        }

        $oldKey = self::normalizeKey($oldName);
        $rows   = $this->db->table($tableName)->select('id, kategori')->get()->getResultArray();
        $ids    = [];

        foreach ($rows as $row) {
            if (self::normalizeKey((string) ($row['kategori'] ?? '')) === $oldKey) {
                $rid = (int) ($row['id'] ?? 0);
                if ($rid > 0) {
                    $ids[] = $rid;
                }
            }
        }

        if ($ids === []) {
            return 0;
        }

        $this->db->table($tableName)
            ->whereIn('id', $ids)
            ->update([
                'kategori'   => $newName,
                'updated_at' => $updatedAt,
            ]);

        return count($ids);
    }
}
