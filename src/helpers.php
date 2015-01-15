<?php

/**
 * Helper for config facade. Checks if config helper function already exists
 * for Laravel 5 support
 *
 * @param string $key
 * @param string $default
 * @return mixed (array or string)
 */
if (!function_exists('config')) {
    function config($key, $default = NULL)
    {
        return Config::get($key, $default);
    }
}

/**
 * Returns a single lined path for a baum node
 *
 * @param $node
 * @return string
 */
if (!function_exists('renderNode')) {

    function renderNode($node)
    {
        $html = '';

        if (is_object($node)) {
            $ancestors = $node->getAncestorsAndSelf();

            foreach ($ancestors as $ancestor) {
                if ($node->equals($ancestor) && $node->isRoot()) {
                    $html .= sprintf('<b>%s</b>', $ancestor->name);
                } elseif ($node->equals($ancestor)) {
                    $html .= sprintf(' > <b>%s</b>', $ancestor->name);
                } elseif ($ancestor->isRoot()) {
                    $html .= sprintf('%s', $ancestor->name);
                } else {
                    $html .= sprintf(' > %s', $ancestor->name);
                }
            }

            return $html;
        }
        return $node;
    }

}