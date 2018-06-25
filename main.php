<?php
/*
Plugin Name: Word Cloud v2
Plugin URI: https://github.com/atrus1701/word-cloud-v2-cached-version
Description: Displays a word cloud using cached terms.  This is a fork of the word-cloud.
Version: 2.2.0
Author: Aaron Forsyth, Crystal Barton
Author URI: https://pages.uncc.edu/oat
GitHub Plugin URI: https://github.com/clas-web/word-cloud
*/


if( ! defined( 'WORD_CLOUD' ) ):

/**
 * The full title of the Word Cloud plugin.
 * @var  string
 */
define( 'WORD_CLOUD', 'Word Cloud' );

/**
 * True if debug is active, otherwise False.
 * @var  bool
 */
define( 'WORD_CLOUD_DEBUG', false );

/**
 * The path to the plugin.
 * @var  string
 */
define( 'WORD_CLOUD_PLUGIN_PATH', __DIR__ );

/**
 * The url to the plugin.
 * @var  string
 */
define( 'WORD_CLOUD_PLUGIN_URL', plugins_url('', __FILE__) );

/**
 * The version of the plugin.
 * @var  string
 */
define( 'WORD_CLOUD_VERSION', '2.0.1' );

/**
 * The database version of the plugin.
 * @var  string
 */
define( 'WORD_CLOUD_DB_VERSION', '2.0' );

/**
 * The database options key for the Word Cloud version.
 * @var  string
 */
define( 'WORD_CLOUD_VERSION_OPTION', 'word-cloud-version' );

/**
 * The database options key for the Word Cloud database version.
 * @var  string
 */
define( 'WORD_CLOUD_DB_VERSION_OPTION', 'word-cloud-db-version' );

/**
 * The database options key for the Word Cloud options.
 * @var  string
 */
define( 'WORD_CLOUD_LIST', 'word_cloud_list' );
define( 'WORD_CLOUD_SETTINGS', 'word_cloud_settings' );
define( 'WORD_CLOUD_CACHE', 'word_cloud_cache' );

/**
 * The full path to the log file used for debugging.
 * @var  string
 */
define( 'WORD_CLOUD_LOG_FILE', __DIR__.'/log.txt' );

endif;


/* Add filter for CRON job caching */
add_filter( 'query_vars', 'word_cloud_query_vars' );
add_action( 'parse_request', 'word_cloud_parse_request' );


/* Add widget and shortcode */
require_once( __DIR__ . '/control.php' );
WordCloud_WidgetShortcodeControl::register_widget();
WordCloud_WidgetShortcodeControl::register_shortcode();


if( is_admin() ):
 	add_action( 'wp_loaded', 'word_cloud_load' );
endif;


/**
 * Setup the site admin pages.
 */
if( !function_exists('word_cloud_load') ):
function word_cloud_load()
{
	require_once( __DIR__.'/admin-pages/require.php' );
	
	$wc_pages = new APL_Handler( false );

	$wc_pages->add_page( new WordCloud_CloudsAdminPage() );
	$wc_pages->setup();
	
	if( $wc_pages->controller )
	{
//		add_action( 'admin_enqueue_scripts', 'word_cloud_enqueue_scripts' );
		add_action( 'admin_menu', 'word_cloud_update', 5 );
	}
}
endif;


/**
 * Update the database if a version change.
 */
if( !function_exists('word_cloud_update') ):
function word_cloud_update()
{
	update_option( WORD_CLOUD_VERSION_OPTION, WORD_CLOUD_VERSION );
	update_option( WORD_CLOUD_DB_VERSION_OPTION, WORD_CLOUD_DB_VERSION );
}
endif;


/**
 * Add filtering keys to the query vars.
 * @param  Array  $query_vars  The query vars.
 * @return  Array  The altered query vars.
 */
if( !function_exists('word_cloud_query_vars') ):
function word_cloud_query_vars( $query_vars )
{
	$query_vars[] = 'wccache';
	$query_vars[] = 'cloud_name';
	return $query_vars;
}
endif;


/**
 * Parse the request to search for filtering keys.
 * @param  WP  $wp  The WP object.
 */
if( !function_exists('word_cloud_parse_request') ):
function word_cloud_parse_request( &$wp )
{
	if( array_key_exists( 'wccache', $wp->query_vars ) )
	{
		require_once( WORD_CLOUD_PLUGIN_PATH . '/classes/model.php' );
		require_once( WORD_CLOUD_PLUGIN_PATH . '/classes/output.php' );
		$model = WordCloud_Model::get_instance();
		$output = WordCloud_Output::get_instance();
		$output->include_datetime = true;
		
		$output->write_line();
		$output->write_line( 'Word Cloud caching begins' );
		$output->write_line();
		
		switch( $wp->query_vars['wccache'] )
		{
			case 'all':
				$model->cache_all_clouds();
				break;
				
			case 'single':
				if( ! array_key_exists( 'cloud_name', $wp->query_vars ) ) {
					$output->write_line( 'No cloud name given.' );
					break;
				}
				
				$model->cache_cloud( $wp->query_vars['cloud_name'] );
				break;
		}

		$output->write_line();
		$output->write_line( 'Word Cloud caching complete' );
		$output->write_line();
		exit;
	}
}
endif;

