<?php
function wpemail_settings_init() {

    register_setting( 'wpemail', 'wpemail_options' );


    add_settings_section(
        'wpemail_section_recipients',
        __( 'Settings', 'wpemail' ),
        'wpemail_section_recipients_cb',
        'wpemail'
    );



    add_settings_field(
        'wpemail_field_recipients',
        __( 'Recipients', 'wpemail' ),
        'wpemail_field_recipients_cb',
        'wpemail',
        'wpemail_section_recipients',
        [
            'label_for' => 'wpemail_field_recipients',
            'class' => 'wpemail_row',
            'wpemail_custom_data' => 'custom',
        ]
    );

}

add_action( 'admin_init', 'wpemail_settings_init' );



function wpemail_section_recipients_cb( $args ) {

}

function wpemail_field_recipients_cb( $args ) {

    $options = get_option( 'wpemail_options' );

    ?>
    <input id="<?php echo esc_attr( $args['label_for'] ); ?>" data-custom="<?php echo esc_attr( $args['wpemail_custom_data'] ); ?>"
           name="wpemail_options[<?php echo esc_attr( $args['label_for'] ); ?>]" type="text"  size="100" placeholder="Email Addresses"
           value="<?php echo $options[ $args['label_for'] ]; ?>"
    />

    <p class="description">
        <?php esc_html_e('Enter email addresses separated by a comma. Leave blank to use default admin email.' , 'wpemail') ?>
    </p>
    <?php
}


function wpemail_options_page()
{
    add_menu_page(
        'Reports by Mail',
        'Reports by Mail',
        'manage_options',
        'wpemail',
        'wpemail_options_page_html',
        plugin_dir_url(__FILE__) . 'icon.png',
        360
    );
}

add_action('admin_menu', 'wpemail_options_page');

function print_debug(){

    $today = date("Y-m-d");

    $thedate = DateTime::createFromFormat('Y-m-d', $today);
    $thedate->modify('-1 day');
    $yesterday = $thedate->format('Y-m-d');

    $thedate = DateTime::createFromFormat('Y-m-d', $today);
    $thedate->modify('-1 week');
    $thedate->modify('-1 day');
    $thisweek = $thedate->format('Y-m-d');

    $thedate = DateTime::createFromFormat('Y-m-d', $today);
    $thedate->modify('-1 month');
    $thedate->modify('-1 day');
    $thismonth = $thedate->format('Y-m-d');


    $query_by_day = array(
        'post_type' => 'post',
        'post_status'   => 'publish',
        'date_query' => array(
            'after' => $yesterday
        )
    );

    $query_by_week = array(
        'post_type' => 'post',
        'post_status'   => 'publish',
        'date_query' => array(
            'after' => $thisweek
        )
    );

    $query_by_month = array(
        'post_type' => 'post',
        'post_status'   => 'publish',
        'date_query' => array(
            'after' => $thismonth
        )
    );

    $query = new WP_Query( $query_by_day );
    ?>

    <section id="wpemail_reports">
        <h1>Published Today (<?php echo $query->post_count; ?>)</h1>
    <?php while ( $query->have_posts() ) : $query->the_post();?>
        <ul class="wpemail_reports">
            <li><?php the_title()?></li>
            <li> @ <?php the_time(); ?></li>
            <li> by <?php the_author();?></li>
        </ul>
    <?php endwhile;?>
        <?php wp_reset_query();?>


        <?php $query = new WP_Query( $query_by_week ); ?>
    <h1>Published This Week (<?php echo $query->post_count; ?>)</h1>
    <?php while ( $query->have_posts() ) : $query->the_post();?>
        <ul class="wpemail_reports">
            <li><?php the_title()?></li>
            <li> @ <?php the_time();?> &mdash; <?php echo get_the_date();?></li>
            <li> by <?php the_author();?></li>
        </ul>
    <?php endwhile;?>
        <?php wp_reset_query();?>


        <?php $query = new WP_Query( $query_by_month ); ?>
    <h1>Published This Month (<?php echo $query->post_count; ?>)</h1>
    <?php while ( $query->have_posts() ) : $query->the_post();?>
        <ul class="wpemail_reports">
            <li><?php the_title()?></li>
            <li> @ <?php the_time();?> &mdash; <?php echo get_the_date();?></li>
            <li> by <?php the_author();?></li>
        </ul>
    <?php endwhile;?>
        <?php wp_reset_query();?>

    </section>
    <?php

    echo '<pre>'; print_r( _get_cron_array() ); echo '</pre>';
}


function wpemail_options_page_html() {
    // check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'wpemail_messages', 'wpemail_message', __( 'Settings Saved', 'wpemail' ), 'updated' );
    }

    // show error/update messages
    settings_errors( 'wpemail_messages' );
    ?>
    <ul id="plugin-meta">
        <li><img src="<?php echo plugin_dir_url(__FILE__) . 'logo.png'?>"/></li>
        <li><h1><?php echo esc_html( get_admin_page_title() ); ?></h1><br/><h2>&mdash; Daily reports direct to your inbox.</h2></li>
    </ul>
    
    <div class="wrap">

        <form action="options.php" method="post">
            <?php

            settings_fields( 'wpemail' );

            do_settings_sections( 'wpemail' );

            submit_button( 'Save Settings' );
            ?>

            <hr/>

        </form>

<!--        --><?php //print_debug(); ?>
<!--        --><?php //wpemail_daily_task(true); ?>

    </div>
    <?php
}

?>