<?php

namespace WP2Static;

class DetectPostURLs {

    /**
     * Detect Post URLs
     *
     * @return string[] list of URLs
     */
    public static function detect() : array {
        global $wpdb;

        $post_urls = [];

        $query = "SELECT ID
            FROM {$wpdb->posts}
            WHERE post_status = 'publish'
            AND post_type = 'post'";

        $query = JobProcessor::adjustPostsQueryBasedOnJobMeta($query);

        $post_ids = $wpdb->get_col($query);

        foreach ( $post_ids as $post_id ) {
            $permalink = get_permalink( $post_id );

            if ( ! $permalink ) {
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
