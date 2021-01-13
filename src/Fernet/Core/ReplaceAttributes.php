<?php

declare(strict_types=1);

namespace Fernet\Core;

use Fernet\Framework;
use function get_class;
use JsonException;

class ReplaceAttributes
{
    private const REGEX_FORM_SUBMIT = '/<form.*?(@(onSubmit)=(["\'])(.*?)\3)/';
    private const REGEX_A_ONCLICK = '/<a.*?(@(onClick)=(["\'])(.*?)\3)/';
    private const REGEX_BIND = '/<input.*?(@(bind)=(["\'])(.*?)\3)/';
    private Routes $routes;

    public function __construct(Routes $routes)
    {
        $this->routes = $routes;
    }

    public function replace(string $content, object $component): string
    {
        $class = get_class($component);
        $classWithoutNamespace = $class;
        $namespaces = Framework::config('componentNamespaces');
        foreach ($namespaces as $namespace) {
            $classWithoutNamespace = str_replace($namespace.'\\', '', $classWithoutNamespace);
        }

        $raws = [];
        $contents = [];
        foreach ([
            static::REGEX_FORM_SUBMIT => 'action="%s" method="POST"',
            static::REGEX_A_ONCLICK => 'href="%s"',
        ] as $regexp => $attr) {
            if (preg_match_all($regexp, $content, $matches)) {
                foreach ($matches[1] as $i => $key) {
                    $raws[] = $matches[1][$i];
                    $type = $matches[2][$i];
                    $definition = $matches[4][$i];
                    $args = null;
                    if (preg_match('/(.+)\((.*)\)$/', $definition, $match)) {
                        [, $definition, $args] = $match;
                        $args = @unserialize(html_entity_decode($args), ['allowed_classes' => true]);
                    }
                    $url = $this->routes->get($classWithoutNamespace, $definition, $args);
                    // TODO Refactor this mess
                    if (!$url) {
                        $url = Framework::config('urlPrefix').Helper::hyphen($classWithoutNamespace).'/'.Helper::hyphen($definition);
                        if ($args) {
                            $param = [];
                            foreach ($args as $arg) {
                                $param[] = serialize($arg);
                            }
                            $url .= '?'.htmlentities(http_build_query(['fernet-params' => $param]));
                        }
                    }
                    $contents[] = sprintf($attr, $url).$this->addJs($type, $class, $definition);
                }
            }
        }
        foreach ([
            static::REGEX_BIND => 'name="%s" value="%s"',
        ] as $regexp => $attr) {
            if (preg_match_all($regexp, $content, $matches)) {
                foreach ($matches[1] as $i => $key) {
                    $raws[] = $matches[1][$i];
                    $type = $matches[2][$i];
                    $definition = $matches[4][$i];
                    $value = $component;
                    $vars = explode('.', $definition);
                    foreach ($vars as $var) {
                        $value = $value->$var;
                    }
                    $contents[] = sprintf($attr, "fernet-bind[$definition]", $value).$this->addJs($type, $class, $definition);
                }
            }
        }

        return str_replace($raws, $contents, $content);
    }

    public function addJs($type, $class, $definition): string
    {
        try {
            return Framework::config('enableJs') ?
                " fernet-$type=".json_encode("$class.$definition", JSON_THROW_ON_ERROR) :
                '';
        } catch (JsonException $e) {
            Framework::getInstance()->getLog()->error("Error on converting \"$class.$definition\" to JSON");

            return '';
        }
    }
}
