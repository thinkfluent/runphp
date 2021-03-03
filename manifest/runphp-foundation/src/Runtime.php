<?php

namespace ThinkFluent\RunPHP;

class Runtime
{

    public const MODE_DEV = 'development';
    public const MODE_PROD = 'production';

    public const ENV_MODE = 'RUNPHP_MODE';
    public const ENV_VERSION = 'RUNPHP_VERSION';
    public const ENV_FOUNDATION_VERSION = 'RUNPHP_FOUNDATION_VERSION';
    public const ENV_GOOGLE_CLOUD = 'RUNPHP_GOOGLE_CLOUD';
    public const ENV_PROD_ADMIN = 'RUNPHP_ALLOW_PRODUCTION_ADMIN';
    public const ENV_PROFILING = 'RUNPHP_XHPROF_PROFILING';
    public const ENV_PREPEND = 'RUNPHP_EXTRA_PREPEND';

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
        return $this->isProfilingEnabled() && (false === strpos(($_SERVER['REQUEST_URI'] ?? ''), '/xhprof'));
    }

    /**
     * Has profiling been enabled in the Environment?
     *
     * @return bool
     */
    public function isProfilingEnabled(): bool
    {
        return 'true' === ($this->arr_env[self::ENV_PROFILING] ?? 'false');
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
}
