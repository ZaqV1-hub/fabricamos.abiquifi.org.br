<?php
// Temporary script to replace old URLs inside Elementor data without wp-admin/WP-CLI.
// Delete this file after use.

define( 'WP_USE_THEMES', false );
require __DIR__ . '/wp-load.php';

// Simple token gate to prevent public access.
$token = '1234';
if ( ! isset( $_GET['token'] ) || $_GET['token'] !== $token ) {
	http_response_code( 403 );
	echo 'Forbidden';
	exit;
}

if ( ! class_exists( '\\Elementor\\Utils' ) ) {
	http_response_code( 500 );
	echo 'Elementor not loaded';
	exit;
}

// Change these two URLs to match your old/new domains.
$from = 'https://abiquifi.brevia.company';
$to   = 'https://abiquifi.org.br';

try {
	$result = \Elementor\Utils::replace_urls( $from, $to );
	echo $result;
} catch ( Exception $e ) {
	http_response_code( 500 );
	echo $e->getMessage();
}
