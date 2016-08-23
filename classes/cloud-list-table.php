<?php

if( !defined('WORD_CLOUD') ) return;

if( !class_exists('WP_List_Table') )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

if( !class_exists('WordCloud_Model') )
	require_once( WORD_CLOUD_PLUGIN_PATH . '/classes/model/model.php' );


/**
 * The WP_List_Table class for the Clouds table.
 * 
 * @package    word-cloud
 * @subpackage classes
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('WordCloud_CloudListTable') ):
class WordCloud_CloudListTable extends WP_List_Table
{
	/**
	 * Parent admin page.
	 * @var  APL_AdminPage
	 */
	private $parent;

	/**
	 * The main Word Cloud model.
	 * @var  WordCloud_Model
	 */
	private $model;
	
	
	/**
	 * Constructor.
	 */
	public function __construct( $parent )
	{
		$this->parent = $parent;
		$this->model = WordCloud_Model::get_instance();
	}
	

	/**
	 * Loads the list table.
	 */
	public function load()
	{
		parent::__construct(
            array(
                'singular' => 'word-cloud-cloud',
                'plural'   => 'word-cloud-clouds',
                'ajax'     => false
            )
        );

		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
	}
	
	/**
	 * Prepare the table's items.
	 * @param  array  $filter  An array of filter name and values.
	 * @param  array  $search  An array of search columns and phrases.
	 * @param  bool  $only_errors  True if filter out OrgHub users with errors.
	 * @param  string  $orderby  The column to orderby.
	 */
	public function prepare_items()
	{
		$cloud_count = $this->model->get_cloud_count();
	
		$current_page = $this->get_pagenum();
		$per_page = $this->parent->get_screen_option( 'word-cloud_clouds_per_page' );

		$this->set_pagination_args( array(
    		'total_items' => $cloud_count,
    		'per_page'    => $per_page
  		) );
  		
  		$offset = ( $current_page - 1 ) * $per_page;
		$this->items = $this->model->get_filtered_cloud_list( $offset, $per_page, true );
	}


	/**
	 * Get the columns for the table.
	 * @return  array  An array of columns for the table.
	 */
	public function get_columns()
	{
		return array(
			'name' => 'Name',
			'settings' => 'Settings',
			'cache' => 'Cache',
		);
	}
	
	
	/**
	 * Get the column that are hidden.
	 * @return  array  An array of hidden columns.
	 */
	public function get_hidden_columns()
	{
		$screen = get_current_screen();
		$hidden = get_user_option( 'manage' . $screen->id . 'columnshidden' );
		
		if( $hidden === false )
		{
			$hidden = array(
			);
		}
		
		return $hidden;
	}

	
	/**
	 * Get the sortable columns.
	 * @return  array  An array of sortable columns.
	 */
	public function get_sortable_columns()
	{
		return array(
		);
	}
	
	
	/**
	 * Echos the text to display when no users are found.
	 */
	public function no_items()
	{
  		_e( 'No clouds found.' );
	}
	
				
	/**
	 * Generates the html for a column.
	 * @param  array  $item  The item for the current row.
	 * @param  string  $column_name  The name of the current column.
	 * @return  string  The heml for the current column.
	 */
	public function column_default( $item, $column_name )
	{
		return '<strong>ERROR:</strong><br/>'.$column_name;
	}
	
	
	/**
	 * Generates the html for the cloud name column.
	 * @param  array  $item  The item for the current row.
	 * @return  string  The html for the cloud name and actions.
	 */
	public function column_name( $item )
	{
		$actions = array(
			'edit' => sprintf( '<a href="%s">Edit</a>', 'admin.php?page=word-clouds&tab=edit&name=' . $item['name'] ),
			'remove' => sprintf( '<a href="%s">Remove</a>', 'admin.php?page=word-clouds&tab=delete&name=' . $item['name'] ),
		);
		
		return sprintf( '%1$s<br/>%2$s', $item['name'],  $this->row_actions($actions) );
	}
	
	
	/**
	 * Generates the html for the cloud settings column.
	 * @param  array  $item  The item for the current row.
	 * @return  string  The heml for the cloud settings column.
	 */
	public function column_settings( $item )
	{
		$html = '';
		$html .= '<strong>Post Types:</strong> ' . esc_html( implode( ', ', $item['post_types'] ) );
		$html .= '<br />';
		$html .= '<strong>Taxonomies:</strong> ' . esc_html( implode( ', ', $item['taxonomies'] ) );
		$html .= '<br />';
		if( 'none' != $item['filterby_taxonomy'] ) {
			$html .= '<strong>Filter:</strong> ' . 
				esc_html( $item['filterby_taxonomy'] ) . ' / ' . 
				esc_html( $item['filterby_terms'] );
		}
		
		return $html;
	}
	
	
	/**
	 * Generates the html for the cloud cache column.
	 * @param  array  $item  The item for the current row.
	 * @return  string  The heml for the cloud cache column.
	 */
	public function column_cache( $item )
	{
		if( false === $item['cached'] ) {
			return 'No valid cache';
		}
		
		return esc_html( $item['cached'] );
	}
	
	
} // class OrgHub_UsersListTable extends WP_List_Table
endif; // if( !class_exists('OrgHub_UsersListTable') ):

