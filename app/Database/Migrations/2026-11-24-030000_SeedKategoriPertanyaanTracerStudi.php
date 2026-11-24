<?php

namespace App\Database\Migrations;

use App\Models\BankPertanyaanTracerStudiModel;
use App\Models\KategoriTracerStudiModel;
use App\Models\PertanyaanTracerStudiAwalModel;
use CodeIgniter\Database\Migration;

/**
 * Menambahkan 9 pertanyaan Tracer Study standar DIKTI (3 pertanyaan per kategori)
 * untuk kategori "Masa Tunggu & Gaji", "Relevansi & Kompetensi", dan "Status & Pekerjaan".
 *
 * Pembagian per kategori:
 * - 2 pertanyaan langsung berstatus AKTIF pada `pertanyaan_tracer_studi_awal` (total 6 aktif)
 * - 1 pertanyaan cadangan nonaktif pada `bank_pertanyaan_tracer_studi` (total 3 bank)
 *
 * Bersifat idempoten: hanya menambahkan pertanyaan jika teks ternormalisasi belum ada di Form Aktif maupun Bank.
 */
class SeedKategoriPertanyaanTracerStudi extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        $this->ensureTargetCategoriesExist($now);

        $existingNorms = $this->collectExistingNormalizedTexts();
        $this->seedActiveQuestions($existingNorms, $now);
        $this->seedBankQuestions($existingNorms, $now);
    }

    public function down()
    {
        $allSeedTexts = array_map(
            static fn (array $item): string => PertanyaanTracerStudiAwalModel::normalizeQuestionText((string) ($item['teks_pertanyaan'] ?? '')),
            array_merge(self::activeQuestionDefinitions(), self::bankQuestionDefinitions())
        );
        $targetSet = array_flip(array_filter($allSeedTexts, static fn (string $v): bool => $v !== ''));

        if ($this->db->tableExists('pertanyaan_tracer_studi_awal')) {
            $answeredIds = [];
            if ($this->db->tableExists('jawaban_tracer_studi_awal')) {
                foreach ($this->db->table('jawaban_tracer_studi_awal')->select('pertanyaan_id')->distinct()->get()->getResultArray() as $a) {
                    $answeredIds[(int) ($a['pertanyaan_id'] ?? 0)] = true;
                }
            }

            $rowsToDelete = [];
            foreach ($this->db->table('pertanyaan_tracer_studi_awal')->select('id, teks_pertanyaan')->get()->getResultArray() as $row) {
                $qid  = (int) ($row['id'] ?? 0);
                $norm = PertanyaanTracerStudiAwalModel::normalizeQuestionText((string) ($row['teks_pertanyaan'] ?? ''));
                if ($qid > 0 && isset($targetSet[$norm]) && ! isset($answeredIds[$qid])) {
                    $rowsToDelete[] = $qid;
                }
            }

            if ($rowsToDelete !== []) {
                $this->db->table('pertanyaan_tracer_studi_awal')->whereIn('id', $rowsToDelete)->delete();
                (new PertanyaanTracerStudiAwalModel($this->db))->normalizeUrutan();
            }
        }

        if ($this->db->tableExists('bank_pertanyaan_tracer_studi')) {
            $bankIdsToDelete = [];
            foreach ($this->db->table('bank_pertanyaan_tracer_studi')->select('id, teks_pertanyaan')->get()->getResultArray() as $row) {
                $bid  = (int) ($row['id'] ?? 0);
                $norm = PertanyaanTracerStudiAwalModel::normalizeQuestionText((string) ($row['teks_pertanyaan'] ?? ''));
                if ($bid > 0 && isset($targetSet[$norm])) {
                    $bankIdsToDelete[] = $bid;
                }
            }

            if ($bankIdsToDelete !== []) {
                $this->db->table('bank_pertanyaan_tracer_studi')->whereIn('id', $bankIdsToDelete)->delete();
            }
        }
    }

    /**
     * Daftar 6 pertanyaan yang langsung diaktifkan di form mahasiswa (2 per kategori).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function activeQuestionDefinitions(): array
    {
        return [
            [
                'kategori'        => 'Status & Pekerjaan',
                'teks_pertanyaan' => 'Bagaimana status atau rencana aktivitas Anda saat ini menjelang kelulusan?',
                'tipe_jawaban'    => 'PILIHAN_GANDA',
                'opsi_jawaban'    => json_encode([
                    'Sudah bekerja (Full-time / Part-time)',
                    'Wiraswasta / Mendirikan usaha',
                    'Melanjutkan studi (S2 / Profesi)',
                    'Sedang mencari pekerjaan',
                    'Belum memutuskan / Belum mencari kerja',
                ], JSON_UNESCAPED_UNICODE),
                'is_wajib'        => 1,
                'status'          => 'AKTIF',
            ],
            [
                'kategori'        => 'Status & Pekerjaan',
                'teks_pertanyaan' => 'Apabila sudah bekerja, magang lanjut, atau berwirausaha, tuliskan nama instansi/perusahaan serta posisi/bidang Anda.',
                'tipe_jawaban'    => 'ISIAN_SINGKAT',
                'opsi_jawaban'    => null,
                'is_wajib'        => 0,
                'status'          => 'AKTIF',
            ],
            [
                'kategori'        => 'Masa Tunggu & Gaji',
                'teks_pertanyaan' => 'Berapa lama perkiraan masa tunggu Anda hingga memperoleh pekerjaan pertama atau memulai usaha (dihitung sebelum/sesudah lulus)?',
                'tipe_jawaban'    => 'PILIHAN_GANDA',
                'opsi_jawaban'    => json_encode([
                    'Sudah bekerja sebelum lulus (< 0 bulan)',
                    'Kurang dari 3 bulan',
                    '3 - 6 bulan',
                    'Lebih dari 6 bulan',
                    'Belum bekerja / sedang mencari kerja',
                    'Tidak mencari kerja (melanjutkan studi / lainnya)',
                ], JSON_UNESCAPED_UNICODE),
                'is_wajib'        => 1,
                'status'          => 'AKTIF',
            ],
            [
                'kategori'        => 'Masa Tunggu & Gaji',
                'teks_pertanyaan' => 'Berapa kisaran penghasilan (take-home pay) per bulan pada pekerjaan atau usaha Anda saat ini?',
                'tipe_jawaban'    => 'PILIHAN_GANDA',
                'opsi_jawaban'    => json_encode([
                    'Di bawah Rp 3.000.000',
                    'Rp 3.000.000 - Rp 5.000.000',
                    'Rp 5.000.001 - Rp 8.000.000',
                    'Di atas Rp 8.000.000',
                    'Belum berpenghasilan',
                ], JSON_UNESCAPED_UNICODE),
                'is_wajib'        => 1,
                'status'          => 'AKTIF',
            ],
            [
                'kategori'        => 'Relevansi & Kompetensi',
                'teks_pertanyaan' => 'Seberapa erat kesesuaian bidang pekerjaan atau rencana studi lanjut Anda dengan program studi yang ditempuh?',
                'tipe_jawaban'    => 'PILIHAN_GANDA',
                'opsi_jawaban'    => json_encode([
                    'Sangat erat / sangat sesuai',
                    'Erat / sesuai',
                    'Cukup erat / cukup sesuai',
                    'Kurang sesuai',
                    'Tidak relevan / lintas bidang',
                ], JSON_UNESCAPED_UNICODE),
                'is_wajib'        => 1,
                'status'          => 'AKTIF',
            ],
            [
                'kategori'        => 'Relevansi & Kompetensi',
                'teks_pertanyaan' => 'Seberapa besar kurikulum, praktikum, dan mata kuliah di program studi membekali kompetensi yang Anda butuhkan di dunia kerja?',
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
        ];
    }

    /**
     * Daftar 3 pertanyaan cadangan nonaktif pada Bank Pertanyaan (1 per kategori).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function bankQuestionDefinitions(): array
    {
        return [
            [
                'kategori'         => 'Status & Pekerjaan',
                'teks_pertanyaan'  => 'Melalui jalur utama apa Anda memperoleh pekerjaan, tawaran kerja, atau peluang usaha pertama Anda?',
                'tipe_jawaban'     => 'PILIHAN_GANDA',
                'opsi_jawaban'     => json_encode([
                    'Program magang / MBKM sebelumnya',
                    'Bursa kerja / Career Center Universitas',
                    'Portal lowongan kerja daring (LinkedIn, JobStreet, Glints, dll.)',
                    'Relasi dosen / alumni / rekan',
                    'Melamar langsung ke perusahaan / instansi',
                    'Belum bekerja / membuka usaha',
                ], JSON_UNESCAPED_UNICODE),
                'is_wajib_default' => 1,
            ],
            [
                'kategori'         => 'Masa Tunggu & Gaji',
                'teks_pertanyaan'  => 'Berapa bulan perkiraan waktu tunggu sejak Anda mulai aktif melamar kerja hingga mendapatkan panggilan wawancara atau penawaran kerja pertama?',
                'tipe_jawaban'     => 'ISIAN_SINGKAT',
                'opsi_jawaban'     => null,
                'is_wajib_default' => 0,
            ],
            [
                'kategori'         => 'Relevansi & Kompetensi',
                'teks_pertanyaan'  => 'Kompetensi teknis, sertifikasi, atau soft skill apa yang menurut Anda paling dibutuhkan di dunia kerja dan perlu diperkuat di program studi?',
                'tipe_jawaban'     => 'TEKS',
                'opsi_jawaban'     => null,
                'is_wajib_default' => 0,
            ],
        ];
    }

    private function ensureTargetCategoriesExist(string $now): void
    {
        if (! $this->db->tableExists('kategori_tracer_studi')) {
            return;
        }

        $targetSeeds = array_filter(
            KategoriTracerStudiModel::defaultSeeds(),
            static fn (array $seed): bool => in_array(
                $seed['nama_kategori'],
                ['Status & Pekerjaan', 'Masa Tunggu & Gaji', 'Relevansi & Kompetensi'],
                true
            )
        );

        $existingKeys = [];
        foreach ($this->db->table('kategori_tracer_studi')->select('nama_kategori')->get()->getResultArray() as $row) {
            $key = KategoriTracerStudiModel::normalizeKey((string) ($row['nama_kategori'] ?? ''));
            if ($key !== '') {
                $existingKeys[$key] = true;
            }
        }

        $toInsert = [];
        foreach ($targetSeeds as $seed) {
            $key = KategoriTracerStudiModel::normalizeKey($seed['nama_kategori']);
            if (! isset($existingKeys[$key])) {
                $existingKeys[$key] = true;
                $toInsert[]         = [
                    'nama_kategori' => $seed['nama_kategori'],
                    'deskripsi'     => $seed['deskripsi'],
                    'is_default'    => 0,
                    'created_by'    => 'Sistem',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
        }

        if ($toInsert !== []) {
            $this->db->table('kategori_tracer_studi')->insertBatch($toInsert);
        }
    }

    /**
     * @return array<string, bool>
     */
    private function collectExistingNormalizedTexts(): array
    {
        $norms = [];

        if ($this->db->tableExists('pertanyaan_tracer_studi_awal')) {
            foreach ($this->db->table('pertanyaan_tracer_studi_awal')->select('teks_pertanyaan')->get()->getResultArray() as $row) {
                $norm = PertanyaanTracerStudiAwalModel::normalizeQuestionText((string) ($row['teks_pertanyaan'] ?? ''));
                if ($norm !== '') {
                    $norms[$norm] = true;
                }
            }
        }

        if ($this->db->tableExists('bank_pertanyaan_tracer_studi')) {
            foreach ($this->db->table('bank_pertanyaan_tracer_studi')->select('teks_pertanyaan')->get()->getResultArray() as $row) {
                $norm = PertanyaanTracerStudiAwalModel::normalizeQuestionText((string) ($row['teks_pertanyaan'] ?? ''));
                if ($norm !== '') {
                    $norms[$norm] = true;
                }
            }
        }

        return $norms;
    }

    /**
     * @param array<string, bool> $existingNorms
     */
    private function seedActiveQuestions(array &$existingNorms, string $now): void
    {
        if (! $this->db->tableExists('pertanyaan_tracer_studi_awal')) {
            return;
        }

        $hasKategoriCol = $this->db->fieldExists('kategori', 'pertanyaan_tracer_studi_awal');
        $maxRow         = $this->db->table('pertanyaan_tracer_studi_awal')->selectMax('urutan')->get()->getRowArray();
        $nextUrutan     = (int) ($maxRow['urutan'] ?? 0);

        $rows = [];
        foreach (self::activeQuestionDefinitions() as $def) {
            $norm = PertanyaanTracerStudiAwalModel::normalizeQuestionText((string) $def['teks_pertanyaan']);
            if ($norm === '' || isset($existingNorms[$norm])) {
                continue;
            }
            $existingNorms[$norm] = true;
            $nextUrutan++;

            $row = [
                'teks_pertanyaan' => $def['teks_pertanyaan'],
                'urutan'          => $nextUrutan,
                'tipe_jawaban'    => $def['tipe_jawaban'],
                'opsi_jawaban'    => $def['opsi_jawaban'],
                'is_wajib'        => $def['is_wajib'],
                'status'          => $def['status'],
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
            if ($hasKategoriCol) {
                $row['kategori'] = $def['kategori'];
            }

            $rows[] = $row;
        }

        if ($rows !== []) {
            $this->db->table('pertanyaan_tracer_studi_awal')->insertBatch($rows);
        }
    }

    /**
     * @param array<string, bool> $existingNorms
     */
    private function seedBankQuestions(array &$existingNorms, string $now): void
    {
        if (! $this->db->tableExists('bank_pertanyaan_tracer_studi')) {
            return;
        }

        $rows = [];
        foreach (self::bankQuestionDefinitions() as $def) {
            $norm = PertanyaanTracerStudiAwalModel::normalizeQuestionText((string) $def['teks_pertanyaan']);
            if ($norm === '' || isset($existingNorms[$norm])) {
                continue;
            }
            $existingNorms[$norm] = true;

            $rows[] = [
                'kategori'         => $def['kategori'],
                'teks_pertanyaan'  => $def['teks_pertanyaan'],
                'tipe_jawaban'     => $def['tipe_jawaban'],
                'opsi_jawaban'     => $def['opsi_jawaban'],
                'is_wajib_default' => $def['is_wajib_default'],
                'created_by'       => 'Sistem',
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }

        if ($rows !== []) {
            $this->db->table('bank_pertanyaan_tracer_studi')->insertBatch($rows);
        }
    }
}
