<?php
/*
Plugin Name: WPSiteSync for Gutenberg Blocks
Plugin URI: https://wpsitesync.com
Description: Adds handlers for third-party Gutenberg Blocks to WPSiteSync so all your favorite blocks can be synchronized.
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

		// array of block names and the properties they reference
		// property is in the form: '[name.name:type'
		//		a '[' at the begining indicates that the property is an array of items
		//		':type' is the type of propert:
		//			nothing indicates a reference to an image id
		//			:u indicates a reference to a user id
		//			:p indicates a reference to a post id
		//			:l indicates a reference to a link. the link can include a post id: /wp-admin/post.php?post={post_id}\u0026action=edit
		private $_props = array(
			// block name =>							property list

			// properties for: Atomic Blocks v1.4.23
		//	wp:atomic-blocks/ab-accordion - no ids in json
		//	wp:atomic-blocks/ab-button - no ids in json
			'wp:atomic-blocks/ab-container' =>			'profileImgID',
			'wp:atomic-blocks/ab-cta' =>				'imgID',
		//	wp:atomic-blocks/ab-drop-cap - no ids in json
		//	wp:atomic-blocks/ab-notice - no ids in json
		//	wp:atomic-blocks/ab-post-grid - no ids in json
			'wp:atomic-blocks/ab-profile-box' =>		'profileImgID',
		//	wp:atomic-blocks/ab-sharing - no ids in json
		//	wp:atomic-blocks/ab-spacer - no ids in json
			'wp:atomic-blocks/ab-testimonial' =>		'testimonialImgID',

			// properties for: Premium Blocks for Gutenberg v1.6.2
		//	wp:premium/accordion - no ids in json
			'wp:premium/banner' =>						'imageID',	#11
		//	wp:premium/button - no ids in json
			'wp:premium/container' =>					'imageID', #17 "Section"
			'wp:premium/countup' =>						'imageID|backgroundImageID',	#10
			'wp:premium/dheading-block' =>				'imageID',	#13
			'wp:premium/icon' =>						'imageID',	#14
			'wp:premium/icon-box' =>					'imageID|iconImgId',	#15
			'wp:premium/maps' =>						'markerIconId',	#16
		//	wp:premium/pricing-table - no ids in json
			'wp:premium/testimonial' =>					'imageID|authorImgId',
			'wp:premium/video-box' =>					'overlayImgID',	#12

			// properties for: Ultimate Addons for Gutenberg v1.13.1
		//	wp:uagb/advanced-heading - no ids in json
			'wp:uagb/blockquote' =>						'authorImage.id|author.author:u', #18
		//	wp:uagb/buttons Multi Buttons - no ids in json
		//	wp:uagb/call-to-action - no ids in json
			'wp:uagb/cf7-styler' =>						'', #19
		//	wp:uagb/content-timeline - no ids in json
		//	wp:uagb/marketing-button - no ids in json
			'wp:uagb/columns' =>						'[image.id|[image.author:u|[image.editLink:l', #20
			'wp:uagb/icon-list' =>						'[icons.image.id|[icons.image.author:u|[icons.image.editLink:l', #21
			'wp:uagb/gf-styler' =>						'', #22
		//	wp:uagb/google-map - no ids in json
			'wp:uagb/info-box' =>						'iconImage.id|iconImage.uploadedTo:p|iconImage.author:u|iconImage.editLink:l|iconImage.uploadedToLink:l', #23
		//	wp:uagb/post-carousel - no ids in json
		//	wp:uagb/post-grid - no ids in json
		//	wp:uagb/post-masonry - no ids in json
		//	wp:uagb/post-timeline - no ids in json
			'wp:uagb/restaurant-menu' =>				'[rest_menu_item_arr.image.id|[rest_menu_item_arr.image.author:u|[rest_menu_item_arr.image.uploadedTo:p|[rest_menu_item_arr.image.editLink:l|[rest_menu_item_arr.image.uploadedToLink:l', #24
			'wp:uagb/section' =>						'backgroundImage.id|backgroundImage.author:u|backgroundImage.editLink:l|backgroundImage.uploadedToLink:l', #25
			'wp:uagb/social-share' =>					'[socials.image.id|[socials.image.author:u|[socials.image.editLink:l|[socials.image.uploadedToLink:l', #26
		//	wp:uagb/table-of-contents - no ids in json
			'wp:uagb/team' =>							'image.id|image.author:u|image.editLink:l|image.uploadedToLink:l', #27
			'wp:uagb/testimonial' =>					'[test_block.image.id|[test_block.image.author:u|[test_block.image.uploadedTo:p|[test_block.image.editLink:l|[test_block.image.uploadedToLink:l', #28

			// properties for: Kadence Blocks v1.5.3
		//	wp:kadence/spacer - no ids in json
		//	wp:kadence/advancedbtn - no ids in json
			'wp:kadence/rowlayout' =>					'bgImgID|overlayBgImgID', #29
		//	wp:kadence/column - no ids in json
		//	wp:kadence/icon - no ids in json							
		//	wp:kadence/advancedheading - no ids in json
		//	wp:kadence/tabs - no ids in json
		//	wp:kadence/tab - no ids in json
		//	wp:kadence/infobox - no ids in json
		//	wp:kadence/accordion - no ids in json
		//	wp:kadence/pane - no ids in json
		//	wp:kadence/iconlist - no ids in json
			'wp:kadence/testimonials' =>				'[testimonials.id',	#30

			// properties for: Advanced Gutenberg v1.10.10
		//	wp:advgb/accordion
		//	wp:advgb/button "Advanced Button"
			'wp:advgb/image' =>							'imageID', #31 "Advanced Image"
		//	wp:advgb/list "Advanced List"
		//	wp:advgb/table "Advanced Table"
			'wp:advgb/video' =>							'videoID|posterID', #32 "Advanced Video"
		//	wp:advgb/contact-form ??
		//	wp:advgb/container
		//	wp:advgb/count-up
			'wp:advgb/images-slider' =>					'[images.id', #33
			'wp:advgb/map' =>							'markerIconID', #35
		//	wp:advgb/newsletter
		//	wp:advgb/recent-posts
			'wp:advgb/social-links' =>					'[items.iconID', #34
		//	wp:advgb/summary
		//	wp:advgb/tabs
			'wp:advgb/testimonial' =>					'avatarID|avatarID2|avatarID3|avatarID4|[items.avatarID', #35
		//	wp:advgb/woo-products
		);

		const PROPTYPE_IMAGE = 1;					// :i
		const PROPTYPE_POST = 2;					// :p
		const PROPTYPE_USER = 3;					// :u
		const PROPTYPE_LINK = 4;					// :l
		const PROPTYPE_GF = 5;						// :gf gravity form
		const PROPTYPE_CF = 6;						// :cf contact form 7

		private $_prop_type = 0;					// property type- one of the PROPTYPE_ constants
		private $_prop_name = NULL;					// name of the property to update
		private $_prop_array = FALSE;				// set to TRUE if property refers to an array
		private $_prop_list = NULL;					// list of property elements

		private $_block_names = NULL;				// array of block names (keys) from $_props
		private $_thumb_id = NULL;					// post ID of thumbnail for current post
		private $_api_controller = NULL;			// copy of API Controller instance used on Target
		private $_sync_model = NULL;				// instance of SyncModel used for image lookup

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
			add_filter('spectrom_sync_notice_code_to_text', array($this, 'filter_notice_codes'), 10, 2);

			$this->_block_names = array_keys($this->_props);
		}

		/**
		 * Loads a specified class file name and optionally creates an instance of it
		 * @param string $name Name of class to load
		 * @param boolean $create TRUE to create an instance of the loaded class
		 * @return boolean|object Created instance if $create is TRUE; otherwise FALSE
		 */
		public function load_class($name, $create = FALSE)
		{
			$file = __DIR__ . '/classes/' . strtolower($name) . '.php';
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' loading class "' . $file . '"');
			if (file_exists($file))
				require_once($file);
			if ($create) {
				$instance = 'Sync' . $name;
				return new $instance();
			}
			return FALSE;
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
		 * Parses the property, setting the type, name and list from the name
		 * @param string $prop The property name to be parsed
		 */
		private function _parse_property($prop)
		{
			$this->_prop_type = self::PROPTYPE_IMAGE;
			$this->_prop_array = FALSE;

			// check for the suffix and set the _prop_type from that
			if (FALSE !== ($pos = strpos($prop, ':'))) {
				switch (substr($prop, $pos)) {
				case ':i':			$this->_prop_type = self::PROPTYPE_IMAGE;		break;
				case ':l':			$this->_prop_type = self::PROPTYPE_LINK;		break;
				case ':p':			$this->_prop_type = self::PROPTYPE_POST;		break;
				case ':u':			$this->_prop_type = self::PROPTYPE_USER;		break;
				case ':cf':			$this->_prop_type = self::PROPTYPE_CF;			break;
				case ':gf':			$this->_prop_type = self::PROPTYPE_GF;			break;
				}
				$prop = substr($prop, 0, $pos);			// remove the suffix
			}

			// check for array references
			if ('[' === substr($prop, 0, 1)) {
				$this->_prop_array = TRUE;
				$prop = substr($prop, 1);
			}

			if (FALSE !== strpos($prop, '.')) {
				// this section handles Ultimate Addons for Gutenberg's nested properties
				// right now, it only handles one level of property nesting
				$this->_prop_name = NULL;
				$this->_prop_list = explode('.', $prop);
if (count($this->_prop_list) > 3)
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' ERROR: more than three properties: ' . implode('->', $this->_prop_list));
			} else {
				$this->_prop_name = $prop;
				$this->_prop_list = NULL;
			}
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' type=' . $this->_prop_type . ' arr=' . ($this->_prop_array ? 'T' : 'F') .
				' name=' . (NULL === $this->_prop_name ? '(NULL)' : $this->_prop_name) .
				' list=' . (NULL === $this->_prop_list ? '(NULL)' : implode('->', $this->_prop_list)));
		}

		/**
		 * Obtains a property's value
		 * @param stdClass $obj JSON object reference
		 * @param int $ndx Index into array, if current property references an array
		 * @return multi the value from the object referenced by the current property
		 */
		private function _get_val($obj, $ndx = 0)
		{
			$val = 0;
			$idx = 0;						// this is the index within the _prop_list array to use for property references
			$prop_name = '';
if ($this->_prop_array) {
	$idx = 1;
	$prop_name = $this->_prop_list[0] . '[' . $ndx . ']->';
}
			$idx2 = $idx + 1;

			if (NULL === $this->_prop_name) {									// nested reference
$prop_name .= $this->_prop_list[$idx] . '->' . $this->_prop_list[$idx2];
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' getting property: ' . $prop_name);
				if (isset($obj->{$this->_prop_list[$idx]}->{$this->_prop_list[$idx2]}))
					$val = $obj->{$this->_prop_list[$idx]}->{$this->_prop_list[$idx2]};
			} else {															// single reference
$prop_name .= $this->_prop_name;
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' getting property: ' . $prop_name);
				// property denotes a single reference
				if (isset($obj->{$this->_prop_name}))
					$val = $obj->{$this->_prop_name};
			}
			return $val;
		}

		/**
		 * Sets a property's value
		 * @param stdClass $obj JSON object reference
		 * @param multi $val The value to set for the current property
		 * @param int $ndx Index into array, if current property references an array
		 */
		private function _set_val($obj, $val, $ndx = 0)
		{
			$idx = 0;
			$prop_name = '';
if ($this->_prop_array) {
	$idx = 1;
	$prop_name = $this->_prop_list[0] . '[' . $ndx . ']->';
}
			$idx2 = $idx + 1;

			if (NULL === $this->_prop_name) {									// nexted reference
$prop_name .= $this->_prop_list[$idx] . '->' . $this->_prop_list[$idx2];
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' setting property: ' . $prop_name);
				if (isset($obj->{$this->_prop_list[$idx]}->{$this->_prop_list[$idx2]}))
					$obj->{$this->_prop_list[$idx]}->{$this->_prop_list[$idx2]} = $val;
				else
					throw new Exception('Property "' . $prop_name . '" does not exist in object');
			} else {															// single reference
$prop_name .= $this->_prop_name;
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' setting property: ' . $prop_name);
				if (isset($obj->{$this->_prop_name}))
					$obj->{$this->_prop_name} = $val;
				else
					throw new Exception('Property "' . $prop_name . '" does not exist in object');
			}
		}

		/**
		 * Gets a Target ID value from a given Source ID value, based on current property type
		 * @param int $source_ref_id The Source ID value
		 * @return boolean|int FALSE on failure; otherwise integer value ofr Target property
		 */
		private function _get_target_ref($source_ref_id)
		{
			switch ($this->_prop_type) {
			case self::PROPTYPE_IMAGE:
				$source_ref_id = abs($source_ref_id);		// these are always an integer
				$sync_data = $this->_sync_model->get_sync_data($source_ref_id, $this->_api_controller->source_site_key);
				if (NULL === $sync_data)
					return FALSE;			// no data found, indicate this to caller
				return abs($sync_data->target_content_id);
				break;
			case self::PROPTYPE_LINK:
				return FALSE;
				// note: this will return the string with the post ID modified
				break;
			case self::PROPTYPE_USER:
				return FALSE;
				// note: this will return the user id
			}
			return FALSE;
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
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' block name=' . $block_name);
			if (in_array($block_name, $this->_block_names)) {
				// check block name for types that cannot be synchronized and provide a notice
				switch ($block_name) {
				case 'wp:uagb/cf7-styler':
					WPSiteSync_Gutenberg_Blocks::get_instance()->load_class('gutenbergblocksapirequest', FALSE);
					$resp = $apirequest->get_response();
					$resp->notice_code(SyncGutenbergBlocksApiRequest::NOTICE_GB_CONTACTFORM7);
					break;

				case 'wp:uagb/gf-styler':
					WPSiteSync_Gutenberg_Blocks::get_instance()->load_class('gutenbergblocksapirequest', FALSE);
					$resp = $apirequest->get_response();
					$resp->notice_code(SyncGutenbergBlocksApiRequest::NOTICE_GB_GRAVITYFORM);
					break;
				}

				// the block name is found within our list of known block types to update
				$obj = json_decode($json);
if ('wp:uagb/info-box' === $block_name) SyncDebug::log(__METHOD__.'():' . __LINE__ . ' found json block data for "' . $block_name . '" : ' . var_export($obj, TRUE));

				if (!empty($json) && NULL !== $obj) {
					// this block has a JSON object embedded within it
					$props = explode('|', $this->_props[$block_name]);
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' props=' . var_export($props, TRUE));
					foreach ($props as $property) {
						// for each property listed in the $_props array, look to see if it refers to an image ID
						$ref_ids = array();
						$this->_parse_property($property);
						$prop_name = $this->_prop_name;

						if ($this->_prop_array) {								// property denotes an array reference
							if (isset($obj->{$this->_prop_list[0]})) {			// make sure property exists
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' checking array: "' . $this->_prop_list[0] . '"');
								$idx = 0;
								foreach ($obj->{$this->_prop_list[0]} as $entry) {
									$ref_id = abs($this->_get_val($entry, $idx));
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' source ref=' . var_export($source_ref_id, TRUE));
									if (0 !== $ref_id)
										$ref_ids[] = $ref_id;
									++$idx;
								}
							}
						} else {												// not an array reference, look up single property
							$ref_id = abs($this->_get_val($obj));
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' source ref=' . var_export($ref_id, TRUE));
							if (0 !== $ref_id)
								$ref_ids[] = $ref_id;
						}
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' found property "' . $prop_name . '" referencing ids ' . implode(',', $ref_ids));

						switch ($this->_prop_type) {
						case self::PROPTYPE_IMAGE:
							// get the thumbnail id if we haven't already
							if (NULL === $this->_thumb_id)			// if the thumb id hasn't already been determined, get it here
								$this->_thumb_id = abs(get_post_thumbnail_id($source_post_id));

							// now go through the list. it's a list since Ultimate Addons uses arrays for some of it's block data
							foreach ($ref_ids as $ref_id) {
								if (0 !== $ref_id) {
									// the property has a non-zero value, it's an image reference
									if (FALSE === $apirequest->gutenberg_attachment_block($ref_id, $source_post_id, $this->_thumb_id, $block_name)) {
										// TODO: error recovery
									}
								} // 0 !== $ref_id
							}
							break;
						case self::PROPTYPE_LINK:
							break;
						case self::PROPTYPE_POST:
							break;
						case self::PROPTYPE_USER:
							break;
						}

					} // foreach
				} // !empty($json)
			} // in_array($block_name, $this->_props)
else {
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' block name not recognized...continuing');
}
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' exiting parse_gutenberg_block()');
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
			// look for known block names
			if (in_array($block_name, $this->_block_names)) {
				// check to see if it's one of the form block names; skip those
				switch ($block_name) {
				case 'wp:uagb/cf7-styler':
				case 'wp:uagb/gf-styler':
					// simply return the content. this allows product specific add-ons to update Gutenberg content appropriately
					return $content;
				}

				$obj = json_decode($json);
				if (!empty($json) && NULL !== $obj) {
					$updated = FALSE;
					if (NULL === $this->_sync_model) {
						// create instance if not already set
						$this->_api_controller = SyncApiController::get_instance();
						$this->_sync_model = new SyncModel();
					}

					$props = explode('|', $this->_props[$block_name]);
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' props=' . var_export($props, TRUE));
					foreach ($props as $property) {
						// check for each property name found within the block's data
						$this->_parse_property($property);
						$prop_name = $this->_prop_name;

						if ($this->_prop_array) {								// property denotes an array reference
							if (isset($obj->{$this->_prop_list[0]})) {			// make sure property exists
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' checking array: "' . $this->_prop_list[0] . '"');
								$idx = 0;
								foreach ($obj->{$this->_prop_list[0]} as &$entry) {
									$source_ref_id = $this->_get_val($entry, $idx);
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' source ref=' . var_export($source_ref_id, TRUE));
									if (0 !== $source_ref_id) {
										// get the Target's post ID from the Source's post ID
										$target_ref_id = $this->_get_target_ref($source_ref_id);
										if (FALSE !== $target_ref_id) {
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' updating Source ID ' . $source_ref_id . ' to Target ID ' . $target_ref_id);
											$this->_set_val($entry, $target_ref_id, $idx);
											$updated = TRUE;
										}
									}
									++$idx;
								}
							} // isset
						} else {												// single reference
							$source_ref_id = $this->_get_val($obj);
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' source ref=' . var_export($source_ref_id, TRUE));
							if (0 !== $source_ref_id) {
								// get the Target's post ID from the Source's post ID
								$target_ref_id = $this->_get_target_ref($source_ref_id);
								if (FALSE !== $target_ref_id) {
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' updating Source ID ' . $source_ref_id . ' to Target ID ' . $target_ref_id);
									$this->_set_val($obj, $target_ref_id);
									$updated = TRUE;
								}
							}
						}
					} // foreach

					if ($updated) {
						// one or more properties were updated with their Target post ID values- update the content
						$new_obj_data = json_encode($obj);
						$content = substr($content, 0, $start) . $new_obj_data . substr($content, $end + 1);
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' original: ' . $json . PHP_EOL . ' updated: ' . $new_obj_data);
					}
				} // !empty($json)
			} // in_array($block_name, $this->_props)
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' returning');
			return $content;
		}

		public function filter_notice_codes($msg, $code)
		{
SyncDebug::log(__METHOD__.'():' . __LINE__);
			$api = $this->load_class('gutenbergblocksapirequest', TRUE);
			return $api->filter_notice_codes($msg, $code);
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