<?php
class Csv_importPlugin extends MantisPlugin
{
	function register() {
		$this->name			= 'Csv_import' ;
		$this->description	= 'import CSV file';
		$this->version		= '1.1.2';
		$this->requires   	= array('MantisCore'       => '1.2.0',);
		$this->author		= 'Udo Sommer, see readme for further details!';
		$this->contact		= '';
		$this->url			= '';
		$this->page			= 'config';
	}

	 	/*** Default plugin configuration.	 */
	function config() {
		return array(
			'import_issues_threshold'	=> 70 ,
			);
	}

	
    function hooks() {
        $hooks = array
        (
                'EVENT_MENU_MANAGE' => 'csv_import_menu',
        );
        return $hooks;
    }

	function csv_import_menu() {
		return array
        (
				'<a href="' . plugin_page( 'import_issues_page' ) . '">' . lang_get( 'manage_import_issues_link' ) . '</a>',
		);
	}
}