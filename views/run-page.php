<?php
// phpcs:disable Generic.Files.LineLength.MaxExceeded
// phpcs:disable Generic.Files.LineLength.TooLong

$run_nonce = wp_create_nonce( 'wp2static-run-page' );
?>

<script type="text/javascript">
var latest_log_row = 0;

jQuery(document).ready(function($){
    var run_data = {
        action: 'wp2static_run',
        security: '<?php echo $run_nonce; ?>',
    };
    var detect_data = {
        action: 'wp2static_detect',
        security: '<?php echo $run_nonce; ?>',
    };
    var crawl_data = {
        action: 'wp2static_crawl',
        security: '<?php echo $run_nonce; ?>',
    };
    var post_process_data = {
        action: 'wp2static_post_process',
        security: '<?php echo $run_nonce; ?>',
    };
    var copy_uploads_data = {
        action: 'wp2static_copy_uploads',
        security: '<?php echo $run_nonce; ?>',
    };

    var log_data = {
        dataType: 'text',
        action: 'wp2static_poll_log',
        startRow: latest_log_row,
        security: '<?php echo $run_nonce; ?>',
    };

    function set_all_btns_disabled(disabled) {
        $('#wp2static-run').prop('disabled', disabled);
        $('#wp2static-detect').prop('disabled', disabled);
        $('#wp2static-crawl').prop('disabled', disabled);
        $('#wp2static-post-process').prop('disabled', disabled);
        $('#wp2static-copy-uploads').prop('disabled', disabled);
    }

    function responseErrorHandler( jqXHR, textStatus, errorThrown ) {
        $("#wp2static-spinner").removeClass("is-active");
        set_all_btns_disabled(false);

        console.log(errorThrown);
        console.log(jqXHR.responseText);

        alert(`${jqXHR.status} error code returned from server.
Please check your server's error logs or try increasing your max_execution_time limit in PHP if this consistently fails after the same duration.
More information of the error may be logged in your browser's console.`);
    }

    function pollLogs() {
        $.post(ajaxurl, log_data, function(response) {
            $('#wp2static-run-log').val(response);
            $("#wp2static-poll-logs" ).prop('disabled', false);
        });
    }

    $( "#wp2static-run" ).click(function() {
        $("#wp2static-spinner").addClass("is-active");
        set_all_btns_disabled(true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: run_data,
            timeout: 0,
            success: function() {
                $("#wp2static-spinner").removeClass("is-active");
                set_all_btns_disabled(false);
                pollLogs();
            },
            error: responseErrorHandler
        });

    });

    $( "#wp2static-detect" ).click(function() {
        $("#wp2static-spinner").addClass("is-active");
        set_all_btns_disabled(true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: detect_data,
            timeout: 0,
            success: function() {
                $("#wp2static-spinner").removeClass("is-active");
                set_all_btns_disabled(false);
                pollLogs();
            },
            error: responseErrorHandler
        });
    });

    $( "#wp2static-crawl" ).click(function() {
        $("#wp2static-spinner").addClass("is-active");
        set_all_btns_disabled(true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: crawl_data,
            timeout: 0,
            success: function() {
                $("#wp2static-spinner").removeClass("is-active");
                set_all_btns_disabled(false);
                pollLogs();
            },
            error: responseErrorHandler
        });
    });

    $( "#wp2static-post-process" ).click(function() {
        $("#wp2static-spinner").addClass("is-active");
        set_all_btns_disabled(true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: post_process_data,
            timeout: 0,
            success: function() {
                $("#wp2static-spinner").removeClass("is-active");
                set_all_btns_disabled(false);
                pollLogs();
            },
            error: responseErrorHandler
        });
    });

    $( "#wp2static-copy-uploads" ).click(function() {
        const response = confirm("Are you sure you want to copy the uploads folder?");

        if (!response) {
            return;
        }

        $("#wp2static-spinner").addClass("is-active");
        set_all_btns_disabled(true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: copy_uploads_data,
            timeout: 0,
            success: function() {
                $("#wp2static-spinner").removeClass("is-active");
                set_all_btns_disabled(false);
                pollLogs();
            },
            error: responseErrorHandler
        });
    });

    $( "#wp2static-poll-logs" ).click(function() {
        $("#wp2static-poll-logs" ).prop('disabled', true);
        pollLogs();
    });

    function refetchLogs() {
        $.post(ajaxurl, log_data, function (response) {
            $('#wp2static-run-log').val(response);
        })

    }

    $("#wp2static-poll-logs-auto-refresh").click(function () {
        if (window.log_auto_refresh_timer) {
            clearInterval(window.log_auto_refresh_timer);
            window.log_auto_refresh_timer = null;
            $(this).text("Start Auto Refresh").css("background-color", "#f6f7f7");
        } else {
            refetchLogs();
            window.log_auto_refresh_timer = setInterval(refetchLogs, 5000);
            $(this).text("Stop Auto Refresh").css("background-color", "#ffacac");
        }
    });
});
</script>

<!DOCTYPE html>
<html>
  <head>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <style>
  /* General styles */
  .wrap {
    width: 80%;
    margin: 20px;
    padding: 20px;
    text-align: left;
  }

  /* Accordion styles */
  #accordion {
    width: 100%;
    margin: 0 auto;
    text-align: left;
  }

  .accordion h3 {
    background-color: black;
    color: white;
    text-align: center;
    padding: 10px;
    box-shadow: 2px 2px 2px #888888;
    border: 1px solid #ddd;
    border-radius: 5px;
    cursor: pointer;
    font-size:16px;
  }

  .note {
    color: red;
    font-weight: bold;
  }

  #accordion div {
    background-color: #f7f7f7;
    color: #333;
    padding: 15px;
    border: 1px solid #ddd;
    border-top: 0;
  }

  /* Button styles */
  .button {
    background-color: #000;
    color: #fff;
    padding: 10px 20px;
    border: 0;
    border-radius: 5px;
    cursor: pointer;
    margin: 10px;
  }

  .button:hover {
    background-color: #000;
  }

  .button-primary {
    background-color: #000;
  }

  .button-primary:hover {
    background-color: #005c99;
  }
  #wpfooter{
      display:none;
  }
  li p {
    font-size: 15px !important;
}
ul {
    list-style: inside;
    padding: 5px;
    font-size: 14px;
}
.ui-accordion .ui-accordion-header{
    font-size: 15px;
}
</style>

  </head>
  <body>
  
  <div class="wrap">
    <h2>Quick Setup</h2>
    <p>Before you run the static site generator, please check the tabs below:</p>
    <div id="accordion">
        <h3>WPCLI and rsync</h3>
        <div>
            <p>Please check if WP-CLI and rsync are installed. If not, go to the Advanced Misc Options in the Advanced page and deactivate the WP job execution.</p>
        </div>
        <h3>Process Queue Interval</h3>
        <div>
            <p>Please check the Jobs page to choose which jobs you want to add to the queue.</p>
            <p class="note">Note: If you will make many changes in many posts, make sure to disable events like "post save" and "post delete" to prevent overload on your server.</p>
        </div>
        <h3>Options Page</h3>
        <div>
            <p>Please check the Control Detected URLs and Crawling Options before you start generating the website.</p>
        </div>
        <h3>After that, you can generate the site by clicking the "Generate static site" button below.</h3>
        <div>
            <h3>If your website is big, please split the process into the following steps:</h3>
    <ul>
        <li>Detect URLs: will detect all URLs in the website.</li>
        <li>Crawl URLs: will crawl all detected URLs and create a static HTML in the upload folder called "wp2static-crawled-site".</li>
        <li>Post process: will check all URLs in the static HTML files and replace the website URL with the URL in the Post-processing Options in the options page. The processed site folder will be in the upload folder called "wp2static-processed-site". Copy the plugins and themes folder to upload/wp2static-processed-site/wp-content/.</li>
        <li>Copy uploads: copy the uploads folder to upload/wp2static-processed-site/wp-content/.</li>
    </ul>
    <p>The final static site will be in "sitename"/wp-content/upload/wp2static-processed-site/</p>

        </div>
    </div>
</div>





      <br>

      <button class="button button-primary" id="wp2static-detect">Detect URLs</button>

      <button class="button button-primary" id="wp2static-crawl">Crawl URLs</button>

      <button class="button button-primary" id="wp2static-post-process">Post process</button>

      <button class="button button-primary" id="wp2static-copy-uploads">Copy uploads</button>

      <button class="button button-primary" id="wp2static-run">Generate static site</button>

      <div id="wp2static-spinner" class="spinner" style="padding:2px;float:none;"></div>

      <br>
      <br>

      <button class="button" id="wp2static-poll-logs">Refresh logs</button>
      <button class="button" id="wp2static-poll-logs-auto-refresh">Start Auto Refresh</button>
      <br>
      <br>
      <textarea id="wp2static-run-log" rows=30 style="width:99%;">
      Logs will appear here on completion or click "Refresh logs" to check progress
      </textarea>
    </div>

    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script>
      $( function() {
        $( "#accordion" ).accordion();
      } );
    </script>
  </body>
</html>

