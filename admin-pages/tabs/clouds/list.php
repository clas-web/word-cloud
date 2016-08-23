<?php

if( !class_exists('WordCloud_CloudListTable') )
	require_once( WORD_CLOUD_PLUGIN_PATH.'/classes/cloud-list-table.php' );


/**
 * Controls the tab admin page "Clouds > List".
 * 
 * @package    word-cloud
 * @subpackage admin-pages/tabs/users
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('WordCloud_CloudsListTabAdminPage') ):
class WordCloud_CloudsListTabAdminPage extends APL_TabAdminPage
{
	/**
	 * The main model for the Word Cloud.
	 * @var  WordCloud_Model
	 */	
	private $model = null;	

	/**
	 * The Users admin table.
	 * @var  WordCloud_UsersListTable
	 */
	private $list_table = null;
	
	/**
	 * True to filter only items with errors, otherwise False.
	 * @var  bool
	 */
	private $show_errors;
	
	
	/**
	 * Constructor.
	 */
	public function __construct(
		$parent,
		$name = 'list', 
		$tab_title = 'List', 
		$page_title = 'Cloud List' )
	{
		parent::__construct( $parent, $name, $tab_title, $page_title );
		$this->model = WordCloud_Model::get_instance();
	}

	
	/**
	 * Initialize the admin page by setting up the filters and list table.
	 */
	public function init()
	{
		$this->list_table = new WordCloud_CloudListTable( $this );
	}
	
	/**
	 * Loads the list table's items.
	 */
	public function load()
	{
		$this->list_table->load();
	}
	

	/**
	 * Add screen options.
	 */
	public function add_screen_options()
	{
		$this->add_per_page_screen_option( 'word-cloud_clouds_per_page', 'Clouds', 100 );
		$this->add_selectable_columns( $this->list_table->get_selectable_columns() );
	}
	
	
	/**
	 * Displays the current admin page.
	 */
	public function display()
	{
		?>
		<a href="<?php echo esc_attr( $this->get_page_url( array( 'tab' => 'add' ) ) ); ?>">
			Add New Cloud
		</a>
		<?php
		
		// Prepare list table.
		$this->list_table->prepare_items();
		
		// Display list table form.
		$this->form_start( 'clouds-table' );
		$this->list_table->display();
		$this->form_end();
	}

} // class WordCloud_CloudsListTabAdminPage extends APL_TabAdminPage
endif; // if( !class_exists('WordCloud_CloudsListTabAdminPage') )

