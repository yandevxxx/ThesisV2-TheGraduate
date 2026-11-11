<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model Daftar Pertanyaan Form Tracer Studi Awal Sisi The Graduate.
 *
 * Daftar pertanyaan bersifat global dan seragam untuk seluruh program studi.
 */
class PertanyaanTracerStudiAwalModel extends Model
{
    public const VALID_TIPE_JAWABAN = ['PILIHAN_GANDA', 'SKALA', 'ISIAN_SINGKAT', 'TEKS'];
    public const VALID_STATUS       = ['AKTIF', 'NON-AKTIF'];

    protected $table            = 'pertanyaan_tracer_studi_awal';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'teks_pertanyaan',
        'urutan',
        'tipe_jawaban',
        'opsi_jawaban',
        'is_wajib',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    /**
     * Memeriksa apakah tabel pertanyaan_tracer_studi_awal sudah tersedia di database.
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
     * Mengambil daftar pertanyaan aktif form tracer studi awal terurut sesuai urutan.
     *
     * Tidak memfilter berdasarkan program studi karena pertanyaan berlaku sama
     * untuk semua program studi.
     */
    public function getDaftarPertanyaan(): array
    {
        if (! $this->isTableReady()) {
            $defaults = self::defaultQuestions();
            usort($defaults, static fn (array $a, array $b): int => ((int) $a['urutan']) <=> ((int) $b['urutan']));

            return $defaults;
        }

        return $this->where('status', 'AKTIF')
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Mengambil seluruh daftar pertanyaan (AKTIF dan NON-AKTIF) untuk tampilan manajemen Staf & Prodi.
     */
    public function getDaftarManajemenPertanyaan(): array
    {
        if (! $this->isTableReady() || ! isset($this->db)) {
            return $this->getDaftarPertanyaan();
        }

        return $this->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Daftar pertanyaan standar awal form tracer studi awal (dipakai untuk seed & fallback).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaultQuestions(): array
    {
        return [
            [
                'id'              => 1,
                'urutan'          => 1,
                'teks_pertanyaan' => 'Bagaimana status atau rencana aktivitas Anda saat ini menjelang kelulusan?',
                'tipe_jawaban'    => 'PILIHAN_GANDA',
                'opsi_jawaban'    => json_encode([
                    'Sudah bekerja',
                    'Melanjutkan studi (S2/Profesi)',
                    'Berwirausaha',
                    'Sedang mencari pekerjaan',
                    'Belum menentukan rencana',
                ], JSON_UNESCAPED_UNICODE),
                'is_wajib'        => 1,
                'status'          => 'AKTIF',
            ],
            [
                'id'              => 2,
                'urutan'          => 2,
                'teks_pertanyaan' => 'Apabila sudah bekerja atau memiliki penawaran kerja, tuliskan nama instansi/perusahaan dan posisi pekerjaan Anda.',
                'tipe_jawaban'    => 'ISIAN_SINGKAT',
                'opsi_jawaban'    => null,
                'is_wajib'        => 0,
                'status'          => 'AKTIF',
            ],
            [
                'id'              => 3,
                'urutan'          => 3,
                'teks_pertanyaan' => 'Apakah bidang pekerjaan atau rencana studi lanjut Anda sesuai dengan program studi yang ditempuh?',
                'tipe_jawaban'    => 'PILIHAN_GANDA',
                'opsi_jawaban'    => json_encode([
                    'Sangat sesuai',
                    'Sesuai',
                    'Cukup sesuai',
                    'Kurang sesuai',
                    'Tidak relevan',
                ], JSON_UNESCAPED_UNICODE),
                'is_wajib'        => 1,
                'status'          => 'AKTIF',
            ],
            [
                'id'              => 4,
                'urutan'          => 4,
                'teks_pertanyaan' => 'Sejauh mana kurikulum dan mata kuliah program studi membekali kompetensi yang Anda butuhkan setelah lulus?',
                'tipe_jawaban'    => 'SKALA',
                'opsi_jawaban'    => json_encode([
                    '1 - Sangat Kurang',
                    '2 - Kurang',
                    '3 - Cukup',
                    '4 - Baik',
                    '5 - Sangat Baik',
                ], JSON_UNESCAPED_UNICODE),
                'is_wajib'        => 1,
                'status'          => 'AKTIF',
            ],
            [
                'id'              => 5,
                'urutan'          => 5,
                'teks_pertanyaan' => 'Bagaimana tingkat kepuasan Anda terhadap layanan pembimbingan tugas akhir/skripsi di program studi?',
                'tipe_jawaban'    => 'SKALA',
                'opsi_jawaban'    => json_encode([
                    '1 - Sangat Tidak Puas',
                    '2 - Tidak Puas',
                    '3 - Cukup Puas',
                    '4 - Puas',
                    '5 - Sangat Puas',
                ], JSON_UNESCAPED_UNICODE),
                'is_wajib'        => 1,
                'status'          => 'AKTIF',
            ],
            [
                'id'              => 6,
                'urutan'          => 6,
                'teks_pertanyaan' => 'Bagaimana tingkat kepuasan Anda terhadap layanan administrasi akademik dan pengurusan kelulusan fakultas?',
                'tipe_jawaban'    => 'SKALA',
                'opsi_jawaban'    => json_encode([
                    '1 - Sangat Tidak Puas',
                    '2 - Tidak Puas',
                    '3 - Cukup Puas',
                    '4 - Puas',
                    '5 - Sangat Puas',
                ], JSON_UNESCAPED_UNICODE),
                'is_wajib'        => 1,
                'status'          => 'AKTIF',
            ],
            [
                'id'              => 7,
                'urutan'          => 7,
                'teks_pertanyaan' => 'Kompetensi atau keterampilan apa yang menurut Anda masih perlu ditingkatkan dalam pembelajaran di program studi?',
                'tipe_jawaban'    => 'TEKS',
                'opsi_jawaban'    => null,
                'is_wajib'        => 1,
                'status'          => 'AKTIF',
            ],
            [
                'id'              => 8,
                'urutan'          => 8,
                'teks_pertanyaan' => 'Tuliskan saran atau masukan Anda untuk peningkatan kualitas program studi dan layanan kelulusan ke depannya.',
                'tipe_jawaban'    => 'TEKS',
                'opsi_jawaban'    => null,
                'is_wajib'        => 0,
                'status'          => 'AKTIF',
            ],
        ];
    }

    /**
     * Label tampilan tipe jawaban pertanyaan.
     */
    public static function formatTipeJawaban(?string $tipe): string
    {
        return match (strtoupper(trim((string) $tipe))) {
            'PILIHAN_GANDA' => 'Pilihan Ganda',
            'SKALA'         => 'Skala Penilaian',
            'ISIAN_SINGKAT' => 'Isian Singkat',
            default         => 'Uraian Teks',
        };
    }

    /**
     * Skala penilaian standar 1-5 apabila tipe SKALA dipilih tanpa daftar opsi khusus.
     *
     * @return string[]
     */
    public static function defaultSkalaOpsi(): array
    {
        return [
            '1 - Sangat Kurang',
            '2 - Kurang',
            '3 - Cukup',
            '4 - Baik',
            '5 - Sangat Baik',
        ];
    }

    /**
     * Memvalidasi payload penambahan pertanyaan tracer studi awal baru (B-11).
     *
     * @param array<string, mixed> $input
     *
     * @return array{errors: string[], data: array<string, mixed>}
     */
    public static function validateNewPertanyaan(array $input): array
    {
        return self::validatePertanyaanPayload($input);
    }

    /**
     * Memvalidasi payload tambah/ubah pertanyaan tracer studi awal (B-11 & B-15).
     *
     * @param array<string, mixed> $input
     *
     * @return array{errors: string[], data: array<string, mixed>}
     */
    public static function validatePertanyaanPayload(array $input): array
    {
        $errors = [];

        $teks = trim((string) ($input['teks_pertanyaan'] ?? ''));
        if ($teks === '') {
            $errors[] = 'Teks pertanyaan wajib diisi.';
        }

        $tipe = strtoupper(trim((string) ($input['tipe_jawaban'] ?? '')));
        if ($tipe === '') {
            $errors[] = 'Tipe jawaban wajib dipilih.';
        } elseif (! in_array($tipe, self::VALID_TIPE_JAWABAN, true)) {
            $errors[] = 'Tipe jawaban tidak valid.';
        }

        $rawOpsi  = $input['opsi_jawaban'] ?? '';
        $opsiJson = null;

        if ($tipe === 'PILIHAN_GANDA') {
            $parsedOpsi = self::normalizeOpsiInput($rawOpsi);
            if (count($parsedOpsi) < 2) {
                $errors[] = 'Untuk tipe Pilihan Ganda, masukkan minimal 2 pilihan jawaban.';
            } else {
                $opsiJson = json_encode($parsedOpsi, JSON_UNESCAPED_UNICODE);
            }
        } elseif ($tipe === 'SKALA') {
            $parsedOpsi = self::normalizeOpsiInput($rawOpsi);
            if ($parsedOpsi === []) {
                $parsedOpsi = self::defaultSkalaOpsi();
            } elseif (count($parsedOpsi) < 2) {
                $errors[] = 'Untuk tipe Skala Penilaian, masukkan minimal 2 tingkat skala.';
            }
            $opsiJson = json_encode($parsedOpsi, JSON_UNESCAPED_UNICODE);
        }

        $urutanRaw = trim((string) ($input['urutan'] ?? ''));
        $urutan    = $urutanRaw !== '' ? (int) $urutanRaw : 0;
        if ($urutanRaw !== '' && $urutan <= 0) {
            $errors[] = 'Urutan pertanyaan harus berupa angka positif minimal 1.';
        }

        $isWajibInput = $input['is_wajib'] ?? '1';
        $isWajib      = self::parseIsWajibFlag($isWajibInput);

        $status = strtoupper(trim((string) ($input['status'] ?? 'AKTIF')));
        if ($status === 'NONAKTIF') {
            $status = 'NON-AKTIF';
        }
        if (! in_array($status, self::VALID_STATUS, true)) {
            $status = 'AKTIF';
        }

        return [
            'errors' => $errors,
            'data'   => [
                'teks_pertanyaan' => $teks,
                'urutan'          => $urutan,
                'tipe_jawaban'    => $tipe,
                'opsi_jawaban'    => $opsiJson,
                'is_wajib'        => $isWajib,
                'status'          => $status,
            ],
        ];
    }

    /**
     * Memvalidasi dan menyimpan pertanyaan tracer studi awal baru ke database (B-11).
     *
     * @param array<string, mixed> $input
     *
     * @return array{success: bool, errors: string[], id: ?int, data: ?array<string, mixed>}
     */
    public function createPertanyaan(array $input): array
    {
        $validation = self::validateNewPertanyaan($input);
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
                'errors'  => ['Tabel pertanyaan_tracer_studi_awal belum tersedia. Jalankan migrasi terlebih dahulu.'],
                'id'      => null,
                'data'    => null,
            ];
        }

        $data = $validation['data'];
        if ((int) ($data['urutan'] ?? 0) <= 0) {
            $maxRow         = $this->selectMax('urutan')->first();
            $data['urutan'] = (int) ($maxRow['urutan'] ?? 0) + 1;
        }

        $insertedId = $this->insert($data, true);
        if (! $insertedId) {
            return [
                'success' => false,
                'errors'  => ['Gagal menyimpan pertanyaan tracer study awal ke database.'],
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
     * Memvalidasi dan memperbarui pertanyaan tracer studi awal (B-15).
     *
     * Hanya mengubah master pertanyaan; jawaban mahasiswa yang sudah tersimpan tetap utuh.
     *
     * @param array<string, mixed> $input
     *
     * @return array{success: bool, errors: string[], id: ?int, data: ?array<string, mixed>, notFound: bool}
     */
    public function updatePertanyaan(int $id, array $input): array
    {
        if (! $this->isTableReady()) {
            return [
                'success'  => false,
                'errors'   => ['Tabel pertanyaan_tracer_studi_awal belum tersedia. Jalankan migrasi terlebih dahulu.'],
                'id'       => null,
                'data'     => null,
                'notFound' => false,
            ];
        }

        if ($id <= 0) {
            return [
                'success'  => false,
                'errors'   => ['ID pertanyaan tidak valid.'],
                'id'       => null,
                'data'     => null,
                'notFound' => true,
            ];
        }

        $existing = $this->find($id);
        if (! is_array($existing)) {
            return [
                'success'  => false,
                'errors'   => ['Pertanyaan tracer study awal tidak ditemukan atau sudah dihapus.'],
                'id'       => null,
                'data'     => null,
                'notFound' => true,
            ];
        }

        $validation = self::validatePertanyaanPayload($input);
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
        if ((int) ($data['urutan'] ?? 0) <= 0) {
            $existingUrutan = (int) ($existing['urutan'] ?? 0);
            if ($existingUrutan > 0) {
                $data['urutan'] = $existingUrutan;
            } else {
                $maxRow         = $this->selectMax('urutan')->first();
                $data['urutan'] = (int) ($maxRow['urutan'] ?? 0) + 1;
            }
        }

        if (! $this->update($id, $data)) {
            return [
                'success'  => false,
                'errors'   => ['Gagal menyimpan perubahan pertanyaan tracer study awal ke database.'],
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
     * Menghapus pertanyaan tracer studi awal dari daftar (B-16).
     *
     * Hanya menghapus baris pada master pertanyaan sehingga jawaban tersimpan mahasiswa tetap utuh di B-14.
     *
     * @return array{success: bool, error: ?string, deleted: ?array<string, mixed>, notFound: bool}
     */
    public function deletePertanyaan(int $id): array
    {
        if (! $this->isTableReady()) {
            return [
                'success'  => false,
                'error'    => 'Tabel pertanyaan_tracer_studi_awal belum tersedia. Jalankan migrasi terlebih dahulu.',
                'deleted'  => null,
                'notFound' => false,
            ];
        }

        if ($id <= 0) {
            return [
                'success'  => false,
                'error'    => 'ID pertanyaan tidak valid.',
                'deleted'  => null,
                'notFound' => true,
            ];
        }

        $existing = $this->find($id);
        if (! is_array($existing)) {
            return [
                'success'  => false,
                'error'    => 'Pertanyaan tracer study awal tidak ditemukan atau sudah dihapus.',
                'deleted'  => null,
                'notFound' => true,
            ];
        }

        if (! $this->delete($id)) {
            return [
                'success'  => false,
                'error'    => 'Gagal menghapus pertanyaan tracer study awal dari database.',
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
     * Mengurai input sifat pengisian menjadi 1 (Wajib) atau 0 (Opsional).
     *
     * @param mixed $raw
     */
    public static function parseIsWajibFlag($raw): int
    {
        if ($raw === false || $raw === 0) {
            return 0;
        }

        return in_array(strtolower(trim((string) $raw)), ['0', 'false', 'no', 'off'], true) ? 0 : 1;
    }

    /**
     * Mengaktifkan atau mematikan pengaturan "Wajib Diisi" pada satu pertanyaan (B-17).
     *
     * Jika $isWajib === null, nilai is_wajib saat ini otomatis dibalik (toggle).
     *
     * @return array{success: bool, error: ?string, id: ?int, is_wajib: ?int, notFound: bool}
     */
    public function toggleWajibPertanyaan(int $id, ?bool $isWajib = null): array
    {
        if (! $this->isTableReady()) {
            return [
                'success'  => false,
                'error'    => 'Tabel pertanyaan_tracer_studi_awal belum tersedia. Jalankan migrasi terlebih dahulu.',
                'id'       => null,
                'is_wajib' => null,
                'notFound' => false,
            ];
        }

        if ($id <= 0) {
            return [
                'success'  => false,
                'error'    => 'ID pertanyaan tidak valid.',
                'id'       => null,
                'is_wajib' => null,
                'notFound' => true,
            ];
        }

        $existing = $this->find($id);
        if (! is_array($existing)) {
            return [
                'success'  => false,
                'error'    => 'Pertanyaan tracer study awal tidak ditemukan atau sudah dihapus.',
                'id'       => null,
                'is_wajib' => null,
                'notFound' => true,
            ];
        }

        $current = ((int) ($existing['is_wajib'] ?? 1)) === 1;
        $nextVal = $isWajib !== null ? ($isWajib ? 1 : 0) : ($current ? 0 : 1);

        if (! $this->update($id, ['is_wajib' => $nextVal])) {
            return [
                'success'  => false,
                'error'    => 'Gagal memperbarui pengaturan Wajib Diisi ke database.',
                'id'       => $id,
                'is_wajib' => null,
                'notFound' => false,
            ];
        }

        return [
            'success'  => true,
            'error'    => null,
            'id'       => $id,
            'is_wajib' => $nextVal,
            'notFound' => false,
        ];
    }

    /**
     * Memperbarui urutan tampil pertanyaan tracer studi awal secara massal (B-18).
     *
     * Mendukung input daftar ID berurutan (hasil drag-and-drop) maupun peta [id => nomor_urut]
     * (input nomor urut manual). Menormalkan urutan menjadi 1..N dan tidak mengubah jawaban tersimpan.
     *
     * @param array<int|string, int|string> $orderInput
     *
     * @return array{success: bool, error: ?string, updatedCount: int, orderedIds: int[]}
     */
    public function updateUrutanPertanyaan(array $orderInput, bool $isOrderedIdList = false): array
    {
        if (! $this->isTableReady()) {
            return [
                'success'      => false,
                'error'        => 'Tabel pertanyaan_tracer_studi_awal belum tersedia. Jalankan migrasi terlebih dahulu.',
                'updatedCount' => 0,
                'orderedIds'   => [],
            ];
        }

        $existingRows = $this->db->table($this->table)
            ->select('id, urutan')
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        if ($existingRows === []) {
            return [
                'success'      => false,
                'error'        => 'Belum ada pertanyaan pada daftar untuk diurutkan.',
                'updatedCount' => 0,
                'orderedIds'   => [],
            ];
        }

        $existingIds = [];
        $oldPosById  = [];
        foreach ($existingRows as $idx => $row) {
            $qid              = (int) ($row['id'] ?? 0);
            $existingIds[]    = $qid;
            $urutanVal        = (int) ($row['urutan'] ?? ($idx + 1));
            $oldPosById[$qid] = $urutanVal > 0 ? $urutanVal : ($idx + 1);
        }

        $finalOrder = [];
        if ($isOrderedIdList) {
            $seen = [];
            foreach ($orderInput as $rawId) {
                $qid = (int) $rawId;
                if ($qid > 0 && isset($oldPosById[$qid]) && ! isset($seen[$qid])) {
                    $seen[$qid]   = true;
                    $finalOrder[] = $qid;
                }
            }
            if ($finalOrder === []) {
                return [
                    'success'      => false,
                    'error'        => 'Daftar urutan pertanyaan tidak valid.',
                    'updatedCount' => 0,
                    'orderedIds'   => [],
                ];
            }
            foreach ($existingIds as $qid) {
                if (! isset($seen[$qid])) {
                    $finalOrder[] = $qid;
                }
            }
        } else {
            if ($orderInput === []) {
                return [
                    'success'      => false,
                    'error'        => 'Masukkan nomor urut minimal untuk satu pertanyaan.',
                    'updatedCount' => 0,
                    'orderedIds'   => [],
                ];
            }

            $sortable = [];
            foreach ($existingRows as $idx => $row) {
                $qid    = (int) ($row['id'] ?? 0);
                $oldPos = $oldPosById[$qid];
                $newPos = $oldPos;

                if (array_key_exists($qid, $orderInput) || array_key_exists((string) $qid, $orderInput)) {
                    $rawVal = trim((string) ($orderInput[$qid] ?? $orderInput[(string) $qid] ?? ''));
                    if ($rawVal === '' || ! ctype_digit($rawVal) || (int) $rawVal <= 0) {
                        return [
                            'success'      => false,
                            'error'        => 'Nomor urut setiap pertanyaan harus berupa angka positif minimal 1.',
                            'updatedCount' => 0,
                            'orderedIds'   => [],
                        ];
                    }
                    $newPos = (int) $rawVal;
                }

                $direction = ($newPos < $oldPos) ? 0 : (($newPos > $oldPos) ? 2 : 1);
                $sortable[] = [
                    'id'        => $qid,
                    'newPos'    => $newPos,
                    'direction' => $direction,
                    'oldPos'    => $oldPos,
                    'origIdx'   => $idx,
                ];
            }

            usort($sortable, static function (array $a, array $b): int {
                return [$a['newPos'], $a['direction'], $a['oldPos'], $a['origIdx'], $a['id']]
                    <=> [$b['newPos'], $b['direction'], $b['oldPos'], $b['origIdx'], $b['id']];
            });

            $finalOrder = array_map(static fn (array $item): int => (int) $item['id'], $sortable);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->transStart();
        foreach ($finalOrder as $seqIdx => $qid) {
            $this->db->table($this->table)
                ->where('id', $qid)
                ->update([
                    'urutan'     => $seqIdx + 1,
                    'updated_at' => $now,
                ]);
        }
        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return [
                'success'      => false,
                'error'        => 'Gagal menyimpan urutan pertanyaan ke database.',
                'updatedCount' => 0,
                'orderedIds'   => [],
            ];
        }

        return [
            'success'      => true,
            'error'        => null,
            'updatedCount' => count($finalOrder),
            'orderedIds'   => $finalOrder,
        ];
    }

    /**
     * Memperbarui pertanyaan mana saja yang ditampilkan pada form tracer studi awal (B-12).
     *
     * @param array<int|string> $selectedIds
     *
     * @return array{success: bool, error: ?string, activeCount: int, inactiveCount: int}
     */
    public function updateTampilPertanyaan(array $selectedIds): array
    {
        if (! $this->isTableReady()) {
            return [
                'success'       => false,
                'error'         => 'Tabel pertanyaan_tracer_studi_awal belum tersedia. Jalankan migrasi terlebih dahulu.',
                'activeCount'   => 0,
                'inactiveCount' => 0,
            ];
        }

        $rows = $this->db->table($this->table)->select('id')->get()->getResultArray();
        if ($rows === []) {
            return [
                'success'       => false,
                'error'         => 'Belum ada pertanyaan pada daftar untuk diatur.',
                'activeCount'   => 0,
                'inactiveCount' => 0,
            ];
        }

        $existingIds = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $rows);
        $requested   = array_values(array_unique(array_filter(
            array_map('intval', $selectedIds),
            static fn (int $id): bool => $id > 0
        )));

        $activeIds = array_values(array_intersect($existingIds, $requested));
        if ($activeIds === []) {
            return [
                'success'       => false,
                'error'         => 'Pilih minimal satu pertanyaan untuk ditampilkan pada form tracer study awal.',
                'activeCount'   => 0,
                'inactiveCount' => count($existingIds),
            ];
        }

        $inactiveIds = array_values(array_diff($existingIds, $activeIds));
        $now         = date('Y-m-d H:i:s');

        $this->db->transStart();
        $this->db->table($this->table)
            ->whereIn('id', $activeIds)
            ->update([
                'status'     => 'AKTIF',
                'updated_at' => $now,
            ]);

        if ($inactiveIds !== []) {
            $this->db->table($this->table)
                ->whereIn('id', $inactiveIds)
                ->update([
                    'status'     => 'NON-AKTIF',
                    'updated_at' => $now,
                ]);
        }
        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return [
                'success'       => false,
                'error'         => 'Gagal menyimpan pengaturan tampil pertanyaan ke database.',
                'activeCount'   => 0,
                'inactiveCount' => 0,
            ];
        }

        return [
            'success'       => true,
            'error'         => null,
            'activeCount'   => count($activeIds),
            'inactiveCount' => count($inactiveIds),
        ];
    }

    /**
     * @param mixed $raw
     *
     * @return string[]
     */
    protected static function normalizeOpsiInput($raw): array
    {
        if (is_array($raw)) {
            $items = [];
            foreach ($raw as $item) {
                $text = trim((string) $item);
                if ($text !== '') {
                    $items[] = $text;
                }
            }

            return array_values(array_unique($items));
        }

        return array_values(array_unique(self::parseOpsiJawaban((string) $raw)));
    }

    /**
     * Mengurai daftar opsi jawaban dari JSON, baris baru, atau pemisah titik koma.
     *
     * @return string[]
     */
    public static function parseOpsiJawaban(?string $raw): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $items = [];
            foreach ($decoded as $item) {
                $text = trim((string) $item);
                if ($text !== '') {
                    $items[] = $text;
                }
            }

            return $items;
        }

        $parts = preg_split('/[\r\n;]+/', $raw) ?: [];

        return array_values(array_filter(array_map('trim', $parts), static fn ($v) => $v !== ''));
    }
}
