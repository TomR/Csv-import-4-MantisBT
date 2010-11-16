<?php
	# Mantis - a php based bugtracking system

	require_once( 'core.php' );

	access_ensure_project_level( config_get( 'import_issues_threshold' ) );

	html_page_top1( lang_get( 'manage_import_issues' ) );
	html_page_top2();
?>

<br />

<?php
	include( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'import_issues_inc.php' );

	# Look if the import file name is in the posted data
	$f_import_file = gpc_get_file( 'import_file', -1 );

	# File is not given yet, ask for it
	if( $f_import_file == -1 )
	{
		$t_max_file_size = (int)min( ini_get_number( 'upload_max_filesize' ), ini_get_number( 'post_max_size' ),
		 config_get( 'max_file_size' ) );
?>

<form method="post" enctype="multipart/form-data" action="import_issues_page.php">
<table class="width100" cellspacing="1">
<tr>
	<td class="form-title" colspan="2">
<?php
	echo lang_get( 'import_issues_file' )
?>
	</td>
</tr>
<tr class="row-1">
	<td class="category" width="15%">
		<?php echo lang_get( 'select_file' ) ?><br />
		<?php echo '<span class="small">(' . lang_get( 'max_file_size' ) . ': ' . number_format( $t_max_file_size/1000 ) . 'k)</span>'?>
	</td>
	<td width="85%">
		<input type="hidden" name="max_file_size" value="<?php echo $t_max_file_size ?>" />
		<input type="file" name="import_file" size="40" />
		<input type="submit" class="button" value="<?php echo lang_get( 'upload_file_button' ) ?>" />
	</td>
</tr>
</table>
</form>

<?php
	}

	# File is defined, go to step 2
	else
	{
		# Check fields are set
		if ( is_blank( $f_import_file['tmp_name'] ) || ( $f_import_file['size'] == 0 ) )
	  {
			trigger_error( ERROR_EMPTY_FIELD, ERROR );
		}

		# File analysis
		$t_file_content = read_csv_file( $f_import_file['tmp_name'] );
		$t_separator = config_get( 'csv_separator' );
		$t_column_count = -1;
		$t_column_title = array();
		if( count( $t_file_content ) <= 0 )
		{
			error_parameters( lang_get( 'import_error_nolines' ) );
			trigger_error( ERROR_IMPORT_FILE_FORMAT, ERROR );
		}
		foreach( $t_file_content as $t_file_line )
		{
			$t_elements = read_csv_row( $t_file_line, $t_separator );
			if( $t_column_count < 0 )
			{
				if( count( $t_elements ) <= 1 )
				{
					error_parameters( sprintf( lang_get( 'import_error_noseparator' ),  $t_separator) );
					trigger_error( ERROR_IMPORT_FILE_FORMAT, ERROR );
				}
				elseif( count( $t_elements ) > count( $g_all_fields ) )
				{
					error_parameters( lang_get( 'import_error_manycols' ) );
					trigger_error( ERROR_IMPORT_FILE_FORMAT, ERROR );
				}
				$t_column_count = count( $t_elements );
				$t_column_title = $t_elements;
			}
			elseif( $t_column_count != count( $t_elements ) )
			{
				error_parameters( sprintf( lang_get( 'import_error_col_count' ),  $t_separator) );
				trigger_error( ERROR_IMPORT_FILE_FORMAT, ERROR );
			}
		}

		# Move file
    $t_file_name = tempnam( '', 'tmp' );
		move_uploaded_file( $f_import_file['tmp_name'], $t_file_name );

		# Column analysis
		$t_title_is_fields = true;
		$t_column_title = array_map( 'trim', $t_column_title );
		foreach( $t_column_title as $t_element )
		{
		  if( !isset( $g_all_fields[$t_element] ) )
		  {
		    $t_title_is_fields = false;
		    break;
      }
		}
		if( !$t_title_is_fields )
		{
			$t_title_is_fields = true;
			foreach( $t_column_title as $t_key => $t_element )
			{
				$t_found_key = array_search( $t_element, $g_all_fields );
			  if( $t_found_key !== false )
			  {
			  	$t_column_title[$t_key] = $t_found_key;
				}
				else
				{
			    $t_title_is_fields = false;
			    break;
				}
			}
		}
		$t_title_is_fields &= count( $t_column_title ) == count( array_unique( $t_column_title ) );
?>

<!-- File extraction -->
<table class="width100" cellspacing="1">
	<tr>
		<td class="form-title" colspan="<?php echo $t_column_count ?>">
				<?php echo $f_import_file['name'] ?>
		</td>
	</tr>
	<tr class="row-category">
	<?php
    for( $i = 0; $i < $t_column_count; $i++ )
    {
?>
		<td><?php echo sprintf( lang_get( 'import_column_number' ),  $i + 1) ?></td>
<?php
    }
	?>
	</tr>
<?php
		# Display first file lines
		$t_display_max = 3;
		foreach( $t_file_content as $t_file_line )
		{
?>
	<tr <?php echo helper_alternate_class() ?>>
<?php
      if( --$t_display_max < 0 )
      {
		    for( $i = 0; $i < $t_column_count; $i++ )
		    {
?>
		<td>&hellip;</td>
<?php
		    }
		    break;
      }
      else
      {
        foreach( read_csv_row( $t_file_line, $t_separator ) as $t_element )
        {
?>
		<td><?php echo string_html_specialchars( $t_element ) ?></td>
<?php
        }
      }
?>
	</tr>
<?php
		}
?>
</table>

<br />
<!-- Set fields form -->
<div align="center">
<table class="width50" cellspacing="1">
	<form method="post" action="import_issues.php">
	<tr>
		<td class="form-title" colspan="2">
			<?php echo lang_get( 'import_issues_columns' ) ?>
		</td>
	</tr>

	<tr <?php echo helper_alternate_class() ?>>
		<td class="category">
			<?php echo lang_get( 'import_skip_first_line' ) ?>
		</td>
		<td>
			<input type="checkbox" name="skip_first" value="1" <?php check_checked( $t_title_is_fields ) ?> />
		</td>
	</tr>

<?php
    for( $t_fields = $g_all_fields, $i = 0; $i < $t_column_count; next( $t_fields ), $i++ )
		{
?>

	<tr <?php echo helper_alternate_class() ?>>
		<td class="category">
			<?php echo sprintf( lang_get( 'import_column_number' ),  $i + 1) ?>
		</td>
		<td>
			<select name="columns[]">
				<?php print_all_fields_option_list( $t_title_is_fields ? $t_column_title[$i] : key( $t_fields ) ) ?>
			</select>
		</td>
	</tr>

<?php
		}
?>

	<tr>
		<td>&nbsp;</td>
		<td>
			<input type="hidden" name="import_file" value="<?php echo $t_file_name ?>" />
			<input type="submit" class="button" value="<?php echo lang_get( 'import_file_button' ) ?>" />
		</td>
	</tr>
	</form>
</table>
</div>

<?php
	} # Step 2, select fields

?>

<?php html_page_bottom1( __FILE__ ) ?>
