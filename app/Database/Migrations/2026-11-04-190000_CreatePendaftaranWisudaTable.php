<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration pembuatan tabel pendaftaran_wisuda dan berkas_pendaftaran_wisuda untuk The Graduate
 */
class CreatePendaftaranWisudaTable extends Migration
{
    public function up()
    {
        // 1. Tabel pendaftaran_wisuda
        if (! $this->db->tableExists('pendaftaran_wisuda')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'npm' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                ],
                'nama' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => false,
                ],
                'nik' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => true,
                ],
                'tempat_lahir' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'tanggal_lahir' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'jenis_kelamin' => [
                    'type'       => 'ENUM',
                    'constraint' => ['Laki-laki', 'Perempuan'],
                    'null'       => true,
                ],
                'alamat' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'no_hp' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => true,
                ],
                'no_telepon' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => true,
                ],
                'email' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
                'prodi' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'total_sks' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'ipk' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '4,2',
                    'null'       => true,
                ],
                'kode_semester' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
                'tanggal_lulus' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'gelombang_wisuda' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
                'pembimbing1' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
                'pembimbing2' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
                'pembimbing_agama' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
                'dosen_penguji' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
                'judul_skripsi' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'pas_foto' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'status_verifikasi' => [
                    'type'       => 'ENUM',
                    'constraint' => ['MENUNGGU', 'DISETUJUI', 'DITOLAK'],
                    'default'    => 'MENUNGGU',
                ],
                'catatan_verifikasi' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'diverifikasi_oleh' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'diverifikasi_pada' => [
                    'type' => 'DATETIME',
                    'null' => true,
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
            $this->forge->addKey('npm');
            $this->forge->addKey('kode_semester');
            $this->forge->addKey('status_verifikasi');
            $this->forge->createTable('pendaftaran_wisuda', true);

            // Seed 6 data pendaftaran awal menggunakan insert satu per satu
            $now = date('Y-m-d H:i:s');
            $initialPendaftaran = [
                [
                    'npm'                => '2021010001',
                    'nama'               => 'Ahmad Fauzi',
                    'nik'                => '3201234567890001',
                    'prodi'              => 'Teknik Informatika',
                    'total_sks'          => 144,
                    'ipk'                => 3.52,
                    'no_hp'              => '0812-3456-7890',
                    'email'              => 'ahmad.fauzi@example.ac.id',
                    'kode_semester'      => '2026-2',
                    'tanggal_lulus'      => '2025-11-15',
                    'gelombang_wisuda'   => 'Gelombang 1 - 15 November 2025',
                    'judul_skripsi'      => 'Sistem Pendukung Keputusan Penentuan Beasiswa Mahasiswa Menggunakan Metode TOPSIS',
                    'pembimbing1'        => 'Dr. Budi Santoso, S.T., M.T.',
                    'pembimbing2'        => 'Rina Wulandari, S.Kom., M.Kom.',
                    'status_verifikasi'  => 'DISETUJUI',
                    'catatan_verifikasi' => null,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ],
                [
                    'npm'                => '2021010002',
                    'nama'               => 'Siti Rahmawati',
                    'nik'                => '3201234567890002',
                    'prodi'              => 'Sistem Informasi',
                    'total_sks'          => 142,
                    'ipk'                => 3.65,
                    'no_hp'              => '0813-4567-8901',
                    'email'              => 'siti.rahmawati@example.ac.id',
                    'kode_semester'      => '2026-2',
                    'tanggal_lulus'      => '2025-11-15',
                    'gelombang_wisuda'   => 'Gelombang 1 - 15 November 2025',
                    'judul_skripsi'      => 'Rancang Bangun Sistem Informasi Absensi dan Laporan Kerja Praktek Berbasis Web',
                    'pembimbing1'        => 'Dr. Agus Setiawan, S.Kom., M.Kom.',
                    'pembimbing2'        => 'Dewi Lestari, S.T., M.T.',
                    'status_verifikasi'  => 'MENUNGGU',
                    'catatan_verifikasi' => null,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ],
                [
                    'npm'                => '2021010003',
                    'nama'               => 'Rizky Pratama',
                    'nik'                => '3201234567890003',
                    'prodi'              => 'Teknik Informatika',
                    'total_sks'          => 144,
                    'ipk'                => 3.48,
                    'no_hp'              => '0814-5678-9012',
                    'email'              => 'rizky.pratama@example.ac.id',
                    'kode_semester'      => '2026-2',
                    'tanggal_lulus'      => '2025-11-15',
                    'gelombang_wisuda'   => 'Gelombang 1 - 15 November 2025',
                    'judul_skripsi'      => 'Klasifikasi Sentimen Ulasan Produk pada E-Commerce Menggunakan Algoritma Naive Bayes',
                    'pembimbing1'        => 'Dr. Budi Santoso, S.T., M.T.',
                    'pembimbing2'        => 'Rina Wulandari, S.Kom., M.Kom.',
                    'status_verifikasi'  => 'MENUNGGU',
                    'catatan_verifikasi' => null,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ],
                [
                    'npm'                => '2021010004',
                    'nama'               => 'Dinda Permata',
                    'nik'                => '3201234567890004',
                    'prodi'              => 'Desain Komunikasi Visual',
                    'total_sks'          => 140,
                    'ipk'                => 3.70,
                    'no_hp'              => '0815-6789-0123',
                    'email'              => 'dinda.permata@example.ac.id',
                    'kode_semester'      => '2026-2',
                    'tanggal_lulus'      => '2025-11-15',
                    'gelombang_wisuda'   => 'Gelombang 1 - 15 November 2025',
                    'judul_skripsi'      => 'Perancangan Identitas Visual dan Media Promosi Digital untuk UMKM Kuliner',
                    'pembimbing1'        => 'Yuni Kartika, S.Ds., M.Ds.',
                    'pembimbing2'        => 'Hendra Wijaya, S.Ds., M.Ds.',
                    'status_verifikasi'  => 'DISETUJUI',
                    'catatan_verifikasi' => null,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ],
                [
                    'npm'                => '2021010005',
                    'nama'               => 'Bagus Setiawan',
                    'nik'                => '3201234567890005',
                    'prodi'              => 'Sistem Informasi',
                    'total_sks'          => 142,
                    'ipk'                => 3.35,
                    'no_hp'              => '0816-7890-1234',
                    'email'              => 'bagus.setiawan@example.ac.id',
                    'kode_semester'      => '2026-2',
                    'tanggal_lulus'      => '2025-12-15',
                    'gelombang_wisuda'   => 'Gelombang 2 - 15 Desember 2025',
                    'judul_skripsi'      => 'Pengembangan Aplikasi Monitoring Stok Inventaris Berbasis Android pada PT Sinar Jaya',
                    'pembimbing1'        => 'Dewi Lestari, S.T., M.T.',
                    'pembimbing2'        => 'Dr. Agus Setiawan, S.Kom., M.Kom.',
                    'status_verifikasi'  => 'DITOLAK',
                    'catatan_verifikasi' => 'Pas foto belum berlatar belakang merah/biru pakaian formal. Harap unggah ulang.',
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ],
                [
                    'npm'                => '2021010006',
                    'nama'               => 'Nadia Aulia',
                    'nik'                => '3201234567890006',
                    'prodi'              => 'Teknik Informatika',
                    'total_sks'          => 144,
                    'ipk'                => 3.82,
                    'no_hp'              => '0817-8901-2345',
                    'email'              => 'nadia.aulia@example.ac.id',
                    'kode_semester'      => '2026-2',
                    'tanggal_lulus'      => '2025-12-15',
                    'gelombang_wisuda'   => 'Gelombang 2 - 15 Desember 2025',
                    'judul_skripsi'      => 'Deteksi Dini Penyakit Daun Tomat Menggunakan Convolutional Neural Network',
                    'pembimbing1'        => 'Rina Wulandari, S.Kom., M.Kom.',
                    'pembimbing2'        => 'Dr. Budi Santoso, S.T., M.T.',
                    'status_verifikasi'  => 'MENUNGGU',
                    'catatan_verifikasi' => null,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ],
            ];

            foreach ($initialPendaftaran as $row) {
                $this->db->table('pendaftaran_wisuda')->insert($row);
            }
        }

        // 2. Tabel berkas_pendaftaran_wisuda
        if (! $this->db->tableExists('berkas_pendaftaran_wisuda')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'pendaftaran_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'persyaratan_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'nama_persyaratan' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => false,
                ],
                'nama_file' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => false,
                ],
                'ukuran_kb' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'status_berkas' => [
                    'type'       => 'ENUM',
                    'constraint' => ['MENUNGGU', 'VALID', 'TIDAK_VALID'],
                    'default'    => 'MENUNGGU',
                ],
                'catatan' => [
                    'type' => 'TEXT',
                    'null' => true,
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
            $this->forge->addKey('pendaftaran_id');
            $this->forge->createTable('berkas_pendaftaran_wisuda', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('berkas_pendaftaran_wisuda')) {
            $this->forge->dropTable('berkas_pendaftaran_wisuda', true);
        }
        if ($this->db->tableExists('pendaftaran_wisuda')) {
            $this->forge->dropTable('pendaftaran_wisuda', true);
        }
    }
}
