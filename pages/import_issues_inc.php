<?php
	# Mantis - a php based bugtracking system
?>
<?php
	require_once( 'core.php' ) ;

	# The current project
	$g_project_id = helper_get_current_project();
	if( $g_project_id == ALL_PROJECTS )
    {
		trigger_error( ERROR_IMPORT_ALL_PROJECT, ERROR );
	}

	# This identify a custom field
	$g_custom_field_identifier = 'custom_';
	
 
    if( config_is_set('csv_import_columns') )
    {
        $g_all_fields = array();
        $g_all_fields = config_get( 'csv_import_columns' );
    };
    
    $g_all_fields = array(
        'reporter_id',
        'summary',
        'description',
        'steps_to_reproduce',
        'additional_information',
        'category',
        'priority',
        'severity',
        'reproducibility',
        'date_submitted',
        'last_updated',
        'handler_id',
        'status',
        'resolution',
        'os',
        'os_build',
        'platform',
        'version',
        'projection',
        'eta',
        'fixed_in_version',
        'target_version',
        'build',
        'view_state',
        'id',
		'due_date',
    );
    
    $g_all_fields = array_unique($g_all_fields);
    
    foreach( custom_field_get_linked_ids( $g_project_id ) as $t_id )
    {
        $g_all_fields[] =
            $g_custom_field_identifier . custom_field_get_field( $t_id, 'name' );
    };
    $g_all_fields[] = 'ignore_column'; # @@@ u.sommer added
	$g_all_fields = prepare_all_fields_array( $g_all_fields );

	# --------------------
	function prepare_all_fields_array( $p_all_fields )
	{
		global $g_custom_field_identifier;

		# Correspondance between field names and language identifiers
		$t_translated_fields = array(
            'reporter_id' => 'reporter',
            'last_updated' => 'updated',
            'handler_id' => 'assigned_to',
            'os_build' => 'os_version',
            'version' => 'product_version',
            'view_state' => 'view_status',
		);

		# Create the translated array
		$t_fields = array();
		foreach( array_unique( $p_all_fields ) as $t_element )
		{
			$t_lang_id = $t_element;
			if( strpos( $t_element, $g_custom_field_identifier ) === 0 )
				$t_lang_id = substr( $t_element, strlen( $g_custom_field_identifier ) );
			elseif( isset( $t_translated_fields[$t_element] ) )
				$t_lang_id = $t_translated_fields[$t_element];
			$t_fields[$t_element] = lang_get_defaulted( $t_lang_id );
		}

		# Set to all fields
		return $t_fields;
	}

	# --------------------
	function print_all_fields_option_list( $p_selected )
	{
		global $g_all_fields;
		foreach( $g_all_fields as $t_element => $t_translated )
		{
			echo "<option value=\"$t_element\"";
			check_selected( $t_element, $p_selected );
			echo ">" . $t_translated . "</option>";
		}
	}

	# --------------------
	function csv_string_unescape( $p_string )
	{
		$t_wo_quotes = preg_replace( '/\A"(.*)"\z/sm', '${1}', $p_string );
		if( $t_wo_quotes !== $p_string )
			$t_wo_quotes = str_replace( '""', '"', $t_wo_quotes );
		return $t_wo_quotes;
	}

	# --------------------
	function read_csv_file( $p_filename )
	{
		$t_file_content = file_get_contents( $p_filename );
		preg_match_all('/\G((?:[^"\r\n]+|"[^"]*")+)[\r|\n]*/sm', $t_file_content, $t_file_rows);
		return $t_file_rows[1];
	}

	# --------------------
	function read_csv_row( $p_file_row, $p_separator )
	{
		preg_match_all('/\G(?:\A|\\' . $p_separator . ')([^"\\' . $p_separator . ']+|(?:"[^"]*")*)/sm',
		 $p_file_row, $t_row_element);

		# Return result
		return array_map( 'csv_string_unescape', $t_row_element[1] );
	}

	function category_get_id_by_name_ne( $p_category_name, $p_project_id )
    {

		$t_category_table = db_get_table( 'mantis_category_table' );
		$t_project_name = project_get_name( $p_project_id );
	
		$t_query = "SELECT id FROM $t_category_table
				WHERE name=". db_param() . " AND project_id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_category_name, (int) $p_project_id ) );
		$t_count = db_num_rows( $t_result );
		if ( 1 > $t_count ) {
 			return false;
		}

		return db_result( $t_result );
	}
    
    function prepare_output( $t_string , $t_encode_only = false )
    {
        return string_html_specialchars( utf8_encode($t_string) );
    }
 
    function get_csv_import_category_id( $t_project_id , $t_category_name )  
    {
        project_ensure_exists( $t_project_id );
        
        $t_category_id = category_get_id_by_name_NE($t_category_name , $t_project_id);
        if( !$t_category_id )
        {
            return category_add( $t_project_id, $t_category_name );
        }
        else
        {
            return $t_category_id;
        };
        
        # Just in case...
        return null;
    }
	
		# --------------------
	# Breaks up an enum string into num:value elements
	function explode_enum_string( $p_enum_string ) {
		return explode( ',', $p_enum_string );
	} 
	# --------------------
	# Given one num:value pair it will return both in an array
	# num will be first (element 0) value second (element 1)
	function explode_enum_arr( $p_enum_elem ) {
		return explode( ':', $p_enum_elem );
	} 
	?>