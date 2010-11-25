<?php
# Mantis - a php based bugtracking system

require_once( 'core.php' );
$t_core_path = config_get( 'core_path' );
require_once( $t_core_path . 'category_api.php' );
require_once( $t_core_path . 'database_api.php' );
require_once( $t_core_path . 'user_api.php' );
require_once( $t_core_path . 'bug_api.php' );
access_ensure_project_level( config_get( 'manage_site_threshold' ) );
require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'import_issues_inc.php' );

# Check a project is selected
$g_project_id = helper_get_current_project();
if( $g_project_id == ALL_PROJECTS ) {
	plugin_error( 'ERROR_ALL_PROJECT', ERROR );
}

# Get submitted data
$f_create_unknown_cats = gpc_get_bool( 'cb_create_unknown_cats' );
$f_import_file = gpc_get_string( 'import_file' );
$f_columns = gpc_get_string_array( 'columns' );
$f_skip_first = gpc_get_bool( 'cb_skip_first_line' );
$f_separator = gpc_get_string('edt_cell_separator');
$f_keys = gpc_get_string_array( 'cb_keys', array() );

# Load custom field ids
$t_linked_ids = custom_field_get_linked_ids( $g_project_id );

# Get custom field id of primary keys
foreach($t_linked_ids as $cf_id) {
	$t_def = custom_field_get_definition($cf_id);
	$t_custom_col_name = $g_custom_field_identifier . $t_def['name'];

	if(isset($f_keys[$t_custom_col_name]))
	{
		$f_keys[$t_custom_col_name] = $cf_id;
	}
}

# Check given parameters - File
$t_file_content = array();
if( file_exists( $f_import_file ) ) {
	$t_file_content = read_csv_file( $f_import_file );
} else {
	error_parameters( plugin_lang_get( 'error_file_not_found' ) );
	plugin_error( 'ERROR_FILE_FORMAT', ERROR );
}

# Check given parameters - Columns
if( count( $f_columns ) <= 0 ) {
	trigger_error( ERROR_EMPTY_FIELD, ERROR );
}

# ignore_column have to be ... ignored
foreach ($f_columns as $key => $value) {
	if ($value == 'ignore_column') {
		unset($f_columns[$key]);
	}
}

# Other columns check
if( count( $f_columns ) != count( array_unique( $f_columns ) ) ) {
	error_parameters( plugin_lang_get( 'error_col_multiple' ) );
	plugin_error( 'ERROR_FILE_FORMAT', ERROR );
}

# Some default values for filter
$t_page_number = 1;
$t_issues_per_page = 25;
$t_page_count = 0;
$t_issues_count = 0;

# Import file content
$t_first_run = true;
$t_success_count = 0;
$t_failure_count = 0;
$t_error_messages = '';

# Determine import mode
$t_import_mode = 'all_new';
if(array_isearch( 'id', $f_columns ) !== false)
{
	$t_import_mode = 'by_id';
}
else
{
	if(count($f_keys) > 0)
	{
		$t_import_mode = 'by_keys';
	}
}

# Let's go
helper_begin_long_process( );

foreach( $t_file_content as $t_file_row ) {

	# Check if first row skipped
	if( $t_first_run && $f_skip_first ) {
		$t_first_run = false;
		continue;
	}

	# Explode into elements
	$t_file_row = read_csv_row( $t_file_row, $f_separator );

	# Get Id
	$t_bug_id = null;
	switch($t_import_mode)
	{
		case 'by_id' :
			$t_bug_id = get_column_value( 'id', $t_file_row );
			break;

		case 'by_keys' :

			$t_filter = filter_get_default();
			$t_filter[FILTER_PROPERTY_HIDE_STATUS_ID] = array(
				'0' => META_FILTER_ANY,
			);

			$t_values_for_error = array();
			foreach($f_keys as $aKey => $v)
			{
				if(substr($aKey, 0, strlen($g_custom_field_identifier)) != $g_custom_field_identifier)
				{
					$t_filter[$aKey] = array(get_column_value( $aKey, $t_file_row, '' ));
					$t_values_for_error[] = $t_filter[$aKey][0];
				}
				else
				{
					$t_filter['custom_fields'][$v] = array(get_column_value( $aKey, $t_file_row, '' ));
					$t_values_for_error[] = $t_filter['custom_fields'][$v][0];
				}
			}

			$t_issues = filter_get_bug_rows( $t_page_number, $t_issues_per_page, $t_page_count, $t_issues_count, $t_filter );

			switch($t_issues_count)
			{
				case 1:
					$t_bug_id = $t_issues[0]->id;
					break;
				case 0:
					$t_bug_id = null;
					break;
				default :
					$t_bugs_id = array();
					foreach($t_issues as $issue)
					{
						$t_bugs_id[] = $issue->id;
					}

					$t_error_messages .= sprintf( plugin_lang_get( 'error_keys' ), implode('/', $t_values_for_error),
																										implode('/', $t_bugs_id)) . '<br />';
					$t_failure_count++;
					continue 3;
			}

			break;

		default :
			$t_bug_id = null;
	}

	#Default bug will be with default values or bug values
	$t_default = new BugData;

	# Set default parameters
	if( $t_bug_id === null ) {
		// Category
		$t_cat_val =  trim ( get_column_value( 'category', $t_file_row ) );
		$t_cat_val = ($f_create_unknown_cats && $t_cat_val != '') ? $t_cat_val : 'csv_imported';

		$t_default->project_id = $g_project_id;
		$t_default->category_id = get_csv_import_category_id($g_project_id, $t_cat_val);
		$t_default->reporter_id = auth_get_current_user_id();
		$t_default->priority = config_get( 'default_bug_priority' );
		$t_default->severity = config_get( 'default_bug_severity' );
		$t_default->reproducibility = config_get( 'default_bug_reproducibility' );
		$t_default->date_submitted = date('Y-m-d G:i:s');
		$t_default->handler_id = auth_get_current_user_id();
		$t_default->status = config_get( 'bug_submit_status' );
		$t_default->resolution = OPEN;
		$t_default->view_state = config_get( 'default_bug_view_status' );
		$t_default->profile_id = 0;
		$t_default->due_date = date_get_null();
	} else {
		if( !bug_exists( $t_bug_id ) ) {
			$t_error_messages .= sprintf( plugin_lang_get( 'error_bug_not_exist' ), $t_bug_id) . '<br />';
			$t_failure_count++;
			continue;
		}
		$t_default = bug_get( $t_bug_id, true );
		if( $t_default->project_id != $g_project_id ) {
			$t_error_messages .= sprintf( plugin_lang_get( 'error_bug_bad_project' ), $t_bug_id) . '<br />';
			$t_failure_count++;
			continue;
		}
	}
	
	# Set bug data
	$t_bug_data = new BugData;

	$t_bug_data->id = $t_bug_id;
	$t_bug_data->project_id = $t_default->project_id;
	$t_bug_data->reporter_id = get_user_column_value( 'reporter_id', $t_file_row, $t_default->reporter_id );
	$t_bug_data->summary = get_column_value( 'summary', $t_file_row, $t_default->summary );
	$t_bug_data->category_id = get_category_column_value('category', $t_file_row, $t_bug_data->project_id , $t_default->category_id );
	$t_bug_data->priority = get_enum_column_value( 'priority', $t_file_row, $t_default->priority );
	$t_bug_data->severity = get_enum_column_value( 'severity', $t_file_row, $t_default->severity );
	$t_bug_data->reproducibility = get_enum_column_value( 'reproducibility', $t_file_row, $t_default->reproducibility );
	$t_bug_data->date_submitted = get_date_column_value( 'date_submitted', $t_file_row, $t_default->date_submitted );
	$t_bug_data->last_updated = get_date_column_value( 'last_updated', $t_file_row, $t_default->last_updated );
	$t_bug_data->handler_id = get_user_column_value( 'handler_id', $t_file_row, $t_default->handler_id );
	$t_bug_data->status = get_enum_column_value( 'status', $t_file_row, $t_default->status );
	$t_bug_data->resolution = get_enum_column_value( 'resolution', $t_file_row, $t_default->resolution );
	$t_bug_data->os = get_column_value( 'os', $t_file_row, $t_default->os );
	$t_bug_data->os_build = get_column_value( 'os_build', $t_file_row, $t_default->os_build );
	$t_bug_data->platform = get_column_value( 'platform', $t_file_row, $t_default->platform );
	$t_bug_data->version = get_column_value( 'version', $t_file_row, $t_default->version );
	$t_bug_data->projection = get_enum_column_value( 'projection', $t_file_row, $t_default->projection );
	$t_bug_data->eta = get_enum_column_value( 'eta', $t_file_row, $t_default->eta );
	$t_bug_data->fixed_in_version = get_column_value( 'fixed_in_version', $t_file_row, $t_default->fixed_in_version );
	$t_bug_data->target_version = get_column_value( 'target_version', $t_file_row, $t_default->target_version );
	$t_bug_data->build = get_column_value( 'build', $t_file_row, $t_default->build );
	$t_bug_data->duplicate_id = $t_default->duplicate_id;
	$t_bug_data->view_state = get_enum_column_value( 'view_state', $t_file_row, $t_default->view_state );
	$t_bug_data->sponsorship_total = $t_default->sponsorship_total;
	$t_bug_data->profile_id = $t_default->profile_id;
	$t_bug_data->due_date = get_date_column_value( 'due_date', $t_file_row, $t_default->due_date );

	$t_bug_data->description = get_column_value( 'description', $t_file_row, '' );
	$t_bug_data->steps_to_reproduce = get_column_value( 'steps_to_reproduce', $t_file_row, '' );
	$t_bug_data->additional_information = get_column_value( 'additional_information', $t_file_row, '' );

	# Create or update bug
	if( $t_bug_id === null) {
		$t_bug_id = $t_bug_data->create();
	} else {
		if( !$t_bug_data->update( true, ( false == $t_notify ) ) ){
			$t_bug_id = null;
		}
	}

	# Update other bug data
	if( $t_bug_id !== null ) {
		# Variables
		$t_error = false;
		$t_default = bug_get( $t_bug_id, true );

		# Setting values
		if( $t_bug_data->status != $t_default->status
				&& column_value_exists( 'status', $t_file_row ) ) {
			bug_set_field( $t_bug_id, 'status', $t_bug_data->status );
		}
		if( $t_bug_data->resolution != $t_default->resolution
				&& column_value_exists( 'resolution', $t_file_row ) ) {
			bug_set_field( $t_bug_id, 'resolution', $t_bug_data->resolution );
		}
		if( $t_bug_data->target_version != $t_default->target_version ) {
			bug_set_field( $t_bug_id, 'target_version', $t_bug_data->target_version );
		}
		if( $t_bug_data->fixed_in_version != $t_default->fixed_in_version ) {
			bug_set_field( $t_bug_id, 'fixed_in_version', $t_bug_data->fixed_in_version );
		}
		if( $t_bug_data->date_submitted != $t_default->date_submitted
				&& column_value_exists( 'date_submitted', $t_file_row ) ) {
			bug_set_field( $t_bug_id, 'date_submitted', is_numeric($t_bug_data->date_submitted) ?
																$t_bug_data->date_submitted :
																strtotime($t_bug_data->date_submitted) );
		}

		# Import custom fields
		foreach( $t_linked_ids as &$t_id ) {
			# Look if this field is set
			$t_def = custom_field_get_definition( $t_id );
			$t_custom_col_name = $g_custom_field_identifier . $t_def['name'];
			if( !column_value_exists( $t_custom_col_name , $t_file_row ) ) {
				continue;
			}

			# Prepare value
			$t_value = get_column_value( $t_custom_col_name , $t_file_row );
			if( ($t_value != '') && ($t_def['type'] == CUSTOM_FIELD_TYPE_DATE) ) {
				$t_value = strtotime( $t_value );
			}

			# Import value
			if( !custom_field_set_value( $t_id, $t_bug_id, $t_value ) ) {
				$t_error_messages .= sprintf( plugin_lang_get( 'error_custom_field' ), $t_def['name'], $t_bug_data->summary) . '<br />';
				$t_error = true;
			}
		}

		# Result
		if($t_error) {
			$t_failure_count++;
		}
		else {
			$t_success_count++;
		}
	} else {
		$t_error_messages .= sprintf( plugin_lang_get( 'error_any' ), $t_bug_data->summary) . '<br />';
		$t_failure_count++;
		continue;
	}
}

html_page_top1() ;
$t_redirect_url = 'view_all_bug_page.php';
if( $t_failure_count == 0 ) {
	html_meta_redirect( $t_redirect_url );
}
html_page_top2();
?>
<br />
<div align="center">
<?php
echo $t_error_messages;
if( $t_failure_count ) {
	echo sprintf( plugin_lang_get( 'result_failure_ct' ), $t_failure_count) . '<br />';
}
echo sprintf( plugin_lang_get( $t_import_mode != 'all_new' ? 'result_update_success_ct' : 'result_import_success_ct' ),
$t_success_count) . '<br />';
print_bracket_link( $t_redirect_url, lang_get( 'proceed' ) );
?>
</div>

<?php
html_page_bottom1( __FILE__ );
