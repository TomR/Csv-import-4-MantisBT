<?php

# Custom strings

# Select the good file
if( lang_get_current() == 'french' )
	include( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'custom_strings_inc_fr.php' );
else
	include( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'custom_strings_inc_en.php' );

?>
