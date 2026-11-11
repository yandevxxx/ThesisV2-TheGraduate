<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Jawaban form Tracer Studi Awal yang diisi oleh mahasiswa terplot (The Graduate).
 */
class JawabanTracerStudiAwalModel extends Model
{
    protected $table            = 'jawaban_tracer_studi_awal';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'npm',
        'pertanyaan_id',
        'urutan',
        'teks_pertanyaan',
        'jawaban',
        'kode_semester',
        'submitted_at',
    ];
    protected $useTimestamps = true;

    public function isTableReady(): bool
    {
        try {
            return $this->db->tableExists($this->table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Memastikan mahasiswa sudah mengirim minimal satu set jawaban Tracer Studi Awal.
     */
    public function hasCompletedByNpm(string $npm): bool
    {
        $npm = trim($npm);
        if ($npm === '' || ! $this->isTableReady()) {
            return false;
        }

        return $this->where('npm', $npm)->countAllResults() > 0;
    }

    /**
     * Mengambil peta jawaban tersimpan per pertanyaan_id untuk NPM tertentu.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMapByNpm(string $npm): array
    {
        $npm = trim($npm);
        if ($npm === '' || ! $this->isTableReady()) {
            return [];
        }

        $rows = $this->where('npm', $npm)
            ->orderBy('urutan', 'ASC')
            ->orderBy('pertanyaan_id', 'ASC')
            ->findAll();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) ($row['pertanyaan_id'] ?? 0)] = $row;
        }

        return $map;
    }

    public function getLastSubmittedAt(string $npm): ?string
    {
        $npm = trim($npm);
        if ($npm === '' || ! $this->isTableReady()) {
            return null;
        }

        $row = $this->select('submitted_at')
            ->where('npm', $npm)
            ->orderBy('submitted_at', 'DESC')
            ->first();

        $submittedAt = $row['submitted_at'] ?? null;

        return is_string($submittedAt) && $submittedAt !== '' ? $submittedAt : null;
    }

    /**
     * Memvalidasi jawaban mahasiswa terhadap daftar pertanyaan Tracer Studi Awal.
     *
     * @param array<int, array<string, mixed>> $pertanyaanList
     * @param array<int|string, mixed>         $rawInput
     *
     * @return array{errors: array<int, string>, answers: array<int, string>}
     */
    public static function validateJawabanSet(array $pertanyaanList, array $rawInput): array
    {
        $errors  = [];
        $answers = [];

        foreach ($pertanyaanList as $question) {
            $qid        = (int) ($question['id'] ?? $question['urutan'] ?? 0);
            $urutan     = (int) ($question['urutan'] ?? $qid);
            $label      = 'Pertanyaan #' . $urutan;
            $rawValue   = $rawInput[$qid] ?? $rawInput[(string) $qid] ?? '';
            $value      = is_scalar($rawValue) ? trim((string) $rawValue) : '';
            $isRequired = ! empty($question['is_wajib']);
            $tipe       = strtoupper((string) ($question['tipe_jawaban'] ?? 'TEKS'));
            $opsiList   = PertanyaanTracerStudiAwalModel::parseOpsiJawaban($question['opsi_jawaban'] ?? null);

            if ($isRequired && $value === '') {
                $errors[$qid] = "{$label} wajib diisi.";
                continue;
            }

            if (
                $value !== ''
                && in_array($tipe, ['PILIHAN_GANDA', 'SKALA'], true)
                && $opsiList !== []
                && ! in_array($value, $opsiList, true)
            ) {
                $errors[$qid] = "Pilihan jawaban untuk {$label} tidak valid.";
                continue;
            }

            $answers[$qid] = $value;
        }

        return [
            'errors'  => $errors,
            'answers' => $answers,
        ];
    }

    /**
     * Menyimpan seluruh jawaban mahasiswa dalam satu transaksi atomik.
     *
     * @param array<int, array<string, mixed>> $pertanyaanList
     * @param array<int, string>               $answers
     */
    public function saveJawabanMahasiswa(
        string $npm,
        array $pertanyaanList,
        array $answers,
        ?string $kodeSemester = null
    ): void {
        $npm = trim($npm);
        if ($npm === '') {
            throw new \InvalidArgumentException('NPM mahasiswa tidak boleh kosong.');
        }

        if (! $this->isTableReady()) {
            throw new \RuntimeException('Tabel jawaban_tracer_studi_awal belum tersedia. Jalankan migrasi terlebih dahulu.');
        }

        $now  = date('Y-m-d H:i:s');
        $rows = [];

        foreach ($pertanyaanList as $question) {
            $qid    = (int) ($question['id'] ?? $question['urutan'] ?? 0);
            $urutan = (int) ($question['urutan'] ?? $qid);
            $rows[] = [
                'npm'             => $npm,
                'pertanyaan_id'   => $qid,
                'urutan'          => $urutan,
                'teks_pertanyaan' => (string) ($question['teks_pertanyaan'] ?? ''),
                'jawaban'         => (string) ($answers[$qid] ?? ''),
                'kode_semester'   => $kodeSemester,
                'submitted_at'    => $now,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        $this->db->transStart();
        $existingRows = $this->db->table($this->table)
            ->select('id, pertanyaan_id')
            ->where('npm', $npm)
            ->get()
            ->getResultArray();

        $existingByQid = [];
        foreach ($existingRows as $existingRow) {
            $existingByQid[(int) ($existingRow['pertanyaan_id'] ?? 0)] = (int) ($existingRow['id'] ?? 0);
        }

        $toInsert = [];
        foreach ($rows as $row) {
            $qid = (int) ($row['pertanyaan_id'] ?? 0);
            if (isset($existingByQid[$qid]) && $existingByQid[$qid] > 0) {
                $updateData = $row;
                unset($updateData['created_at']);
                $this->db->table($this->table)
                    ->where('id', $existingByQid[$qid])
                    ->update($updateData);
            } else {
                $toInsert[] = $row;
            }
        }

        if ($toInsert !== []) {
            $this->db->table($this->table)->insertBatch($toInsert);
        }
        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new \RuntimeException('Gagal menyimpan jawaban Tracer Study Awal.');
        }
    }

    /**
     * Mengambil daftar mahasiswa terplot beserta status pengisian Tracer Studi Awal (B-14).
     *
     * @param array<string, string> $filters
     *
     * @return array{
     *     tablesReady: bool,
     *     rows: array<int, array<string, mixed>>,
     *     metrics: array{total: int, sudah: int, belum: int},
     *     semesterOptions: string[]
     * }
     */
    public function getMonitoringTerplot(array $filters = []): array
    {
        if (! $this->db->tableExists('mahasiswa_terplot_sk_yudisium')) {
            return [
                'tablesReady'     => false,
                'rows'            => [],
                'metrics'         => ['total' => 0, 'sudah' => 0, 'belum' => 0],
                'semesterOptions' => [],
            ];
        }

        $q            = trim((string) ($filters['q'] ?? ''));
        $statusFilter = strtolower(trim((string) ($filters['status'] ?? '')));
        $semester     = trim((string) ($filters['semester'] ?? ''));

        if (! in_array($statusFilter, ['sudah', 'belum'], true)) {
            $statusFilter = '';
        }

        $hasSemesterCol = false;
        try {
            $hasSemesterCol = in_array('kode_semester', $this->db->getFieldNames('mahasiswa_terplot_sk_yudisium'), true);
        } catch (\Throwable $e) {
            $hasSemesterCol = false;
        }

        // Ambil opsi semester tersedia dari data terplot
        $semesterOptions = [];
        if ($hasSemesterCol) {
            $semRows = $this->db->table('mahasiswa_terplot_sk_yudisium')
                ->select('kode_semester')
                ->where('kode_semester IS NOT NULL')
                ->where('kode_semester !=', '')
                ->distinct()
                ->orderBy('kode_semester', 'DESC')
                ->get()
                ->getResultArray();
            foreach ($semRows as $sr) {
                $kode = trim((string) ($sr['kode_semester'] ?? ''));
                if ($kode !== '') {
                    $semesterOptions[] = $kode;
                }
            }
        }

        // Query mahasiswa terplot (termasuk filter semester & pencarian nama/NPM)
        $builder = $this->db->table('mahasiswa_terplot_sk_yudisium');
        if ($hasSemesterCol && $semester !== '' && strtoupper($semester) !== 'SEMUA') {
            $builder->where('kode_semester', $semester);
        }
        if ($q !== '') {
            $builder->groupStart()
                ->like('npm', $q)
                ->orLike('nama', $q)
                ->groupEnd();
        }
        $terplotList = $builder->orderBy('nama', 'ASC')
            ->orderBy('npm', 'ASC')
            ->get()
            ->getResultArray();

        // Peta ringkasan pengisian tracer per NPM
        $jawabanSummary = [];
        if ($this->isTableReady()) {
            $summaryRows = $this->db->table($this->table)
                ->select('npm, COUNT(*) AS total_jawaban, MAX(submitted_at) AS submitted_at')
                ->groupBy('npm')
                ->get()
                ->getResultArray();
            foreach ($summaryRows as $row) {
                $npmKey = trim((string) ($row['npm'] ?? ''));
                if ($npmKey !== '') {
                    $jawabanSummary[$npmKey] = [
                        'total_jawaban' => (int) ($row['total_jawaban'] ?? 0),
                        'submitted_at'  => $row['submitted_at'] ?? null,
                    ];
                }
            }
        }

        // Opsional: resolusi nama program studi bila tabel program_studi tersedia
        $prodiMap = [];
        try {
            if ($this->db->tableExists('program_studi')) {
                foreach ($this->db->table('program_studi')->select('id, nama')->get()->getResultArray() as $pr) {
                    $prodiMap[(int) ($pr['id'] ?? 0)] = (string) ($pr['nama'] ?? '');
                }
            }
        } catch (\Throwable $e) {
            $prodiMap = [];
        }

        $totalSudah = 0;
        $totalBelum = 0;
        $rows       = [];

        foreach ($terplotList as $mhs) {
            $npm         = trim((string) ($mhs['npm'] ?? ''));
            $summary     = $jawabanSummary[$npm] ?? null;
            $sudahIsi    = $summary !== null && $summary['total_jawaban'] > 0;

            if ($sudahIsi) {
                $totalSudah++;
            } else {
                $totalBelum++;
            }

            if ($statusFilter === 'sudah' && ! $sudahIsi) {
                continue;
            }
            if ($statusFilter === 'belum' && $sudahIsi) {
                continue;
            }

            $prodiId   = (int) ($mhs['id_prodi'] ?? 0);
            $namaProdi = $prodiMap[$prodiId] ?? '-';

            $rows[] = [
                'id'               => (int) ($mhs['id'] ?? 0),
                'npm'              => $npm,
                'nama'             => (string) ($mhs['nama'] ?? ''),
                'status_mahasiswa' => (string) ($mhs['status'] ?? ''),
                'kode_semester'    => (string) ($mhs['kode_semester'] ?? ''),
                'nama_prodi'       => $namaProdi !== '' ? $namaProdi : '-',
                'sudah_mengisi'    => $sudahIsi,
                'status_tracer'    => $sudahIsi ? 'SUDAH' : 'BELUM',
                'total_jawaban'    => $sudahIsi ? (int) $summary['total_jawaban'] : 0,
                'submitted_at'     => $sudahIsi ? ($summary['submitted_at'] ?? null) : null,
            ];
        }

        return [
            'tablesReady'     => $this->isTableReady(),
            'rows'            => $rows,
            'metrics'         => [
                'total' => count($terplotList),
                'sudah' => $totalSudah,
                'belum' => $totalBelum,
            ],
            'semesterOptions' => $semesterOptions,
        ];
    }

    /**
     * Mengambil daftar detail jawaban Tracer Studi Awal mahasiswa berdasar NPM (B-14).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDetailJawabanByNpm(string $npm): array
    {
        $npm = trim($npm);
        if ($npm === '' || ! $this->isTableReady()) {
            return [];
        }

        return $this->where('npm', $npm)
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
