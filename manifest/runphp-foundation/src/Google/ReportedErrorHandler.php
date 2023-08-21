<?php

namespace ThinkFluent\RunPHP\Google;

use ThinkFluent\RunPHP\Runtime;

/**
 * GoogleReportedError Handler
 *
 * Traps errors, exceptions and pushes them out to Google's Stackdriver format
 *
 * @package ThinkFluent\RunPHP
 */
class ReportedErrorHandler
{
    const DTM_FORMAT = 'Y-m-d\TH:i:s.u\Z';

    /**
     * Register various handlers
     */
    public function register()
    {
        register_shutdown_function([$this, 'handleShutdown']);
        set_error_handler([$this, 'handleError'], E_ALL);
        set_exception_handler([$this, 'handleException']);
    }

    /**
     * Handle an 'Exception'
     *
     * Produced message string must start with PHP (Notice|Parse error|Fatal error|Warning)
     *
     * @param \Throwable $obj_thrown
     */
    public function handleException(\Throwable $obj_thrown)
    {
        $this->handleError(
            (int) $obj_thrown->getCode(),
            sprintf(
                "PHP Warning: %s\nStack trace:\n%s",
                (string) $obj_thrown,
                $obj_thrown->getTraceAsString()
            ),
            $obj_thrown->getFile(),
            $obj_thrown->getLine(),
            [
                'function' => $this->getFunctionNameForReport($obj_thrown->getTrace()),
                'exception' => $obj_thrown,
            ]
        );
    }

    /**
     * Handle script shutdown
     *
     * The following types cannot be caught by set_error_handler(), so deal with them on shutdown
     * https://www.php.net/manual/en/function.set-error-handler
     */
    public function handleShutdown()
    {
        $arr_error = error_get_last();
        $int_types = E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING;
        if (!empty($arr_error) && ($arr_error['type'] & $int_types) > 0) {
            $this->handleError($arr_error['type'], $arr_error['message'], $arr_error['file'], $arr_error['line']);
        }
    }

    /**
     * Google ReportedErrorEvent output
     *
     * @link https://cloud.google.com/error-reporting/docs/formatting-error-messages#json_representation
     * @link https://cloud.google.com/error-reporting/reference/rest/v1beta1/projects.events/report#ReportedErrorEvent
     *
     * @param int $int_errno
     * @param string $str_error
     * @param string|null $str_file
     * @param int|null $int_line
     * @param array $arr_context
     */
    public function handleError(
        int $int_errno,
        string $str_error,
        string $str_file = null,
        int $int_line = null,
        array $arr_context = []
    ) {
        static $arr_error_map = [
            E_WARNING           => 'WARNING',
            E_USER_WARNING      => 'WARNING',
            E_NOTICE            => 'NOTICE',
            E_USER_NOTICE       => 'NOTICE',
            E_STRICT            => 'INFO',
            E_DEPRECATED        => 'INFO',
            E_USER_DEPRECATED   => 'INFO',
            E_USER_ERROR        => 'ERROR',
            E_RECOVERABLE_ERROR => 'ERROR',
        ];
        static $arr_env = [];
        if(empty($arr_env)) {
            $arr_env = Runtime::get()->env();
        }
        if ('cli' === PHP_SAPI) {
            // @todo Better handle CLI, kubernetes logs (and review Cloud Run Job workloads)
        }
        $str_severity = $arr_error_map[$int_errno] ?? 'ERROR';
        $arr_payload = [
            '@type' => 'type.googleapis.com/google.devtools.clouderrorreporting.v1beta1.ReportedErrorEvent',
            "eventTime" => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(self::DTM_FORMAT),
            "severity" => $str_severity,
            "serviceContext" => (object)[
                "service" => $arr_env['K_SERVICE'] ?? 'unknown',
                "version" => $arr_env['K_REVISION'] ?? 'unknown',
            ],
            "message" => $str_error,
            "context" => [
                "httpRequest" => [
                    "method" => $_SERVER['REQUEST_METHOD'] ?? '',
                    "url" => $_SERVER['REQUEST_URI'] ?? '',
                    "userAgent" => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    // "referrer" => '',
                    // "responseStatusCode" => '',
                    "remoteIp" => $_SERVER['REMOTE_ADDR'] ?? '',
                ],
                "user" => (PHP_SESSION_ACTIVE === session_status() ? session_id() : ''),
                "reportLocation" => [   // Required if no stack trace is provided.
                    "filePath" => $str_file,
                    "lineNumber" => $int_line,
                    "functionName" => $arr_context['function'] ?? '',
                ]
            ]
        ];
        try {
            $arr_payload['logging.googleapis.com/trace'] = \ThinkFluent\RunPHP\Runtime::get()->getTraceContext();
        } catch (\Throwable $obj_thrown) {
            // swallow
        }
        file_put_contents('php://stderr', json_encode($arr_payload) . PHP_EOL);
    }

    /**
     * Extract the function name from this exception's stack trace.
     *
     * Borrowed (then updated) from the Google Cloud API PHP library.
     *
     * @link https://github.com/googleapis/google-cloud-php/blob/3dc62b4a2b5c098ee36dba09d2fa63bf1e5d8a92/ErrorReporting/src/Bootstrap.php#L254
     *
     * @param array|null $arr_trace
     * @return string
     */
    protected function getFunctionNameForReport(array $arr_trace = null): string
    {
        if (null === $arr_trace) {
            return '<unknown function>';
        }
        if (empty($arr_trace[0]['function'])) {
            return '<none>';
        }
        $arr_func_name = [$arr_trace[0]['function']];
        if (isset($arr_trace[0]['type'])) {
            $arr_func_name[] = $arr_trace[0]['type'];
        }
        if (isset($arr_trace[0]['class'])) {
            $arr_func_name[] = $arr_trace[0]['class'];
        }
        return implode('', array_reverse($arr_func_name));
    }
}
