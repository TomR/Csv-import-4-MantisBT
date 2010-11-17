<?php
	# Mantis - a php based bugtracking system

	require_once( 'core.php' );

	access_ensure_project_level( config_get( 'import_issues_threshold' ) );

	html_page_top1( lang_get( 'manage_import_issues' ) );
	html_page_top2();
?>

<br />

<?php
    #@@@ u.sommer changed: "include( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'import_issues_inc.php' );"
    require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'import_issues_inc.php' );

	# Look if the import file name is in the posted data
	$f_import_file = gpc_get_file( 'import_file', -1 );

	# File is not given yet, ask for it
	if( $f_import_file == -1 )
	{
		$t_max_file_size = (int)min( ini_get_number( 'upload_max_filesize' ), ini_get_number( 'post_max_size' ),
		 config_get( 'max_file_size' ) );
?>

<?php #@@@ u.sommer changed: "<form method="post" enctype="multipart/form-data" action="import_issues_page.php">" ?>
<form method="post" enctype="multipart/form-data" action="<?php echo plugin_page('import_issues_page')?>">
    <div align="center">
        <table class="width50" cellspacing="1">
            <tr>
            	<td class="form-title" colspan="2">
<?php
                    echo lang_get( 'import_issues_file' )
?>
            	</td>
            </tr>

            <?php #@@@ u.sommer added ?>
            <tr class="row-1">
                <td class="category" style="text-align:center">
                    <input name="edt_cell_separator" type="text" size="15" maxlength="1" value="<?php echo config_get( 'csv_separator' )?>" style="text-align:center">
                </td>
                <td>
                    <?php echo lang_get( 'import_file_format_col_spacer' ) ?>
                </td>
            </tr>

            <?php
                # @@@ u.sommer added
                #       What do we want to do: If one skips first line, the content of the first line is used as column header description.
                #       If one does not want to skip first line, the previous format is used as column description.
                #       ("Column # 1", "Column # 2", "Column # 3", and so on...)
            ?>
            <tr class="row-1">
                <td class="category" colspan="1" style="text-align:center">
                    <input type="checkbox" name="cb_skip_first_line" value="1" checked>
                </td>
                <td>
                    <?php echo lang_get( 'import_skip_first_line' ) ?>
                </td>
            </tr>

            <?php #@@@ u.sommer added ?>
            <tr class="row-1">
                <td class="category" colspan="1" style="text-align:center">
                    <input type="checkbox" 	name="cb_skip_blank_lines"	value="1" checked>
                </td>
                <td>
                    <?php echo lang_get( 'import_skip_blank_lines' ) ?>
                </td>
            </tr>

            <?php #@@@ u.sommer added ?>
            <tr class="row-1">
                <td class="category" style="text-align:center">
                    <input type="checkbox" 	name="cb_trim_blank_cols" value="1" checked>
                </td>
                <td colspan="4">
                    <?php echo lang_get( 'import_skip_blank_columns' ) ?>
                </td>
            </tr>

            <tr class="row-1">
            	<td class="category" width="15%">
            		<?php echo lang_get( 'select_file' ) ?><br />
            		<?php echo '<span class="small">(' . lang_get( 'max_file_size' ) . ': ' . number_format( $t_max_file_size/1000 ) . 'k)</span>'?>
            	</td>
            	<td width="85%" colspan="2">
            		<input type="hidden" name="max_file_size" value="<?php echo $t_max_file_size ?>" />
            		<input type="file" name="import_file" size="40" />
            		<input type="submit" class="button" value="<?php echo lang_get( 'upload_file_button' ) ?>" />
            	</td>
            </tr>
        </table>
    </div>
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
		$t_separator = gpc_get_string('edt_cell_separator');        # @@@ u.sommer: added
        $t_trim_columns = gpc_get_bool( 'cb_trim_blank_cols' );     # @@@ u.sommer: added
        $t_trim_rows = gpc_get_bool( 'cb_skip_blank_lines' );       # @@@ u.sommer: added
        $t_skip_first = gpc_get_bool( 'cb_skip_first_line' );       # @@@ u.sommer: added

		$t_column_count = -1;
		$t_column_title = array();
		if( count( $t_file_content ) <= 0 )
		{
			error_parameters( lang_get( 'import_error_nolines' ) );
			trigger_error( ERROR_IMPORT_FILE_FORMAT, ERROR );
		};

        $t_file_line_num = -1;
        foreach( $t_file_content as $t_key => &$t_file_line )
        {
            $t_file_line_num++;
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
                elseif # @@@ u.sommer added
                (
                    # @@@ u.sommer: The row is empty and the user wants to skip it...
                    $t_trim_rows && (trim(eregi_replace($t_separator , '' , implode($t_separator , $t_elements))) == '')
                )
                {
                    if( $t_skip_first )
                    {
                        # @@@ u.sommer: If we are here, we can not skip the first line, even if the user wanted this.
                        #       But if this is the case, we trigger an error because we are not able to get column descriptions.

                        # ToDo: localization
                        #error_parameters( lang_get( 'import_error_manycols' ) );
                        error_parameters(  'LANG: Wenn SkipFirst aktiv ist, dürfen Spaltenheader nicht leer sein!' );
                        trigger_error( ERROR_IMPORT_FILE_FORMAT, ERROR );
                    }
                    else
                    {
                        # @@@ u.sommer: "Skip first" is not active, delete the line. 
                        #       Here we can, because if $t_skip_first == false the standard descriptions are used. (
                        #       ("Column # 1", "Cloumn # 2" , and so on.)
                        $t_file_line = null;
                    };
                };

                if( $t_trim_columns )   # @@@ u.sommer added
                {
                    # @@@ u.sommer: All data from the first empty column header is skipped.
                    for( $i = 0 ; $i < count($t_elements) ; $i++ )
                    {
                        if( trim($t_elements[$i]) == '' )
                        {
                            $t_elements = array_slice( $t_elements , 0 , $i );
                            break 1;
                        };
                    };
                };
                $t_column_count = count( $t_elements );
                $t_column_title = $t_elements;
            }
            elseif( $t_column_count != count( $t_elements ) )
            {
                if( $t_trim_columns )   # @@@ u.sommer added
                {
                    # @@@ u.sommer: All data from the first empty column header will be skippt in all rows. (Cutting the table)
                    //$t_elements = array_slice( $t_elements , 0 , $t_column_count );
                    $t_row = explode( $t_separator , $t_file_line );
                    $t_row = array_slice( $t_row , 0 , $t_column_count );
                    $t_file_line = implode( $t_separator , $t_row );
                }
                else
                {
                    error_parameters( sprintf( lang_get( 'import_error_col_count' ),  $t_separator) );
                    trigger_error( ERROR_IMPORT_FILE_FORMAT, ERROR );
                };
            };

            if # @@@ u.sommer added
            (
                trim(eregi_replace($t_separator , '' , implode($t_separator , $t_elements))) == ''
            )
            {
                if( $t_trim_rows )
                {
                    unset( $t_file_content[$t_key] );
                    $t_file_content = array_merge($t_file_content);
                    //$t_file_content = array_splice( $t_file_content , $t_file_line_num , 1 );
                };
            };
        };

        # @@@ u.sommer: write formated file back:
        if( is_writable($f_import_file['tmp_name']) )
        {
            if( $handle = fopen($f_import_file['tmp_name'],"wb") )
            {
                foreach( $t_file_content as &$t_file_line )
                {
                    $t_written = fwrite( $handle , $t_file_line."\n" );
                };
                fclose( $handle );
            }
            else
            {
                error_parameters( 'LANG: Das Handle auf die CSV-Datei ist ungültig. Gelöscht?' );
                trigger_error( ERROR_IMPORT_FILE_ERROR, ERROR );
            };
        }
        else
        {
            error_parameters( 'LANG: Die Datei ist schreibgeschützt!' );
            trigger_error( ERROR_IMPORT_FILE_ERROR, ERROR );
        };

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
            };
		};

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
                };
			};
		};
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
        if( !$t_skip_first )
        {
?>
            <td><?php echo sprintf( lang_get( 'import_column_number' ),  $i + 1) ?></td>
<?php
        }
        else
        {
?>
            <td><?php echo prepare_output($t_column_title[$i]) ?></td>
<?php
        };
    };
	?>
	</tr>
<?php
		# Display first file lines
        $t_first_run = true;
		$t_display_max = 3;

		foreach( $t_file_content as &$t_file_line )
		{
            if( $t_first_run && $t_skip_first )
            {
                $t_first_run = false;
                continue;
            };

            echo '<tr ' . helper_alternate_class() . '>';
            if( --$t_display_max < 0 )
            {
                for( $i = 0; $i < $t_column_count; $i++ )
                {
                    echo '<td>&hellip;</td>';
                };
                break;
            }
            else
            {
                foreach( read_csv_row( $t_file_line, $t_separator ) as $t_element )
                {
                    echo '<td>' . prepare_output($t_element) . '</td>';
                };
            };
            echo '</tr>';
		};
?>
</table>

<br />
<!-- Set fields form -->
<div align="center">
<table class="width50" cellspacing="1">
    <?php
    # @@@ u.sommer commented out. API function "plugin_page()" instead of direct linking.
    #       <form method="post" action="import_issues.php">
    ?>
	<form method="post" action="<?php echo plugin_page('import_issues')?>">
	<tr>
		<td class="form-title" colspan="2">
			<?php echo lang_get( 'import_issues_columns' ) ?>
		</td>
	</tr>

    <?php
    # @@@ u.sommer: Commented out. See the comment above "cb_skip_first_line"
    /*
	<tr <?php echo helper_alternate_class() ?>>
		<td class="category">
			<?php echo lang_get( 'import_skip_first_line' ); ?>
		</td>
		<td>
			<input type="checkbox" name="skip_first" value="1" <?php check_checked( $t_title_is_fields ) ?> />
		</td>
	</tr>
           */
    ?>
<?php
    for( $t_fields = $g_all_fields, $i = 0; $i < $t_column_count; next( $t_fields ), $i++ )
    {
        ?>
    	<tr <?php echo helper_alternate_class() ?>>
    		<td class="category">
    			<?php
                    if( !$t_skip_first )    # @@@ u.sommer added
                    {
                        echo sprintf( lang_get( 'import_column_number' ),  $i + 1);
                    }
                    else
                    {
                        #echo string_html_entities($t_column_title[$i]);
                        echo prepare_output($t_column_title[$i]);
                    };
                ?>
    		</td>
    		<td>
    			<select name="columns[]">
    				<?php print_all_fields_option_list( $t_title_is_fields ? $t_column_title[$i] : key( $t_fields ) ) ?>
    			</select>
    		</td>
    	</tr><?php
    };
?>

	<tr>
		<td>&nbsp;</td>
		<td>
            <?php # @@@ u.sommer: added following 4 lines ?>
            <input type="hidden" name="cb_skip_first_line" value="<?php echo $t_skip_first ?>" />
            <input type="hidden" name="cb_skip_blank_lines" value="<?php echo $t_trim_rows ?>" />
            <input type="hidden" name="cb_trim_blank_cols" value="<?php echo $t_trim_columns ?>" />
            <input type="hidden" name="edt_cell_separator" value="<?php echo $t_separator ?>" />

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
