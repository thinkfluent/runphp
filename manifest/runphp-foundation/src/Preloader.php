<?php

namespace ThinkFluent\RunPHP;

/**
 * Preload process for PHP startup, using Composer classmap or other "find" strategies
 *
 * @todo Add alternate preload strategies, e.g. find/iterate and include() over opcache_compile_file()
 *
 * @package ThinkFluent\RunPHP
 */
class Preloader
{
    // File list strategy
    public const
        STRATEGY_NONE = 'none',
        STRATEGY_PATHS = 'paths',
        STRATEGY_SRC = 'src',
        STRATEGY_CLASSMAP = 'composer-classmap';

    // How should we pre-compile the PHP files?
    public const
        ACTION_INCLUDE = 'include',
        ACTION_COMPILE = 'compile';

    private PreloadConfig $obj_config;

    private bool $bol_autoload = false;

    private int $int_files = 0;

    public function __construct(PreloadConfig $obj_config)
    {
        $this->obj_config = $obj_config;
    }

    /**
     * Run the pre-compile process
     */
    public function run()
    {
        if (self::STRATEGY_NONE === $this->obj_config->getStrategy()) {
            return;
        }

        // Determine method
        switch ($this->obj_config->getAction()) {
            case self::ACTION_INCLUDE:
                $fnc_method = [$this, 'includeFile'];
                $this->bol_autoload = true;
                break;

            case self::ACTION_COMPILE:
            default:
                $fnc_method = [$this, 'compileFile'];
                break;
        }

        // Find the source file list
        switch ($this->obj_config->getStrategy()) {
            case self::STRATEGY_CLASSMAP:
                $arr_files = $this->getComposerClassmapFiles();
                break;

            case self::STRATEGY_SRC:
                $arr_files = $this->findComposerSrcFiles();
                break;

            case self::STRATEGY_PATHS:
                $arr_files = $this->findFilesForPaths();
                break;

            default:
                return;
        }

        // Pre-compile, skipping any files already in the cache
        $opcacheStatus = opcache_get_status(true);
        foreach (array_unique($arr_files) as $str_file) {
            if (!isset($opcacheStatus['scripts'][$str_file])) {
                $fnc_method($str_file);
            }
        }
    }

    /**
     * Include the file (not a function)
     *
     * @param string $str_file
     */
    private function includeFile(string $str_file)
    {
        $this->int_files++;
        include_once $str_file;
    }

    /**
     * Compile the file
     *
     * @param string $str_file
     */
    private function compileFile(string $str_file)
    {
        $this->int_files++;
        opcache_compile_file($str_file);
    }

    /**
     * Return the contents of the Composer classmap, using the Composer autoloader to fill in the blanks
     *
     * @return array
     */
    private function getComposerClassmapFiles(): array
    {
        $str_composer_path = rtrim($this->obj_config->getComposerPath(), '/');
        if (is_dir($str_composer_path)) {
            $str_autoload_file = $str_composer_path . '/vendor/autoload.php';
            if ($this->bol_autoload && is_readable($str_autoload_file)) {
                require_once $str_autoload_file;
            }
            $str_classmap = $str_composer_path . '/vendor/composer/autoload_classmap.php';
            if (is_readable($str_classmap)) {
                $arr_files = include_once $str_classmap;
                if (is_array($arr_files) && !empty($arr_files)) {
                    return $arr_files;
                }
            }
        }
        return [];
    }

    /**
     * File all the non-test PHP files in the /src folder, using the Composer autoloader to fill in the blanks
     *
     * @return array
     */
    private function findComposerSrcFiles(): array
    {
        $str_composer_path = rtrim($this->obj_config->getComposerPath(), '/');
        $str_src_path = $str_composer_path . '/src';
        if (is_dir($str_src_path)) {
            $str_autoload_file = $str_composer_path . '/vendor/autoload.php';
            if ($this->bol_autoload && is_readable($str_autoload_file)) {
                require_once $str_autoload_file;
            }
        }
        return $this->findFilesInPath($str_src_path);
    }

    private function findFilesForPaths(): array
    {
        $arr_file_sets = [];
        foreach ($this->obj_config->getPaths() as $str_path) {
            $arr_file_sets[] = $this->findFilesInPath($str_path);
        }
        return array_merge(...$arr_file_sets);
    }

    private function findFilesInPath(string $str_src_path): array
    {
        $str_src_path = rtrim($str_src_path, '/');
        if (is_dir($str_src_path)) {
            $obj_php_files = new \RegexIterator(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($str_src_path)
                ),
                PreloadConfig::PHP_FILE_REGEX,
                \RegexIterator::GET_MATCH
            );
            $arr_files = [];
            foreach ($obj_php_files as $key => $file) {
                $arr_files[] = $file[0];
            }
            return $arr_files;
        }
        return [];
    }

    public function getProcessedFileCount(): int
    {
        return $this->int_files;
    }
}
