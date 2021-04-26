<?php

declare(strict_types=1);

namespace Fernet\Core;

use Fernet\Config;
use Fernet\Framework;
use Monolog\Logger;

class PluginLoader
{
    private const PLUGIN_FILE = 'plugin.php';
    private string $rootPath;

    public function __construct(private Framework $framework, private Logger $log, private Config $config)
    {
        $this->rootPath = (string) $framework->getConfig('rootPath');
    }

    /**
     * @throws Exception
     */
    public function load(): void
    {
        $plugins = $this->warmUpPlugins();
        // TODO: Cache warm up
        foreach ($plugins as $pluginName => $class) {
            $this->log->debug("Load plugin $pluginName");
            (new $class())->setUp($this->framework);
        }
    }
    /**
     * @throws Exception
     */
    public function install(): void
    {
        $plugins = $this->warmUpPlugins();
        foreach ($plugins as $pluginName => $class) {
            $this->log->debug("Install plugin $pluginName");
            (new $class())->install($this->framework);
        }
    }

    /**
     * @throws Exception
     */
    public function warmUpPlugins(): array
    {
        $plugins = [];
        $list = $this->config->plugins;
        foreach ($list as $pluginName) {
            $file = "$this->rootPath/vendor/$pluginName/".self::PLUGIN_FILE;
            if (!file_exists($file)) {
                throw new Exception("Plugin \"$pluginName\" is not a valid plugin");
            }
            $class = require $file;
            if (class_exists($class) && is_subclass_of($class, PluginBootstrap::class)) {
                $this->log->debug("Warm up plugin $pluginName");
                $plugins[$pluginName] = $class;
            } else {
                throw new Exception("Plugin \"$pluginName\" Bootstrap class should extend ".PluginBootstrap::class);
            }
        }

        return $plugins;
    }
}
