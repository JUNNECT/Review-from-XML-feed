<?php
/**
* Plugin Name: Reviews from XML feed
* Plugin URI: https://junnect.nl/services/websites/
* Description: Getting reviews from a given XML feed and display them on your website.
* Version: 0.0.1
* Author: JUNNECT
* Author URI: https://junnect.nl/over-ons/
**/

// Registering the shortcode
function reviews_from_xml_feed_shortcode() {
    ob_start();

    // Getting the XML feed
    $xml = simplexml_load_file('https://www.kiyoh.com/v1/review/feed.xml?hash=bedbc4iyxdb1wzu');

    // Looping through the XML feed
    foreach($xml->review as $review) {
        // Getting the review data
        $review_title = $review->title;
        $review_description = $review->description;
        $review_rating = $review->rating;
        $review_date = $review->date;
        $review_author = $review->author;
        $review_author_location = $review->author_location;

        // Displaying the review data
        echo '<div class="review">';
            echo '<h3 class="review-title">' . $review_title . '</h3>';
            echo '<p class="review-description">' . $review_description . '</p>';
            echo '<p class="review-rating">' . $review_rating . '</p>';
            echo '<p class="review-date">' . $review_date . '</p>';
            echo '<p class="review-author">' . $review_author . '</p>';
            echo '<p class="review-author-location">' . $review_author_location . '</p>';
        echo '</div>';
    }

    return ob_get_clean();
}
add_shortcode( 'reviews-from-xml-feed', 'reviews_from_xml_feed_shortcode' );

// Enqueueing the stylesheet
function reviews_from_xml_feed_stylesheet() {
    wp_enqueue_style( 'reviews-from-xml-feed', plugin_dir_url( __FILE__ ) . 'style.css' );
}