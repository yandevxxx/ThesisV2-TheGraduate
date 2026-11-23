<?php

namespace App\Libraries;

/**
 * Server-side allowlist input validator for The Graduate subsystem.
 *
 * Enforces strict type, length, and format allowlists so malformed inputs
 * (including SQL injection payloads) are rejected before reaching queries.
 */
final class GraduateInputValidator
{
    public const NPM_PATTERN           = '/^\d{10}$/';
    public const NIK_PATTERN           = '/^\d{16}$/';
    public const PHONE_PATTERN         = '/^[0-9+\-\s]{8,20}$/';
    public const SEMESTER_CODE_PATTERN = '/^[A-Za-z0-9\-\/ ]{4,30}$/';
    public const INITIAL_PATTERN       = '/^[A-Za-z0-9.\- ]{1,20}$/';
    public const DATE_PATTERN          = '/^\d{4}-\d{2}-\d{2}$/';

    /**
     * Allowlist MIME type per file extension (lowercase) for uploaded files.
     *
     * @var array<string, string[]>
     */
    public const ALLOWED_MIME_BY_EXT = [
        'pdf'  => ['application/pdf', 'application/x-pdf'],
        'jpg'  => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png'  => ['image/png'],
        'doc'  => ['application/msword', 'application/vnd.ms-office', 'application/cdfv2'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/x-zip-compressed',
        ],
        'xls'  => ['application/vnd.ms-excel', 'application/vnd.ms-office', 'application/cdfv2'],
        'xlsx' => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/x-zip-compressed',
        ],
        'zip'  => ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'],
    ];

    /**
     * Validate NPM: required, strictly 10 ASCII digits [0-9].
     *
     * @return array{valid: bool, value: string, error: string|null}
     */
    public static function validateNpm(?string $raw): array
    {
        $clean = trim(preg_replace('/[\s\x{00a0}\x{200b}]+/u', '', (string) $raw) ?? '');

        if ($clean === '') {
            return ['valid' => false, 'value' => '', 'error' => 'NPM wajib diisi.'];
        }

        if (! preg_match('/^\d+$/', $clean)) {
            return ['valid' => false, 'value' => $clean, 'error' => 'NPM hanya boleh berisi angka (0-9).'];
        }

        $len = strlen($clean);
        if ($len < 10) {
            return ['valid' => false, 'value' => $clean, 'error' => 'NPM tidak valid: kurang dari 10 digit (harus tepat 10 digit).'];
        }
        if ($len > 10) {
            return ['valid' => false, 'value' => $clean, 'error' => 'NPM tidak valid: lebih dari 10 digit (harus tepat 10 digit).'];
        }

        return ['valid' => true, 'value' => $clean, 'error' => null];
    }

    /**
     * Validate NIK: required, strictly 16 ASCII digits [0-9] (no silent stripping).
     *
     * @return array{valid: bool, value: string, error: string|null}
     */
    public static function validateNik(?string $raw): array
    {
        $clean = trim((string) $raw);

        if ($clean === '') {
            return ['valid' => false, 'value' => '', 'error' => 'NIK wajib diisi.'];
        }

        if (! preg_match(self::NIK_PATTERN, $clean)) {
            return ['valid' => false, 'value' => $clean, 'error' => 'NIK harus terdiri dari tepat 16 digit angka (0-9).'];
        }

        return ['valid' => true, 'value' => $clean, 'error' => null];
    }

    /**
     * Validate email format and max length (150 chars).
     *
     * @return array{valid: bool, value: string, error: string|null}
     */
    public static function validateEmail(?string $raw, bool $required = true): array
    {
        $clean = trim((string) $raw);

        if ($clean === '') {
            if (! $required) {
                return ['valid' => true, 'value' => '', 'error' => null];
            }

            return ['valid' => false, 'value' => '', 'error' => 'Alamat email wajib diisi.'];
        }

        if (strlen($clean) > 150 || filter_var($clean, FILTER_VALIDATE_EMAIL) === false) {
            return ['valid' => false, 'value' => $clean, 'error' => 'Format alamat email tidak valid atau melebihi 150 karakter.'];
        }

        return ['valid' => true, 'value' => $clean, 'error' => null];
    }

    /**
     * Validate phone/mobile number using allowlist [0-9 + - space], 8..20 chars.
     *
     * @return array{valid: bool, value: string, error: string|null}
     */
    public static function validatePhone(?string $raw, bool $required = false, string $label = 'Nomor HP'): array
    {
        $clean = trim((string) $raw);

        if ($clean === '') {
            if (! $required) {
                return ['valid' => true, 'value' => '', 'error' => null];
            }

            return ['valid' => false, 'value' => '', 'error' => "{$label} wajib diisi."];
        }

        if (! preg_match(self::PHONE_PATTERN, $clean)) {
            return ['valid' => false, 'value' => $clean, 'error' => "{$label} hanya boleh berisi angka, spasi, +, atau - (8-20 karakter)."];
        }

        return ['valid' => true, 'value' => $clean, 'error' => null];
    }

    /**
     * Validate IPK: decimal 0.00 - 4.00.
     *
     * @return array{valid: bool, value: float|null, error: string|null}
     */
    public static function validateIpk($raw, bool $required = false): array
    {
        $clean = str_replace(',', '.', trim((string) $raw));

        if ($clean === '') {
            if (! $required) {
                return ['valid' => true, 'value' => null, 'error' => null];
            }

            return ['valid' => false, 'value' => null, 'error' => 'IPK wajib diisi.'];
        }

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $clean)) {
            return ['valid' => false, 'value' => null, 'error' => 'IPK harus berupa angka desimal yang valid (maksimal 2 angka di belakang koma).'];
        }

        $num = (float) $clean;
        if ($num < 0.0 || $num > 4.0) {
            return ['valid' => false, 'value' => null, 'error' => 'IPK harus berada pada rentang 0.00 sampai 4.00.'];
        }

        return ['valid' => true, 'value' => $num, 'error' => null];
    }

    /**
     * Validate Total SKS: integer 1 - 250.
     *
     * @return array{valid: bool, value: int|null, error: string|null}
     */
    public static function validateSks($raw, bool $required = false): array
    {
        $clean = trim((string) $raw);

        if ($clean === '') {
            if (! $required) {
                return ['valid' => true, 'value' => null, 'error' => null];
            }

            return ['valid' => false, 'value' => null, 'error' => 'Total SKS wajib diisi.'];
        }

        if (! preg_match('/^\d{1,3}$/', $clean)) {
            return ['valid' => false, 'value' => null, 'error' => 'Total SKS harus berupa bilangan bulat positif.'];
        }

        $num = (int) $clean;
        if ($num < 1 || $num > 250) {
            return ['valid' => false, 'value' => null, 'error' => 'Total SKS harus berada pada rentang 1 sampai 250.'];
        }

        return ['valid' => true, 'value' => $num, 'error' => null];
    }

    /**
     * Validate YYYY-MM-DD date string strictly.
     *
     * @return array{valid: bool, value: string|null, error: string|null}
     */
    public static function validateDate(?string $raw, bool $required = false, string $label = 'Tanggal'): array
    {
        $clean = trim((string) $raw);

        if ($clean === '') {
            if (! $required) {
                return ['valid' => true, 'value' => null, 'error' => null];
            }

            return ['valid' => false, 'value' => null, 'error' => "{$label} wajib diisi."];
        }

        if (! preg_match(self::DATE_PATTERN, $clean)) {
            return ['valid' => false, 'value' => null, 'error' => "Format {$label} tidak valid. Gunakan format YYYY-MM-DD."];
        }

        $dt = \DateTime::createFromFormat('Y-m-d', $clean);
        if (! $dt || $dt->format('Y-m-d') !== $clean) {
            return ['valid' => false, 'value' => null, 'error' => "{$label} bukan tanggal kalender yang valid."];
        }

        return ['valid' => true, 'value' => $clean, 'error' => null];
    }

    /**
     * Validasi tanggal lahir mahasiswa dengan batas usia minimal (default 15 tahun)
     * dihitung dari tahun semester aktif.
     *
     * @return array{valid: bool, value: string|null, error: string|null, usia: int|null}
     */
    public static function validateTanggalLahir(?string $raw, int $tahunSemester, int $minAge = 15): array
    {
        $baseCheck = self::validateDate($raw, true, 'Tanggal Lahir');
        if (! $baseCheck['valid']) {
            return $baseCheck + ['usia' => null];
        }

        $tanggalLahir = (string) $baseCheck['value'];
        $tahunLahir = (int) substr($tanggalLahir, 0, 4);

        $usia = $tahunSemester - $tahunLahir;
        if ($usia < $minAge) {
            return [
                'valid' => false,
                'value' => $tanggalLahir,
                'error' => "Usia mahasiswa minimal {$minAge} tahun dihitung dari tahun semester aktif ({$tahunSemester}). Tanggal lahir yang dimasukkan ({$tanggalLahir}) belum memenuhi batas minimal.",
                'usia'  => $usia,
            ];
        }

        if ($usia > 100) {
            return [
                'valid' => false,
                'value' => $tanggalLahir,
                'error' => 'Tanggal lahir tidak valid.',
                'usia'  => $usia,
            ];
        }

        return [
            'valid' => true,
            'value' => $tanggalLahir,
            'error' => null,
            'usia'  => $usia,
        ];
    }

    /**
     * Validate display/person name (1..150 chars, no ASCII control chars).
     * Note: Allows valid punctuation like apostrophes (e.g. Ma'ruf); HTML escaping
     * is handled at output via esc().
     *
     * @return array{valid: bool, value: string, error: string|null}
     */
    public static function validateNama(?string $raw, int $maxLength = 150): array
    {
        $clean = trim((string) (preg_replace('/[\s\x{00a0}\x{200b}]+/u', ' ', (string) $raw) ?? ''));

        if ($clean === '') {
            return ['valid' => false, 'value' => '', 'error' => 'Nama wajib diisi.'];
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $clean)) {
            return ['valid' => false, 'value' => '', 'error' => 'Nama mengandung karakter kontrol yang tidak diizinkan.'];
        }

        if (mb_strlen($clean, 'UTF-8') > $maxLength) {
            return ['valid' => false, 'value' => '', 'error' => "Nama maksimal {$maxLength} karakter."];
        }

        return ['valid' => true, 'value' => $clean, 'error' => null];
    }

    /**
     * Validate Graduate semester code allowlist.
     *
     * @return array{valid: bool, value: string, error: string|null}
     */
    public static function validateKodeSemester(?string $raw, bool $required = true): array
    {
        $clean = trim((string) $raw);

        if ($clean === '') {
            if (! $required) {
                return ['valid' => true, 'value' => '', 'error' => null];
            }

            return ['valid' => false, 'value' => '', 'error' => 'Kode semester wajib diisi.'];
        }

        if (! preg_match(self::SEMESTER_CODE_PATTERN, $clean)) {
            return ['valid' => false, 'value' => $clean, 'error' => 'Format kode semester tidak valid.'];
        }

        return ['valid' => true, 'value' => $clean, 'error' => null];
    }

    /**
     * Validate an uploaded file by extension allowlist, server-detected MIME type,
     * and maximum size in kilobytes.
     *
     * @param mixed    $file
     * @param string[] $allowedExts
     *
     * @return array{valid: bool, ext: string, mime: string, sizeKb: int, error: string|null}
     */
    public static function validateUploadedFile($file, array $allowedExts, int $maxSizeKb = 5120, string $label = 'Berkas'): array
    {
        if (! is_object($file) || ! method_exists($file, 'isValid') || ! $file->isValid()) {
            $errString = "{$label} tidak valid atau tidak dipilih.";
            if (is_object($file) && method_exists($file, 'getError') && $file->getError() === UPLOAD_ERR_INI_SIZE) {
                $errString = "Ukuran {$label} melebihi batas upload maksimal server PHP. Harap kompres berkas Anda.";
            } elseif (is_object($file) && method_exists($file, 'getErrorString') && $file->getErrorString() !== '') {
                $errString = (string) $file->getErrorString();
            }

            return ['valid' => false, 'ext' => '', 'mime' => '', 'sizeKb' => 0, 'error' => $errString];
        }

        $normalizedExts = array_values(array_unique(array_filter(array_map(
            static fn ($e): string => strtolower(trim((string) $e)),
            $allowedExts
        ))));

        $ext = strtolower((string) $file->getExtension());
        if ($ext === '' || ! in_array($ext, $normalizedExts, true)) {
            return [
                'valid'  => false,
                'ext'    => $ext,
                'mime'   => '',
                'sizeKb' => 0,
                'error'  => 'Format file tidak diizinkan. Format yang diperbolehkan: ' . strtoupper(implode(', ', $normalizedExts)),
            ];
        }

        $mime         = strtolower(trim((string) $file->getMimeType()));
        $allowedMimes = self::ALLOWED_MIME_BY_EXT[$ext] ?? [];
        if ($mime === '' || $allowedMimes === [] || ! in_array($mime, $allowedMimes, true)) {
            return [
                'valid'  => false,
                'ext'    => $ext,
                'mime'   => $mime,
                'sizeKb' => 0,
                'error'  => "Isi berkas tidak sesuai dengan format {$ext} yang diizinkan.",
            ];
        }

        $sizeKb = (int) round($file->getSizeByUnit('kb'));
        if ($maxSizeKb > 0 && $sizeKb > $maxSizeKb) {
            $maxMbFormatted = number_format($maxSizeKb / 1024, 1);

            return [
                'valid'  => false,
                'ext'    => $ext,
                'mime'   => $mime,
                'sizeKb' => $sizeKb,
                'error'  => "Ukuran {$label} melebihi batas maksimal ({$maxMbFormatted} MB).",
            ];
        }

        return ['valid' => true, 'ext' => $ext, 'mime' => $mime, 'sizeKb' => $sizeKb, 'error' => null];
    }

    /**
     * Private upload directory outside the web root for Graduate files.
     */
    public static function graduateUploadDir(string $subDir): string
    {
        $cleanSub = trim(str_replace(['..', '\\'], ['', '/'], $subDir), '/');

        return rtrim(WRITEPATH, "\\/") . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'graduate' . DIRECTORY_SEPARATOR . $cleanSub . DIRECTORY_SEPARATOR;
    }

    /**
     * Resolve an uploaded Graduate file from private WRITEPATH storage first,
     * falling back to legacy FCPATH storage if present.
     */
    public static function resolveGraduateUploadPath(string $subDir, ?string $fileName): ?string
    {
        $baseName = basename((string) $fileName);
        if ($baseName === '' || preg_match('/[^a-zA-Z0-9_\.-]/', $baseName)) {
            return null;
        }

        $cleanSub = trim(str_replace(['..', '\\'], ['', '/'], $subDir), '/');
        $candidates = [
            self::graduateUploadDir($cleanSub) . $baseName,
            rtrim(FCPATH, "\\/") . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'graduate' . DIRECTORY_SEPARATOR . $cleanSub . DIRECTORY_SEPARATOR . $baseName,
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Delete an uploaded Graduate file from both WRITEPATH and legacy FCPATH locations.
     */
    public static function deleteGraduateUploadFiles(string $subDir, ?string $fileName): void
    {
        $baseName = basename((string) $fileName);
        if ($baseName === '' || preg_match('/[^a-zA-Z0-9_\.-]/', $baseName)) {
            return;
        }

        $cleanSub = trim(str_replace(['..', '\\'], ['', '/'], $subDir), '/');
        $candidates = [
            self::graduateUploadDir($cleanSub) . $baseName,
            rtrim(FCPATH, "\\/") . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'graduate' . DIRECTORY_SEPARATOR . $cleanSub . DIRECTORY_SEPARATOR . $baseName,
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
