<?php 
	namespace Dplus\SapConcur;
	
	/**
	 * Adds definition of Properties and functions needed
	 * for endpoints that have defined structures
	 */
	trait StructuredClassTraits {
		/**
		 * Returns an array keyed by the keys and values needed for the array 
		 * Takes the array structure, goes through the structure
		 * adds a key value, and uses the dbcolumn property from the structure if needed
		 * @param  array $structure       Section / Key of array to map
		 * @param  array $values          Values array
		 * @return array                  Array with the needed key values
		 */
		protected function create_sectionarray($structure, $values) {
			$structuredarray = array();
			
			foreach ($structure as $field => $fieldproperties) {
				if (isset($fieldproperties['dbcolumn'])) {
					$structuredarray[$field] = $this->get_value($values, $field, $fieldproperties);
				} else {
					$structuredarray[$field] = $this->create_sectionarray($structure[$field], $values);
				}
			}
			return $structuredarray;
		}
		
		/**
		 * Returns a one-dimensional array with fields for keys and values (mainly for database)
		 * @param  array  $structure Defined structure to loop through keys
		 * @param  array  $values    Array of Values
		 * @return array             One-dimensional key-value array
		 */
		protected function create_dbarray($structure, $values) {
			$structuredarray = array();
			
			foreach ($structure as $field => $properties) {
				$column = !empty($properties['dbcolumn']) ? $properties['dbcolumn'] : $field;
				
				if (is_array($values[$field])) {
					$structuredarray = array_merge($structuredarray, $this->create_dbarray($structure[$field], $values[$field]));
				} else {
					$structuredarray[$column] = $this->get_dbvalue($values, $field, $properties);
				}
				
			}
			return $structuredarray;
		}
		
		/**
		 * Determines the Value to get
		 * @param  array  $values          Array of Values, Key value array
		 * @param  string $field           Field to find value for
		 * @param  array  $fieldproperties Array of Properties for that field
		 * @return string                  Determined Value, formatted
		 */
		protected function get_value($values, $field, $fieldproperties) {
			$field = !empty($fieldproperties['dbcolumn']) ? $fieldproperties['dbcolumn'] : $field;
			$value = isset($values[$field]) ? $values[$field] : '';
			return $this->format_value($value, $fieldproperties);
        }
		
		/**
		 * Returns the value from the values array, does not use the dbcolumn
		 * @param  array  $values          Values
		 * @param  string $field           Key
		 * @param  array  $fieldproperties Format Properties for that field
		 * @return mixed                   Formatted Value
		 */
		protected function get_dbvalue($values, $field, $fieldproperties) {
			return $this->format_value($values[$field], $fieldproperties);
        }
		
		/**
		 * Formats the value of a field using the field properties array
		 * @param  mixed $value            Value to format
		 * @param  array $fieldproperties  Properties
		 * @return mixed                   Formatted Value
		 */
		protected function format_value($value, $fieldproperties) {
			if (isset($fieldproperties['format'])) {
                switch ($fieldproperties['format']) {
                    case 'date':
                        $value = date($fieldproperties['date-format'], strtotime($this->clean_value($value)));
                        break;
                }
            } elseif (isset($fieldproperties['strlen'])) {
				$value = substr($this->clean_value($value), 0, $fieldproperties['strlen']);
			} else {
                $value = $this->clean_value($value);
            }
			
			return (empty($value) && isset($fieldproperties['default'])) ? $fieldproperties['default'] : $value;
		}
	}
