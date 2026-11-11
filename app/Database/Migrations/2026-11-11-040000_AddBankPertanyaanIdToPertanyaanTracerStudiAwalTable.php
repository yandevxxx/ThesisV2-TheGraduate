<?php

namespace App\Database\Migrations;

use App\Models\PertanyaanTracerStudiAwalModel;
use CodeIgniter\Database\Migration;

/**
 * Menambahkan kolom referensi opsional bank_pertanyaan_id ke pertanyaan_tracer_studi_awal.
 */
class AddBankPertanyaanIdToPertanyaanTracerStudiAwalTable extends Migration
{
    public function up()
    {
        if (
            $this->db->tableExists('pertanyaan_tracer_studi_awal')
            && ! $this->db->fieldExists('bank_pertanyaan_id', 'pertanyaan_tracer_studi_awal')
        ) {
            $this->forge->addColumn('pertanyaan_tracer_studi_awal', [
                'bank_pertanyaan_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'id',
                ],
            ]);

            $this->db->resetDataCache();

            if ($this->db->tableExists('bank_pertanyaan_tracer_studi')) {
                $bankMap = [];
                foreach ($this->db->table('bank_pertanyaan_tracer_studi')->get()->getResultArray() as $b) {
                    $norm = PertanyaanTracerStudiAwalModel::normalizeQuestionText((string) ($b['teks_pertanyaan'] ?? ''));
                    if ($norm !== '') {
                        $bankMap[$norm] = (int) ($b['id'] ?? 0);
                    }
                }

                if ($bankMap !== []) {
                    $formRows = $this->db->table('pertanyaan_tracer_studi_awal')->get()->getResultArray();
                    foreach ($formRows as $row) {
                        $norm = PertanyaanTracerStudiAwalModel::normalizeQuestionText((string) ($row['teks_pertanyaan'] ?? ''));
                        if ($norm !== '' && isset($bankMap[$norm]) && $bankMap[$norm] > 0) {
                            $this->db->table('pertanyaan_tracer_studi_awal')
                                ->where('id', (int) $row['id'])
                                ->update(['bank_pertanyaan_id' => $bankMap[$norm]]);
                        }
                    }
                }
            }
        }
    }

    public function down()
    {
        if (
            $this->db->tableExists('pertanyaan_tracer_studi_awal')
            && $this->db->fieldExists('bank_pertanyaan_id', 'pertanyaan_tracer_studi_awal')
        ) {
            $this->forge->dropColumn('pertanyaan_tracer_studi_awal', 'bank_pertanyaan_id');
            $this->db->resetDataCache();
        }
    }
}
