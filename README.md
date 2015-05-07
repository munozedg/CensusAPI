# CensusAPI
<h2>The Colorado State Demography Office's Census API (PHP)</h2>

Here is an inventory of the files in this repo:

<b>connect.php</b> contains login credentials to the PostgreSQL Database.  You are free to try the API using the Production Server.  The username given has read-only capabilities.  See the CensusAPI_DB repo for instructions on creating your own copy of the database (if you intend to deploy your own applications).

<b>demog.php</b> allows for a query of any of the currently available datasets.  Output is in either CSV or JSON.

<b>geojson.php</b> is similar to demog.php but accepts arguments for bounding box information and map-zoom level.  Ideal for creating mapping applications.  Output is in GeoJSON.

<b>meta.php</b> retrieves metadata information.

<b>queryapi.html</b> is a basic user interface for accessing the Census API for non-application based data requests.


<h2>General API Instructions - demog.php</h2>

<b>Address:</b>  http://54.69.15.55/CensusAPI/demog.php?

<h3>Parameters</h3>

<b>db</b>: currently valid are acs0913, acs0812, c2010, c2000, c1990, c1980.  Default is acs0913

<b>type</b>: json or csv   (default is json)

<b>schema</b>: no need to specify if using acs or 2010 census.  specify sf1 or sf3 for 1980, 1990, and 2000 census.  If you don't specify it will assume sf1.

<b>field</b>: comma delimited list of fields.  If you specify fields, table parameter will be ignored. 
1980 census format ex: t01001,t11003,t38013
1990, 2000, 2010 census format ex: p1001,p20003,h17a001

<b>table</b>: comma delimited list of tables.
1980 census format ex: t01,t11,t38
1990, 2000, 2010 census format ex: p1,p20,h17a

<b>sumlev</b>: 40 is state, 50 is county, 140 is tract, 150 is block group, 160 is place

<b>state</b>: any state (integer format, no leading zeros).

<b>county</b>: any county (integer format, no leading zeros).  would be smart to also specify state.

<b>geoid</b>:  comma delimited list. (no quotes, leading zeros are necessary)  example: '08' is Colorado, '08031' is Denver County ,  '08031000701' is a Census Tract in Denver County, and '080010078011' is a block group in Adams County, and '0668154' is the city of San Luis Obispo, CA. 
(You can also use the integer format for geoid –geonum- by prefixing the geoid with ‘1’.  San Luis Obispo would then be 10668154.  There are good but technical reasons why I strongly favor the alternate format).

<b>limit</b>:  by default, limit is set to 100 records.  you can override it by specifying a new limit (not required)

<b>moe</b>: set moe=yes to add margin of error fields for acs databases (not required)

example:
(return csv of sex by age table from acs0812 for delaware)
http://54.69.15.55/CensusAPI/demog.php?db=acs0812&table=b01001&sumlev=140&state=10&type=csv

example:
(return json for median home value for 1980 census for places in vermont)
http://54.69.15.55/CensusAPI/demog.php?db=c1980&table=t38&sumlev=160&state=50

example:
(return json for median age for 2010 census for San Luis Obispo, CA)
http://54.69.15.55/CensusAPI/demog.php?db=c2010&table=p13&geonum=10668154


<h2>General API Instructions - geojson.php</h2>

<b>Address:</b>  http://54.69.15.55/CensusAPI/geojson.php?

<h3>Parameters</h3>

<b>db</b>: currently valid are acs0913, acs0812, c2010, c2000, c1990, c1980.  Default is acs0913

<b>schema</b>: no need to specify if using acs or 2010 census.  specify sf1 or sf3 for 1980, 1990, and 2000 census.  If you don't specify it will assume sf1.

<b>field</b>: comma delimited list of fields.  If you specify fields, table parameter will be ignored. 
1980 census format ex: t01001,t11003,t38013
1990, 2000, 2010 census format ex: p1001,p20003,h17a001

<b>table</b>: comma delimited list of tables.
1980 census format ex: t01,t11,t38
1990, 2000, 2010 census format ex: p1,p20,h17a

<b>sumlev</b>: 40 is state, 50 is county, 140 is tract, 150 is block group, 160 is place (required)

<b>state</b>: any state (integer format, no leading zeros).

<b>county</b>: any county (integer format, no leading zeros).  would be smart to also specify state.

<b>geoid</b>:  comma delimited list. (no quotes, leading zeros are necessary)  example: '08' is Colorado, '08031' is Denver County ,  '08031000701' is a Census Tract in Denver County, and '080010078011' is a block group in Adams County, and '0668154' is the city of San Luis Obispo, CA. 

<b>limit</b>:  by default, limit is set to 100 records.  you can override it by specifying a new limit (not required)

<b>moe</b>: set moe=yes to add margin of error fields for acs databases (not required)

<b>zoom</b>: specify the zoom level of the map (from 3 to 16) to return geometry appropriate to that zoom level.  Currently required - will fix this to default to no simplification.

<b>bb</b>: bounding box coordinates of geojson search ex: bb=-105,40,-104,39 (not required)

example query: 
(counties in colorado that intersect a specific bounding box, plus data from table p1 of 1990 census)
http://54.69.15.55/CensusAPI/geojson.php?table=p1&sumlev=50&db=c1990&state=8&bb=-105,40,-104,39&zoom=10


<h2>General API Instructions - meta.php</h2>

<b>Address:</b>  http://54.69.15.55/CensusAPI/meta.php?

<h3>Parameters</h3>

<b>db</b>: currently valid are acs0913, acs0812, c2010, c2000, c1990, c1980.  Required - no default

<b>schema</b>: specify 'sf1' or 'sf3' for c1980, c1990, and c2000.  Specify 'data' for c2010, acs0913, acs0812. Required - no default

example query:
(table metadata for 1990 Census Summary File 1)
http://54.69.15.55/CensusAPI/meta.php?db=c1990&schema=sf1
