<?php

/**
 * Returns a single lined path for a baum node.
 *
 * @param $node
 *
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
