<?php
/*
Plugin Name: WPSiteSync for Gutenberg Blocks
Plugin URI: https://wpsitesync.com
Description: Adds handlers for third-party Gutenberg Blocks to WPSiteSync so your favorite blocks can be synchronized.
Author: WPSiteSync
Author URI: http://wpsitesync.com
Version: 1.0
Text Domain: wpsitesync-gutenberg-blocks
Domain path: /language

The PHP code portions are distributed under the GPL license. If not otherwise stated, all
images, manuals, cascading stylesheets and included JavaScript are NOT GPL.
*/

// this is only needed for systems that the .htaccess won't work on
defined('ABSPATH') or (header('Forbidden', TRUE, 403) || die('Restricted'));

if (!class_exists('WPSiteSync_Gutenberg_Blocks', FALSE)) {
	class WPSiteSync_Gutenberg_Blocks
	{
		const PLUGIN_VERSION = '1.0';
		const PLUGIN_NAME = 'WPSiteSync for Gutenberg Blocks';
		const PLUGIN_KEY = '8d2f305fbb56ac7d5e4c79924fd4a8ab';

		private static $_instance = NULL;

		private function __construct()
		{
			add_action('spectrom_sync_init', array($this, 'init'));
			add_action('wp_loaded', array($this, 'wp_loaded'));
		}

		public static function get_instance()
		{
			if (NULL === self::$_instance)
				self::$_instance = new self();
			return self::$_instance;
		}

		public function init()
		{
			add_filter('spectrom_sync_active_extensions', array($this, 'filter_active_extensions'), 10, 2);

#			if (!WPSiteSyncContent::get_instance()->get_license()->check_license('sync_gutenbergblocks', self::PLUGIN_KEY, self::PLUGIN_NAME)) {
#SyncDebug::log(__METHOD__ . '() no license');
#				return;
#			}

			add_filter('spectrom_sync_allowed_post_types', array($this, 'allow_custom_post_types'));
			add_action('spectrom_sync_parse_gutenberg_block', array($this, 'parse_gutenberg_block'), 10, 6);
			add_filter('spectrom_sync_process_gutenberg_block', array($this, 'process_gutenberg_block'), 10, 7);
		}

		/**
		 * Adds all custom post types to the list of `spectrom_sync_allowed_post_types`
		 * @param  array $post_types The post types to allow
		 * @return array
		 */
		public function allow_custom_post_types($post_types)
		{
			$post_types[] = 'wp_block';
			return $post_types;
		}

		/**
		 * Called when WP is loaded so we can check if parent plugin is active.
		 */
		public function wp_loaded()
		{
			if (is_admin() && !class_exists('WPSiteSyncContent', FALSE) && current_user_can('activate_plugins')) {
				add_action('admin_notices', array($this, 'notice_requires_wpss'));
			}
		}

		/**
		 * Displays the warning message stating the WPSiteSync is not present.
		 */
		public function notice_requires_wpss()
		{
			$install = admin_url('plugin-install.php?tab=search&s=wpsitesync');
			$activate = admin_url('plugins.php');
			echo '<div class="notice notice-warning">';
			echo	'<p>', sprintf(__('The <em>WPSiteSync for Gutenberg Blocks</em> plugin requires the main <em>WPSiteSync for Content</em> plugin to be installed and activated. Please %1$sclick here</a> to install or %2$sclick here</a> to activate.', 'wpsitesync-gutenberg-blocks'),
						'<a href="' . $install . '">',
						'<a href="' . $activate . '">'), '</p>';
			echo '</div>';
		}

		/**
		 * Handle notifications of Gutenberg Block names during content parsing on the Source site
		 * @param string $block_name A String containing the Block Name, such as 'wp:cover'
		 * @param string $json A string containing the JSON data found in the Gutenberg Block Marker
		 * @param int $source_post_id The post ID being parsed on the Source site
		 * @param array $data The data array being assembled for the Push API call
		 * @param int $pos The position within the $data['post_content'] where the Block Marker is found
		 * @param SyncApiRequest The instance making the API request
		 */
		public function parse_gutenberg_block($block_name, $json, $source_post_id, $data, $pos, $apirequest)
		{
			$ref_ids = array();

			switch ($block_name) {
			/* Look for Atomic Block types:
			 *	<!-- wp:atomic-blocks/ab-testimonial {"testimonialImgID":{post_id}} -->
			 *	<!-- wp:atomic-blocks/ab-profile-box {"profileImgID":{post_id}} -->
			 *	Notice - no ids in json
			 *	Drop Cap - no ids in json
			 *	Button - no ids in json
			 *	Spacer - no ids in json
			 *	Accordion - no ids in json
			 *	<!-- wp:atomic-blocks/ab-cta {"buttonText":"click here","imgID":{post_id}} -->
			 *	Sharing - no ids in json
			 *	Post Grid - no ids in json
			 *	<!-- wp:atomic-blocks/ab-container {"profileImgID":{post_id}} --> (property currently not supported)
			 */

			case 'wp:atomic-blocks/ab-testimonial':
				$obj = json_decode($json);
				if (!empty($json) && NULL !== $obj && isset($obj->testimonialImgID)) {
					// if there's an image ID referenced, handle that attachment
					$ref_ids[] = abs($obj->testimonialImgID);
				}
				break;

			case 'wp:atomic-blocks/ab-profile-box':
				$obj = json_decode($json);
				if (!empty($json) && NULL !== $obj && isset($obj->profileImgID)) {
					$ref_ids[] = abs($obj->profileImgID);
				}
				break;

			case 'wp:atomic-blocks/ab-cta':
				$obj = json_decode($json);
				if (!empty($json) && NULL !== $obj && isset($obj->imgID)) {
					$ref_ids[] = abs($obj->imgID);
				}
				break;

			case 'wp:atomic-blocks/ab-container':
				$obj = json_decode($json);
				if (!empty($json) && NULL !== $obj && isset($obj->profileImgID)) {
					$ref_ids[] = abs($obj->profileImgID);
				}
				break;

			/* Look for Premium Block types:
			 *	<!-- wp:premium/banner {"imageID":{post_id},"hoverEffect":"gray","minHeight":70,"opacity":19,"id":"{id}"} -->
			 *	<!-- wp:premium/video-box {"videoBoxId":"premium-video-box-{id}","controls":false,"overlay":true,"overlayImgID":{post_id},"overlayImgURL":"{image_url}"} -->
			 *	Accordion - no ids in json
			 *	Button - no ids in json
			 *	<!-- wp:premium/countup {"align":"left","icon":"img","imageID":{post_id},"imageURL":"{url}","iconSize":93,"selfAlign":"flex-start"} -->
			 *	<!-- wp:premium/dheading-block {"imageID":{post_id},"imageURL":"{url}"} -->
			 *	<!-- wp:premium/icon {"imageID":{post_id},"imageURL":"{url}","backgroundSize":"contain"} -->
			 *	<!-- wp:premium/icon-box {"id":"{id}","iconImage":"image","iconImgId":{post_id},"iconImgUrl":"{url}","iconSize":62,"imageID":{post_id},"imageURL":"http://guten.loc/wp-content/uploads/2018/12/space_8372725172284686336.jpg"} -->
			 *	<!-- wp:premium/maps {"mapID":"{id}","centerLat":"38.889218","centerLng":"-77.050176","markerIconUrl":"{url}","markerIconId":{post_id},"markerCustom":true,"maxWidth":32,"boxPadding":32} -->
			 *	Pricing Table - no ids in json
			 *	Section: <!-- wp:premium/container {"imageID":{post_id},"imageURL":"{url}"} -->
			 *	<!-- wp:premium/testimonial {"authorImgId":{pist_id},"authorImgUrl":"{url}","authorColor":"#eeeeee","authorComColor":"#eeeeee","quotColor":"#eeeeee","bodyColor":"#eeeeee","dashColor":"#abb8c3","imageID":{post_id},"imageURL":"{url}"} -->
			 */

			case 'wp:premium/banner':				// references imageID
			case 'wp:premium/container':			// references imageID
			case 'wp:premium/countup':				// references imageID and backgroundImageID
			case 'wp:premium/dheading-block':		// references imageID
			case 'wp:premium/icon':					// references imageID
				$obj = json_decode($json);
				if (!empty($json) && NULL !== $obj) {
					if (isset($obj->imageID)) {
						$ref_ids[] = abs($obj->imageID);
					}
					if (isset($obj->backgroundImageID)) {
						$ref_ids[] = abs($obj->backgroundImageID);
					}
				}
				break;

			case 'wp:premium/icon-box':
				$obj = json_decode($json);
				if (!empty($json) && NULL !== $obj) {
					if (isset($obj->iconImgId)) {
						$ref_ids[] = abs($obj->iconImgId);
					}
					if (isset($obj->imageID)) {
						$ref_ids[] = abs($obj->imageID);
					}
				}
				break;

			case 'wp:premium/maps':
				$obj = json_decode($json);
				if (!empty($json) && NULL !== $obj && isset($obj->markerIconId)) {
					$ref_ids[] = abs($obj->markerIconId);
				}
				break;

			case 'wp:premium/testimonial':
				$obj = json_decode($json);
				if (!empty($json) && NULL !== $obj) {
					if (isset($obj->authorImgId)) {
						$ref_ids[] = abs($obj->authorImgId);
					}
					if (isset($obj->imageID)) {
						$ref_ids[] = abs($obj->imageID);
					}
				}
				break;

			case 'wp:premium/video-box':
				$obj = json_decode($json);
				if (!empty($json) && NULL !== $obj && isset($obj->overlayImgID)) {
					$ref_ids[] = abs($obj->overlayImgID);
				}
				break;
			}

			// any images referenced are now in the $ref_ids array. check this and handle image references

			if (NULL !== $ref_ids) {
				if (!is_array($ref_ids))
					$ref_ids = array($ref_ids);			// convert to array

				// get the post thumbnail id- this is passed to send_media()
				$thumb = get_post_thumbnail_id($source_post_id);
				foreach ($ref_ids as $ref_id) {
					if (0 !== $ref_id) {
						if (FALSE === $apirequest->gutenberg_attachment_block($ref_id, $source_post_id, $thumb, $block_name)) {
							// TODO: error recovery
						}
					}
				}
			}
		}

		/**
		 * Processes the Gutenberg content on the Target site, adjusting Block Content as necessary
		 * @param string $content The content for the entire post
		 * @param string $block_name A string containing the Block Name, such as 'wp:cover'
		 * @param string $json A string containing the JSON data found in the Gutenberg Block Marker
		 * @param int $target_post_id The post ID being processed on the Target site
		 * @param int $start The starting offset within $content for the current Block Marker JSON
		 * @param int $end The ending offset within the $content for the current Block Marker JSON
		 * @param int $pos The starting offset within the $content where the Block Marker `<!-- wp:{block_name}` is found
		 * @return string The $content modified as necessary so that it works on the Target site
		 */
		public function process_gutenberg_block($content, $block_name, $json, $target_post_id, $start, $end, $pos)
		{
SyncDebug::log(__METHOD__.'():' . __LINE__);
			/*
			 * Look for block types:
			 *	<!-- wp:atomic-blocks/ab-testimonial {"testimonialImgID":{post_id}} -->
			 *	<!-- wp:atomic-blocks/ab-profile-box {"profileImgID":{post_id}} -->
			 *	<!-- wp:atomic-blocks/ab-cta {"buttonText":"click here","imgID":{post_id}} -->
			 *	<!-- wp:atomic-blocks/ab-container {"profileImgID":{post_id}} --> (property currently not supported)
			 */
			switch ($block_name) {
			case 'wp:atomic-blocks/ab-testimonial':
				$obj = json_decode($json);
				if (!empty($json) && NULL !== $obj && isset($obj->testimonialImgID)) {
					$source_ref_id = abs($obj->testimonialImgID);
					if (0 !== $source_ref_id) {
						$apicontroller = SyncApiController::get_instance();
						$sync_mode = new SyncModel();
						$sync_data = $sync_model->get_sync_data($source_ref_id, $apicontroller->source_site_key);
						if (NULL !== $sync_data) {
							$target_ref_id = abs($sync_data->target_content_id);
							$obj->testimonialImgID = $target_ref_id;
							$new_obj_data = json_encode($obj);
							$content = substr($content, 0, $start) . $new_obj_data . substr($content, $end + 1);
							// no classes or other id references within HTML
						}
					}
				}
				break;

			case 'wp:atomic-blocks/ab-profile-box':
			case 'wp:atomic-blocks/ab-container':
				$obj = json_decode($json);
				if (!empty($json) && NULL !== $obj && isset($obj->profileImgID)) {
					$source_ref_id = abs($obj->profileImgID);
					if (0 !== $source_ref_id) {
						$apicontroller = SyncApiController::get_instance();
						$sync_mode = new SyncModel();
						$sync_data = $sync_model->get_sync_data($source_ref_id, $apicontroller->source_site_key);
						if (NULL !== $sync_data) {
							$target_ref_id = abs($sync_data->target_content_id);
							$obj->profileImgID = $target_ref_id;
							$new_obj_data = json_encode($obj);
							$content = substr($content, 0, $start) . $new_obj_data . substr($content, $end + 1);
							// no classes or other id references within the HTML
						}
					}
				}
				break;

			case 'wp:atomic-blocks/ab-cta':
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' json=' . $json);
				$obj = json_decode($json);
				if (!empty($json) && NULL !== $obj && isset($obj->imgID)) {
					$source_ref_id = abs($obj->imgID);
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' ref id=' . $source_ref_id);
					if (0 !== $source_ref_id) {
						$apicontroller = SyncApiController::get_instance();
						$sync_model = new SyncModel();
						$sync_data = $sync_model->get_sync_data($source_ref_id, $apicontroller->source_site_key);
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' data=' . var_export($sync_data, TRUE));
						if (NULL !== $sync_data) {
							$target_ref_id = abs($sync_data->target_content_id);
							$obj->imgID = $target_ref_id;
							$new_obj_data = json_encode($obj);
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' new json=' . $new_obj_data);
							$content = substr($content, 0, $start) . $new_obj_data . substr($content, $end + 1);
							// no classes or other id references within the HTML
						}
					}
				}
				break;
			}
			return $content;
		}

		/**
		 * Add the Auto Sync add-on to the list of known WPSiteSync extensions
		 * @param array $extensions The list to add to
		 * @param boolean $set
		 * @return array The list of extensions, with the WPSiteSync Auto Sync add-on included
		 */
		public function filter_active_extensions($extensions, $set = FALSE)
		{
//SyncDebug::log(__METHOD__.'()');
			if ($set || WPSiteSyncContent::get_instance()->get_license()->check_license('sync_gutenbergblocks', self::PLUGIN_KEY, self::PLUGIN_NAME))
				$extensions['sync_gutenbergblocks'] = array(
					'name' => self::PLUGIN_NAME,
					'version' => self::PLUGIN_VERSION,
					'file' => __FILE__,
				);
			return $extensions;
		}
	}
} // class exists

WPSiteSync_Gutenberg_Blocks::get_instance();

// EOF