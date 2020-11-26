<?php

namespace ThinkFluent\RunPHP;

/**
 * Sync PHP extensions with the composer JSON files
 *
 * @package ThinkFluent\RunPHP
 */
class ComposerExtensionSync extends Extensions
{

    const COMPOSER_JSON = '/composer.json';
    const COMPOSER_INSTALLED = '/vendor/composer/installed.json';

    /**
     * @var string
     */
    protected string $str_composer_path;

    /**
     * @var array
     */
    protected array $arr_root_package;

    /**
     * @var array
     */
    protected array $arr_required_extensions;

    /**
     * Evaluate the composer path
     */
    public function __construct()
    {
        $this->str_composer_path = rtrim((string)getenv('RUNPHP_COMPOSER_PATH'), '/');
    }

    /**
     * Evaluate composer package and install files, enable any missing extensions
     */
    public function run()
    {
        if (!is_dir($this->str_composer_path)) {
            return;
        }
        $this->extractMainPackage();
        $this->extractInstalledPackages();
        $this->processRequirements();
    }

    /**
     * Load the main package file
     */
    protected function extractMainPackage()
    {
        $str_composer_file = $this->str_composer_path . self::COMPOSER_JSON;
        if (is_readable($str_composer_file)) {
            $this->arr_root_package = json_decode(file_get_contents($str_composer_file), true);
        }
    }

    /**
     * Load the extension data from the installed packages
     */
    protected function extractInstalledPackages()
    {
        $str_install_file = $this->str_composer_path . self::COMPOSER_INSTALLED;
        if (is_readable($str_install_file)) {
            $arr_installed = json_decode(file_get_contents($str_install_file), true);
            if (empty($arr_installed)) {
                $arr_installed = [];
            }

            // Support composer v1, v2 format files
            if (isset($arr_installed['packages'])) {
                $arr_installed = $arr_installed['packages'];
            }

            // Top-up wth root package
            if (!empty($this->arr_root_package)) {
                $arr_installed[] = $this->arr_root_package;
            }

            if (empty($arr_installed)) {
                return;
            }

            // Extract the required extensions from all the packages
            $this->arr_required_extensions = (array) array_filter(
                preg_replace(
                    '#^((ext-)([a-zA-Z0-9_\-]+))?.*#',
                    '$3',
                    array_keys(
                        array_merge(
                            ...array_column($arr_installed, 'require')
                        )
                    )
                )
            );
        }
    }

    /**
     * Process any missing extensions
     */
    protected function processRequirements()
    {
        if (empty($this->arr_required_extensions)) {
            return;
        }
        $arr_enabled = explode(' ', strtolower(implode(' ', get_loaded_extensions())));
        $arr_missing = array_diff($this->arr_required_extensions, $arr_enabled);
        if (empty($arr_missing)) {
            return;
        }
        echo 'Application requires: ', implode(' ', $this->arr_required_extensions), PHP_EOL;
        $arr_installed = $this->fetchInstalledExtList();
        $arr_to_enable = array_intersect($arr_missing, $arr_installed);
        $arr_unavailable = array_diff($arr_missing, $arr_installed);
        if (!empty($arr_unavailable)) {
            echo 'Unavailable extensions: ', implode(' ', $arr_unavailable), PHP_EOL;
        }
        if (empty($arr_to_enable)) {
            return;
        }
        echo 'Enabling additional extensions: ', implode(' ', $arr_to_enable), PHP_EOL;
        shell_exec('docker-php-ext-enable ' . implode(' ', $arr_to_enable));
    }
}
