<?php

$here = plugin_dir_path( __FILE__ );

function process_template($templatefile, $report){

    if(!$fd = fopen( plugin_dir_path( __FILE__ ) . '/templates/' . $templatefile, "r"))
    {
        echo "Template $templatefile not found!";
        exit();
    }
    else
    {
        $template = fread ($fd, filesize (plugin_dir_path( __FILE__ ) . '/templates/' . $templatefile));
        fclose ($fd);
        $template = stripslashes($template);
        $template = preg_replace("/<% site_name %>/", $report[0]['site_name'], $template);
        $template = preg_replace("/<% site_url %>/", $report[0]['site_url'], $template);
        $template = preg_replace("/<% report_type %>/", $report[0]['report_type'], $template);
        $template = preg_replace("/<% report_period %>/", $report[0]['report_period'], $template);
        $template = preg_replace("/<% total_posts %>/", $report[0]['total_posts'], $template);


        $i = 0;

        $report_list = null;


        foreach ($report as $row){
            if ($i > 0){ // First (0) row of report contains meta, we want to skip this

                $report_row = "<tr>";
                $report_row .= "<td>" . $row['time'] . "</td>";
                $report_row .= "<td>" . $row['title'] . "</td>";
                $report_row .= "<td>" . $row['author'] . "</td>";
                $report_row .= "</tr>";
                $report_list .= $report_row;
            }

            $i++;

        }

        $template = preg_replace("/<% report %>/", $report_list, $template);

        return $template;
    }
}

function wpemail_daily_task ($send){

    $today = date("Y-m-d");

    $thedate = DateTime::createFromFormat('Y-m-d', $today);
    $thedate->modify('-1 day');
    $yesterday = $thedate->format('Y-m-d');

    $report = [];

    $query_by_day = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'date_query' => array(
            'after' => $yesterday
        )
    );


    $admin_email = get_option('admin_email');

    $options = get_option('wpemail_options');

    $to = $options['wpemail_field_recipients'];


    $query = new WP_Query($query_by_day);

    array_push($report, [
        'report_period' => $today,
        'report_type' => 'Daily',
        'total_posts' => $query->post_count,
        'site_name' => get_option('blogname'),
        'site_url' => get_option('siteurl')
    ]);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            array_push($report, [
                'time' => get_the_time(),
                'title' => get_the_title(),
                'author' => get_the_author(),
            ]);
        }
    }

    $msg = process_template('daily.html', $report);

    if ($send) {
        if (!$to) {
            if ($admin_email) {
                send_report($admin_email, $msg);
            }
        } else {
            send_report($to, $msg);
        }
    } else {
        echo $msg;
    }
    
}


function send_report($to, $message){
    $subject = "WordPress Reports By Mail: Your Daily Report";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . get_option('admin_email') . "\r\n";

    wp_mail( $to, $subject, $message, $headers);
}


add_action( 'wpemail_daily_report', 'wpemail_daily_task' );

if ( ! wp_next_scheduled( 'wpemail_daily_report' ) ) {
    wp_schedule_event( time(), 'daily', 'wpemail_daily_report' );
}



//Deactivate
register_deactivation_hook( __FILE__, 'wpemail_deactivate' );

function wpemail_deactivate() {
    $timestamp = wp_next_scheduled( 'wpemail_daily_report' );
    wp_unschedule_event( $timestamp, 'wpemail_daily_report' );
}