<?php

namespace SymfonyCodeBlockChecker\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * This extension will contain filters and functions that exists in Symfony. This
 * will help the parser to verify the correctness.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class DummyExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('asset', function () {}),
            new TwigFunction('asset_version', function () {}),
            new TwigFunction('csrf_token', function () {}),
            new TwigFunction('dump', function () {}),
            new TwigFunction('expression', function () {}),
            new TwigFunction('form_widget', null),
            new TwigFunction('form_errors', null),
            new TwigFunction('form_label', null),
            new TwigFunction('form_help', null),
            new TwigFunction('form_row', null),
            new TwigFunction('form_rest', null),
            new TwigFunction('form', null),
            new TwigFunction('form_start', null),
            new TwigFunction('form_end', null),
            new TwigFunction('csrf_token', null),
            new TwigFunction('form_parent', null),
            new TwigFunction('absolute_url', function () {}),
            new TwigFunction('relative_path', function () {}),
            new TwigFunction('render', function () {}),
            new TwigFunction('render_*', function () {}),
            new TwigFunction('controller', null),
            new TwigFunction('logout_url', function () {}),
            new TwigFunction('logout_path', function () {}),
            new TwigFunction('url', function () {}),
            new TwigFunction('path', function () {}),
            new TwigFunction('is_granted', function () {}),
            new TwigFunction('link', function () {}),
            new TwigFunction('preload', function () {}),
            new TwigFunction('dns_prefetch', function () {}),
            new TwigFunction('preconnect', function () {}),
            new TwigFunction('prefetch', function () {}),
            new TwigFunction('prerender', function () {}),
            new TwigFunction('workflow_can', function () {}),
            new TwigFunction('workflow_transitions', function () {}),
            new TwigFunction('workflow_has_marked_place', function () {}),
            new TwigFunction('workflow_marked_places', function () {}),
            new TwigFunction('workflow_metadata', function () {}),
            new TwigFunction('workflow_transition_blockers', function () {}),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('abbr_class', function () {}),
            new TwigFilter('abbr_method', function () {}),
            new TwigFilter('format_args', function () {}),
            new TwigFilter('format_args_as_text', function () {}),
            new TwigFilter('file_excerpt', function () {}),
            new TwigFilter('format_file', function () {}),
            new TwigFilter('format_file_from_text', function () {}),
            new TwigFilter('format_log_message', function () {}),
            new TwigFilter('file_link', function () {}),
            new TwigFilter('file_relative', function () {}),
            new TwigFilter('humanize', function () {}),
            new TwigFilter('form_encode_currency', function () {}),
            new TwigFilter('yaml_encode', function () {}),
            new TwigFilter('yaml_dump', function () {}),
            new TwigFilter('trans', function () {}),
            new TwigFilter('transchoice', function () {}),
        ];
    }
}
