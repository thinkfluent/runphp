<?php

declare(strict_types=1);

namespace ThinkFluent\RunPHP;

class PreloadConfig
{
    public const
        PHP_FILE_REGEX = '/.+((?<!Test)+\.php$)/i',
        ENV_PATHS = 'RUNPHP_PRELOAD_PATHS',
        ENV_COMPOSER_PATH = 'RUNPHP_COMPOSER_PATH',
        ENV_STRATEGY = 'RUNPHP_PRELOAD_STRATEGY',
        ENV_ACTION = 'RUNPHP_PRELOAD_ACTION';

    private string $str_strategy = Preloader::STRATEGY_SRC;
    private string $str_action = Preloader::ACTION_COMPILE;

    private string $str_composer_path;

    private array $arr_paths = [];

    public function setStrategy(string $str_strategy): self
    {
        $this->str_strategy = $str_strategy;
        return $this;
    }
    public function getStrategy(): string
    {
        return $this->str_strategy;
    }

    public function setAction(string $str_action): self
    {
        $this->str_action = $str_action;
        return $this;
    }

    public function getAction(): string
    {
        return $this->str_action;
    }

    public function setComposerPath(string $str_composer_path): self
    {
        $this->str_composer_path = $str_composer_path;
        return $this;
    }

    public function getComposerPath(): string
    {
        return $this->str_composer_path;
    }

    public function setPaths(array $arr_paths): self
    {
        $this->arr_paths = $arr_paths;
        return $this;
    }

    public function getPaths(): array
    {
        return $this->arr_paths;
    }

    public static function fromEnv(): self
    {
        $env = Runtime::get()->env();
        $obj_config = new self();
        $obj_config->setStrategy($env[self::ENV_STRATEGY] ?? Preloader::STRATEGY_NONE);
        $obj_config->setAction($env[self::ENV_ACTION] ?? Preloader::ACTION_COMPILE);
        $obj_config->setComposerPath($env[self::ENV_COMPOSER_PATH] ?? '');
        // Paths (explode from ENV var)
        $str_paths = $env[self::ENV_PATHS] ?? '';
        if (!empty($str_paths)) {
            $obj_config->setPaths(explode(',', $str_paths));
        }
        return $obj_config;
    }
}
