<?php
/*
Plugin Name: Woocommerce-Tickets
Plugin URI: URL_COMING_SOON.COM
Description: Adds ticket functionality to woocommerce, Software is as is.
Version: 0.9
Author: Red Pixie Media Solutions
Author URI: URL_COMING_SOON.COM
*/

include 'Meta.php';
include 'Shop-Edits.php';

add_shortcode('ticket_calendar', 'woocommerceTicketsCalendarShortcode');
function woocommerceTicketsCalendarShortcode() {
	$pageID = get_the_ID();
	include 'Calendar.php';
}

//Additional Header Tags (HTML)
//add_action( 'admin_head', 'addToPageHeaderFunction' );
?>