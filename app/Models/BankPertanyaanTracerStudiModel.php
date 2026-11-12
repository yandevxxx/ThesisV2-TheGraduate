<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model Bank Pertanyaan Tracer Study Sisi The Graduate Web.
 *
 * Menyimpan katalog/template pertanyaan Tracer Study yang dapat dikelola
 * dan dipilih oleh Staf/Tendik maupun Program Studi ke dalam form aktif.
 */
class BankPertanyaanTracerStudiModel extends Model
{
    public const DEFAULT_KATEGORI = 'Umum';
    public const PRESET_KATEGORI  = [
        'Status & Pekerjaan',
        'Masa Tunggu & Gaji',
        'Relevansi & Kompetensi',
        'Evaluasi Layanan & Kurikulum',
        'Saran & Masukan',
        'Umum',
    ];

    protected $table            = 'bank_pertanyaan_tracer_studi';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'kategori',
        'teks_pertanyaan',
        'tipe_jawaban',
        'opsi_jawaban',
        'is_wajib_default',
        'created_by',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    /**
     * Memeriksa apakah tabel bank_pertanyaan_tracer_studi sudah tersedia di database.
     */
    public function isTableReady(): bool
    {
        try {
            return $this->db->tableExists($this->table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Mengambil daftar pertanyaan di Bank Pertanyaan Tracer Study dengan filter opsional.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDaftarBank(?string $kategori = null, ?string $keyword = null): array
    {
        $kategoriFilter = trim((string) $kategori);
        $keywordFilter  = trim((string) $keyword);

        if (! $this->isTableReady() || ! isset($this->db)) {
            $rows = self::defaultBankQuestions();

            return array_values(array_filter($rows, static function (array $row) use ($kategoriFilter, $keywordFilter): bool {
                if ($kategoriFilter !== '' && strcasecmp((string) ($row['kategori'] ?? ''), $kategoriFilter) !== 0) {
                    return false;
                }
                if ($keywordFilter !== '') {
                    $haystack = strtolower((string) ($row['teks_pertanyaan'] ?? '') . ' ' . (string) ($row['kategori'] ?? ''));

                    return str_contains($haystack, strtolower($keywordFilter));
                }

                return true;
            }));
        }

        $builder = $this->builder();
        if ($kategoriFilter !== '') {
            $builder->where('kategori', $kategoriFilter);
        }
        if ($keywordFilter !== '') {
            $builder->groupStart()
                ->like('teks_pertanyaan', $keywordFilter)
                ->orLike('kategori', $keywordFilter)
                ->groupEnd();
        }

        return $builder
            ->orderBy('kategori', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Mengambil daftar kategori unik yang tersedia di Bank Pertanyaan digabung dengan preset.
     *
     * @return string[]
     */
    public function getKategoriList(): array
    {
        $categories = self::PRESET_KATEGORI;
        if ($this->isTableReady()) {
            $rows = $this->builder()->select('kategori')->distinct()->get()->getResultArray();
            foreach ($rows as $row) {
                $cat = trim((string) ($row['kategori'] ?? ''));
                if ($cat !== '' && ! in_array($cat, $categories, true)) {
                    $categories[] = $cat;
                }
            }
        }

        return $categories;
    }

    /**
     * Mengambil kumpulan item bank berdasar daftar ID dengan urutan input tetap terjaga.
     *
     * @param array<int|string, mixed> $ids
     *
     * @return array<int, array<string, mixed>>
     */
    public function getByIds(array $ids): array
    {
        $normalizedIds = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn (int $id): bool => $id > 0
        )));

        if ($normalizedIds === [] || ! $this->isTableReady()) {
            return [];
        }

        $rows = $this->whereIn('id', $normalizedIds)->findAll();
        $map  = [];
        foreach ($rows as $row) {
            $map[(int) ($row['id'] ?? 0)] = $row;
        }

        $ordered = [];
        foreach ($normalizedIds as $id) {
            if (isset($map[$id])) {
                $ordered[] = $map[$id];
            }
        }

        return $ordered;
    }

    /**
     * Memvalidasi payload template pertanyaan bank dengan aturan bersama PertanyaanTracerStudiAwalModel.
     *
     * @param array<string, mixed> $input
     *
     * @return array{errors: string[], data: array<string, mixed>}
     */
    public static function validateBankPayload(array $input): array
    {
        $base = PertanyaanTracerStudiAwalModel::validatePertanyaanPayload([
            'teks_pertanyaan' => $input['teks_pertanyaan'] ?? '',
            'tipe_jawaban'    => $input['tipe_jawaban'] ?? '',
            'opsi_jawaban'    => $input['opsi_jawaban'] ?? '',
            'urutan'          => '',
            'is_wajib'        => $input['is_wajib_default'] ?? $input['is_wajib'] ?? '1',
            'status'          => 'AKTIF',
        ]);

        $errors   = $base['errors'];
        $kategori = trim((string) ($input['kategori'] ?? ''));
        if ($kategori === '') {
            $kategori = self::DEFAULT_KATEGORI;
        }
        if (mb_strlen($kategori) > 80) {
            $errors[] = 'Nama kategori maksimal 80 karakter.';
        }

        return [
            'errors' => $errors,
            'data'   => [
                'kategori'         => $kategori,
                'teks_pertanyaan'  => $base['data']['teks_pertanyaan'],
                'tipe_jawaban'     => $base['data']['tipe_jawaban'],
                'opsi_jawaban'     => $base['data']['opsi_jawaban'],
                'is_wajib_default' => (int) ($base['data']['is_wajib'] ?? 1),
            ],
        ];
    }

    /**
     * Menyimpan pertanyaan template baru ke Bank Pertanyaan Tracer Study.
     *
     * @param array<string, mixed> $input
     *
     * @return array{success: bool, errors: string[], id: ?int, data: ?array<string, mixed>}
     */
    public function createBankPertanyaan(array $input, ?string $createdBy = null): array
    {
        $validation = self::validateBankPayload($input);
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
                'errors'  => ['Tabel bank_pertanyaan_tracer_studi belum tersedia. Jalankan migrasi terlebih dahulu.'],
                'id'      => null,
                'data'    => null,
            ];
        }

        $data = $validation['data'];
        $data['created_by'] = $createdBy !== null && trim($createdBy) !== '' ? trim($createdBy) : null;

        $insertedId = $this->insert($data, true);
        if (! $insertedId) {
            return [
                'success' => false,
                'errors'  => ['Gagal menyimpan pertanyaan ke Bank Pertanyaan Tracer Study.'],
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
     * Memperbarui template pertanyaan di Bank Pertanyaan Tracer Study.
     *
     * @param array<string, mixed> $input
     *
     * @return array{success: bool, errors: string[], id: ?int, data: ?array<string, mixed>, notFound: bool}
     */
    public function updateBankPertanyaan(int $id, array $input): array
    {
        if (! $this->isTableReady()) {
            return [
                'success'  => false,
                'errors'   => ['Tabel bank_pertanyaan_tracer_studi belum tersedia. Jalankan migrasi terlebih dahulu.'],
                'id'       => null,
                'data'     => null,
                'notFound' => false,
            ];
        }

        if ($id <= 0 || ! is_array($this->find($id))) {
            return [
                'success'  => false,
                'errors'   => ['Item Bank Pertanyaan tidak ditemukan atau sudah dihapus.'],
                'id'       => null,
                'data'     => null,
                'notFound' => true,
            ];
        }

        $validation = self::validateBankPayload($input);
        if ($validation['errors'] !== []) {
            return [
                'success'  => false,
                'errors'   => $validation['errors'],
                'id'       => $id,
                'data'     => null,
                'notFound' => false,
            ];
        }

        $data = $validation['data'];
        if (! $this->update($id, $data)) {
            return [
                'success'  => false,
                'errors'   => ['Gagal memperbarui item Bank Pertanyaan Tracer Study.'],
                'id'       => $id,
                'data'     => null,
                'notFound' => false,
            ];
        }

        $data['id'] = $id;

        return [
            'success'  => true,
            'errors'   => [],
            'id'       => $id,
            'data'     => $data,
            'notFound' => false,
        ];
    }

    /**
     * Menghapus template pertanyaan dari Bank Pertanyaan tanpa memengaruhi form aktif maupun arsip jawaban.
     *
     * @return array{success: bool, error: ?string, deleted: ?array<string, mixed>, notFound: bool}
     */
    public function deleteBankPertanyaan(int $id): array
    {
        if (! $this->isTableReady()) {
            return [
                'success'  => false,
                'error'    => 'Tabel bank_pertanyaan_tracer_studi belum tersedia. Jalankan migrasi terlebih dahulu.',
                'deleted'  => null,
                'notFound' => false,
            ];
        }

        $existing = $id > 0 ? $this->find($id) : null;
        if (! is_array($existing)) {
            return [
                'success'  => false,
                'error'    => 'Item Bank Pertanyaan tidak ditemukan atau sudah dihapus.',
                'deleted'  => null,
                'notFound' => true,
            ];
        }

        if (! $this->delete($id)) {
            return [
                'success'  => false,
                'error'    => 'Gagal menghapus item dari Bank Pertanyaan Tracer Study.',
                'deleted'  => null,
                'notFound' => false,
            ];
        }

        return [
            'success'  => true,
            'error'    => null,
            'deleted'  => $existing,
            'notFound' => false,
        ];
    }

    /**
     * Menyimpan salinan pertanyaan dari form aktif ke Bank Pertanyaan (atau mengembalikan ID yang sudah ada).
     *
     * @param array<string, mixed> $pertanyaanRow
     *
     * @return array{success: bool, id: ?int, created: bool, error: ?string}
     */
    public function findOrCreateFromPertanyaan(array $pertanyaanRow, ?string $kategori = null, ?string $createdBy = null): array
    {
        if (! $this->isTableReady()) {
            return [
                'success' => false,
                'id'      => null,
                'created' => false,
                'error'   => 'Tabel bank_pertanyaan_tracer_studi belum tersedia.',
            ];
        }

        $teks = trim((string) ($pertanyaanRow['teks_pertanyaan'] ?? ''));
        $norm = PertanyaanTracerStudiAwalModel::normalizeQuestionText($teks);
        if ($norm === '') {
            return [
                'success' => false,
                'id'      => null,
                'created' => false,
                'error'   => 'Teks pertanyaan kosong.',
            ];
        }

        foreach ($this->findAll() as $bankRow) {
            if (PertanyaanTracerStudiAwalModel::normalizeQuestionText((string) ($bankRow['teks_pertanyaan'] ?? '')) === $norm) {
                return [
                    'success' => true,
                    'id'      => (int) ($bankRow['id'] ?? 0),
                    'created' => false,
                    'error'   => null,
                ];
            }
        }

        $created = $this->createBankPertanyaan([
            'kategori'         => $kategori ?: self::DEFAULT_KATEGORI,
            'teks_pertanyaan'  => $teks,
            'tipe_jawaban'     => (string) ($pertanyaanRow['tipe_jawaban'] ?? 'TEKS'),
            'opsi_jawaban'     => $pertanyaanRow['opsi_jawaban'] ?? null,
            'is_wajib_default' => ! empty($pertanyaanRow['is_wajib']) ? '1' : '0',
        ], $createdBy);

        return [
            'success' => $created['success'],
            'id'      => $created['id'],
            'created' => $created['success'],
            'error'   => $created['errors'] !== [] ? implode(' ', $created['errors']) : null,
        ];
    }

    /**
     * Menghapus item bank yang sudah aktif pada form (berdasarkan ID bank atau kesamaan teks ternormalisasi).
     *
     * @param int[]    $activeBankIds
     * @param string[] $activeNormalizedTexts
     */
    public function removeActiveDuplicates(array $activeBankIds = [], array $activeNormalizedTexts = []): int
    {
        if (! $this->isTableReady()) {
            return 0;
        }

        $idMap   = array_flip(array_values(array_unique(array_filter(array_map('intval', $activeBankIds), static fn (int $v): bool => $v > 0))));
        $textMap = array_flip(array_values(array_filter(array_map('strval', $activeNormalizedTexts), static fn (string $v): bool => $v !== '')));

        if ($idMap === [] && $textMap === []) {
            return 0;
        }

        $deleteIds = [];
        foreach ($this->findAll() as $bankRow) {
            $bid  = (int) ($bankRow['id'] ?? 0);
            $norm = PertanyaanTracerStudiAwalModel::normalizeQuestionText((string) ($bankRow['teks_pertanyaan'] ?? ''));
            if (($bid > 0 && isset($idMap[$bid])) || ($norm !== '' && isset($textMap[$norm]))) {
                if ($bid > 0) {
                    $deleteIds[] = $bid;
                }
            }
        }

        if ($deleteIds === []) {
            return 0;
        }

        $this->whereIn('id', $deleteIds)->delete();

        return count($deleteIds);
    }

    /**
     * Menghapus satu atau beberapa teks yang cocok dari Bank Pertanyaan (misalnya saat diaktifkan).
     */
    public function deleteByNormalizedText(string $text): int
    {
        $norm = PertanyaanTracerStudiAwalModel::normalizeQuestionText($text);
        if ($norm === '') {
            return 0;
        }

        return $this->removeActiveDuplicates([], [$norm]);
    }

    /**
     * Daftar template pertanyaan nonaktif bawaan untuk seed Bank Pertanyaan Tracer Study (katalog DIKTI).
     * Pertanyaan aktif standar disimpan terpisah di pertanyaan_tracer_studi_awal.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaultBankQuestions(): array
    {
        return [
            [
                'id'               => 1,
                'kategori'         => 'Masa Tunggu & Gaji',
                'teks_pertanyaan'  => 'Berapa perkiraan masa tunggu Anda sejak lulus/yudisium hingga memperoleh pekerjaan pertama?',
                'tipe_jawaban'     => 'PILIHAN_GANDA',
                'opsi_jawaban'     => json_encode([
                    'Sebelum lulus (kurang dari 0 bulan)',
                    'Kurang dari 3 bulan',
                    '3 - 6 bulan',
                    'Lebih dari 6 bulan',
                    'Belum bekerja / tidak mencari kerja',
                ], JSON_UNESCAPED_UNICODE),
                'is_wajib_default' => 0,
            ],
            [
                'id'               => 2,
                'kategori'         => 'Masa Tunggu & Gaji',
                'teks_pertanyaan'  => 'Berapa kisaran penghasilan atau take-home pay per bulan pada pekerjaan pertama Anda saat ini?',
                'tipe_jawaban'     => 'PILIHAN_GANDA',
                'opsi_jawaban'     => json_encode([
                    'Di bawah Rp 3.000.000',
                    'Rp 3.000.000 - Rp 5.000.000',
                    'Rp 5.000.001 - Rp 8.000.000',
                    'Di atas Rp 8.000.000',
                    'Belum berpenghasilan',
                ], JSON_UNESCAPED_UNICODE),
                'is_wajib_default' => 0,
            ],
            [
                'id'               => 3,
                'kategori'         => 'Status & Pekerjaan',
                'teks_pertanyaan'  => 'Apa jenis instansi/perusahaan tempat Anda bekerja atau magang menjelang kelulusan?',
                'tipe_jawaban'     => 'PILIHAN_GANDA',
                'opsi_jawaban'     => json_encode([
                    'Instansi Pemerintah / BUMN',
                    'Perusahaan Swasta Nasional',
                    'Perusahaan Multinasional / Startup Asing',
                    'Lembaga Pendidikan / Riset',
                    'Wiraswasta / Usaha Sendiri',
                    'Belum Bekerja',
                ], JSON_UNESCAPED_UNICODE),
                'is_wajib_default' => 0,
            ],
            [
                'id'               => 4,
                'kategori'         => 'Status & Pekerjaan',
                'teks_pertanyaan'  => 'Melalui jalur apa Anda terutama mendapatkan informasi lowongan atau peluang kerja pertama?',
                'tipe_jawaban'     => 'PILIHAN_GANDA',
                'opsi_jawaban'     => json_encode([
                    'Bursa kerja / Career Center Universitas',
                    'Program magang / MBKM sebelumnya',
                    'Relasi dosen / alumni / rekan',
                    'Portal lowongan kerja daring (LinkedIn, JobStreet, dll.)',
                    'Melamar langsung ke instansi/perusahaan',
                ], JSON_UNESCAPED_UNICODE),
                'is_wajib_default' => 0,
            ],
            [
                'id'               => 5,
                'kategori'         => 'Evaluasi Layanan & Kurikulum',
                'teks_pertanyaan'  => 'Bagaimana tingkat kepuasan Anda terhadap layanan informasi karier, magang, dan sertifikasi dari Program Studi/Fakultas?',
                'tipe_jawaban'     => 'SKALA',
                'opsi_jawaban'     => json_encode([
                    '1 - Sangat Tidak Puas',
                    '2 - Tidak Puas',
                    '3 - Cukup Puas',
                    '4 - Puas',
                    '5 - Sangat Puas',
                ], JSON_UNESCAPED_UNICODE),
                'is_wajib_default' => 0,
            ],
            [
                'id'               => 6,
                'kategori'         => 'Relevansi & Kompetensi',
                'teks_pertanyaan'  => 'Sertifikasi profesi atau keterampilan praktis apa yang paling membantu lulusan Program Studi Anda saat memasuki dunia kerja?',
                'tipe_jawaban'     => 'ISIAN_SINGKAT',
                'opsi_jawaban'     => null,
                'is_wajib_default' => 0,
            ],
        ];
    }
}
