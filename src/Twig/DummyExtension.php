<?php

namespace SymfonyTools\CodeBlockChecker\Twig;

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
            new TwigFunction('asset'),
            new TwigFunction('asset_version'),
            new TwigFunction('csrf_token'),
            new TwigFunction('dump'),
            new TwigFunction('expression'),
            new TwigFunction('form_widget'),
            new TwigFunction('form_errors'),
            new TwigFunction('form_label'),
            new TwigFunction('form_help'),
            new TwigFunction('form_row'),
            new TwigFunction('form_rest'),
            new TwigFunction('form'),
            new TwigFunction('form_start'),
            new TwigFunction('form_end'),
            new TwigFunction('csrf_token'),
            new TwigFunction('form_parent'),
            new TwigFunction('absolute_url'),
            new TwigFunction('relative_path'),
            new TwigFunction('render'),
            new TwigFunction('render_*'),
            new TwigFunction('controller'),
            new TwigFunction('logout_url'),
            new TwigFunction('logout_path'),
            new TwigFunction('url'),
            new TwigFunction('path'),
            new TwigFunction('is_granted'),
            new TwigFunction('link'),
            new TwigFunction('preload'),
            new TwigFunction('dns_prefetch'),
            new TwigFunction('preconnect'),
            new TwigFunction('prefetch'),
            new TwigFunction('prerender'),
            new TwigFunction('workflow_can'),
            new TwigFunction('workflow_transitions'),
            new TwigFunction('workflow_has_marked_place'),
            new TwigFunction('workflow_marked_places'),
            new TwigFunction('workflow_metadata'),
            new TwigFunction('workflow_transition_blockers'),
            new TwigFunction('encore_entry_link_tags'),
            new TwigFunction('encore_entry_script_tags'),
            new TwigFunction('impersonation_exit_path'),
            new TwigFunction('impersonation_exit_url'),
            new TwigFunction('workflow_transition'),
            new TwigFunction('t'),
            new TwigFunction('mercure'),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('abbr_class'),
            new TwigFilter('abbr_method'),
            new TwigFilter('format_args'),
            new TwigFilter('format_args_as_text'),
            new TwigFilter('file_excerpt'),
            new TwigFilter('format_file'),
            new TwigFilter('format_file_from_text'),
            new TwigFilter('format_log_message'),
            new TwigFilter('file_link'),
            new TwigFilter('file_relative'),
            new TwigFilter('humanize'),
            new TwigFilter('form_encode_currency'),
            new TwigFilter('yaml_encode'),
            new TwigFilter('yaml_dump'),
            new TwigFilter('trans'),
            new TwigFilter('transchoice'),
            new TwigFilter('inline_css'),
            new TwigFilter('markdown_to_html'),
            new TwigFilter('inky_to_html'),
            new TwigFilter('serialize'),
        ];
    }
}
