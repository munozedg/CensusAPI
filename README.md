# CensusAPI
<h2>The Colorado State Demography Office's Census API (PHP)</h2>

Here is an inventory of the files in this repo:

<b>connect.php</b> contains login credentials to the PostgreSQL Database.  You are free to try the API using the Production Server.  The username given has read-only capabilities.  See the CensusAPI_DB repo for instructions on creating your own copy of the database (if you intend to deploy your own applications).

<b>demog.php</b> allows for a query of any of the currently available datasets.  Output is in either CSV or JSON.

<b>geojson.php</b> is similar to demog.php but accepts arguments for bounding box information and map-zoom level.  Ideal for creating mapping applications.  Output is in GeoJSON.

<b>meta.php</b> retrieves metadata information.

<b>queryapi.html</b> is a basic user interface for accessing the Census API for non-application based data requests.


<h2>General API Instructions</h2>




