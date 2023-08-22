<?php

namespace WP2Static;

class JobProcessor
{
    /*
    todo: ctl: this function need to be refactored
    */
    static $job = null;

    public static function getCurrentJob() {
        return self::$job;
    }

    public static function processJob($job): void
    {
        global $wpdb;

        self::$job = $job;

        $lock = $wpdb->prefix . '.wp2static_jobs.' . $job->job_type;
        $query = "SELECT GET_LOCK('$lock', 30) AS lck";
        $locked = intval($wpdb->get_row($query)->lck);
        if (!$locked) {
            WsLog::l("Failed to acquire \"$lock\" lock.");
            return;
        }
        try {
            JobQueue::setStatus($job->id, 'processing');

            switch ($job->job_type) {
                case 'detect':
                    WsLog::l('Starting URL detection');
                    CrawlQueue::truncate();
                    $detected_count = URLDetector::enqueueURLs();
                    WsLog::l("URL detection completed ($detected_count URLs detected)");
                    break;
                case 'crawl':
                    Controller::wp2staticCrawl();
                    break;
                case 'post_process':
                    WsLog::l('Starting post-processing');
                    $post_processor = new PostProcessor();
                    $processed_site_dir =
                        SiteInfo::getPath('uploads') . 'wp2static-processed-site';
                    $processed_site = new ProcessedSite();
                    $post_processor->processStaticSite(StaticSite::getPath());
                    WsLog::l('Post-processing completed');
                    break;
                case 'copy_uploads':
                    WsLog::l('Starting copying uploads');

                    // check if rsync command is available
                    if (exec('rsync --version') === '') {
                        WsLog::l('rsync command not found.');
                        WsLog::l('Failed to copy uploads.');
                        break;
                    }

                    $uploads_src_path = SiteInfo::getPath('uploads');
                    $wp_upload_dest_path = $uploads_src_path . 'wp2static-processed-site/wp-content/uploads/';

                    $ignore_folders = ['wp2static-processed-site/', 'wp2static-crawled-site/'];

                    $command = <<<EOT
                    mkdir -p "$wp_upload_dest_path" && \
                    rsync -a "$uploads_src_path" "$wp_upload_dest_path"
EOT;
                    foreach ($ignore_folders as $ignore_folder) {
                        $command .= " --exclude ${ignore_folder}";
                    }

                    exec($command, $output, $return_var);
                    if ($return_var !== 0) {
                        WsLog::l('Failed to copy uploads.');
                        WsLog::l('rsync command returned: ' . print_r($output, true));
                        WsLog::l($command);
                    }

                    WsLog::l('Copying uploads folder completed');
                    break;
                case 'deploy':
                    $deployer = Addons::getDeployer();

                    if (!$deployer) {
                        WsLog::l('No deployment add-ons are enabled, skipping deployment.');
                    } else {
                        WsLog::l('Starting deployment');
                        do_action(
                            'wp2static_deploy',
                            ProcessedSite::getPath(),
                            $deployer
                        );
                    }
                    WsLog::l('Starting post-deployment actions');
                    do_action('wp2static_post_deploy_trigger', $deployer);

                    break;
                default:
                    WsLog::l('Trying to process unknown job type');
            }

            JobQueue::setStatus($job->id, 'completed');
        } catch (\Throwable $e) {
            JobQueue::setStatus($job->id, 'failed');
            // We don't want to crawl and deploy if the detect step fails.
            // Skip all waiting jobs when one fails.
            $table_name = $wpdb->prefix . 'wp2static_jobs';
            $wpdb->query(
                "UPDATE $table_name
                     SET status = 'skipped'
                     WHERE status = 'waiting'"
            );
            throw $e;
        } finally {
            $wpdb->query("DO RELEASE_LOCK('$lock')");
        }
    }

    public static function isJobLimited(): bool
    {
        return !empty(self::$job->job_meta);
    }

    public static function adjustPostsQueryBasedOnJobMeta($query)
    {
        $job = self::getCurrentJob();

        if (!empty($job->job_meta['detect_last_n_day'])) {
            $query .= " AND post_modified > DATE_SUB(NOW(), INTERVAL {$job->job_meta['detect_last_n_day']} DAY)";
        }

        return $query;
    }
}
