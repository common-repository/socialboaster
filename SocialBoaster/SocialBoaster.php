<?php
/*
Plugin Name: SocialBoaster
Plugin URI: http://nial.me/2008/08/announcing-socialboaster/
Description: Creates blog posts that coincide with your social activity!
Version: 1.0
Author: Nial Giacomelli
Author URI: http://nial.me/

For more information, visit http://nial.me/2008/08/announcing-socialboaster/
*/

include_once(ABSPATH.WPINC.'/rss.php');

define('MAGPIE_CACHE_ON', FALSE);				// Caching is off. Play nice and only call this script every so often!
define('MAGPIE_INPUT_ENCODING', 'UTF-8');

add_action('sbFetchFeedsEvent', 'sbFetchFeeds');
add_action('admin_menu', 'sbSetupOptionsPage');

register_activation_hook(__FILE__, 'sbActivation');
function sbActivation() {
	
	wp_schedule_event(time(), 'hourly', 'sbFetchFeedsEvent');
	
}

register_deactivation_hook(__FILE__, 'sbDeactivate');
function sbDeactivate() {
	
	delete_option("sbDiggUsername");
	delete_option("sbDiggCategory");
	delete_option("sbDeliciousUsername");
	delete_option("sbDeliciousCategory");
	delete_option("sbTwitterUsername");
	delete_option("sbTwitterCategory");
	delete_option("sbUpdateFrequency");
	
	$timestamp = wp_next_scheduled('sbFetchFeedsEvent');
	wp_unschedule_event($timestamp, "sbFetchFeedsEvent");
	
}//02087634513 ref 9988599

function sbFetchFeeds() {

	$diggUsername = get_option( 'sbDiggUsername' );
	$diggCategory = get_option( 'sbDiggCategory' );
	$deliciousUsername = get_option( 'sbDeliciousUsername' );
	$deliciousCategory = get_option( 'sbDeliciousCategory' );
	$twitterUsername = get_option( 'sbTwitterUsername' );
	$twitterCategory = get_option( 'sbTwitterCategory' );
	$updateFrequency = get_option( 'sbUpdateFrequency' );
	$authorID = get_option( 'sbAuthorID' );
	
	if( strlen( $twitterUsername ) > 0 ) sbFetchFeed( "TWITTER", $twitterUsername, $twitterCategory, $authorID );
	if( strlen( $diggUsername ) > 0 ) sbFetchFeed( "DIGG", $diggUsername, $diggCategory, $authorID );
	if( strlen( $deliciousUsername ) > 0 ) sbFetchFeed( "DELICIOUS", $deliciousUsername, $deliciousCategory, $authorID );
	
}

function sbSetupOptionsPage() {
	
	add_options_page('SocialBoaster Options', 'SocialBoaster', 8, 'socialboasteroptions', 'sbOptionsPage');

}

function sbOptionsPage() {

	if( $_POST['sbUpdateOptions'] == 1 ) {
	    
	    update_option( 'sbDiggUsername', $_POST['sbDiggUsername'] );
	    update_option( 'sbDiggCategory', $_POST['sbDiggCategory'] );
	    update_option( 'sbDeliciousUsername', $_POST['sbDeliciousUsername'] );
	    update_option( 'sbDeliciousCategory', $_POST['sbDeliciousCategory'] );
	    update_option( 'sbTwitterUsername', $_POST['sbTwitterUsername'] );
	    update_option( 'sbTwitterCategory', $_POST['sbTwitterCategory'] );
	    update_option( 'sbUpdateFrequency', $_POST['sbUpdateFrequency'] );
	    update_option( 'sbAuthorID', $_POST['sbAuthorID'] );
	    
	    $timestamp = wp_next_scheduled('sbFetchFeedsEvent');
	    wp_unschedule_event($timestamp, "sbFetchFeedsEvent");
	    wp_schedule_event(time(), $_POST['sbUpdateFrequency'], 'sbFetchFeedsEvent');
	    
?>
<div class="updated"><p><strong><?php _e('Options saved.', 'mt_trans_domain' ); ?></strong></p></div>
<?php

	}
	
	$diggUsername = get_option( 'sbDiggUsername' );
	$diggCategory = get_option( 'sbDiggCategory' );
	$deliciousUsername = get_option( 'sbDeliciousUsername' );
	$deliciousCategory = get_option( 'sbDeliciousCategory' );
	$twitterUsername = get_option( 'sbTwitterUsername' );
	$twitterCategory = get_option( 'sbTwitterCategory' );
	$updateFrequency = get_option( 'sbUpdateFrequency' );
	$authorID = get_option( 'sbAuthorID' );
	
	$authors = sbGetAllAuthors();
	
	echo '<div class="wrap">';
	echo "<h2>" . __( 'SocialBoaster Options', 'mt_trans_domain' ) . "</h2>";

?>

<p><?php _e("If you do not currently use one of the services listed below, simply leave the username field blank and SocialBoaster will ignore it.", 'mt_trans_domain' ); ?></p>

<form name="SocialBoaster" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
<input type="hidden" name="sbUpdateOptions" value="1">

<p><span style="width: 200px; display: block; float: left;"><?php _e("DIGG Username:", 'mt_trans_domain' ); ?></span>
<input type="text" name="sbDiggUsername" value="<?php echo $diggUsername; ?>" size="40">
</p>
<p><span style="width: 200px; display: block; float: left;"><?php _e("Post DUGG stories to:", 'mt_trans_domain' ); ?></span>
<select name="sbDiggCategory">
<?php 
	$categories=  get_categories(''); 
	foreach ($categories as $cat) {
	      $option = '<option value="'.$cat->cat_ID.'"';
	      if( $diggCategory == $cat->cat_ID ) $option .= ' selected ';
	      $option .= '>';
	      $option .= $cat->cat_name;
	      $option .= ' ('.$cat->category_count.')';
	      $option .= '</option>';
	      echo $option;
	}
?>
</select>
</p>

<p><span style="width: 200px; display: block; float: left;"><?php _e("Del.icio.us Username:", 'mt_trans_domain' ); ?></span>
<input type="text" name="sbDeliciousUsername" value="<?php echo $deliciousUsername; ?>" size="40">
</p>
<p><span style="width: 200px; display: block; float: left;"><?php _e("Post Delicious bookmarks to:", 'mt_trans_domain' ); ?></span>
<select name="sbDeliciousCategory">
<?php 
	$categories=  get_categories(''); 
	foreach ($categories as $cat) {
	      $option = '<option value="'.$cat->cat_ID.'"';
	      if( $deliciousCategory == $cat->cat_ID ) $option .= ' selected ';
	      $option .= '>';
	      $option .= $cat->cat_name;
	      $option .= ' ('.$cat->category_count.')';
	      $option .= '</option>';
	      echo $option;
	}
?>
</select>
</p>

<p><span style="width: 200px; display: block; float: left;"><?php _e("Twitter Username:", 'mt_trans_domain' ); ?></span>
<input type="text" name="sbTwitterUsername" value="<?php echo $twitterUsername; ?>" size="40">
</p>
<p><span style="width: 200px; display: block; float: left;"><?php _e("Post Tweets to:", 'mt_trans_domain' ); ?></span>
<select name="sbTwitterCategory">
<?php 
	$categories=  get_categories(''); 
	foreach ($categories as $cat) {
	      $option = '<option value="'.$cat->cat_ID.'"';
	      if( $twitterCategory == $cat->cat_ID ) $option .= ' selected ';
	      $option .= '>';
	      $option .= $cat->cat_name;
	      $option .= ' ('.$cat->category_count.')';
	      $option .= '</option>';
	      echo $option;
	}
?>
</select>
</p>

<hr />

<p><?php _e("How often would you like SocialBoaster to blog your feeds?", 'mt_trans_domain' ); ?> 
<select name="sbUpdateFrequency">
	<option value="hourly" <?php if( $updateFrequency == "hourly" ) echo 'selected'; ?> ><?php _e("Hourly", 'mt_trans_domain' ); ?></option>
	<option value="daily" <?php if( $updateFrequency == "daily" ) echo 'selected'; ?> ><?php _e("Daily", 'mt_trans_domain' ); ?></option>
</select>
</p>
<p><?php _e("Which user SocialBoaster posts be attributed to?", 'mt_trans_domain' ); ?> 
<select name="sbAuthorID">
<?php foreach( $authors as $index => $author ) {
	echo '<option value="'.$index.'"';
	if( $index == $authorID ) echo ' selected ';
	echo '>';
	_e($author, 'mt_trans_domain' ); 
	echo '</option>';
} ?>
</select>
</p>

<p class="submit">
<input type="submit" name="Submit" value="<?php _e('Update Options', 'mt_trans_domain' ) ?>" />
</p>

</form>
</div>

<?php
 
}

function sbGetAllAuthors() {
	
	global $wpdb;
	$order = 'user_nicename';
	$user_ids = $wpdb->get_col("SELECT ID FROM $wpdb->users ORDER BY $order");

	foreach($user_ids as $user_id) {
		$user = get_userdata($user_id);
		if( $user->user_level > 2 ) $all_authors[$user_id] = $user->display_name;
	}
	return $all_authors;

}

function urlExists( $url ) {
	
	global $wpdb;
	
	$q = "
	    SELECT wpostmeta.meta_key 
	    FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta
	    WHERE wpostmeta.meta_key = 'external_url' 
	    AND wpostmeta.meta_value = '".$wpdb->escape($url)."' 
	    AND wposts.post_status = 'publish' 
	    AND wposts.post_type = 'post'";
	
	$post_count = $wpdb->get_results($q, ARRAY_A);
	
	return count($post_count);
}

function sbFetchFeed( $site, $username, $category, $author ) {
	
	global $wpdb;
	
	$feed_url = null;
	
	switch( $site ) {
		
		case "DIGG":
			$feed_url = 'http://digg.com/users/'.Susername.'/history.rss';
			$post_category = $category;
		break;
		case "DELICIOUS":
			$feed_url = 'http://feeds.delicious.com/v2/rss/'.$username;
			$post_category = $category;
		break;
		case "TWITTER":
			$feed_url = 'http://twitter.com/statuses/user_timeline/'.$username.'.rss' ;
			$post_category = $category;
		break;
	}
	
	if( $feed_url ) {
		
		$feed = fetch_rss( $feed_url );
		
		if( $feed ) {
			
			foreach ($feed->items as $item ) {
				
				if( $site == "DELICIOUS" || $site == "DIGG" ) {
					$title = $item['title'];
					$url = $item['link'];
				} else if( $site == "TWITTER" ) {
					$title = $item['description'];
					$url = $item['guid'];
				}
				$pubDate = strtotime($item['pubdate']);
				
				if( !urlExists($url) ) {
					
					$data = array(
						'post_content' => $wpdb->escape( $title ),
						'post_title' => $wpdb->escape( $title ),
						'post_date' => get_gmt_from_date( date('Y-m-d H:i:s', $pubDate) ),
						'post_category' => array( $category ),
						'post_status' => 'publish',
						'post_author' => $author
					);
					$post_id = wp_insert_post($data);
					add_post_meta( $post_id, 'external_url', $url, true);
					
				}
				
			}
			
		}
		
	}
	
}

?>