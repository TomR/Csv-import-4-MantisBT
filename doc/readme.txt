Mantis Issue Importer
=====================

Version | Author                 | Action
------------------------------------------
 1.1.3  | Stéphane Veyret        | Make the strings "private" with plugin_lang_get
        |                        | Add french version
 1.1.2  | Udo Sommer and Cas Nuy | Transformed into plugin
 1.0    | Stéphane Veyret        | Improvements
(old)   | ave                    | Importer creation

The licence of CSV Import remains untouched, means GPL,
as stated in THE GPL.

Installation instructions are the same as installing a plugin.


Function:
_________

Imports/update one or more issues from a CSV text file into Mantis.


Requirements:
_____________

Made for and tested against Mantis version 1.2.0


Installation:
_____________

1. Install as any other plugin.

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