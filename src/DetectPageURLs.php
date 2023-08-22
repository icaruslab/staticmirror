<?php

namespace WP2Static;

class DetectPageURLs {

    /**
     * Detect Page URLs
     *
     * @return string[] list of URLs
     */
    public static function detect() : array {
        global $wpdb;

        $page_urls = [];

        $query = "SELECT ID
            FROM {$wpdb->posts}
            WHERE post_status = 'publish'
            AND post_type = 'page'";

        $query = JobProcessor::adjustPostsQueryBasedOnJobMeta($query);

        $page_ids = $wpdb->get_col($query);

        foreach ( $page_ids as $page_id ) {
            $permalink = get_page_link( $page_id );

            if ( strpos( $permalink, '?post_type' ) !== false ) {
                continue;
            }

            $page_urls[] = $permalink;
        }

        return $page_urls;
    }
}
