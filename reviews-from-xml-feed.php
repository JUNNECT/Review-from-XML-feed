<?php
/**
* Plugin Name: Kiyho - Reviews from XML feed
* Plugin URI: https://junnect.nl/services/websites/
* Description: Getting reviews from a given XML feed and display them on your website.
* Version: 1.0.5
* Requires at least: 5.0
* Requires PHP: 7.0
* License: GPL v2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Author: JUNNECT
* Author URI: https://junnect.nl/over-ons/
**/

// Update the plugin
require 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/JUNNECT/Review-from-XML-feed',
	__FILE__,
	'review-from-xml-feed'
);

//Optional: Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');

// Cron job to run the function every day
register_activation_hook(__FILE__, 'reviews_cron_schedule');
register_deactivation_hook(__FILE__, 'reviews_cron_unschedule');

function reviews_cron_schedule() {
    if (!wp_next_scheduled('reviews_cron_job')) {
        wp_schedule_event(time(), 'daily', 'reviews_cron_job');
    }

    fetch_and_store_reviews();
}

function reviews_cron_unschedule() {
    wp_clear_scheduled_hook('reviews_cron_job');
}

// Fetch and store reviews
add_action('reviews_cron_job', 'fetch_and_store_reviews');
function fetch_and_store_reviews() {
    $url = get_option( 'reviews_from_xml_feed_url' ); // Replace this with the URL of your XML file

    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return;
    }

    $xml = wp_remote_retrieve_body($response);

    update_option('reviews_cron_data', $xml);
}

// Creating admin page for the plugin to add the XML feed URL
function reviews_from_xml_feed_admin_menu() {
    add_options_page( 'Reviews from XML feed', 'Reviews from XML feed', 'manage_options', 'reviews-from-xml-feed', 'reviews_from_xml_feed_admin_page' );
}
add_action( 'admin_menu', 'reviews_from_xml_feed_admin_menu' );

// Creating the admin page for the plugin where a user can add the XML feed URL, the class for the title, the class for the description and select the text color
function reviews_from_xml_feed_admin_page() {
    // Checking if the user has the right to access the admin page
    if ( !current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    // Loading the form and saving it
    if ( isset( $_POST['reviews_from_xml_feed_hidden'] ) && $_POST['reviews_from_xml_feed_hidden'] == 'Y' ) {
        $reviews_from_xml_feed_url = $_POST['reviews_from_xml_feed_url'];
        update_option( 'reviews_from_xml_feed_url', $reviews_from_xml_feed_url );

        $reviews_from_xml_feed_title_class = $_POST['reviews_from_xml_feed_title_class'];
        update_option( 'reviews_from_xml_feed_title_class', $reviews_from_xml_feed_title_class );

        $reviews_from_xml_feed_description_class = $_POST['reviews_from_xml_feed_description_class'];
        update_option( 'reviews_from_xml_feed_description_class', $reviews_from_xml_feed_description_class );

        $reviews_from_xml_feed_text_color = $_POST['reviews_from_xml_feed_text_color'];
        update_option( 'reviews_from_xml_feed_text_color', $reviews_from_xml_feed_text_color );
    }

    // Getting the XML feed URL from the database
    $reviews_from_xml_feed_url = get_option( 'reviews_from_xml_feed_url' );
    $reviews_from_xml_feed_title_class = get_option( 'reviews_from_xml_feed_title_class' );
    $reviews_from_xml_feed_description_class = get_option( 'reviews_from_xml_feed_description_class' );
    $reviews_from_xml_feed_text_color = get_option( 'reviews_from_xml_feed_text_color' );

    // Creating the form that
    echo '<div class="wrap">';
    echo '<h1>Reviews from XML feed</h1>';
    echo '<form name="reviews_from_xml_feed_form" method="post" action="' . str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ) . '">';
    echo '<input type="hidden" name="reviews_from_xml_feed_hidden" value="Y">';
    echo '<p>XML feed URL: <input type="text" name="reviews_from_xml_feed_url" value="' . $reviews_from_xml_feed_url . '" size="50"></p>';
    echo '<p>Title class: <input type="text" name="reviews_from_xml_feed_title_class" value="' . $reviews_from_xml_feed_title_class . '" size="50"></p>';
    echo '<p>Description class: <input type="text" name="reviews_from_xml_feed_description_class" value="' . $reviews_from_xml_feed_description_class . '" size="50"></p>';
    echo '<p>Text color: <input type="text" name="reviews_from_xml_feed_text_color" value="' . $reviews_from_xml_feed_text_color . '" size="50"></p>';
    echo '<p><input type="submit" name="Submit" value="Save" class="button button-primary"></p>';
    echo '</form>';
    echo '</div>';

    ?>
        <form method="post">
            <input type="submit" name="fetch_reviews" value="Fetch Reviews" class="button button-primary">
        </form>
        <p>Toevoegen met de shortcode [reviews-from-xml-feed]</p>
    <?php

    if (isset($_POST['fetch_reviews'])) {
        fetch_and_store_reviews();

        // Display a success message
        add_action('admin_notices', 'reviews_cron_success_notice');
    }

    // Succes or error message after submit - Check if the XML feed URL is saved and if the URL is valid
    if ( isset( $_POST['reviews_from_xml_feed_hidden'] ) && $_POST['reviews_from_xml_feed_hidden'] == 'Y' ) {
        if ( $reviews_from_xml_feed_url ) {
            if ( @simplexml_load_file( $reviews_from_xml_feed_url ) ) {
                echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Invalid XML feed URL.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>XML feed URL is required.</p></div>';
        }
    }

    // Enqueueing the CSS file
    wp_enqueue_style( 'reviews-from-xml-feed', plugin_dir_url( __FILE__ ) . 'css/reviews-from-xml-feed.css' );

}

function reviews_cron_success_notice() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('Reviews fetched successfully.', 'reviews-cron'); ?></p>
    </div>
    <?php
}

// Adding function that loads in the slick script and stylesheet
function reviews_from_xml_feed_scripts() {
    wp_enqueue_script( 'slick', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.js', array( 'jquery' ), '1.9.0', true );
    wp_enqueue_style( 'slick', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.css', array(), '1.9.0', 'all' );
    wp_enqueue_style( 'slick-theme', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick-theme.min.css', array(), '1.9.0', 'all' );

    // Loading in the slick settings
    wp_enqueue_script( 'slick-settings', plugin_dir_url( __FILE__ ) . 'slick-settings.js', array( 'jquery' ), '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'reviews_from_xml_feed_scripts' );

// Creating the shortcode
function reviews_from_xml_feed_shortcode() {
    ob_start();

    $xml_string = get_option('reviews_cron_data');

    // Check if the XML feed is blank, show error message if it is blank and show the reviews if it is not blank
    if ( empty( $xml_string )) {
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p>XML feed is blank.</p>';
        echo '</div>';
    } else {
        $xml = simplexml_load_string( $xml_string );
        echo '<div class="slick review-slider">';

        // adding a max to the foreach loop 
        $max = 10;
        $i = 0;

        foreach($xml->reviews->reviews as $review) {
            if ($i == $max) break;
            $i++;
            // Show the reviews in a slider with slick carousel and adding the slick class to the div
            echo '<div class="review" style="color:'.get_option( 'reviews_from_xml_feed_text_color' ).'">';
                echo '<p class="review__content__title '.get_option( 'reviews_from_xml_feed_title_class' ).'">' . $review->reviewContent->reviewContent[1]->rating . '</p>';
                echo '<p class="review__content__description '.get_option( 'reviews_from_xml_feed_description_class' ).'">' . $review->reviewContent->reviewContent[2]->rating . '</p>';
                echo '<p class="review__header__author__info '.get_option( 'reviews_from_xml_feed_description_class' ).'">' . 'door ' . $review->reviewAuthor . ', ' . $review->city . ' - ' . '<span class="review__header__rating__number">' . $review->rating . '</span>' . '<span class="review__header__rating__out-of"> / 10</span>' . '</p>';
            echo '</div>';
        }
        echo '</div>';
    }

    // Enqueueing the stylesheet
    wp_enqueue_style( 'reviews-from-xml-feed', plugin_dir_url( __FILE__ ) . 'style.css' );


    return ob_get_clean();
}
add_shortcode( 'reviews-from-xml-feed', 'reviews_from_xml_feed_shortcode' );