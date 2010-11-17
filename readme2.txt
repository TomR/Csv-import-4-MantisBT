This version of csv Import is extended by Udo Sommer.

The CSV Import is based uppon csv_import-1.0, but is now "pluginified".
This means it is able to use Mantis plugin system. (Mantis Version > 1.2.0)
The Mantis language system is now supported, too.
(Feel free to extend the language packs for CSV Import.)

The licence of CSV Import remains untouched, means GPL,
as stated in THE GPL.

Installation instructions are the same as installing a plugin.
You do not need to follow the installation instructions in readme.txt
any longer.

CSV Import is not able to create the needed configuration options by itself, yet.
You had to create "import_issues_threshold" (type of int) on "adm_config_report.php"
by hand, giving it the threshold value of whatever you need.
(I believe 70 is a good value, because it is normally MANAGERs threshold.)

Last but not least:
My comments are prefixed with "#@@@ u.sommer".
My work was done on a german MS-Windows station, testing environment:
- http server: Apache (from the xampp package)
- MySQL (xampp package)
As of this circumstance, I fear that many parts of my work could happen to do not work on other systems.
(Please make it better working on other machines, if needed...)
My coding style is not what the Mantis team will like to see,
but intentionally this work was done only for my company. Sorry for this.

Do not forget to read README.TXT!