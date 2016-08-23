<?php
/**
 * Controls the admin page "Clouds".
 * 
 * @package    word-cloud
 * @subpackage admin-pages/pages
 * @author     Crystal Barton <atrus1701@gmail.com>
 */

if( !class_exists('WordCloud_CloudsAdminPage') ):
class WordCloud_CloudsAdminPage extends APL_AdminPage
{
	
	/**
	 * Creates an OrgHub_UsersAdminPage object.
	 */
	public function __construct(
		$name = 'word-clouds',
		$menu_title = 'Word Clouds',
		$page_title = 'Word Clouds',
		$capability = 'administrator' )
	{
		parent::__construct( $name, $menu_title, $page_title, $capability );
	
		$this->display_page_tab_list = false;
		$this->add_tab( new WordCloud_CloudsListTabAdminPage($this) );
		$this->add_tab( new WordCloud_CloudsAddTabAdminPage($this) );
		$this->add_tab( new WordCloud_CloudsEditTabAdminPage($this) );
		$this->add_tab( new WordCloud_CloudsDeleteTabAdminPage($this) );
	}
	
} // class WordCloud_CloudsAdminPage extends APL_AdminPage
endif; // if( !class_exists('WordCloud_CloudsAdminPage') )

