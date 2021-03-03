<?php

namespace ThinkFluent\RunPHP;

/**
 * Preload process for PHP startup, using Composer classmap
 *
 * @todo Add alternate preload strategies, e.g. find/iterate and include() over opcache_compile_file()
 *
 * @package ThinkFluent\RunPHP
 */
class Preloader
{
    // File list strategy
    private const
        ENV_STRATEGY = 'RUNPHP_PRELOAD_STRATEGY',
        STRATEGY_SRC = 'src',
        STRATEGY_CLASSMAP = 'composer-classmap';

    // How should we pre-compile the PHP files?
    private const
        ENV_ACTION = 'RUNPHP_PRELOAD_ACTION',
        ACTION_INCLUDE = 'include',
        ACTION_COMPILE = 'compile';

    /**
     * Run the pre-compile process
     */
    public function run()
    {
        $obj_runtime = Runtime::get();

        // Determine method
        switch ($obj_runtime->env()[self::ENV_ACTION] ?? 'unknown') {
            case self::ACTION_INCLUDE:
                $fnc_method = [$this, 'includeFile'];
                break;

            case self::ACTION_COMPILE:
            default:
            $fnc_method = [$this, 'compileFile'];
                break;
        }

        // Find the source file list
        switch ($obj_runtime->env()[self::ENV_STRATEGY] ?? 'unknown') {
            case self::STRATEGY_CLASSMAP:
                $arr_files = $this->getComposerClassmapFiles();
                break;

            case self::STRATEGY_SRC:
                $arr_files = $this->getSrcFiles();
                break;

            default:
                // Nothing to do
                return;
        }

        // Pre-compile
        foreach ($arr_files as $str_file) {
            $fnc_method($str_file);
        }

        // Confirm output
        error_log(sprintf("PHP preload done, source files [%d]", count($arr_files)));
    }

    /**
     * Include the file (not a function)
     *
     * @param string $str_file
     */
    private function includeFile(string $str_file)
    {
        include_once $str_file;
    }

    /**
     * Compile the file
     *
     * @param string $str_file
     */
    private function compileFile(string $str_file)
    {
        opcache_compile_file($str_file);
    }

    /**
     * Return the contents of the Composer classmap, using the Composer autoloader to fill in the blanks
     *
     * @return array
     */
    private function getComposerClassmapFiles(): array
    {
        $arr_files = [];
        $str_composer_path = rtrim((string)getenv('RUNPHP_COMPOSER_PATH'), '/');
        $str_autoload_file = $str_composer_path . '/vendor/autoload.php';
        if (is_dir($str_composer_path)) {
            if (is_readable($str_autoload_file)) {
                require_once $str_autoload_file;
            }
            $str_classmap = $str_composer_path . '/vendor/composer/autoload_classmap.php';
            if (is_readable($str_classmap)) {
                $arr_files = include_once $str_classmap;
            }
        }
        if (is_array($arr_files) && !empty($arr_files)) {
            return $arr_files;
        }
        return [];
    }

    /**
     * File all the non-test PHP files in the /src folder, using the Composer autoloader to fill in the blanks
     *
     * @return array
     */
    private function getSrcFiles(): array
    {
        $arr_files = [];
        $str_composer_path = rtrim((string)getenv('RUNPHP_COMPOSER_PATH'), '/');
        $str_src_path = $str_composer_path . '/src';
        $str_autoload_file = $str_composer_path . '/vendor/autoload.php';
        if (is_dir($str_src_path)) {
            if (is_readable($str_autoload_file)) {
                require_once $str_autoload_file;
            }
            $obj_directory = new \RecursiveDirectoryIterator($str_src_path);
            $obj_full_tree = new \RecursiveIteratorIterator($obj_directory);
            $obj_php_files = new \RegexIterator(
                $obj_full_tree,
                '/.+((?<!Test)+\.php$)/i',
                \RecursiveRegexIterator::GET_MATCH
            );
            foreach ($obj_php_files as $key => $file) {
                $arr_files[] = $file[0];
            }
        }
        if (is_array($arr_files) && !empty($arr_files)) {
            return $arr_files;
        }
        return [];
    }

}