<?php
/*
 Plugin Name: m.io Admin OmniSearch
 Plugin URI: https://github.com/manuelpiepereit/wp-admin-omnisearch
 Description: A global admin search living in the admin bar
 Version: 0.9.0
 Author: manou.io
 Author URI: https://www.manou.io
 Text Domain: mio-omnisearch
 Domain Path: /lang
*/

namespace mio\omnisearch;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class mio_omnisearch {

	private $version = '0.3.1';
	private $slug = 'mio-omnisearch';

	public function __construct() {
		add_action('rest_api_init', [$this, 'add_custom_users_api']);
		add_action('admin_bar_menu', [$this, 'create_admin_bar_icon'], 20);
		add_action('admin_enqueue_scripts', [$this, 'add_assets']);
		add_action('wp_enqueue_scripts', [$this, 'add_assets']);
	}


	/**
	 * Adds seach note to admin bar
	 *
	 * @param [type] $wp_admin_bar
	 * @return void
	 */
	public static function create_admin_bar_icon($wp_admin_bar) {
		if ( !current_user_can(apply_filters('mio_omnisearch_capability', 'edit_others_posts')) ) {
			return;
		}

		$args = array(
			'id' => 'mio-omnisearch-app',
			'title' => '.',
			'parent' => 'top-secondary',
		);
		$wp_admin_bar->add_node($args);
	}


	/**
	 * Creates a custom API route
	 *
	 * @return void
	 */
	public function add_custom_users_api() {
		// only when adminbar is available
		if (!is_admin_bar_showing()) {
			return;
		}

		register_rest_route('mio', '/omnisearch', array(
				'methods' => 'GET',
				'callback' => [$this, 'rest_callback_admin_search'],
			));
	}


	/**
	 * Returns search results via custom API route
	 *
	 * @param [type] $request
	 * @return void
	 */
	public function rest_callback_admin_search($request) {
		// get posts by request params
		$params = $request->get_params();
		$args = apply_filters( 'mio_omnisearch_query', array(
				'post_status' => 'any',
				'posts_per_page' => 20,
				'orderby' => array('title' => 'ASC', 'date' => 'DESC', 'type' => 'ASC'),
			));
		$args['s'] = $params['search'];
		$args['post_type'] = $params['type'];
		$query = new \WP_Query($args);

		// bail if no posts are found
		if (!$query->have_posts()) {
			return new \WP_Error('nothing_found', __('Nothing found', 'mio-omnisearch'), array( 'status' => 200));
		}

		// create posts array
		$posts = array();
		foreach ($query->get_posts() as $post) {
			// show all not published states
			$status = (get_post_status($post->ID) !== 'publish') ? __(ucfirst(get_post_status($post->ID))) : '';

			// string for front page and posts page
			if ($post->ID === intval(get_option('page_on_front'))) {
				$status = __('Front Page');
			} else if ($post->ID === intval(get_option('page_for_posts'))) {
				$status = __('Posts Page');
			}

			// add to response array
			$posts[] = array(
					'id' => $post->ID,
					'title' => $post->post_title,
					'slug' => $post->post_name,
					'type' => __(get_post_type_object($post->post_type)->labels->singular_name),
					'status' => $status,
					'image' => ('attachment' === $post->post_type) ? wp_get_attachment_thumb_url($post->ID) : get_the_post_thumbnail_url($post->ID, 'thumbnail'),
					'link_view'=> get_permalink($post->ID),
					'link_edit' => get_admin_url() . 'post.php?post='.$post->ID.'&action=edit',
				);
		}

		// create response
		return rest_ensure_response($posts);
	}


	/**
	 * Adds css and js assets
	 *
	 * @param [type] $hook
	 * @return void
	 */
	function add_assets($hook) {
		// only when adminbar is available
		if (!is_admin_bar_showing()) {
			return;
		}

		$min = '.min';
		// @note load dev file when script_debug is on
		// $min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG === true ) ? '' : $min;

		// add css
		wp_register_style($this->slug, plugin_dir_url(__FILE__)."dist/app${min}.css", array('admin-bar'), $this->version);
		wp_enqueue_style($this->slug);

		// fetch post types and make them available for js
		$types = array('any' => array('name' => 'any', 'label' => __('Show all post types', 'mio-omnisearch')));
		// only use post types that aren't exluded from search are are in shown in rest
		$post_types = get_post_types(array('exclude_from_search' => false, 'show_in_rest' => true), 'objects');
		foreach ($post_types as $post_type) {
			// we only need the name and label
			$types[$post_type->name] = array('name' => $post_type->name, 'label' => $post_type->label);
		}

		// add localized data
		$js_data = array(
				'types' => apply_filters( 'mio_omnisearch_posttypes', $types),
				'ui' => array(
					'edit' => __('Edit', 'mio-omnisearch'),
					'view' => __('View', 'mio-omnisearch'),
					'search' => __('Search', 'mio-omnisearch'),
					'searchResults' => __('Search results', 'mio-omnisearch'),
					'nothingFound' => __('Nothing found', 'mio-omnisearch'),
					'openInfo' => __('edit selection ⏎', 'mio-omnisearch'),
				),
			);

		// load js
		wp_register_script($this->slug, plugin_dir_url(__FILE__)."dist/app${min}.js", array(), $this->version, true);
		wp_localize_script($this->slug, 'mio_omnisearch', $js_data);
		wp_enqueue_script($this->slug);
	}

}

new mio_omnisearch();
