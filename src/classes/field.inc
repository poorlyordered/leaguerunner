<?php
class Field extends LeaguerunnerObject
{
	function load ( $array = array() )
	{
		$result = field_query( &$array );

		if(1 != db_num_rows($result)) {
			return false;
		}

		$this->_in_database = true;
		
		$item = db_fetch_array($result);

		foreach ($item as $key => $value) {
			$this->{$key} = $value;
		}

		// If we have a parent, override the overridables.
		if( $this->parent_fid ) {
			$result = field_query( array('fid' => $item['parent_fid']) );
			$parent = db_fetch_object($result);
			$this->name = $parent->name;
			$this->code = $parent->code;
			$this->region = $parent->region;
			$this->ward_id = $parent->ward_id;
			$this->site_directions = $parent->site_directions;
			$this->site_instructions = $parent->site_instructions;
			$this->location_url = $parent->location_url;
			$this->layout_url = $parent->layout_url;
			$this->fullname = join(" ", array($this->name, $this->num));
		}
		
		return true;
	}
	
	function save ()
	{
		if(! count($this->_modified_fields)) {
			// No modifications, no need to save
			return true;
		}

		if( ! $this->_in_database ) {
			if( ! $this->create() ) {
				error_exit("Couldn't create field");
			}
		}
	
		$fields      = array();
		$fields_data = array();

		foreach ( $this->_modified_fields as $key => $value) {
			if( !isset($this->{$key}) || ('' == $this->{$key}) ) {
				$fields[] = "$key = %s";
				$fields_data[] = 'NULL';
			} else {
				$fields[] = $key . " = " . $this->get_placeholder($key, $this->{$key});
				$fields_data[] = $this->{$key};
			}
		}
		
		if(count($fields_data) != count($fields)) {
			error_exit("Internal error: Incorrect number of fields set");
		}
		
		$sql = "UPDATE field SET ";
		$sql .= join(", ", $fields);	
		$sql .= " WHERE fid = %d";

		$fields_data[] = $this->fid;

		db_query( $sql, $fields_data);
		if(1 < db_affected_rows()) {
			# Affecting zero rows is possible
			error_exit("Internal error: Strange number of rows affected");
		}
		
		unset($this->_modified_fields);
		return true;
	}
	
	function create ()
	{
		if( $this->_in_database ) {
			return false;
		}

		if( ! $this->num ) {
			return false;
		}

		if( ! $this->parent_fid ) {
			if( ! $this->code ) {
				return false;
			}
			if( ! $this->name ) {
				return false;
			}
			db_query("INSERT into field (num, name, code) VALUES(%d, '%s','%s')", $this->num, $this->name, $this->code);
		} else {
			db_query("INSERT into field (num, parent_fid) VALUES(%d, %d)", $this->num, $this->parent_fid);
		}

		if( 1 != db_affected_rows() ) {
			return false;
		}

		$this->fid = db_result(db_query("SELECT LAST_INSERT_ID() from field"));
		
		return true;
	}
}

function field_query ( $array = array() )
{

	$query = array();
	$query[] = '1 = 1';
	$order = '';
	foreach ($array as $key => $value) {
		switch( $key ) {
			case '_extra':
				/* Just slap on any extra query fields desired */
				$query[] = $value;
				break;
			case '_order':
				$order = ' ORDER BY ' . $value;
				break;
			default:
				$query[] = "f.$key = '" . check_query($value) . "'";
		}
	}

	return db_query("SELECT 
		f.*,
		CONCAT_WS(' ',f.name,f.num) as fullname
		FROM field f
	WHERE " . implode(' AND ',$query) .  $order);
}

/**
 * Wrapper for convenience and backwards-compatibility.
 */
function field_load( $array = array() )
{
	$s = new Field;
	if($s->load($array)) {
		return $s;
	} else {
		return null;
	}
}
?>