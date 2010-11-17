<?php
# Mantis - a php based bugtracking system

# Copyright (C) 2002 - 2008  Mantis Team   - mantisbt-dev@lists.sourceforge.net

# Mantis is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# Mantis is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Mantis.  If not, see <http://www.gnu.org/licenses/>.

require_once( 'core.php' );
$t_core_path = config_get( 'core_path' );
# require_once( $t_core_path . 'auth_api.php' );
# require_once( $t_core_path . 'access_api.php' );
require_once( config_get( 'class_path' ) . 'MantisPlugin.class.php' );

class csv_importPlugin extends MantisPlugin
{
	function register() {
		$this->name			= lang_get( 'manage_import_issues_link' );
		$this->description	= lang_get( 'import_issues_file' );
		$this->page			= 'import_issues_page';

		$this->version		= '1.0.1';
		$this->requires		= array(
			'MantisCore' => '1.2.0',
		);

		$this->author		= 'Udo Sommer, see readme for further details!';
		$this->contact		= '';
		$this->url			= '';
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