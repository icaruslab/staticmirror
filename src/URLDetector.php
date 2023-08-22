<?php
/*
    URLDetector

    Detects URLs from WordPress DB, filesystem and user input

    Users can control detection levels

    Saves URLs to CrawlQueue

*/

namespace WP2Static;

class URLDetector {

    public static function countURLs() : int {
        return count( static::detectURLs( $quiet = true ) );
    }

    /**
     * Detect URLs within site
     *
     * @return array<string>
     */
    public static function detectURLs( bool $quiet = false ) : array {
        if ( ! $quiet ) {
            WsLog::l( 'Starting to detect WordPress site URLs.' );
        }

        do_action(
            'wp2static_detect'
        );

        $arrays_to_merge = [];

        // TODO: detect robots.txt, etc before adding
        $arrays_to_merge[] = [
            '/',
            '/robots.txt',
            '/favicon.ico',
            '/sitemap.xml',
        ];

        /*
            TODO: reimplement detection for URLs:
                'detectCommentPagination',
                'detectComments',
                'detectFeedURLs',

        // other options:

         - robots
         - favicon
         - sitemaps

        */
        $count = 0;

        if ( CoreOptions::getValue( 'detectPosts' ) ) {
            WsLog::l( 'Detecting posts...' );
            $arrays_to_merge[] = DetectPostURLs::detect();
            $count += count($arrays_to_merge[ count( $arrays_to_merge ) - 1 ]);
            WsLog::l(
                "$count urls detected..."
            );
        }

        if ( CoreOptions::getValue( 'detectPages' ) ) {
            WsLog::l( 'Detecting pages...' );
            $arrays_to_merge[] = DetectPageURLs::detect();
            $count += count($arrays_to_merge[ count( $arrays_to_merge ) - 1 ]);
            WsLog::l(
                "$count urls detected..."
            );
        }

        if ( CoreOptions::getValue( 'detectCustomPostTypes' ) ) {
            WsLog::l( 'Detecting custom post types...' );
            $arrays_to_merge[] = DetectCustomPostTypeURLs::detect(CoreOptions::getValue( 'excludeCustomPostTypes' ));
            $count += count($arrays_to_merge[ count( $arrays_to_merge ) - 1 ]);
            WsLog::l(
                "$count urls detected..."
            );
        }

        if ( CoreOptions::getValue( 'detectUploads' ) ) {
            $filenames_to_ignore = CoreOptions::getLineDelimitedBlobValue( 'filenamesToIgnore' );

            $filenames_to_ignore =
                apply_filters(
                    'wp2static_filenames_to_ignore',
                    $filenames_to_ignore
                );

            $file_extensions_to_ignore = CoreOptions::getLineDelimitedBlobValue(
                'fileExtensionsToIgnore'
            );

            $file_extensions_to_ignore =
                apply_filters(
                    'wp2static_file_extensions_to_ignore',
                    $file_extensions_to_ignore
                );

            $arrays_to_merge[] =
                FilesHelper::getListOfLocalFilesByDir(
                    SiteInfo::getPath( 'uploads' ),
                    $filenames_to_ignore,
                    $file_extensions_to_ignore
                );

            $count += count($arrays_to_merge[ count( $arrays_to_merge ) - 1 ]);
            WsLog::l(
                "$count urls detected..."
            );
        }

        $detect_sitemaps = apply_filters( 'wp2static_detect_sitemaps', 1 );

        if ( $detect_sitemaps ) {
            WsLog::l( 'Detecting sitemaps...' );
            $arrays_to_merge[] = DetectSitemapsURLs::detect( SiteInfo::getURL( 'site' ) );
            $count += count($arrays_to_merge[ count( $arrays_to_merge ) - 1 ]);
            WsLog::l(
                "$count urls detected..."
            );
        }

        $detect_parent_theme = apply_filters( 'wp2static_detect_parent_theme', 1 );

        if ( $detect_parent_theme ) {
            $arrays_to_merge[] = DetectThemeAssets::detect( 'parent' );
            $count += count($arrays_to_merge[ count( $arrays_to_merge ) - 1 ]);
            WsLog::l(
                "$count urls detected..."
            );
        }

        $detect_child_theme = apply_filters( 'wp2static_detect_child_theme', 1 );

        if ( $detect_child_theme ) {
            $arrays_to_merge[] = DetectThemeAssets::detect( 'child' );
            $count += count($arrays_to_merge[ count( $arrays_to_merge ) - 1 ]);
            WsLog::l(
                "$count urls detected..."
            );
        }

        $detect_plugin_assets = apply_filters( 'wp2static_detect_plugin_assets', 1 );

        if ( $detect_plugin_assets ) {
            $arrays_to_merge[] = DetectPluginAssets::detect();
            $count += count($arrays_to_merge[ count( $arrays_to_merge ) - 1 ]);
            WsLog::l(
                "$count urls detected..."
            );
        }

        $detect_wpinc_assets = apply_filters( 'wp2static_detect_wpinc_assets', 1 );

        if ( $detect_wpinc_assets ) {
            $arrays_to_merge[] = DetectWPIncludesAssets::detect();
            $count += count($arrays_to_merge[ count( $arrays_to_merge ) - 1 ]);
            WsLog::l(
                "$count urls detected..."
            );
        }

        $detect_vendor_cache = apply_filters( 'wp2static_detect_vendor_cache', 1 );

        if ( $detect_vendor_cache ) {
            $arrays_to_merge[] = DetectVendorFiles::detect( SiteInfo::getURL( 'site' ) );
            $count += count($arrays_to_merge[ count( $arrays_to_merge ) - 1 ]);
            WsLog::l(
                "$count urls detected..."
            );
        }

        $detect_posts_pagination = apply_filters(
            'wp2static_detect_posts_pagination',
            CoreOptions::getValue( 'detectPagination' ) ?? 1
        );

        if ( $detect_posts_pagination ) {
            WsLog::l( 'Detecting posts pagination...' );
            $arrays_to_merge[] = DetectPostsPaginationURLs::detect( SiteInfo::getURL( 'site' ) );
            $count += count($arrays_to_merge[ count( $arrays_to_merge ) - 1 ]);
            WsLog::l(
                "$count urls detected..."
            );
        }

        $detect_archives = apply_filters( 'wp2static_detect_archives', 1 );

        if ( $detect_archives ) {
            WsLog::l( 'Detecting archives...' );
            $arrays_to_merge[] = DetectArchiveURLs::detect();
            $count += count($arrays_to_merge[ count( $arrays_to_merge ) - 1 ]);
            WsLog::l(
                "$count urls detected..."
            );
        }

        $detect_categories = apply_filters( 'wp2static_detect_categories', 1 );

        if ( $detect_categories ) {
            WsLog::l( 'Detecting categories...' );
            $arrays_to_merge[] = DetectCategoryURLs::detect();
            $count += count($arrays_to_merge[ count( $arrays_to_merge ) - 1 ]);
            WsLog::l(
                "$count urls detected..."
            );
        }

        $detect_category_pagination = apply_filters(
            'wp2static_detect_category_pagination',
            CoreOptions::getValue( 'detectPagination' ) ?? 1
        );

        if ( $detect_category_pagination ) {
            WsLog::l( 'Detecting category pagination...' );
            $arrays_to_merge[] = DetectCategoryPaginationURLs::detect();
            $count += count($arrays_to_merge[ count( $arrays_to_merge ) - 1 ]);
            WsLog::l(
                "$count urls detected..."
            );
        }

        $detect_authors = apply_filters( 'wp2static_detect_authors', 1 );

        if ( $detect_authors ) {
            WsLog::l( 'Detecting authors...' );
            $arrays_to_merge[] = DetectAuthorsURLs::detect();
            $count += count($arrays_to_merge[ count( $arrays_to_merge ) - 1 ]);
            WsLog::l(
                "$count urls detected..."
            );
        }

        // WPML detection
        WsLog::l( 'Detecting WPML urls...' );
        $arrays_to_merge[] = DetectWPMLURLs::detect();
        $count += count($arrays_to_merge[ count( $arrays_to_merge ) - 1 ]);
        WsLog::l(
            "$count urls detected..."
        );

        $detect_authors_pagination = apply_filters( 'wp2static_detect_authors_pagination', 1 );

        if ( $detect_authors_pagination ) {
            WsLog::l( 'Detecting authors pagination...' );
            $arrays_to_merge[] = DetectAuthorPaginationURLs::detect( SiteInfo::getUrl( 'site' ) );
            $count += count($arrays_to_merge[ count( $arrays_to_merge ) - 1 ]);
            WsLog::l(
                "$count urls detected..."
            );
        }

        $url_queue = call_user_func_array( 'array_merge', $arrays_to_merge );

        $url_queue = FilesHelper::cleanDetectedURLs( $url_queue );

        $url_queue = apply_filters(
            'wp2static_modify_initial_crawl_list',
            $url_queue
        );

        WsLog::l('Cleaning up duplicate URLs...');
        $unique_urls = array_unique( $url_queue );

        $total_detected = (string) count( $unique_urls );

        if ( ! $quiet ) {
            WsLog::l(
                "Detection complete. $total_detected URLs added to Crawl Queue."
            );
        }

        return $unique_urls;
    }

    public static function enqueueURLs() : string {
        try {
            $unique_urls = static::detectURLs();// No longer truncate before adding
            // addUrls is now doing INSERT IGNORE based on URL hash to be
            // additive and not error on duplicate
            CrawlQueue::addUrls($unique_urls);
            return (string)count($unique_urls);
        } catch (\Exception $e) {
            WsLog::l("There was an exception during detection, the exception is: {$e->getMessage()}");
        }
        return '0';
    }
}

