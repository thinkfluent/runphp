<?php

namespace ThinkFluent\RunPHP;

class Runtime
{

    public const
        MODE_DEV = 'development',
        MODE_PROD = 'production';

    public const
        ENV_MODE = 'RUNPHP_MODE',
        ENV_VERSION = 'RUNPHP_VERSION',
        ENV_FOUNDATION_VERSION = 'RUNPHP_FOUNDATION_VERSION',
        ENV_GOOGLE_CLOUD = 'RUNPHP_GOOGLE_CLOUD',
        ENV_PROD_ADMIN = 'RUNPHP_ALLOW_PRODUCTION_ADMIN',
        ENV_PROFILING = 'RUNPHP_XHPROF_PROFILING',
        ENV_PREPEND = 'RUNPHP_EXTRA_PREPEND',
        ENV_TRACE_PROJECT = 'RUNPHP_TRACE_PROJECT';

    public const
        SERVER_TRACE_CONTEXT_HEADER = 'HTTP_X_CLOUD_TRACE_CONTEXT';

    /**
     * @var self
     */
    private static self $obj_instance;

    /**
     * @var bool
     */
    private bool $bol_is_cloud;

    /**
     * @var array
     */
    private array $arr_env = [];

    /**
     * @return self
     */
    public static function get(): self
    {
        if (empty(self::$obj_instance)) {
            self::$obj_instance = new self();
        }
        return self::$obj_instance;
    }

    /**
     * Runtime constructor
     */
    private function __construct()
    {
        $this->arr_env = (array) getenv();
        $this->bol_is_cloud = ('yes' === ($this->arr_env[self::ENV_GOOGLE_CLOUD] ?? 'unknown'));
    }

    /**
     * @return bool
     */
    public function isGoogleCloud(): bool
    {
        return $this->bol_is_cloud;
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->arr_env[self::ENV_MODE] ?? 'unknown';
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->arr_env[self::ENV_VERSION] ?? 'unknown';
    }

    /**
     * @return string
     */
    public function getFoundationVersion(): string
    {
        return $this->arr_env[self::ENV_FOUNDATION_VERSION] ?? 'unknown';
    }

    /**
     * Are we allowed to answer "admin" requests (/_runphp*)
     * @return bool
     */
    public function allowAdmin(): bool
    {
        return (
            self::MODE_DEV === $this->getMode()
            || 'true' === ($this->arr_env[self::ENV_PROD_ADMIN] ?? 'false')
        );
    }

    /**
     * Should we allow profiling?
     *
     * @return bool
     */
    public function shouldProfile(): bool
    {
        return (
            // self::MODE_DEV === $this->getMode() &&
            'true' === ($this->arr_env[self::ENV_PROFILING] ?? 'false')
        ) && (false === strpos(($_SERVER['REQUEST_URI'] ?? ''), '/xhprof'));
    }

    /**
     * Called as a shutdown function, if we are profiling the request
     */
    public function profileRequestShutdown(): void
    {
        $str_data = serialize(tideways_xhprof_disable());
        file_put_contents(
            rtrim(getenv('XHPROF_OUTPUT'), '/') . '/' .
            uniqid() . '.http_' .
            preg_replace('#[^A-Za-z0-9]#', '_', ($_SERVER['REQUEST_URI'] ?? '')) .
            '.xhprof',
            $str_data
        );
    }

    /**
     * @return string
     */
    public function getAdditionalPrependFile(): string
    {
        return $this->arr_env[self::ENV_PREPEND] ?? '';
    }

    /**
     * @return array
     */
    public function env(): array
    {
        return $this->arr_env;
    }

    /**
     * Fetch metadata from the Google metadata server
     *
     * @return \stdClass|null
     */
    public function fetchMetadata(): ?\stdClass
    {
        static $obj_metadata = null;
        if (empty($obj_metadata)) {
            try {
                require_once __DIR__ . '/../src/Google/Metadata.php';
                $str_metadata = (new \ThinkFluent\RunPHP\Google\Metadata())->fetch()->getData();
                $obj_metadata = \json_decode($str_metadata);
            } catch (\Throwable $obj_thrown) {
                // Swallow. We do not want to impact runtime
            }
        }
        return $obj_metadata;
    }

    /**
     * Build a trace ID, to allow logs in GCP to be grouped together
     *
     * @return string
     */
    public function getTraceContext(): string
    {
        if (isset($_SERVER[self::SERVER_TRACE_CONTEXT_HEADER])) {
            $arr_trace_parts = explode('/', $_SERVER[self::SERVER_TRACE_CONTEXT_HEADER]);
            $str_project_id = $this->arr_env[self::ENV_TRACE_PROJECT] ?? '';
            if (empty($str_project_id)) {
                $obj_metadata = $this->fetchMetadata();
                $str_project_id = $obj_metadata->computeMetadata->v1->project->projectId ?? 'unknown';
            }
            return sprintf('projects/%s/traces/%s', $str_project_id, $arr_trace_parts[0]);
        }
        return 'unknown';
    }
}
