<?php
header("Access-Control-Allow-Origin: *");


//strict
function make_safe($string) {
    $string = preg_replace("/[^A-Za-z0-9, \-.]/", '', $string);
    return $string;
}

require 'connect.php';
//file with connection information
//setup like:
//$server="server";
//$user="username";
//$password="password";


//$GET Variables

//potential multi select (comma delimited list)
if (isset($_GET['db'])){$db = make_safe($_GET['db']);} 
if (isset($_GET['schema'])){$schema = make_safe($_GET['schema']);}

//declare useful vars
  $tblarrfull=[];  //final table metadata array
  $tblarr=[];

// attempt a connection
$dbh = pg_connect("host=".$server." dbname=".$db." user=".$user." password=".$password);

if (!$dbh) {
    die("Error in connection: " . pg_last_error());
}


  //Query metadata
  $tblsql="SELECT table_id, table_title, universe from ".$schema.".census_table_metadata;";
  $tblresult = pg_query($dbh, $tblsql);
  
while ($tblrow = pg_fetch_array($tblresult)) {

  //add metadata information to metadata array for each (non-moe) field
  $tblarr=array('table_id' => $tblrow['table_id'], 'table_title' => $tblrow['table_title'], 'universe' => $tblrow['universe']);
    array_push($tblarrfull, $tblarr);
  }

 usort($tblarrfull, "cmp2"); //sort table field array  

      header('Content-Type: application/json');
      echo json_encode($tblarrfull);




//supporting functions



//sort table meta array
function cmp2($a, $b)
{
    return strcmp($a["table_id"], $b["table_id"]);
}

?>