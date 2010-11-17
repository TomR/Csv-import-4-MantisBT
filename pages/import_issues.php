<?php
	# Mantis - a php based bugtracking system

	require_once( 'core.php' );
    
	$t_core_path = config_get( 'core_path' );
	require_once( $t_core_path . 'category_api.php' );
	require_once( $t_core_path . 'database_api.php' );    
    require_once( $t_core_path . 'user_api.php' );
    
    #@@@ u.sommer: I've done changes in configuration system for this plugin.
    #       I think, it is better to save this option as an DB config entry.
    #       Now one can configure the import threshold through "adm_config_report.php"
    #       ToDo: Initial configuration of options in database. At the moment i've done this by hand.
    #
    #       The int_value can be looked up in the language files, for e.g. "MANAGER" has in "strings_german.txt" the value 70.
    access_ensure_project_level( config_get( 'manage_site_threshold' ) );

    #@@@ u.sommer: "require_once".
	#include( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'import_issues_inc.php' );
    require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'import_issues_inc.php' );

	# Check a project is selected
	$g_project_id = helper_get_current_project();
	if( $g_project_id == ALL_PROJECTS )
    {
		trigger_error( ERROR_IMPORT_ALL_PROJECT, ERROR );
	};

	# Get submitted data
	$f_import_file = gpc_get_string( 'import_file' );
	$f_columns = gpc_get_string_array( 'columns' );
	$f_skip_first = gpc_get_bool( 'cb_skip_first_line' );           # @@@ u.sommer: changed "$f_skip_first = gpc_get_bool( 'skip_first' );"    
    $f_skip_blank_lines = gpc_get_bool( 'cb_skip_blank_lines' );    # @@@ u.sommer: added
    $f_trim_columns = gpc_get_bool( 'cb_trim_blank_cols' );         # @@@ u.sommer: added
    $f_separator = gpc_get_string('edt_cell_separator');            # @@@ u.sommer: moved to here, because we use the submitted separator. Previous under "Import file content"
    
	# Check given parameters - File
	$t_file_content = array();
	if( file_exists( $f_import_file ) )
	{
		$t_file_content = read_csv_file( $f_import_file );
		#unlink( $f_import_file );
	}
	else
	{
		error_parameters( lang_get( 'import_error_file_not_found' ) );
		trigger_error( ERROR_IMPORT_FILE_FORMAT, ERROR );
	};

	# Check given parameters - Columns
	if( count( $f_columns ) <= 0 )
	{
		trigger_error( ERROR_EMPTY_FIELD, ERROR );
	}
	elseif( count( $f_columns ) != count( array_unique( $f_columns ) ) )
	{
		error_parameters( lang_get( 'import_error_col_multiple' ) );
		trigger_error( ERROR_IMPORT_FILE_FORMAT, ERROR );
	};
    
    # @@@ u.sommer added.
    #       See "http://de.php.net/manual/de/function.array-search.php#80578" for author.
    #       (Thanks Fyorl!)
    function array_isearch( $str , $array ) {
        foreach($array as $k => $v) 
        {
            if(strcasecmp($str, $v) == 0) 
                return $k;
        };
        return false;
    }
    
	#-----------------------
	function get_enum_column_value( $p_name, $p_row, $p_default )
	{
		$t_value = get_column_value( $p_name, $p_row );
		if( is_blank( $t_value ) ) return $p_default;

		# First chance, search element in language enumeration string
		$t_element_enum_string = lang_get( $p_name . '_enum_string' );
		$t_arr = explode_enum_string( $t_element_enum_string );
		$t_arr_count = count( $t_arr );
		for( $i = 0; $i < $t_arr_count; $i++ )
		{
			$elem_arr = explode_enum_arr( $t_arr[$i] );
			if( $elem_arr[1] == $t_value )
			{
				return $elem_arr[0];
			}
            #@@@ u.sommer added:
            elseif( $elem_arr[0] == $t_value )  
            {
            	return $elem_arr[0];
            };
		};

		# Second chance, search element in configuration enumeration string
		$t_element_enum_string = config_get( $p_name . '_enum_string' );
		$t_arr = explode_enum_string( $t_element_enum_string );
		$t_arr_count = count( $t_arr );
		for( $i = 0; $i < $t_arr_count; $i++ )
		{
			$elem_arr = explode_enum_arr( $t_arr[$i] );
			if( $elem_arr[1] == $t_value )
			{
				return $elem_arr[0];
			}
            #@@@ u.sommer added:
            elseif( $elem_arr[0] == $t_value )  
            {
            	return $elem_arr[0];
            };
		};
        
		
		#@@@ u.sommer changed: 
        #       #  Last chance, try to use the numeric value
        #       # return intval( $t_value );
        #       In this case, we do not use the numeric value, because, if someone has a user defined state oder string in this column
        #       which mantis is not aware of, we can not enum this and then it results in an "@0@" (null value),
        #       or later on in bug view, mantis is not able to translate the enum value back to enum string.
        #       Instead we use the default value.
        #       For using the numeric value, see above chances, I've done a little work on it.
        return $p_default;
	}

	#-----------------------
	function get_date_column_value( $p_name, $p_row, $p_default )
	{
		$t_date = get_column_value( $p_name, $p_row );
		return is_blank( $t_date ) ? $p_default : strtotime( $t_date );
	}
    
    #@@@ u.sommer added
    function string_MkPretty( $t_str )
    {
    	$t_str = utf8_encode(strtolower(trim(utf8_decode($t_str))));
        
    	$t_str = preg_replace('/\xfc/ui', 'ue', $t_str);
    	$t_str = preg_replace('/\xf6/ui', 'oe', $t_str);
    	$t_str = preg_replace('/\xe4/ui', 'ae', $t_str);
    	$t_str = preg_replace('/\xdf/ui', 'ss', $t_str);
        
        # Disabled, because I do not want to check every char not of (\w \-), I only want to check until the first occurrence.
        # To understand this you may look into your config_defaults_inc.php and seek for "$g_user_login_valid_regex"
        #$t_str = preg_split(
        #    config_get(user_login_valid_regex), 
        #    $t_str
        #);
        
        $t_str = preg_split('/[^(\w\-)]+/', $t_str);       
        return $t_str[0];
    }    
    
	# @@@ u.sommer heavily edited
	function get_user_column_value( $p_name, $p_row, $p_default )
	{
        # @@@ u.sommer extended for adding non existing users. Password is set to username,
        #       Because of this, your users should, for security reason, review theyre accounts asap after import of csv-files!
        #
        #       Note:   User names are lowercase and are only valid if they consist of [\w\-]. For definition of [\w] so your RegExp implementation.
        #                   string_MkPretty escapes Umlaute with there phonetical pendant and user names are trimmed from the first special 
        #                   char (all not of [\w \-]) to the end of the string.
        #                   E.g. Import of Username C3p-0.
        #                       0> User has to log in with c3p-0\c3p-0 as login\password
		$t_username = get_column_value( $p_name, $p_row );
        
        # print_r( $t_username ); echo "\n";
        if( is_blank( $t_username ) )
            return $p_default;
            
        if( ($t_user_id = user_get_id_by_name($t_username)) !== false )
            return $t_user_id; 
        
        $t_username = string_MkPretty($t_username);
        if( ($t_user_id = user_get_id_by_name($t_username)) !== false )
            return $t_user_id;         
        
        if( user_create( $t_username , $t_username ) )
            return user_get_id_by_name($t_username);
            
        return $p_default;
	}

	#-----------------------
	function get_column_value( $p_name, $p_row, $p_default = '' )
	{
		global $f_columns;

		$t_column = array_isearch( $p_name, $f_columns );
        #@@@ u.sommer changed: "return ( $t_column === false || !isset( $p_row[$t_column] ) ) ? $p_default : trim( $p_row[$t_column] );"
        #       Here are occure some problems if we have a file which is not utf8 encoded. (For example ther are problems with the german Umlaute.)
        #       I decided to utf8ify the return value in case that it is a string.
        
		return ( ($t_column === false) || (!isset( $p_row[$t_column] )) ) ? $p_default : utf8_encode(trim( $p_row[$t_column] ));
	}

	#-----------------------
	function column_value_exists( $p_name, $p_row )
	{
		global $f_columns;
		$t_column = array_isearch( $p_name, $f_columns );
		return (($t_column != false) && (isset( $p_row[$t_column] ))) ? true : false;
	}

	function get_category_column_value( $p_name, $p_row, $p_project, $p_default )
	{
        $t_category_id = category_get_id_by_name_ne
        ( 
            trim
            ( 
                get_column_value( $p_name, $p_row ) 
            ) , 
            $p_project 
        );

        return (($t_category_id === false) ? $p_default : $t_category_id);
	}      
    
	# Import file content
    # @@@ u.sommer, we ask for this on import_issues_page.php, so get it over $_POST (see "Get submitted data")
	# $f_separator = config_get( 'csv_separator' );
    $t_first_run = true;
	$t_success_count = 0;
	$t_failure_count = 0;
	$t_error_messages = '';

	$t_bug_exists = array_isearch( 'id', $f_columns );
	foreach( $t_file_content as $t_file_row )
	{
		# Check if first row skipped
		if( $t_first_run && $f_skip_first )
		{
			$t_first_run = false;
			continue;
		};

		# Explode into elements
		$t_file_row = read_csv_row( $t_file_row, $f_separator );

		# Variables
		$t_bug_id = get_column_value( 'id', $t_file_row );
		$t_default = new BugData;
        
		# Set default parameters
		if( !$t_bug_exists )
		{
            
			$t_default->project_id = $g_project_id;
            $t_default->category_id = get_csv_import_category_id
            (
                $g_project_id,
                'csv_imported'
            );
            $t_default->reporter_id = auth_get_current_user_id();
            $t_default->steps_to_reproduce = config_get( 'default_bug_steps_to_reproduce' );
            $t_default->additional_information = config_get( 'default_bug_additional_info' );
            $t_default->priority = config_get( 'default_bug_priority' );
            $t_default->severity = config_get( 'default_bug_severity' );
            $t_default->reproducibility = config_get( 'default_bug_reproducibility' );
            $t_default->date_submitted = date('Y-m-d G:i:s');
            $t_default->handler_id = auth_get_current_user_id();
            $t_default->status = config_get( 'bug_submit_status' );
            $t_default->resolution = OPEN;
            $t_default->view_state = config_get( 'default_bug_view_status' );
            $t_default->profile_id = 0;
		}

		# Check existing bug consistency
		else
		{
			if( !bug_exists( $t_bug_id ) )
			{
				$t_error_messages .= sprintf( lang_get( 'import_error_bug_not_exist' ), $t_bug_id) . '<br />';
				$t_failure_count++;
				continue;
			};
			$t_default = bug_get( $t_bug_id, true );
			if( $t_default->project_id != $g_project_id )
			{
				$t_error_messages .= sprintf( lang_get( 'import_error_bug_bad_project' ), $t_bug_id) . '<br />';
				$t_failure_count++;
				continue;
			};
		};
        
		# Set bug data
		$t_bug_data = new BugData;
		$t_bug_data->project_id = $t_default->project_id;
		$t_bug_data->reporter_id =
            get_user_column_value( 'reporter_id', $t_file_row, $t_default->reporter_id );
		$t_bug_data->summary =
            get_column_value( 'summary', $t_file_row, $t_default->summary );
		$t_bug_data->description =
            get_column_value( 'description', $t_file_row, $t_default->description );
		$t_bug_data->steps_to_reproduce =
            get_column_value( 'steps_to_reproduce', $t_file_row, $t_default->steps_to_reproduce );
		$t_bug_data->additional_information =
            get_column_value( 'additional_information', $t_file_row, $t_default->additional_information );
		$t_bug_data->category_id = get_category_column_value
        (
            'category', 
            $t_file_row, 
            $t_bug_data->project_id , 
            $t_default->category_id
        );  # @@@ u.sommer edited
		$t_bug_data->priority =
            get_enum_column_value( 'priority', $t_file_row, $t_default->priority );
		$t_bug_data->severity =
            get_enum_column_value( 'severity', $t_file_row, $t_default->severity );
		$t_bug_data->reproducibility =
            get_enum_column_value( 'reproducibility', $t_file_row, $t_default->reproducibility );
		$t_bug_data->date_submitted =
            get_date_column_value( 'date_submitted', $t_file_row, $t_default->date_submitted );
		$t_bug_data->last_updated =
            get_date_column_value( 'last_updated', $t_file_row, $t_default->last_updated );
		$t_bug_data->handler_id =
            get_user_column_value( 'handler_id', $t_file_row, $t_default->handler_id );
		$t_bug_data->status =
            get_enum_column_value( 'status', $t_file_row, $t_default->status );
		$t_bug_data->resolution =
            get_enum_column_value( 'resolution', $t_file_row, $t_default->resolution );
		$t_bug_data->os =
            get_column_value( 'os', $t_file_row, $t_default->os );
		$t_bug_data->os_build =
            get_column_value( 'os_build', $t_file_row, $t_default->os_build );
		$t_bug_data->platform =
            get_column_value( 'platform', $t_file_row, $t_default->platform );
		$t_bug_data->version =
            get_column_value( 'version', $t_file_row, $t_default->version );
		$t_bug_data->projection =
            get_enum_column_value( 'projection', $t_file_row, $t_default->projection );
		$t_bug_data->eta =
            get_enum_column_value( 'eta', $t_file_row, $t_default->eta );
		$t_bug_data->fixed_in_version =
            get_column_value( 'fixed_in_version', $t_file_row, $t_default->fixed_in_version );
		$t_bug_data->target_version =
            get_column_value( 'target_version', $t_file_row, $t_default->target_version );
		$t_bug_data->build =
            get_column_value( 'build', $t_file_row, $t_default->build );
		$t_bug_data->duplicate_id = $t_default->duplicate_id;
		$t_bug_data->view_state =
            get_enum_column_value( 'view_state', $t_file_row, $t_default->view_state );
		$t_bug_data->sponsorship_total = $t_default->sponsorship_total;
		$t_bug_data->profile_id = $t_default->profile_id;
        
		# Create or update bug
		if( !$t_bug_exists ) $t_bug_id = bug_create( $t_bug_data );
		else if( !bug_update( $t_bug_id, $t_bug_data ) ) $t_bug_id = 0;

		# Update other bug data
		if( $t_bug_id )
		{
			# Variables
			$t_error = false;
			$t_default = bug_get( $t_bug_id, true );

			# Setting values
			if( $t_bug_data->status != $t_default->status
				&& column_value_exists( 'status', $t_file_row ) )
				bug_set_field( $t_bug_id, 'status', $t_bug_data->status );
			if( $t_bug_data->resolution != $t_default->resolution
				&& column_value_exists( 'resolution', $t_file_row ) )
				bug_set_field( $t_bug_id, 'resolution', $t_bug_data->resolution );
			if( $t_bug_data->target_version != $t_default->target_version )
				bug_set_field( $t_bug_id, 'target_version', $t_bug_data->target_version );
			if( $t_bug_data->fixed_in_version != $t_default->fixed_in_version )
				bug_set_field( $t_bug_id, 'fixed_in_version', $t_bug_data->fixed_in_version );
			if( $t_bug_data->date_submitted != $t_default->date_submitted
				&& column_value_exists( 'date_submitted', $t_file_row ) )
				bug_set_field( $t_bug_id, 'date_submitted', $t_bug_data->date_submitted );

			# Import custom fields
            $t_linked_ids = custom_field_get_linked_ids( $g_project_id );
			foreach( $t_linked_ids as &$t_id )
			{
				# Look if this field is set
				$t_def = custom_field_get_definition( $t_id );
                
                #@@@ u.sommer added
                #       Forgot the "custom_" ??? This has wasted 3 hours of debugging time - as far as debugging is possible with php. 8-/                
                $t_custom_col_name = 'custom_'.$t_def['name'];
                
                #@@@ u.sommer changed: "if( !column_value_exists( $t_def['name'] , $t_file_row ) );"
				if( !column_value_exists( $t_custom_col_name , $t_file_row ) )
                {   
                    continue;
                };

				# Prepare value
                #@@@ u.sommer changed: "$t_value = &get_column_value( $t_def['name'] , $t_file_row );"
				$t_value = get_column_value( $t_custom_col_name , $t_file_row );
				if( ($t_value != '') && ($t_def['type'] == CUSTOM_FIELD_TYPE_DATE) )
                {
					$t_value = strtotime( $t_value );
                };
                
				# Import value
				if( !custom_field_set_value( $t_id, $t_bug_id, $t_value ) )
				{
					$t_error_messages .=
					 sprintf( lang_get( 'import_error_custom_field' ), $t_def['name'], $t_bug_data->summary) . '<br />';
					$t_error = true;
				};
			};

			# Result
			if($t_error) $t_failure_count++;
			else $t_success_count++;
		}
		else
		{
			$t_error_messages .= sprintf( lang_get( 'import_error' ), $t_bug_data->summary) . '<br />';
			$t_failure_count++;
			continue;
		};
	};
?>
<?php html_page_top1() ?>
<?php
	$t_redirect_url = 'view_all_bug_page.php';

	if( $t_failure_count == 0 )
	{
		html_meta_redirect( $t_redirect_url );
	};
?>
<?php html_page_top2() ?>

<br />
<div align="center">

<?php
    echo $t_error_messages;
	if( $t_failure_count )
		echo sprintf( lang_get( 'import_result_failure_ct' ), $t_failure_count) . '<br />';
	echo sprintf( lang_get( $t_bug_exists ? 'import_result_update_success_ct' : 'import_result_import_success_ct' ),
		$t_success_count) . '<br />';
	print_bracket_link( $t_redirect_url, lang_get( 'proceed' ) );
?>
</div>

<?php html_page_bottom1( __FILE__ ) ?>
