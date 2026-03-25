<?php
require 'wp-load.php';
global $wpdb;
$needle = '%fab-grid%';
$results = [];
$results['posts'] = $wpdb->get_results($wpdb->prepare("SELECT ID, post_type, post_status, post_title FROM {$wpdb->posts} WHERE post_content LIKE %s LIMIT 30", $needle), ARRAY_A);
$results['postmeta'] = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_key, LEFT(meta_value, 220) AS snippet FROM {$wpdb->postmeta} WHERE meta_value LIKE %s LIMIT 30", $needle), ARRAY_A);
$results['options'] = $wpdb->get_results($wpdb->prepare("SELECT option_name, LEFT(option_value, 220) AS snippet FROM {$wpdb->options} WHERE option_value LIKE %s LIMIT 30", $needle), ARRAY_A);
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
