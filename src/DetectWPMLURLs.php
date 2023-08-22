<?php

namespace WP2Static;

class DetectWPMLURLs
{

    public static function detect()
    {
        $is_wpml_installed = is_plugin_active('sitepress-multilingual-cms/sitepress.php');

        if ( $is_wpml_installed ) {
            $wpmlHomePages = self::detectWpmlLanguages();
            $wpmlCustomPostArchivePages = self::detectCustomPostsArchivePages();
            $wpmlTaxonomyArchivePages = self::detectTaxonomyArchivePages();
            return array_merge($wpmlHomePages, $wpmlCustomPostArchivePages, $wpmlTaxonomyArchivePages);
        }

        return [];
    }

    private static function detectWpmlLanguages()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'icl_languages';

        $langs = $wpdb->get_results("SELECT * FROM $table_name where active = 1;");

        $wpml_urls = [];

        foreach ($langs as $lang) {
            $wpml_urls[] = '/' . $lang->code . '/';
        }

        return $wpml_urls;
    }

    private static function detectCustomPostsArchivePages()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'icl_languages';

        $langs = $wpdb->get_results("SELECT * FROM $table_name where active = 1;");

        $query = sprintf("select post_type from %s where post_status = 'publish'", $wpdb->posts);

        $query = JobProcessor::adjustPostsQueryBasedOnJobMeta($query);

        $query .= 'group by post_type';

        $customPosts = $wpdb->get_results($query);

        $wpml_urls = [];

        foreach ($customPosts as $customPost) {
            $wpml_urls[] = '/' . $customPost->post_type . '/';
        }

        foreach ($langs as $lang) {
            foreach ($customPosts as $customPost) {
                $wpml_urls[] = '/' . $lang->code . '/' . $customPost->post_type . '/';
            }
        }

        return $wpml_urls;
    }

    private static function detectTaxonomyArchivePages()
    {
        global $wpdb;

        // get wordpress option
        $wpml_config = get_option('icl_sitepress_settings');
        $default_language = $wpml_config['default_language'] ?? 'en';

        $wpml_urls = [];

        $taxonomies = [
            'category' => 'category_base',
            'post_tag' => 'tag_base',
        ];

        foreach ($taxonomies as $taxonomy => $taxonomy_option_name) {
            $tax_prefix = get_option($taxonomy_option_name);
            if (!$tax_prefix) {
                continue;
            }

            $query = <<<EOF
            select language_code, slug, taxonomy from {$wpdb->prefix}term_taxonomy
            left join {$wpdb->prefix}terms
                on {$wpdb->prefix}term_taxonomy.term_id = {$wpdb->prefix}terms.term_id
            left join {$wpdb->prefix}icl_translations
                on {$wpdb->prefix}term_taxonomy.term_taxonomy_id = {$wpdb->prefix}icl_translations.element_id
            where {$wpdb->prefix}icl_translations.element_type = 'tax_{$taxonomy}'
            and taxonomy = '{$taxonomy}';
EOF;

            $tax_slugs = $wpdb->get_results($query);

            foreach ($tax_slugs as $tax_slug) {
                if ($tax_slug->language_code == $default_language) {
                    $wpml_urls[] = '/' . $tax_prefix . '/' . $tax_slug->slug . '/';
                } else {
                    $wpml_urls[] = '/' . $tax_slug->language_code . '/' . $tax_prefix . '/' . $tax_slug->slug . '/';
                }
            }
        }

        return $wpml_urls;
    }
}
