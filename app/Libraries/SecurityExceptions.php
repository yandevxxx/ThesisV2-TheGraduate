<?php

namespace App\Libraries;

use CodeIgniter\Debug\Exceptions;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Exceptions as ExceptionsConfig;
use Config\Services;
use Throwable;

/**
 * Penanganan error dan exception terpusat (Mode A · The Graduate).
 *
 * Setiap error menghasilkan Event ID unik (EVT-YYYYMMDD-XXXXXX).
 * Detail teknis (sudah dibersihkan dari secret) masuk ke log server bersama Event ID,
 * sedangkan pengguna hanya melihat pesan umum beserta Event ID tersebut.
 */
class SecurityExceptions extends Exceptions
{
    /** @var string|null */
    protected $activeEventId;

    public function __construct(?ExceptionsConfig $config = null, ?RequestInterface $request = null, ?ResponseInterface $response = null)
    {
        parent::__construct(
            $config ?? config('Exceptions'),
            $request ?? Services::request(),
            $response ?? Services::response()
        );
    }

    /**
     * Laporkan exception ke log server dengan Event ID & sanitasi data rahasia.
     */
    public static function report(Throwable $exception, string $contextLabel = 'app', ?RequestInterface $request = null, array $extra = []): string
    {
        $eventId     = SecurityLogSanitizer::newEventId();
        $safeMessage = SecurityLogSanitizer::sanitizeText($exception->getMessage());
        $safeTrace   = SecurityLogSanitizer::sanitizeText($exception->getTraceAsString());
        $aksi        = SecurityAuditLogger::describeRequestAction($request, $contextLabel);

        log_message(
            'error',
            '[EVENT_ID: ' . $eventId . '] [' . SecurityLogSanitizer::sanitizeText($contextLabel) . '] aksi=' . $aksi
            . ' class=' . get_class($exception) . ' msg=' . $safeMessage . ' trace=' . substr($safeTrace, 0, 1500)
        );

        if ($extra !== []) {
            $safeExtra = SecurityLogSanitizer::sanitizeContext($extra);
            log_message('error', '[EVENT_ID: ' . $eventId . '] context=' . json_encode($safeExtra, JSON_UNESCAPED_SLASHES));
        }

        return $eventId;
    }

    public static function publicErrorMessage(string $eventId, string $baseMessage = 'Terjadi kesalahan sistem internal. Silakan coba lagi atau hubungi administrator.'): string
    {
        return rtrim($baseMessage, '.') . ' (ID kejadian: ' . $eventId . ').';
    }

    /**
     * Hasilkan respons publik yang aman (HTML/JSON/Redirect) untuk controller.
     */
    public static function buildControllerErrorResponse(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        string $contextLabel,
        string $baseMessage = 'Terjadi kesalahan sistem internal. Silakan coba lagi atau hubungi administrator.',
        ?string $redirectUrl = null,
        string $title = 'Terjadi Kesalahan',
        int $statusCode = 500
    ) {
        $eventId       = self::report($exception, $contextLabel, $request);
        $publicMessage = self::publicErrorMessage($eventId, $baseMessage);

        if (self::wantsJson($request)) {
            $payload = [
                'status'   => $statusCode,
                'success'  => false,
                'title'    => $title,
                'message'  => $publicMessage,
                'event_id' => $eventId,
            ];
            if (function_exists('csrf_token') && function_exists('csrf_hash')) {
                $payload['csrf_token'] = csrf_token();
                $payload['csrf_hash']  = csrf_hash();
            }

            return $response->setStatusCode($statusCode)->setJSON($payload);
        }

        if ($redirectUrl !== null && $redirectUrl !== '') {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => $title,
                'text'  => $publicMessage,
            ]);

            return redirect()->to($redirectUrl);
        }

        return (new self(config('Exceptions'), $request, $response))
            ->renderPublicError($exception, $request, $response, $statusCode, $eventId);
    }

    /**
     * Render respons HTTP publik untuk uncaught exception (testable di PHPUnit).
     */
    public function renderPublicError(
        Throwable $exception,
        ?RequestInterface $request = null,
        ?ResponseInterface $response = null,
        int $statusCode = 500,
        ?string $existingEventId = null
    ): ResponseInterface {
        $req  = $request ?? $this->request;
        $resp = $response ?? $this->response;

        $eventId             = $existingEventId ?: self::report($exception, 'uncaught_exception', $req);
        $this->activeEventId = $eventId;
        $publicMessage       = self::publicErrorMessage($eventId);

        if (self::wantsJson($req)) {
            return $resp->setStatusCode($statusCode)->setJSON([
                'status'   => $statusCode,
                'success'  => false,
                'message'  => $publicMessage,
                'event_id' => $eventId,
            ]);
        }

        $html = view('errors/html/production', [
            'code'     => $statusCode,
            'event_id' => $eventId,
            'message'  => $publicMessage,
        ]);

        return $resp->setStatusCode($statusCode)->setBody($html);
    }

    /**
     * @codeCoverageIgnore
     */
    public function exceptionHandler(Throwable $exception)
    {
        [$statusCode, $exitCode] = $this->determineCodes($exception);
        $this->activeEventId     = SecurityLogSanitizer::newEventId();

        if ($this->config->log === true && ! in_array($statusCode, $this->config->ignoreCodes, true)) {
            $safeMessage = SecurityLogSanitizer::sanitizeText($exception->getMessage());
            $safeTrace   = SecurityLogSanitizer::sanitizeText($exception->getTraceAsString());
            log_message('critical', '[EVENT_ID: ' . $this->activeEventId . '] ' . $safeMessage . "\n{trace}", [
                'trace' => $safeTrace,
            ]);
        }

        if (! is_cli()) {
            $this->response->setStatusCode($statusCode);
            header(sprintf(
                'HTTP/%s %s %s',
                $this->request->getProtocolVersion(),
                $this->response->getStatusCode(),
                $this->response->getReasonPhrase()
            ), true, $statusCode);

            if (strpos(strtolower($this->request->getHeaderLine('accept')), 'text/html') === false) {
                $body = (ENVIRONMENT === 'development' && defined('CI_DEBUG') && CI_DEBUG)
                    ? $this->collectVars($exception, $statusCode)
                    : [
                        'status'   => $statusCode,
                        'success'  => false,
                        'message'  => self::publicErrorMessage($this->activeEventId),
                        'event_id' => $this->activeEventId,
                    ];

                $this->respond($body, $statusCode)->send();
                exit($exitCode);
            }
        }

        $this->render($exception, $statusCode);
        exit($exitCode);
    }

    protected function determineView(Throwable $exception, string $templatePath): string
    {
        if (ENVIRONMENT !== 'development' || ! defined('CI_DEBUG') || ! CI_DEBUG) {
            if ($exception instanceof \CodeIgniter\Exceptions\PageNotFoundException) {
                return 'error_404.php';
            }

            return 'production.php';
        }

        return parent::determineView($exception, $templatePath);
    }

    protected function collectVars(Throwable $exception, int $statusCode): array
    {
        $vars             = parent::collectVars($exception, $statusCode);
        $vars['event_id'] = $this->activeEventId ?: SecurityLogSanitizer::newEventId();

        if (ENVIRONMENT !== 'development' || ! defined('CI_DEBUG') || ! CI_DEBUG) {
            $vars['message'] = self::publicErrorMessage((string) $vars['event_id']);
            $vars['trace']   = [];
            $vars['file']    = '';
            $vars['line']    = 0;
        }

        return $vars;
    }

    private static function wantsJson(RequestInterface $request): bool
    {
        $isAjax = method_exists($request, 'isAJAX') && $request->isAJAX();
        $accept = strtolower((string) $request->getHeaderLine('Accept'));

        return $isAjax || strpos($accept, 'application/json') !== false;
    }
}
