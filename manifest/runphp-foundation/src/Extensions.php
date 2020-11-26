<?php

namespace ThinkFluent\RunPHP;

/**
 * Generic extension analysis
 *
 * @package ThinkFluent\RunPHP
 */
class Extensions
{
    /**
     * Fetch a list of the installed (but not necessarily enabled) php extensions
     *
     * @return string[]
     */
    public function fetchInstalledExtList()
    {
        return (array) preg_replace('#(.*)/([a-z0-9_]*)\.so$#', '$2', glob(ini_get("extension_dir") . '/*.so'));
    }

    /**
     * Fetch a list of the enabled extensions
     *
     * @return string[]
     */
    public function fetchEnabledExtensions()
    {
        return (array) explode(' ', strtolower(implode(' ', get_loaded_extensions())));
    }
}
