<?php

/** 
 * 	Copyright 2011 Owen Mundy 
 *
 *	This file is part of Give Me My Data.
 *
 *	Give Me My Data is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *	
 *	Give Me My Data is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *	
 *	You should have received a copy of the GNU General Public License
 *	along with Give Me My Data.  If not, see <http://www.gnu.org/licenses/>.
 */ 




 
/**
 * Format and return data as GDF file
 *
 * @params	array $data	A 2d array
 * 			string $comments (optional)
 * @return	string
 * @author	Owen Mundy <owenmundy.com>
 */  
function array2gdf($data, $comments='')
{	
	$nodes = array();
	// loop through data and get unique nodes from key and value of each sub-array
	foreach ($data as $key => $value)
	{
		if ( ! in_array	($value['uid1'],$nodes) ) {
			$nodes[] = $value['uid1'];
		}
		if ( ! in_array	($value['uid2'],$nodes) ) {
			$nodes[] = $value['uid2'];
		}
	}
	sort($nodes);
	
	$node_count = count($nodes);	// number of nodes
	$edge_count = 0;
	
	$values .= "nodedef> name\n";
	foreach ($nodes as $key => $value)
	{
		$values .= $value ."\n";
	}
	
	$values .= "edgedef> n1,n2\n";
	foreach ($data as $key => $value)
	{
		$values .= $value['uid1'] .','. $value['uid2'] ."\n";
		$edge_count ++;
	}
	
	$html_out = "
	
#
#	Guess (GDF) format generated by Give Me My Data http://givememydata.com a Facebook data liberation application
#	
#	Instructions: 
#	1. copy and paste the contents below into a new plain text file with the .gdf extension.
#	2. Use Gephi http://gephi.org to open the file. 
#
#	Nodes: $node_count 
#	Edges: $edge_count 
#	

$values

";
	return $html_out;
}


 
/**
 * Format and return data as "Nodebox"
 *
 * @params	array $data	A 2d array
 * 			string $comments (optional)
 * @return	string
 * @author	Owen Mundy <owenmundy.com>
 */  
function array2nb($data, $comments='')
{
	$count = count($data);	// number of records
	
	foreach ($data as $key => $value)
	{
		$values .= 'g.add_edge("' . $value['uid1'] .'","'. $value['uid2'] .'")'."\n";
	}
	// to determine distance in nb graph visualization
	$distance = ($count < 1000) ? $distance = 3.5 : .0008*$count;
	
	$html_out = "

#
#	Nodebox 'format' generated by Give Me My Data http://givememydata.com a Facebook data liberation application
#	
#	Instructions: 
#	1. copy and paste the contents below into a new plain text file with the .py extension.
#	2. Open Nodebox 1.0 http://nodebox.net and run the file. Also see: http://nodebox.net/code/index.php/Graph
#
#	Comments: $comments
#	Connections: $count 
#	

graph =  ximport(\"graph\")
g = graph.create(iterations=1000, depth=False, distance=". $distance .")

$values 

g.styles.default.background = color(.1)
g.styles.apply()
g.solve()
g.draw()

";
	return $html_out;
}








/**
 * Format and return data as "DOT"
 *
 * @params	array $data	A 2d array
 * 			string $comments (optional)
 * @return	string
 * @author	Owen Mundy <owenmundy.com>
 */  
function array2dot($data, $comments='')
{
	$count = count($data);	// number of records
	
	foreach ($data as $key => $value)
	{
		$values .= $data[$key].';'."\n";
	}
	
	$html_out = "
	
/*
 *	DOT format generated by Give Me My Data http://givememydata.com a Facebook data liberation application
 *	
 *	Instructions: 
 *	1. copy and paste the contents below into a new plain text file with the .dot extension.
 *	2. Open Graphviz http://graphviz.org and load the dot file.
 *
 *	Comments: $comments
 *	Connections: $count
 */

graph G
{

$values 

}";
	return $html_out;
}







/**
 * Format and return data as "plain text"
 *
 * @params	array $data	A 2d array
 * 			string $comments (optional)
 * @return	string
 * @author	Owen Mundy <owenmundy.com>
 */ 
function array2plain($data, $comments='')
{
	$count = count($data);	// number of records
	
	// Create an array to hold the results
	$result_array = array();
	
	// Recursively traverse a multi-dimensional array
	function traverseArray($array,$level=1,$suffix='')
	{ 
		// Make result array global
		global $result_array;
		
		if (count($array) > 1){
			// Loops through each element,
			foreach($array as $key => $value)
			{ 
				// if element again is array, 
				if(is_array($value))
				{ 
					// function is recalled.
					traverseArray($value,$level+1,$key); 
				}
				else
				{	
					// Make a new key name so description of field is readable
					$new_key = $key . $suffix;
				
					// If not, result is echoed/stored.
					$result_array[$new_key] = $value;
				} 
			}
		}
		return $result_array;		
	}
	// at this point we have a flattened array
	$result_array = traverseArray($data);
	
	// make sure there is data before we continue
	if ($result_array){
	
		// so now we need to loop through and make it into a PLAIN TEXT and clean some data...
		foreach ($result_array as $key => $value)
		{
			$values .= $key .": \t";
			
			// trim whitespace
			$value = trim($value);
			
			// for some reason the birth date was breaking the PLAIN TEXT even when I wrapped it in quotes
			// so this conditional swaps the date string for a different format
			if (empty($value))
			{
				$values .= "NULL";
			}
			else if (strstr($value, ',') && strtotime($value) > 1)
			{
				$values .= date("m/d/Y", strtotime($value));	
			}
			else if (strstr($value, ','))
			{
				$values .= str_replace(',','-',$value);		
			}
			else
			{
				/**/
				// if the value is a number there is a good chance it is a timestamp
				if ( ctype_digit($value) )
				{
					$values .= convert_timestamp($key, $value);
				}
				else
				{
					$values .= $value;	
				}
				
				//$values .= $value;
			}
			$values .= "\n";
		}
	}
	else
	{
		$values = "No records found";	
	}
	$html_out = "
	
<!--
 *	Plain text format generated by Give Me My Data http://givememydata.com a Facebook data liberation application
 *
 *	Instructions: 
 *	1. Copy and paste the contents below into a new plain text file with the .txt or .rtf extension.
 *	2. Open in any text-based application; Google Docs, TextEdit, Pages (Mac), Word (Win).
 *
 *	Comments: $comments
 *	Records: $count
 -->
 
$values

";
	return $html_out;  
}







/**
 * Format and return data as "CSV"
 * - ref: http://kylehall.info/index.php/2009/10/19/convert-a-2d-associative-array-to-csv-using-php/
 *
 * @params	array $data	A 2d array
 * 			string $comments (optional)
 * @return	string
 * @author	Owen Mundy <owenmundy.com>
 */ 
function array2csv($array, $comments='')
{
	$count = count($array);	// number of records
	
	if ($count > 0)
	{
		$csv;
		
		## Grab the first element to build the header
		$arr = array_pop( $array );
		$temp = array();
		foreach( $arr as $key => $data ) 
		{
			$temp[] = $key;
		}
		$csv = implode( ',', $temp ) . "\n";
		
		## Add the data from the first element
		$csv .= to_csv_line( $arr );
		
		## Add the data for the rest
		foreach( $array as $arr ) 
		{   
			$csv .= to_csv_line( $arr );
		}
		
		$html_out = "
		
<!--
 *	CSV format generated by Give Me My Data http://givememydata.com a Facebook data liberation application
 *
 *	Instructions: 
 *	1. Copy and paste the contents below into a new plain text file with the .csv extension.
 *	2. Open in any spreadsheet application; Google Docs, Numbers (Mac), Excel (Win).
 *
 *	Comments: $comments
 *	Records: $count
 -->
	 
";
		
		$html_out .= $keys ."\n". $csv;
		
		return $html_out;
	} 
	else
	{
		return "No data was found.";	
	}
}
	
function to_csv_line( $array ) 
{
	$temp = array();
	foreach( $array as $key => $value ) 
	{
		if (is_array($value))
		{
			$temp[] = to_csv_line( $value);
		}
		else
		{
			// trim whitespace
			$value = trim($value);
			
			if (empty($value))
			{
				$value = "NULL";
			}
			else if (strstr($value, ',') && strtotime($value) > 1)
			{
				$value = date("m/d/Y", strtotime($value));		
			}
			else if (strstr($value, ','))
			{
				$value = str_replace(',','-',$value);		
			}
			else
			{
				
				
				// if the value is a number there is a good chance it is a timestamp
				if ( ctype_digit($value) )
				{
					
					$value = convert_timestamp($key, $value);
				}
				
			}
			$temp[] = '"' . addslashes( clean_html($value) ) . '"';
		}
	}
	
	$string = implode( ',', $temp ) . "\n";

	// ghetto, I know
	return str_replace("\n\n","\n",$string);
}








/**
 * Format and return data as "CSV"
 * - The original function I used for a year, eventually realized the data 
 * - was presented with the columns/rows interchanged. Keeping it around to reuse parts.
 *
 * @params	array $data	A 2d array
 * 			string $comments (optional)
 * @return	string
 * @author	Owen Mundy <owenmundy.com>
 */
 /*
function array2csv_ORIG($data, $comments='')
{
	$count = count($data);	// number of records
	
	// Create an array to hold the results
	$result_array = array();
	
	// Recursively traverse a multi-dimensional array
	function traverseArray($array,$level=1,$suffix='')
	{ 
		// Make result array global
		global $result_array;
		
		// Loops through each element,
		foreach($array as $key => $value)
		{ 
			// if element again is array, 
			if(is_array($value))
			{ 
				// function is recalled.
				traverseArray($value,$level+1,$key); 
			}
			else
			{	
				// Make a new key name so description of field is readable
				$new_key = $key.$suffix;
			
				// If not, result is echoed/stored.
				$result_array[$new_key] = $value;	
			} 
		}
		return $result_array;		
	}
	// at this point we have a flattened array
	$result_array = traverseArray($data);
	
	// so now we need to loop through and make it into a CSV and clean some data...
	foreach ($result_array as $key => $value)
	{
		$keys .= $key .", ";
		
		// trim whitespace
		$value = trim($value);
		
		// for some reason the birth date was breaking the CSV even when I wrapped it in quotes
		// so this conditional swaps the date string for a different format
		if (empty($value))
		{
			$values .= "NULL";
		}
		else if (strstr($value, ',') && strtotime($value) > 1)
		{
			$values .= date("m/d/Y", strtotime($value));		
		}
		else if (strstr($value, ','))
		{
			$values .= str_replace(',','-',$value);		
		}
		else
		{
			
			
			// if the value is a number there is a good chance it is a timestamp
			if ( ctype_digit($value) )
			{
				
				$values .= convert_timestamp($key, $value);
			}
			else
			{
				$values .= $value;	
			}
			
		}
		$values .= ", ";
	}
	
	$html_out = "
	
<!--
 *	CSV format generated by Give Me My Data http://givememydata.com a Facebook data liberation application
 *
 *	Instructions: 
 *	1. Copy and paste the contents below into a new plain text file with the .csv extension.
 *	2. Open in any spreadsheet application; Google Docs, Numbers (Mac), Excel (Win).
 *
 *	Comments: $comments
 *	Records: $count
 -->
 
";
	
	$html_out .= $keys ."\n". $values;
	
	return $html_out;  
}

*/




/**
 * Format and return data as "JSON"
 *
 * @params	array $data	A 2d array
 * 			string $comments (optional)
 * @return	string
 * @author	Owen Mundy <owenmundy.com>
 */ 
function array2json($data, $comments='') 
{ 
	$count = count($data);	// number of records
    if(function_exists('json_encode')) $json = json_encode($data);
	$html_out = "
/*
 *	JSON format generated by Give Me My Data http://givememydata.com a Facebook data liberation application
 *
 *	Comments: $comments
 *	Records: $count
 */
 
 ". format_json($json);
 
	return $html_out;
} 



/**
 * Indents a flat JSON string to make it more human-readable.
 *
 * @params	string $json The original JSON string to process
 * @return	string Indented version of the original JSON string
 * @author	http://recursive-design.com/blog/2008/03/11/format-json-with-php/
 */ 
function format_json($json) {

    $result      = '';
    $pos         = 0;
    $strLen      = strlen($json);
    $indentStr   = '  ';
    $newLine     = "\n";
    $prevChar    = '';
    $outOfQuotes = true;

    for ($i=0; $i<=$strLen; $i++) {

        // Grab the next character in the string.
        $char = substr($json, $i, 1);

        // Are we inside a quoted string?
        if ($char == '"' && $prevChar != '\\') {
            $outOfQuotes = !$outOfQuotes;
        
        // If this character is the end of an element, 
        // output a new line and indent the next line.
        } else if(($char == '}' || $char == ']') && $outOfQuotes) {
            $result .= $newLine;
            $pos --;
            for ($j=0; $j<$pos; $j++) {
                $result .= $indentStr;
            }
        }
        
        // Add the character to the result string.
        $result .= $char;

        // If the last character was the beginning of an element, 
        // output a new line and indent the next line.
        if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
            $result .= $newLine;
            if ($char == '{' || $char == '[') {
                $pos ++;
            }
            
            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }
        
        $prevChar = $char;
    }
    return $result;
}





/**
 * Format and return data as "XML"
 *
 * @params	array $data A 2d array
 * 			string $root
 * 			string $tag
 * 			string $comments (optional)
 * @return	String
 * @author	Owen Mundy <owenmundy.com>
 */ 
function array2xml($array, $root, $tag, $comments='') 
{  
	$count = count($array);	// number of records
	
	if ($count > 0)
	{
		// second function for recursion
		function ia2xml($array, $level=1, $node="node") 
		{  
			$xml="\n";
			
			// loop through each value
			foreach ($array as $key=>$value) 
			{  			
				// if numeric key, assume this is a subarray
				if (is_numeric($key)) {
					$key = $node; 				// make new name
				} else {
					$key = strtolower($key);	// otherwise, keep the original name
				}
				
				// If the value is an array
				if (is_array($value)) 
				{  
					$xml .= str_repeat("\t",$level) ."<$key>";		// parent
					$xml .= ia2xml($value,$level+1,strtolower($key));	// child
					$xml .= str_repeat("\t",$level) ."</$key>\n";  		// /parent
				} 
				else
				{
					// add it to the larger string
					$xml .= str_repeat("\t",$level) ."<$key>". clean_value($key,$value) ."</$key>\n";
				}  
			}  
			return $xml;  
		}
		
		$html_out = '<?xml version="1.0" encoding="UTF-8"?>'."
	
<!--
 *	XML format generated by Give Me My Data http://givememydata.com a Facebook data liberation application
 *
 *	Instructions: 
 *	1. Copy and paste the contents below into a new plain text file with the .xml extension.
 *	2. There are many free XML viewers available.
 *
 *	Comments: $comments
 *	Records: $count
 -->
	 
<$root>".ia2xml($array, 1, $tag)."\n</$root>";
		
		return $html_out;  
	} 
	else
	{
		return "No data was found.";	
	}
}








/**
 * Check if value is number or string and treat accordingly
 *
 * @params	string $key
 * 			string $value
 * @return	string
 * @author	Owen Mundy <owenmundy.com>
 */  
function clean_value($key,$value)
{
	
	
	if ( ctype_digit($value) && $value > 1000 )
	{
		// if value is a number > 1000 then probably a timestamp so convert it
		return convert_timestamp($key, $value);
	}
	else
	{
		// else clean the string
		return clean_html($value); 
	}
}






/**
 * Convert timestamp to ISO-8601 date
 *
 * @params	string $key
 * 			string $value
 * @return	string
 * @author	Owen Mundy <owenmundy.com>
 */  
function convert_timestamp($key, $string)
{
	// access timezone
	global $timezone;	
	
	// trim whitespace	
	$string = trim($string);
	
	// list of all keys in FB that contain a timestamp
	$fb_time_names = array (
		'time','profile_update_time','created','modified','created_time','updated_time','update_time','start_time','end_time');

	$c = count($fb_time_names);

	for ($i=0; $i<$c; $i++)
	{
		// if a number and in above array
		if ( strpos($key, $fb_time_names[$i]) !== false)
		{
			// convert it to a ISO-8601 human-readable date
			//return date("Y-m-d", $string) . 'T' . date("H:i:s", $string) . $timezone;
			
			$time_converted = 1;
			break;
		}	
	}
	if ($time_converted == 1)
	{
		return date("c",$string);	
	}
	else
	{
		return $string;	
	}
}







/**
 * Clean all html characters
 *
 * @params	string $string
 * @return	string
 * @author	Owen Mundy <owenmundy.com>
 */  
function clean_html($string)
{
	// trim whitespace	
	$string = trim($string);
	
	// convert smart quotes, etc.	
	// First, replace UTF-8 characters.
	$string = str_replace(
		array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
		array("'", "'", '"', '"', '-', '--', '...'),
		$string);
	// Next, replace their Windows-1252 equivalents.
	$string = str_replace(
		array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
		array("'", "'", '"', '"', '-', '--', '...'),
		$string);
	
	// clean value, converting ampersands twice so they show-up in the form
	$string = str_replace('&','&#38;',htmlspecialchars($string, ENT_QUOTES));
	$string = str_replace('\'','&#38;apos;',$string);
	$string = str_replace('\"','&#38;quote;',$string);
	
	// added this in on 2012-06-02 - not sure why I didn't put it in before...
	$string = str_replace(array("\r","\r\n","\n","\t"),' ', $string);
	
	return $string;
} 





?>