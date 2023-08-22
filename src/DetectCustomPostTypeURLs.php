<?php

namespace WP2Static;

class DetectCustomPostTypeURLs {

    /**
     * Detect Custom Post Type URLs
     *
     * @return string[] list of URLs
     */
    public static function detect($customPostTypesToExclude) : array {
        global $wpdb;

        $post_urls = [];

        $allCustomPostTypesToExclude = [];

        if (!empty($customPostTypesToExclude)) {
            $allCustomPostTypesToExclude = explode(',', $customPostTypesToExclude);
        }

        $allCustomPostTypesToExclude = array_merge(
            $allCustomPostTypesToExclude,
            ['revision', 'attachment']
        );

        $allCustomPostTypesToExclude = array_map(function ($post_type) {
            return "'" . trim($post_type) . "'";
        }, $allCustomPostTypesToExclude);

        $allCustomPostTypesToExclude = implode(',', $allCustomPostTypesToExclude);

        $query = "SELECT ID
            FROM {$wpdb->posts}
            WHERE post_status = 'publish'
            AND post_type NOT IN ($allCustomPostTypesToExclude)";

        $query = JobProcessor::adjustPostsQueryBasedOnJobMeta($query);

        $post_ids = $wpdb->get_col($query);

        foreach ( $post_ids as $post_id ) {
            $permalink = get_permalink( $post_id );

            if ( ! is_string( $permalink ) ) {
                continue;
            }

            if ( strpos( $permalink, '?post_type' ) !== false ) {
                continue;
            }

            $post_urls[] = $permalink;
        }

        return $post_urls;
    }
}
