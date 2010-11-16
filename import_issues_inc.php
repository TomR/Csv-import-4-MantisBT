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
	
  # All column names that can be used with this project
  $g_all_fields = config_get( 'csv_import_columns' );
  if( count( $g_all_fields ) == 0 )
  {
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
  	);
  	foreach( custom_field_get_linked_ids( $g_project_id ) as $t_id )
    {
      $g_all_fields[] =
				$g_custom_field_identifier . custom_field_get_field( $t_id, 'name' );
  	}
	}
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

?>
