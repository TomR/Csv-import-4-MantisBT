Mantis Issue Importer
=====================

(inspired from the importer posted by "ave")


Function:
_________

Imports/update one or more issues from a CSV text file into Mantis.


Requirements:
_____________

Made for and tested against Mantis version 1.1.1 and 1.1.2.


Installation:
_____________

1. Unzip the distribution into a temporary folder.

2. Copy files into mantis root folder:
   - import_issues.php
   - import_issues_inc.php
   - import_issues_page.php
   
3. Edit custom_strings_inc.php located in the mantis root folder.
   If this file does not exist, just copy the custom_strings_inc*.php files from the distribution into the
	 mantis root folder.
   If this file already exists, add the contents of the needed custom_strings_inc*.php from the distribution
	 into the original and save.

4. Edit custom_constant_inc.php located in the mantis root folder.
   If this file does not exist, just copy custom_constant_inc.php from the distribution into the mantis root
	 folder.
   If this file already exists, add the contents of custom_constant_inc.php from the distribution into the
	 original and save. Check the constant values are not already used in you constant file, change them if
	 needed.

5. Edit config_inc.php
   Add the following lines:

	# A configuration option that identifies the columns that can be used in the CSV import.
	# In Mantis 1.1, this option can be overriden using the Generic Configuration screen.
	# Keep empty for automatic construction with all available fields
	$g_csv_import_columns = array ();

	# Set the issue import threshold
	$g_import_issues_threshold = MANAGER;

6. Edit core/html_api.php and find the "function print_menu()". 
   Just after the lines:

				# Report Bugs
				if ( access_has_project_level( config_get( 'report_bug_threshold' ) ) ) {
					$t_menu_options[] = string_get_bug_report_link();
				}

   add the following lines:

				# Import Bugs
				if ( access_has_project_level( config_get( 'import_issues_threshold' ) ) ) {
					if ( ALL_PROJECTS != helper_get_current_project() ) {
						$t_menu_options[] = '<a href="import_issues_page.php">' . lang_get( 'manage_import_issues_link' ) . '</a>';
					} else {
						$t_menu_options[] = '<a href="login_select_proj_page.php?ref=import_issues_page.php">' . lang_get( 'manage_import_issues_link' ) . '</a>';
					}
				}

   Alternatively, you can use the html_api.patch to do this automatically if you have Mantis 1.1.2. Command
	 line for that is:

   patch core/html_api.php html_api.patch


Usage:
______

Take care that this importing functionality can make a mess in your database. It is recommanded that you make
a backup of your database before importing.

1. In the main menu, select Import Issues.

2. Select the file to import. Your file must be in a correct CSV format, and must only contain importable
   columns. The first line of the file can be a title line. If you are using standard column names, or the
	 name of the columns as given in your current language, it will be reconized by the importer.

3. Select the "Import file" button.

4. If the importer did not detect your columns automatically, select them. If your first row is a title, do
   not forget to select "Skip first line".

5. Select the "Import" button.

6. Review the results to ensure they are as expected.


TODO:
_____

When selecting columns, add the ability to ignore some columns of the CSV file.