<?php

declare(strict_types=1);

namespace Fernet\Core;

use Fernet\Params;

class ReplaceComponents
{
    private const REGEX_TAG_WITH_CHILD = '/<([A-Z][\w0-9_\-\.]+)([^>]*)>(.+?)<\/\1>/s';
    private const REGEX_TAG = '/<([A-Z][\w0-9_\-\.]+)([^>]*)\/>/s';
    private const REGEX_ATTRIBUTE = '/(\w+)(?:=(["\'])(.+?)\2)?/s';
    private const REGEX_ATTRIBUTE_WITH_OBJECT = '/(\w+)={(.+)}/s';

    /**
     * @param string $content
     * @return string
     */
    public function replace(string $content): string
    {
        $raws = [];
        $contents = [];
        // FIXME prevent circular reference between components
        // FIXME throw error if there are components inside child content
        foreach ([static::REGEX_TAG, static::REGEX_TAG_WITH_CHILD] as $regexp) {
            if (preg_match_all($regexp, $content, $matches)) {
                foreach ($matches[1] as $i => $tag) {
                    $raws[] = $matches[0][$i];
                    $params = $this->parseAttributes($matches[2][$i]);
                    $childContent = $matches[3][$i] ?? '';
                    $tag = str_replace('.', '\\', $tag);
                    $contents[] = (new ComponentElement($tag, $params, $childContent))->render();
                }
            }
        }

        return str_replace($raws, $contents, $content);
    }

    public function parseAttributes(string $raw): array
    {
        $attributes = [];
        if (preg_match_all(static::REGEX_ATTRIBUTE, $raw, $matches)) {
            foreach ($matches[1] as $i => $key) {
                $attributes[$key] = $matches[3][$i] ?: true;
            }
        }
        if (preg_match_all(static::REGEX_ATTRIBUTE_WITH_OBJECT, $raw, $matches)) {
            foreach ($matches[1] as $i => $key) {
                $attributes[$key] = Params::get($matches[2][$i]);
            }
        }

        return $attributes;
    }
}
