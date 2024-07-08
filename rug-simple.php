<?php

/**
 * Plugin Name: Rug Simple
 * Description: RugSimple is a plugin that allows you to sync data with the mongodb using APIs.
 * Version:     1.0.0
 * Author:      RugSimple
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: rug-simple
 *
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

define('RUG_SIMPLE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RUG_SIMPLE_PLUGIN_VERSION', '1.0.0');

define('RUG_SIMPLE_PLUGIN_DIR', plugin_dir_url(__DIR__));
define('RUG_SIMPLE_PLUGIN_PATH', plugin_dir_path(__FILE__));


/**
 * Files Inclusion
 */

require_once RUG_SIMPLE_PLUGIN_PATH . "/inc/rug-taxonomy.php";
require_once RUG_SIMPLE_PLUGIN_PATH . "/inc/rug-simple-functions.php";
require_once RUG_SIMPLE_PLUGIN_PATH . "/inc/mongodb-settings.php";

/*
 * Function to load plugin textdomain
 */
function rug_simple_load_textdomain()
{

	global $wp_version;

	/**
	 * Load text domain
	 */

	$lang_dir = dirname(plugin_basename(__FILE__)) . '/languages/';
	$lang_dir = apply_filters('rug_simples_languages_directory', $lang_dir);

	$get_locale = get_locale();

	if ($wp_version >= 4.7) {
		$get_locale = get_user_locale();
	}

	$locale = apply_filters('plugin_locale',  $get_locale, 'rug-simple');
	$mofile = sprintf('%1$s-%2$s.mo', 'rug-simple', $locale);

	$mofile_global  = WP_LANG_DIR . '/plugins/' . basename(RUG_SIMPLE_PLUGIN_PATH) . '/' . $mofile;

	if (file_exists($mofile_global)) {
		load_textdomain('rug-simple', $mofile_global);
	} else {
		load_plugin_textdomain('rug-simple', false, $lang_dir);
	}
}

add_action('plugins_loaded', 'rug_simple_load_textdomain');

/**
 * Adding Scripts
 */


add_action('wp_enqueue_scripts', 'rug_simple_scripts');

function rug_simple_scripts()
{

	wp_enqueue_style('rug-simple', RUG_SIMPLE_PLUGIN_URL . 'assets/css/rug-simple.css');
	wp_enqueue_script('rug-simple', RUG_SIMPLE_PLUGIN_URL . 'assets/js/rug-simple.js', array('jquery'), RUG_SIMPLE_PLUGIN_VERSION, true);
}

/**
 * Adding Admin scripts
 *
 * @return void
 */
function mongo_admin_custom_scripts()
{

	wp_enqueue_style('mdb-custom-admin', RUG_SIMPLE_PLUGIN_URL . 'assets/css/mdb-custom-admin.css');
	wp_enqueue_script('mdb-custom-admin', RUG_SIMPLE_PLUGIN_URL . 'assets/js/mdb-custom-admin.js', array('jquery'), RUG_SIMPLE_PLUGIN_VERSION, true);

	wp_localize_script('mdb-custom-admin', 'mdb_admin_global', array(
		'ajaxurl' => admin_url('admin-ajax.php'),
		'loading_img' => RUG_SIMPLE_PLUGIN_URL . 'assets/images/spinner.gif'
	));
}

add_action('admin_enqueue_scripts', 'mongo_admin_custom_scripts');

/**
 * Cron Job on daily bases
 */

register_activation_hook(__FILE__, 'rugs_simple_activation');
register_deactivation_hook(__FILE__, 'rugs_simple_deactivation');

function rugs_simple_activation()
{
	if (!wp_next_scheduled('rugs_update_products')) {
		wp_schedule_event(strtotime('today midnight'), 'daily', 'rugs_update_products');
	}
}

function rugs_simple_deactivation()
{
	$timestamp = wp_next_scheduled('rugs_update_products');
	wp_unschedule_event($timestamp, 'rugs_update_products');
}

add_action('rugs_update_products', 'rugs_update_products_callback');

function rugs_update_products_callback()
{


	global $wpdb;

	// Define the query to get only SKU and last updated date
	$query_args = array(
		'post_type'      => 'product',
		'posts_per_page' => -1, // Get all products
		'post_status'    => 'publish',
		'fields'         => 'ids', // Only get post IDs
	);

	// Create a new WP_Query instance
	$query = new WP_Query($query_args);

	// Loop through the product IDs and get SKU and last updated date
	$products = array();
	if ($query->have_posts()) {
		while ($query->have_posts()) {
			$query->the_post();
			$product_id = get_the_ID();
			// Get the SKU
			$sku = get_post_meta($product_id, '_sku', true);
			$collection_name = get_post_meta($product_id, 'collection_name', true);
			// Get the last updated date
			$last_updated = get_post_modified_time('Y-m-d H:i:s', true, $product_id);
			$products[] = array(
				'id' => $product_id,
				'sku' => $sku,
				'last_updated' => $last_updated,
				'collection_name' => $collection_name,
			);
		}
	}
	// Restore original post data
	wp_reset_postdata();


	$woo_insersion_data = [];

	if (isset($products) && !empty($products) && is_array($products) && sizeof($products) > 0) {

		foreach ($products as $product) {

			$rugID = isset($product['sku']) ? $product['sku'] : '';
			$databaseData = isset($product['collection_name']) ? $product['collection_name'] : '';
			$productID = isset($product['id']) ? $product['id'] : '';

			$product_last_update = isset($product['last_updated']) ? $product['last_updated'] : '';


			$customerRegredadeData = [];
			if ($rugID != '' && $databaseData != '' && $product_last_update != '') {

				$customerRegredadeData = mdb_load_aggregation_data($rugID, $databaseData);
			}

			if (isset($customerRegredadeData) && !empty($customerRegredadeData)) {

				foreach ($customerRegredadeData as $key => $eachCustomerData) {

					$mongoDBupdated_at = isset($eachCustomerData['updated_at']) ? $eachCustomerData['updated_at'] : '';

					$mongo_updated_at = '';
					if ($mongoDBupdated_at != '') {
						$mongo_updated_at = date('Y-m-d H:i:s', strtotime($mongoDBupdated_at));
					}

					if ($mongo_updated_at != '' && $mongo_updated_at > $product_last_update) {

						$wooSubItem = [];

						// product title
						$wooSubItem['product_id'] = isset($productID) ? $productID : '';

						// product title
						$wooSubItem['title'] = isset($eachCustomerData['owned']['title']) ? $eachCustomerData['owned']['title'] . ' cron test ' : '';

						// product description
						$wooSubItem['description'] = isset($eachCustomerData['description']) ? $eachCustomerData['description'] : '';
						// product description
						$wooSubItem['collection_name'] = isset($databaseData) ? $databaseData : '';

						//price
						$wooSubItem['regularPrice'] = isset($eachCustomerData['price']['regularPrice']) ? $eachCustomerData['price']['regularPrice'] : 0;
						$wooSubItem['sellingPrice'] = isset($eachCustomerData['price']['sellingPrice']) ? $eachCustomerData['price']['sellingPrice'] : 0;
						// product dimension
						$wooSubItem['dimension'] = $eachCustomerData['dimension'];
						// on sale or not
						$wooSubItem['isOnSale'] = isset($eachCustomerData['price']['isOnSale']) ? $eachCustomerData['price']['isOnSale'] : false;
						$wooSubItem['onSale'] = isset($eachCustomerData['price']['onSale']) ? $eachCustomerData['price']['onSale'] : [];
						//cost type and cost
						$wooSubItem['costType'] = isset($eachCustomerData['price']['costType']) ? $eachCustomerData['price']['costType'] : '';
						$wooSubItem['cost'] = isset($eachCustomerData['price']['cost']) ? $eachCustomerData['price']['cost'] : '';
						$wooSubItem['costPerSquare'] = isset($eachCustomerData['price']['costPerSquare']) ? $eachCustomerData['price']['costPerSquare'] : [];
						// media
						$wooSubItem['images'] = isset($eachCustomerData['images']) ? $eachCustomerData['images'] : [];
						// tax-field
						$wooSubItem['isTaxable'] = isset($eachCustomerData['price']['isTaxable']) ? $eachCustomerData['price']['isTaxable'] : false;
						// product condition
						$wooSubItem['condition'] = isset($eachCustomerData['condition']) ? $eachCustomerData['condition'] : '';
						// product data
						$wooSubItem['productData'] = isset($eachCustomerData['productData']) ? $eachCustomerData['productData'] : '';
						$wooSubItem['status'] = isset($eachCustomerData['status']) ? $eachCustomerData['status'] : '';
						$wooSubItem['legacySKU'] = isset($eachCustomerData['legacySKU']) ? $eachCustomerData['legacySKU'] : '';
						$wooSubItem['ID'] = isset($eachCustomerData['ID']) ? $eachCustomerData['ID'] : 0;
						// product type rugs or not and rug type
						$wooSubItem['productType'] = isset($eachCustomerData['productType']) ? $eachCustomerData['productType'] : '';
						$wooSubItem['rugType'] = isset($eachCustomerData['rugType']) ? $eachCustomerData['rugType'] : '';
						//Categories
						$wooSubItem['category'] = isset($eachCustomerData['category']['name']) ? $eachCustomerData['category']['name'] : '';
						$wooSubItem['subCategory'] = isset($eachCustomerData['subCategory']['name']) ? $eachCustomerData['subCategory']['name'] : '';
						// colorTags
						$wooSubItem['colourTags'] = isset($eachCustomerData['colourTags']) ? $eachCustomerData['colourTags'] : [];
						$wooSubItem['attributes'] = isset($eachCustomerData['attributes']) ? $eachCustomerData['attributes'] : [];
						// construct type
						$wooSubItem['constructionType'] = isset($eachCustomerData['constructionType']) ? $eachCustomerData['constructionType'] : '';
						// location
						$wooSubItem['country'] = isset($eachCustomerData['country']) ? $eachCustomerData['country'] : '';
						// extra data
						$wooSubItem['collections'] = isset($eachCustomerData['collections']) ? $eachCustomerData['collections'] : [];
						$wooSubItem['customFields'] = isset($eachCustomerData['customFields']) ? $eachCustomerData['customFields'] : [];
						$wooSubItem['production'] = isset($eachCustomerData['production']) ? $eachCustomerData['production'] : '';
						$wooSubItem['primaryMaterial'] = isset($eachCustomerData['primaryMaterial']) ? $eachCustomerData['primaryMaterial'] : '';
						$wooSubItem['design'] = isset($eachCustomerData['design']) ? $eachCustomerData['design'] : '';
						$wooSubItem['palette'] = isset($eachCustomerData['palette']) ? $eachCustomerData['palette'] : '';
						$wooSubItem['pattern'] = isset($eachCustomerData['pattern']) ? $eachCustomerData['pattern'] : '';
						$wooSubItem['pile'] = isset($eachCustomerData['pile']) ? $eachCustomerData['pile'] : '';
						$wooSubItem['period'] = isset($eachCustomerData['period']) ? $eachCustomerData['period'] : '';

						// publish and updated time
						$wooSubItem['created_at'] = isset($eachCustomerData['created_at']) ? $eachCustomerData['created_at'] : '';
						$wooSubItem['updated_at'] = isset($eachCustomerData['updated_at']) ? $eachCustomerData['updated_at'] : '';

						//inventory
						$wooSubItem['inventory'] = isset($eachCustomerData['inventory']) ? $eachCustomerData['inventory'] : [];
						$wooSubItem['shipping'] = isset($eachCustomerData['shipping']) ? $eachCustomerData['shipping'] : [];

						$wooSubItem['otherTags'] = isset($eachCustomerData['otherTags']) ? $eachCustomerData['otherTags'] : [];
						$wooSubItem['sizeCategoryTags'] = isset($eachCustomerData['sizeCategoryTags']) ? $eachCustomerData['sizeCategoryTags'] : [];
						$wooSubItem['styleTags'] = isset($eachCustomerData['styleTags']) ? $eachCustomerData['styleTags'] : [];
						$wooSubItem['shapeCategoryTags'] = isset($eachCustomerData['shapeCategoryTags']) ? $eachCustomerData['shapeCategoryTags'] : [];

						$woo_insersion_data[] = $wooSubItem;
					}
				}
			}

			if (!empty($woo_insersion_data) && sizeof($woo_insersion_data) > 0) {

				mdb_multiple_product_updation($woo_insersion_data);
			}
		}
	}
}

if (defined('WP_DEBUG') && WP_DEBUG) {
	add_action('init', 'your_plugin_manual_trigger');

	function your_plugin_manual_trigger()
	{
		if (isset($_GET['manual_cron_trigger']) && $_GET['manual_cron_trigger'] === '1') {
			do_action('rugs_update_products');
		}
	}
}