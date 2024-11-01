<?php
/*
Template Name: Null Template - Webcake
Template Post Type: post, page, product, property
*/

ini_set('display_startup_errors', 0);

function webcakeLoadContent() {
	global $wpdb;
	
  $sql = $wpdb->prepare("SELECT post_title, post_name, post_content FROM {$wpdb->posts} WHERE ID = %s", get_the_ID());
  $post = $wpdb->get_row($sql);
  $content = $post->post_content;
  $content = str_replace('< !DOCTYPE html>', '<!DOCTYPE html>', $content);
  //echo $content;
  print($content);
  exit;
}

webcakeLoadContent();
?>
