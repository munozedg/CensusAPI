<?php
header("Access-Control-Allow-Origin: *");
//in the future this will go somewhere else (and may differ)

function make_safe($string) {
    $string = preg_replace("/[^A-Za-z0-9, \+\-.()]/", '', $string);
    return $string;
}

require 'connect.php';
//file with connection information
//setup like:
//$server="server";
//$user="username";
//$password="password";


//$GET Variables
if (isset($_GET['advsumlev'])){$advsumlev = make_safe($_GET['advsumlev']);} //sumlev
if (isset($_GET['advstate'])){$advstate = make_safe($_GET['advstate']);} //state
if (isset($_GET['advsign'])){$advsign = make_safe($_GET['advsign']);} //greater than less than etc
if (isset($_GET['advtext'])){$advtext = make_safe($_GET['advtext']);} //condition  'greater than [advtext]'
if (isset($_GET['advtable'])){$advtable = make_safe($_GET['advtable']);} //census table
if (isset($_GET['advnumerator'])){$advnumerator = make_safe($_GET['advnumerator']);} //numerator
if (isset($_GET['advdenominator'])){$advdenominator = make_safe($_GET['advdenominator']);} //denominator

//declare useful vars
  $fullarray=[]; //final data array

  
// attempt a connection
$dbh = pg_connect("host=".$server." dbname=acs0913 user=".$user." password=".$password);

if (!$dbh) {
    die("Error in connection: " . pg_last_error());
}


//state conditional
$statecond="";
if($advstate<>'ALL'){$statecond=" AND state=".$advstate;}


//join conditional
$joincond="";
if($advtable<>'undefined'){$joincond="natural join data.".$advtable;}



//greater than - less than conditional
$gtcond=">";
if($advsign){
  if($advsign=="gt"){$gtcond=">";}
  if($advsign=="lt"){$gtcond="<";}
  if($advsign=="gte"){$gtcond=">=";}
  if($advsign=="lte"){$gtcond="<=";}  
  if($advsign=="e"){$gtcond="=";}
}

//clean numerator and denominator of 'fp.'
$advnumerator = str_replace("fp.", "", $advnumerator);
$advdenominator = str_replace("fp.", "", $advdenominator);

//clean numerator and denominator of 'Number'
$advnumerator = str_replace("Number", "", $advnumerator);
$advdenominator = str_replace("Number", "", $advdenominator);

//put it all together into the formula conditional
$formula="";
if($advtable<>'undefined'){$formula=" AND ((".$advnumerator.")/(CASE WHEN ".$advdenominator."=0 then null else ".$advdenominator." END)) ".$gtcond." ".$advtext;}


  //CONSTRUCT MAIN SQL STATEMENT
// execute query
$sql = "SELECT geonum from search.data ".$joincond." where sumlev=".$advsumlev.$statecond.$formula.";";

$result = pg_query($dbh, $sql);



  //flag error
if (!$result) {
    die("Error in SQL query: " . pg_last_error());
}

  
  //iterate through every search result row
while ($row = pg_fetch_array($result)) {

  //add record to results array
  array_push($fullarray, $row['geonum']);

  }

      
      header('Content-Type: application/json');
      echo json_encode($fullarray);



?>