<?php

namespace App\Database\Migrations;

use App\Models\BankPertanyaanTracerStudiModel;
use App\Models\KategoriTracerStudiModel;
use App\Models\PertanyaanTracerStudiAwalModel;
use CodeIgniter\Database\Migration;

/**
 * Migration penambahan Master Kategori Tracer Study (`kategori_tracer_studi`)
 * serta kolom `kategori` pada `pertanyaan_tracer_studi_awal` agar kategori pertanyaan aktif
 * tersinkron penuh dengan Bank Pertanyaan.
 */
class CreateKategoriTracerStudiTable extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('kategori_tracer_studi')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'nama_kategori' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 80,
                    'null'       => false,
                ],
                'deskripsi' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'is_default' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'created_by' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('nama_kategori');
            $this->forge->createTable('kategori_tracer_studi', true);
            $this->db->resetDataCache();
        }

        if (
            $this->db->tableExists('pertanyaan_tracer_studi_awal')
            && ! $this->db->fieldExists('kategori', 'pertanyaan_tracer_studi_awal')
        ) {
            $this->forge->addColumn('pertanyaan_tracer_studi_awal', [
                'kategori' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 80,
                    'default'    => '',
                    'after'      => $this->db->fieldExists('bank_pertanyaan_id', 'pertanyaan_tracer_studi_awal')
                        ? 'bank_pertanyaan_id'
                        : 'id',
                ],
            ]);
            $this->db->resetDataCache();

            $this->backfillActiveQuestionCategories();
        }

        $this->seedInitialCategories();
    }

    public function down()
    {
        if (
            $this->db->tableExists('pertanyaan_tracer_studi_awal')
            && $this->db->fieldExists('kategori', 'pertanyaan_tracer_studi_awal')
        ) {
            $this->forge->dropColumn('pertanyaan_tracer_studi_awal', 'kategori');
            $this->db->resetDataCache();
        }

        if ($this->db->tableExists('kategori_tracer_studi')) {
            $this->forge->dropTable('kategori_tracer_studi', true);
            $this->db->resetDataCache();
        }
    }

    /**
     * Memetakan pertanyaan bawaan yang sudah ada pada pertanyaan_tracer_studi_awal ke kategori awal yang sesuai.
     */
    private function backfillActiveQuestionCategories(): void
    {
        $bankCategoryByText = [];
        if (
            $this->db->tableExists('bank_pertanyaan_tracer_studi')
            && $this->db->fieldExists('kategori', 'bank_pertanyaan_tracer_studi')
        ) {
            foreach ($this->db->table('bank_pertanyaan_tracer_studi')->select('teks_pertanyaan, kategori')->get()->getResultArray() as $b) {
                $norm = PertanyaanTracerStudiAwalModel::normalizeQuestionText((string) ($b['teks_pertanyaan'] ?? ''));
                $kat  = KategoriTracerStudiModel::cleanNamaKategori((string) ($b['kategori'] ?? ''));
                if ($norm !== '' && $kat !== '') {
                    $bankCategoryByText[$norm] = $kat;
                }
            }
        }

        $defaultTextCategories = PertanyaanTracerStudiAwalModel::defaultKategoriByQuestionText();
        $rows                  = $this->db->table('pertanyaan_tracer_studi_awal')->select('id, teks_pertanyaan, kategori')->get()->getResultArray();
        $now                   = date('Y-m-d H:i:s');

        foreach ($rows as $row) {
            $current = trim((string) ($row['kategori'] ?? ''));
            if ($current !== '' && strcasecmp($current, 'Umum') !== 0) {
                continue;
            }

            $norm       = PertanyaanTracerStudiAwalModel::normalizeQuestionText((string) ($row['teks_pertanyaan'] ?? ''));
            $mappedKat  = $bankCategoryByText[$norm] ?? $defaultTextCategories[$norm] ?? '';

            if ($mappedKat !== $current) {
                $this->db->table('pertanyaan_tracer_studi_awal')
                    ->where('id', (int) $row['id'])
                    ->update([
                        'kategori'   => $mappedKat,
                        'updated_at' => $now,
                    ]);
            }
        }
    }

    /**
     * Mengisi kategori bawaan dan menyinkronkan kategori kustom yang sudah ada di Bank maupun Form Aktif.
     */
    private function seedInitialCategories(): void
    {
        if (! $this->db->tableExists('kategori_tracer_studi')) {
            return;
        }

        $now          = date('Y-m-d H:i:s');
        $existingRows = $this->db->table('kategori_tracer_studi')->select('nama_kategori')->get()->getResultArray();
        $seenKeys     = [];

        foreach ($existingRows as $row) {
            $key = KategoriTracerStudiModel::normalizeKey((string) ($row['nama_kategori'] ?? ''));
            if ($key !== '') {
                $seenKeys[$key] = true;
            }
        }

        $toInsert = [];
        foreach (KategoriTracerStudiModel::defaultSeeds() as $seed) {
            $key = KategoriTracerStudiModel::normalizeKey($seed['nama_kategori']);
            if (! isset($seenKeys[$key])) {
                $seenKeys[$key] = true;
                $toInsert[]     = [
                    'nama_kategori' => $seed['nama_kategori'],
                    'deskripsi'     => $seed['deskripsi'],
                    'is_default'    => $seed['is_default'],
                    'created_by'    => 'Sistem',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
        }

        $appendDistinctFromTable = function (string $tableName) use (&$seenKeys, &$toInsert, $now): void {
            if (! $this->db->tableExists($tableName) || ! $this->db->fieldExists('kategori', $tableName)) {
                return;
            }

            $rows = $this->db->table($tableName)->select('kategori')->distinct()->get()->getResultArray();
            foreach ($rows as $row) {
                $clean = KategoriTracerStudiModel::cleanNamaKategori((string) ($row['kategori'] ?? ''));
                if ($clean === '') {
                    continue;
                }
                $key = KategoriTracerStudiModel::normalizeKey($clean);
                if (! isset($seenKeys[$key])) {
                    $seenKeys[$key] = true;
                    $toInsert[]     = [
                        'nama_kategori' => mb_substr($clean, 0, KategoriTracerStudiModel::MAX_NAMA_LENGTH),
                        'deskripsi'     => null,
                        'is_default'    => 0,
                        'created_by'    => 'Sistem',
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }
            }
        };

        $appendDistinctFromTable('bank_pertanyaan_tracer_studi');
        $appendDistinctFromTable('pertanyaan_tracer_studi_awal');

        if ($toInsert !== []) {
            $this->db->table('kategori_tracer_studi')->insertBatch($toInsert);
        }
    }
}
