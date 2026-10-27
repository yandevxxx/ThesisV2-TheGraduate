<?php

namespace App\Libraries;

use Throwable;

/**
 * Ownership policy for data that belongs to a mahasiswa.
 *
 * Every route parameter and every known owner field in GET/POST is checked.
 * Unknown parameterised methods fail closed (403) until they are mapped.
 */
final class StudentOwnership
{
    /** @var string|null */
    private $npm;

    /** @var callable|null */
    private $resolver;

    /**
     * Method => parameter index => resource type.
     *
     * Keep this list in sync with every public method that receives a route
     * parameter. RbacPolicyTest fails when a new parameterised method is not
     * listed here.
     */
    private const PARAM_RESOURCES = [
        'insertpengajuanpenulisanskripsi' => [0 => 'npm'],
        'detailproposal'                    => [0 => 'proposal'],
        'updateproposal'                    => [0 => 'proposal'],
        'updatevideosempro'                 => [0 => 'seminar_proposal'],
        'updatehasilbimbingan'              => [0 => 'catatan_bimbingan'],
        'deletehasilbimbingan'              => [0 => 'catatan_bimbingan'],
        'cetakformbimbingan'                => [0 => 'npm'],
        'detailskripsi'                     => [0 => 'skripsi'],
        'updateskripsi'                     => [0 => 'skripsi'],
        'detailpengajuanprasidang'          => [0 => 'pengajuan_prasidang'],
        'updatepengajuanprasidang'          => [0 => 'pengajuan_prasidang'],
        'detailpengajuansidangskripsi'      => [0 => 'pengajuan_sidang'],
        'updatepengajuansidangskripsi'      => [0 => 'pengajuan_sidang'],
        'hasilsidangskripsi'                => [0 => 'sidang_skripsi'],
        'updatemakalah'                     => [0 => 'makalah'],
        'downloadkhs'                       => [0 => 'npm'],
        'downloadkrs'                       => [0 => 'npm'],
        'downloadpersetujuanskripsi'        => [0 => 'npm'],
        'downloadproposal'                  => [0 => 'proposal'],
        'downloaddraft'                     => [0 => 'pengajuan_prasidang'],
        'downloadlembarpersetujuan'         => [0 => 'pengajuan_prasidang'],
        'downloaddraftfinal'                => [0 => 'pengajuan_sidang'],
        'downloadformbimbingan'             => [0 => 'pengajuan_sidang'],
        'downloadpersyaratansidang'         => [0 => 'pengajuan_sidang'],
        'downloadskripsi'                   => [0 => 'skripsi'],
        'deletekhs'                         => [0 => 'npm'],
        'deletekrs'                         => [0 => 'npm'],
        'deletepersetujuanskripsi'          => [0 => 'npm'],
        'deleteproposal'                    => [0 => 'proposal'],
        'deletedraft'                       => [0 => 'pengajuan_prasidang'],
        'deletelembarpersetujuan'           => [0 => 'pengajuan_prasidang'],
        'deletedraftfinal'                  => [0 => 'pengajuan_sidang'],
        'deleteformbimbingan'               => [0 => 'pengajuan_sidang'],
        'deletepersyaratansidang'           => [0 => 'pengajuan_sidang'],
        'deleteskripsi'                     => [0 => 'skripsi'],
        'deletemakalah'                     => [0 => 'makalah'],
    ];

    /**
     * Owner field in GET/POST => resource type.
     */
    private const REQUEST_RESOURCES = [
        'id_proposal'                 => 'proposal',
        'id_skripsi'                  => 'skripsi',
        'id_pengajuan_prasidang'      => 'pengajuan_prasidang',
        'id_pengajuan_sidang'         => 'pengajuan_sidang',
        'id_pengajuan_sidang_skripsi' => 'pengajuan_sidang',
        'id_sidang_skripsi'           => 'sidang_skripsi',
        'id_makalah'                  => 'makalah',
        'pembimbing'                  => 'pembimbing',
        'id_pembimbing'               => 'pembimbing',
        'id_catatan'                  => 'catatan_bimbingan',
        'id_hardcover'                => 'pengajuan_hardcover',
    ];

    public function __construct(?string $npm = null, ?callable $resolver = null)
    {
        $this->npm      = $npm === null ? null : trim($npm);
        $this->resolver = $resolver;
    }

    /**
     * Build the policy from the current user_session payload.
     *
     * @param mixed $session
     */
    public static function fromSession($session, ?callable $resolver = null): self
    {
        if (! is_array($session)) {
            return new self(null, $resolver);
        }

        $npm = isset($session['npm']) && $session['npm'] !== ''
            ? (string) $session['npm']
            : null;

        if ($npm === null) {
            $roles   = AccessControl::normalizeRoles($session);
            $primary = $roles[0] ?? '';

            if (in_array($primary, ['mahasiswa', 'wisudawan'], true)
                && isset($session['id']) && $session['id'] !== '') {
                $npm = (string) $session['id'];
            }
        }

        if ($npm === null && ! empty($session['email'])) {
            try {
                $row = \Config\Database::connect()
                    ->table('mahasiswa')
                    ->select('npm')
                    ->where('email', $session['email'])
                    ->get()
                    ->getRowArray();

                if (! empty($row['npm'])) {
                    $npm = (string) $row['npm'];
                }
            } catch (Throwable $e) {
                $npm = null;
            }
        }

        return new self($npm, $resolver);
    }

    public function npm(): ?string
    {
        return $this->npm;
    }

    /**
     * @param mixed $npm
     */
    public function ownsNpm($npm): bool
    {
        if ($this->npm === null || ! is_scalar($npm)) {
            return false;
        }

        return (string) $npm === $this->npm;
    }

    /**
     * @param mixed $id
     */
    public function ownsResource(string $resource, $id): bool
    {
        if ($this->npm === null || $id === null || $id === '' || ! is_scalar($id)) {
            return false;
        }

        if ($resource === 'npm') {
            return $this->ownsNpm($id);
        }

        $owner = $this->resolveOwner($resource, $id);

        return $owner !== null && (string) $owner === $this->npm;
    }

    /**
     * Check route parameters and every known owner field in the request.
     *
     * @param array $params Route parameters.
     * @param array $query  GET values.
     * @param array $post   POST values.
     */
    public function authorize(string $method, array $params, array $query = [], array $post = []): bool
    {
        if ($this->npm === null) {
            return false;
        }

        if (array_key_exists('npm', $query) && ! $this->ownsNpm($query['npm'])) {
            return false;
        }

        if (array_key_exists('npm', $post) && ! $this->ownsNpm($post['npm'])) {
            return false;
        }

        foreach (self::REQUEST_RESOURCES as $field => $resource) {
            foreach ([$query, $post] as $bag) {
                if (array_key_exists($field, $bag)
                    && $bag[$field] !== ''
                    && ! $this->ownsResource($resource, $bag[$field])) {
                    return false;
                }
            }
        }

        if ($params === []) {
            return true;
        }

        $method = strtolower(trim($method));
        $rule   = self::PARAM_RESOURCES[$method] ?? null;

        // Unknown parameterised method: fail closed.
        if ($rule === null || count($params) !== count($rule)) {
            return false;
        }

        foreach ($rule as $index => $resource) {
            if (! array_key_exists($index, $params)) {
                return false;
            }

            if ($resource === 'npm') {
                if (! $this->ownsNpm($params[$index])) {
                    return false;
                }

                continue;
            }

            if (! $this->ownsResource($resource, $params[$index])) {
                return false;
            }
        }

        return true;
    }

    public static function hasParamRule(string $method): bool
    {
        return isset(self::PARAM_RESOURCES[strtolower(trim($method))]);
    }

    /**
     * @param mixed $id
     */
    private function resolveOwner(string $resource, $id): ?string
    {
        if ($this->resolver !== null) {
            $owner = call_user_func($this->resolver, $resource, $id);

            return $owner === null ? null : (string) $owner;
        }

        $sql = null;

        switch ($resource) {
            case 'proposal':
                $sql = 'SELECT npm FROM proposal WHERE id = ?';
                break;
            case 'skripsi':
                $sql = 'SELECT npm FROM skripsi WHERE id = ?';
                break;
            case 'makalah':
                $sql = 'SELECT npm FROM makalah WHERE id = ?';
                break;
            case 'seminar_proposal':
                $sql = 'SELECT p.npm FROM seminar_proposal sp INNER JOIN proposal p ON p.id = sp.id_proposal WHERE sp.id = ?';
                break;
            case 'pengajuan_prasidang':
                $sql = 'SELECT s.npm FROM pengajuan_prasidang pp INNER JOIN skripsi s ON s.id = pp.id_skripsi WHERE pp.id = ?';
                break;
            case 'pengajuan_sidang':
                $sql = 'SELECT s.npm FROM pengajuan_sidang ps INNER JOIN skripsi s ON s.id = ps.id_skripsi WHERE ps.id = ?';
                break;
            case 'sidang_skripsi':
                $sql = 'SELECT s.npm FROM sidang_skripsi ss INNER JOIN skripsi s ON s.id = ss.id_skripsi WHERE ss.id = ?';
                break;
            case 'catatan_bimbingan':
                $sql = 'SELECT s.npm FROM catatan_bimbingan cb INNER JOIN pembimbing pb ON pb.id = cb.id_pembimbing INNER JOIN skripsi s ON s.id = pb.id_skripsi WHERE cb.id = ?';
                break;
            case 'pembimbing':
                $sql = 'SELECT s.npm FROM pembimbing pb INNER JOIN skripsi s ON s.id = pb.id_skripsi WHERE pb.id = ?';
                break;
            case 'pengajuan_hardcover':
                $sql = 'SELECT s.npm FROM pengajuan_hardcover ph INNER JOIN skripsi s ON s.id = ph.id_skripsi WHERE ph.id = ?';
                break;
        }

        if ($sql === null) {
            return null;
        }

        try {
            $row = \Config\Database::connect()->query($sql, [$id])->getRowArray();
        } catch (Throwable $e) {
            return null;
        }

        return isset($row['npm']) ? (string) $row['npm'] : null;
    }
}
