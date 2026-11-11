<?php

namespace App\Database\Migrations;

use App\Models\BankPertanyaanTracerStudiModel;
use App\Models\PertanyaanTracerStudiAwalModel;
use CodeIgniter\Database\Migration;

/**
 * Menjaga Bank Pertanyaan Tracer Study agar hanya berisi pertanyaan berstatus nonaktif.
 * Pertanyaan nonaktif di form dipindahkan ke Bank, dan pertanyaan aktif dihapus dari Bank.
 */
class CleanActiveQuestionsFromBankTracerStudi extends Migration
{
    public function up()
    {
        if (
            ! $this->db->tableExists('pertanyaan_tracer_studi_awal')
            || ! $this->db->tableExists('bank_pertanyaan_tracer_studi')
        ) {
            return;
        }

        $pertanyaanModel = new PertanyaanTracerStudiAwalModel($this->db);
        $bankModel       = new BankPertanyaanTracerStudiModel($this->db);
        $pertanyaanModel->syncExclusiveWithBank($bankModel, 'Sistem');
    }

    public function down()
    {
        // Tidak memulihkan duplikat pertanyaan aktif ke Bank Pertanyaan.
    }
}
