Mantis Issue Importer
=====================

History:
_________

Version | Author                 | Action
--------------------------------------------------------------------------------------
 1.2.0  | lionheart33806         | import_issues_page.php splitted to become
        |                        |  import_issues_page_init.php and import_issues_page_col_set.php
        |                        | Can create unknown categories
        |                        | Little JS to use tab as separator
 1.1.5  | lionheart33806         | Code closer of mantis coding conventions
        |                        | "all projects" categories reusable
        |                        | Can import "submitted date" with DD/MM/YYYY format
        |                        | Checkbox for alternative import
        |                        |     because of double quotes
        |                        | Added helper_begin_long_process() for very long imports
 1.1.4  | Stéphane Veyret        | Add german version of jojow 
 1.1.3  | Stéphane Veyret        | Make the strings "private" with plugin_lang_get
        |                        | Add french version
 1.1.2  | Udo Sommer and Cas Nuy | Transformed into plugin
 1.0    | Stéphane Veyret        | Improvements
(old)   | ave                    | Importer creation


Know Issues:
____________

* "ignore_column" can only be used once.


Licence:
_________

The licence of CSV Import remains untouched, means GPL,
as stated in THE GPL.


Installation:
_____________

Installation instructions are the same as installing a plugin.


Function:
_________

Imports/update one or more issues from a CSV text file into Mantis.


Requirements:
_____________

Made for and tested against Mantis version 1.2.0


Usage:
______

Take care that this importing functionality can make a mess in your database. It is recommanded that you make
a backup of your database before importing.

1. In the manager menu, select Import Issues.

2. Select the file to import. Your file must be in a correct CSV format, and must only contain importable
   columns. The first line of the file can be a title line. If you are using standard column names, or the
	 name of the columns as given in your current language, it will be reconized by the importer.

3. Select the "Import file" button.

4. If the importer did not detect your columns automatically, select them. If your first row is a title, do
   not forget to select "Skip first line".

5. Select the "Import" button.

6. Review the results to ensure they are as expected.
