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

function wpemail_daily_task ($debug){

    logger("Daily Task Called");

    $today_date = date("Y-m-d");

    $today = $today_date . " " . date("H:i:s", strtotime("00:00:00"));

    $nine_am = strtotime($today_date . " " . date("H:i:s", strtotime("09:00:00")));
    $nine_am = get_gmt_from_date( date( 'Y-m-d H:i:s', $nine_am ), 'U' );

    
    $report = [];

    $query_by_day = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'date_query' => array(
            'after' => $today
        )
    );


    $admin_email = get_option('admin_email');

    $options = get_option('wpemail_options');

    $to = $options['wpemail_field_recipients'];


    $query = new WP_Query($query_by_day);

    array_push($report, [
        'report_period' => $today_date,
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

    if (!$debug) {
        if (!$to) {
            if ($admin_email) {
                schedule_mail($admin_email, $msg, $nine_am);
            }
        } else {
            schedule_mail($to, $msg, $nine_am);
        }
    } else {
        echo $msg;
    }
    
}


function schedule_mail($to, $msg, $time){

    logger("Schedule Mail Called");

    if (!wp_next_scheduled('wpemail_send_report')) {

        wp_schedule_single_event(strtotime($time), 'wpemail_send_report', [$to, $msg]);

        logger("Cron job registered: ". strtotime($time));
        //Debug
//        wp_schedule_single_event(time(), 'wpemail_send_report', [$to, $msg]);
    }

}


function wpemail_send_task($to, $msg){
    $subject = "WordPress Reports By Mail: Your Daily Report";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

    if(wp_mail( $to, $subject, $msg, $headers)){
        logger("Report sent to: " . $to);
    }else {
        logger("Mail Failed");
    };
}


function logger($statement){
    $fn = plugin_dir_path( __FILE__ ) . '/reports-by-mail.log';
    $fp = fopen($fn, 'a');
    fputs($fp, date("Y-m-d H:i:s" ,time()) . ' - ' . $statement . "\n");
    fclose($fp);
}

