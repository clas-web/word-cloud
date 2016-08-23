<?php


class WordCloud_Output
{
	/**
	 * The only instance of the current model.
	 * @var  WordCloud_Model
	 */	
	private static $instance = null;
	
	/**
	 * The path to the log file, if writing to log.
	 * @var  string
	 */	
	public $log_file = null;

	/**
	 * True if a datetime should be included at beginning of text line.
	 * @var  bool
	 */	
	public $include_datetime = false;
		
	
	/**
	 * Private Constructor.  Needed for a Singleton class.
	 */
	protected function __construct() { } 
	
	
	/**
	 * Get the only instance of this class.
	 * @return  WordCloud_Output  A singleton instance of the output class.
	 */
	public static function get_instance()
	{
		if( self::$instance	=== null ) {
			self::$instance = new WordCloud_Output();
		}
		return self::$instance;
	}

	
	public function write( $text )
	{
		if( $this->log_file ) {
			file_put_contents( $this->log_file, $text, FILE_APPEND );
		}
		
		echo $text;
	}
	
	public function write_line( $text = '' )
	{
		if( $this->include_datetime ) {
			$this->write_datetime();
		}
		
		$this->write( $text . "\r\n" );
	}
	
	public function write_datetime()
	{
		$this->write( '[ ' . date( 'Y-m-d H:i:s' ) . ' ]  ' );
	}
	
}

