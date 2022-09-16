<?php
/*
* Plugin Name: Tinychat API
* Plugin URI: https://wordpress.org/plugins/tc-room-spy/
* Author: Ruddernation Designs
* Author URI: https://profiles.wordpress.org/ruddernationdesigns
* Description: You can use this to search Tinychat profiles/rooms, This contains no CSS! So you may need to add your own custom CSS.
* Requires at least: WordPress 2.0
* Tested up to: 5.8.2
* Version: 1.3.7
* License: GNUv3 
* License URI: https://opensource.org/licenses/MIT
* Date: 16th September 2022
*/
define('COMPARE_VERSION', '1.3.6');
defined( 'ABSPATH' ) or die( 'Hola' );
register_activation_hook(__FILE__, 'rndtc_room_spy_install');
function rndtc_room_spy_install() {
	global $wpdb, $wp_version;
	$post_date = date("Y-m-d H:i:s");
	$post_date_gmt = gmdate("Y-m-d H:i:s");
	$sql = "SELECT * FROM ".$wpdb->posts." WHERE post_content LIKE '%[rndtc_room_spy_page]%' AND `post_type` NOT IN('revision') LIMIT 1";
	$page = $wpdb->get_row($sql, ARRAY_A);
	if($page == NULL) {
		$sql ="INSERT INTO ".$wpdb->posts."(
			post_author, post_date, post_date_gmt, post_content, post_content_filtered, post_title, post_excerpt,  post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_parent, menu_order, post_type)
			VALUES
			('1', '$post_date', '$post_date_gmt', '[rndtc_room_spy_page]', '', 'tcroomspy', '', 'publish', 'closed', 'closed', '', 'tinychat-api', '', '', '$post_date', '$post_date_gmt', '0', '0', 'page')";
		$wpdb->query($sql);
		$post_id = $wpdb->insert_id;
		$wpdb->query("UPDATE $wpdb->posts SET guid = '" . get_permalink($post_id) . "' WHERE ID = '$post_id'");
	} else {
		$post_id = $page['ID'];
	}
	update_option('rndtc_room_spy_url', get_permalink($post_id));
}
add_filter('the_content', 'wp_show_rndtc_room_spy_page', 52);
function wp_show_rndtc_room_spy_page($content = '') {
	if(preg_match("/\[rndtc_room_spy_page]/",$content)) {
		wp_show_rndtc_room_spy();
		return "";
	}
	return $content;
}
function wp_show_rndtc_room_spy() {
	if(!get_option('rndtc_room_spy_enabled', 0)) 
	{
		if(isset($_POST['chosen'])) 
		{
			$room = $_POST['room'];
			
			if(preg_match('/^[a-z0-9]/', $room=strtolower($room))){
				$room=preg_replace('/[^a-zA-Z0-9]/', '',$room);
				if (strlen($room) < 3)
					$room = substr($room, 0, 0);
				if (strlen($room) > 36)
					$room = substr(0, 36);
				
				function file_get_contents_new($data) 
				{
					$curl = curl_init();
					curl_setopt($curl, CURLOPT_URL, $data);
    				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($curl, CURLOPT_REFERER, "https://tinychat.com");
					$new = curl_exec($curl);
    				curl_close($curl);
					return $new;
				}
				$data=html_entity_decode(file_get_contents_new('https://tinychat.com/api/v1.0/user/profile?username='.$room.''));
				$new = json_decode($data, true);
				}
		}
?>
This allows you to view the Tinychat API for your room, it does not do a live view anymore.
<form method="post">
<input type="text" tabindex="1" name="room" placeholder="Tinychat room/user" id="roomname" list="roomdata" autofocus required/> 
<input type="hidden" name="chosen" value="true">
<button type="submit" class="button">View</button></form><br>
<?php
		
		if (preg_match('/^[a-z0-9]/', $room=strtolower($room)))
		{
			$room=preg_replace('/[^a-zA-Z0-9]/','',$room);
			{
				if(isset($_POST['chosen']))
				{
					if
						(!empty($new["result"] == "nouser")) { 
						echo '<h2><strong>That profile does not exist!</strong></h2>';
					}
					elseif
						($new["result"] == "success")
					{
						echo '<br><img src="'.$new["avatarUrl"].'" alt="'.$new["username"].'"></img><br>';
						echo '<p><br><strong>Username: ' .$new["username"].'</strong>';
						$url = '@(http)?(s)?(://)?(([a-zA-Z])([-\w]+\.)+([^\s\.]+[^\s]*)+[^,.\s])@';
						$new["biography"] = preg_replace($url, '<strong><a href="http$2://$4" target="_blank" title="$0">$0</a></strong>',$new["biography"]);
						echo '<br><strong>Biography: ' .$new["biography"].'</strong>';
						
						if ($new["gender"] == "M")
						{
							echo str_replace("M", "", ""), '<br>' ,'<strong>Gender: Male</strong>';
						}
						elseif ($new["gender"] == "F")
						{
							echo str_replace("F", "", ""), '<br>' ,'<strong>Gender: Female</strong>';
						}
						echo '<br><strong>Age: ' .$new["age"].'</strong></strong>';
						echo '<br><strong>Location: ' .$new["location"].'</strong>';
						if (!empty($new["role"] == "")) 
						{
							echo '<br><strong>Membership: Platinum';
						}
						else
						echo '<br><strong>Membership: ' .$new["role"].'</strong>';
						echo '<br><strong>Points: ' .$new["giftpoints"]." - ".'To Next Level: '.$new["percentToNextAchieve"].'%</p></strong>';
						echo '<br><strong><a href="https://tinychat.com/'.$room.'">Join Chat</a></strong>';
						
					}
				}
			}
		}
	}
}
?>
