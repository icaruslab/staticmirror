<?php
/*
    PostProcessor

    Processes each file in StaticSite, saving to ProcessedSite
*/

namespace WP2Static;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class PostProcessor {

    /**
     * PostProcessor constructor
     */
    public function __construct() {

    }

    /**
     * Process StaticSite
     *
     * Iterates on each file, not directory
     *
     * @param string $static_site_path Static site path
     * @throws WP2StaticException
     */
    public function processStaticSite(
        string $static_site_path
    ) : void {
        WsLog::l('Copying themes and plugins files.');
        $this->syncThemesAndPlugins();

        WsLog::l(
            'Processing crawled site.'
        );

        if ( ! is_dir( $static_site_path ) ) {
            WsLog::l(
                'No static site directory to process.'
            );

            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $static_site_path,
                RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        foreach ( $iterator as $filename => $file_object ) {
            $save_path = str_replace( $static_site_path, '', $filename );

            // copy file to ProcessedSite dir, then process it
            // this allows external processors to have their way with it
            ProcessedSite::add( $filename, $save_path );

            $file_processor = new FileProcessor();

            $file_processor->processFile( ProcessedSite::getPath() . $save_path );
        }

        WsLog::l( 'Finished processing crawled site.' );

        do_action( 'wp2static_post_process_complete', ProcessedSite::getPath() );
    }


    public function syncThemesAndPlugins() : void
    {
        // check if rsync command is available
        if (exec('rsync --version') === '') {
            WsLog::l('rsync command not found.');
            WsLog::l('Failed to sync themes and plugins.');
            return;
        }

        $processed_site_path = SiteInfo::getPath('uploads') . 'wp2static-processed-site/';
        $plugins_src_path = WP_PLUGIN_DIR;
        $themes_src_path = get_theme_root();
        $wp_content_dest_path = $processed_site_path . 'wp-content/';
        $wp_includes_path = ABSPATH . WPINC;
        $command = <<<EOT
        mkdir -p $wp_content_dest_path && \
        rsync -a "$plugins_src_path" "$wp_content_dest_path" && \
        rsync -a "$themes_src_path" "$wp_content_dest_path" && \
        rsync -a "$wp_includes_path" "$processed_site_path"
EOT;
        exec($command, $output, $return_var);
        if ($return_var !== 0) {
            WsLog::l('Failed to sync themes and plugins.');
        }
    }
}

