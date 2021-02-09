<?php

declare(strict_types=1);

namespace Fernet\Core;

use Fernet\Framework;
use JsonException;
use Monolog\Logger;

class PluginLoader
{
    private const PLUGIN_FILE = 'plugin.php';
    private Logger $log;
    private string $configFile;
    private string $rootPath;
    private Framework $framework;

    public function __construct(Framework $framework, Logger $log)
    {
        $this->log = $log;
        $this->framework = $framework;
        $this->configFile = $framework->configFile('pluginFile');
        $this->rootPath = (string) $framework->getConfig('rootPath');
    }

    /**
     * @throws Exception
     */
    public function loadPlugins(): void
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
    public function warmUpPlugins(): array
    {
        if (!file_exists($this->configFile)) {
            return [];
        }
        $plugins = [];
        try {
            $list = json_decode(file_get_contents($this->configFile), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new Exception("Plugin file \"$this->configFile\" is not a valid JSON");
        }
        if (!is_array($plugins)) {
            throw new Exception("Plugin file \"$this->configFile\" should contain an array");
        }
        foreach ($list as $pluginName) {
            $file = "$this->rootPath/vendor/$pluginName/".self::PLUGIN_FILE;
            if (!file_exists($file)) {
                throw new Exception("Plugin \"$pluginName\" is not a valid plugin");
            }
            $class = require $file;
            if (class_exists($class) && is_subclass_of($class, PluginBootstrap::class)) {
                $this->log->debug("Warm up plugin $pluginName");
                $plugins[$pluginName] = $class;
                // TODO: When I should run the install?
                $this->log->debug("Install plugin $pluginName");
                (new $class())->install($this->framework);
            } else {
                throw new Exception("Plugin \"$pluginName\" Bootstrap class should extend ".PluginBootstrap::class);
            }
        }

        return $plugins;
    }
}
