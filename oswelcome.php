<?php
error_reporting(0); // this is just so stupid errors are not displayed
/*** *** *** *** *** ***
* @package OpenSim Search page for viewers
* @file    oswelcome.php
* @start   February 04, 2014
* @author  Christopher Strachan
* @license http://www.opensource.org/licenses/gpl-license.php
* @version 1.0.1
* @link    http://www.littletech.net
*** *** *** *** *** ***
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*** *** *** *** *** ***
* Comments are always before the code they are commenting.
*** *** *** *** *** ***/
$now = time(); // the time now in seconds since Jan 1 1970.

$grid_name = "Your Grid"; // ignore this if using a logo image
$logoimg = ""; // if there is a image here it will replace $grid_name on the page.
$twittername = "";
$dir = "bgimg"; // directory aka folder where your background images aka screenshots will go
$ShowStats = true; // show stats count?
$loginuri = "http://localhost:8002/"; // This is the address found in Robust.ini for Grid, Opensim.ini for Standalone.
$ip2robust = "localhost"; // IP or domain to the robust server. This is used to see if Robust.exe (or OpenSim.exe for Standalone) is online.
$port2robust = "8002"; // 8002 for Grid Robust.exe, 9000 for Standalone OpenSim.exe
/*****
* if you are just forwarding a subdomain to your robust ip to be used as a loginURI,
* please still put the ip in $ip2robust
* this is so this script can do a direct check to see if robust is online or not.
* the ip must be the same as in your robust.ini for LoginURI
*****/

// Database connect info to the robust database.
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_port = "3306";

// where accounts are stored. ie, gridusers and useraccounts. Just need these for counting records, nothing else.
$db_opensim = "opensim";
// where popularplaces table is. This is for destinations to display on the login screen.
$db_osmod = "osmodules";

$null_key = "00000000-0000-0000-0000-000000000000";

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_opensim);

if (mysqli_connect_errno()) {
    echo "Connect failed";
    exit();
}

if ($fp = fsockopen($ip2robust, $port2robust, $errno, $errstr, 1)) {
	$online = TRUE;
}else{
	$online = FALSE;
}
fclose($fp);

if ($online == TRUE) {
	$onoff = "Online";
	$onoffcolour = "#00EE00";
}else if ($online == FALSE) {
	$onoff = "Offline";
	$onoffcolour = "#FA1D2F";
}

$fiveago = $now - 300;
$onlineq = $mysqli->query("SELECT * FROM Presence WHERE RegionID != '{$zw->nullkey}'");
$online = $onlineq->num_rows;

$totalq = $mysqli->query("SELECT * FROM UserAccounts");
$totalc = $totalq->num_rows;

$monthago = $now - 2592000;
$latestq = $mysqli->query("SELECT * FROM GridUser WHERE Login > '$monthago' AND Login != '0'");
$latestc = 0;
while ($latestr = $latestq->fetch_array(MYSQLI_BOTH)) {
	$checkmuuid = $latestr['UserID'];
	$checkusermq = $mysqli->query("SELECT * FROM UserAccounts WHERE PrincipalID = '$checkmuuid'");
	$checkusermn = $checkusermq->num_rows;
	if ($checkusermn) {
		$latestc++;
	}
}
$latestq->close();

$activeperc = $latestc / $totalc;
$activepercent = $activeperc * 100;

$hoursago = $now - 86400;
$latest24q = $mysqli->query("SELECT * FROM GridUser WHERE Login > '$hoursago' AND Login != '0'");
$latest24c = 0;
while ($latest24r = $latest24q->fetch_array(MYSQLI_BOTH)) {
	$check24uuid = $latest24r['UserID'];
	$checkuser24q = $mysqli->query("SELECT * FROM UserAccounts WHERE PrincipalID = '$check24uuid'");
	$checkuser24n = $checkuser24q->num_rows;
	if ($checkuser24n) {
		$latest24c++;
	}
}
$latest24q->close();

$defsize = "256";
$defsqm = "65536";
$sizextotal = "";
$sizeytotal = "";
$sizetotal = "";
$totalsingleregions = "";
$regionq = $mysqli->query("SELECT * FROM regions");
$regionc = $regionq->num_rows;
while($regionr = $regionq->fetch_array(MYSQLI_BOTH)) {
	$x = $regionr['sizeX'];
	$y = $regionr['sizeY'];
	$sizextotal += $x;
	$sizeytotal += $y;
	$xytotal = $x * $y;
	$sizetotal += $xytotal;
	if ($xytotal >= $defsqm) {
		$totalsingleregions += $xytotal / $defsqm;
	}else if($xytotal == $defsqm) {
		++$totalsingleregions;
	}
}
$regionq->close();

if ($logoimg) {
	$logo = "<img src='$logoimg' border='0'>";
}else{
	$logo = "<h1>" . $grid_name . "</h1>";
}

if ($_GET['api']) {
	$apiarray = array('Status' => $onoff, "UsersOnline" => $online, 'TotalUsers' => $totalc, 'ActivePercent' => $activepercent, 'TotalRegions' => $regionc, 'TotalSingleRegions' => $totalsingleregions, 'Active30Days' => $latestc, 'Active24Hours' => $latest24c);
	$api = $_GET['api'];
	if ($api == "online") {
		echo $onoff;
	}
	if ($api == "usersonline" && $ShowStats) {
		echo $online;
	}
	if ($api == "totalusers" && $ShowStats) {
		echo $totalc;
	}
	if ($api == "totalregions" && $ShowStats) {
		echo $regionc;
	}
	if ($api == "active30days" && $ShowStats) {
		echo $latestc;
	}
	if ($api == "totalsingleregions" && $ShowStats) {
		echo $totalsingleregions;
	}
	if ($api == "activepercent" && $ShowStats) {
		echo $activepercent;
	}
	if ($api == "json") {
		echo json_encode($apiarray);
	}
	if ($api == "xmlrpc") {
		echo xmlrpc_encode($apiarray);
	}
	if ($api == "lsl") {
		echo $onoff."=".$online."=".$totalc."=".$regionc."=".$latest24c."=".$latestc."=".$totalsingleregions."=".$activepercent;
	}
}else{
$dir = "bgimg"; // directory aka folder where your background images aka screenshots will go
if (is_dir($dir))
{
	if ($dh = opendir($dir))
	{
		while (false !== ($file = readdir($dh)))
		{
			if ($file == '.' || $file == '..') { 
			}else{
			$jbgimg .= "'" . $file . "', ";
			$cbgimg = $file;
			}
		}
	closedir($dh);
	}
	$jquerypics .= $jbgimg."~~";
	$jquerypics = str_replace(", ~~", "", $jquerypics);
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />

  <title>Welcome to <?php echo $grid_name; ?></title>
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
  <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
<script>
var newBg = [<?php echo $jquerypics; ?>];
var path="<?php echo $dir; ?>/";
var i = 0;
var rotateBg = setInterval(function(){
    $('body').css('background-image' ,  "url('" +path+newBg[i]+ "')");
    i++;
}, 20000);
</script>
<script>
var newBg = [<?php echo $jquerypics; ?>];
var path="<?php echo $dir; ?>/";
var i = 0;
var rotateBg = setInterval(function(){
    $('body').css('backgroundImage' ,  "url('" +path+newBg[i]+ "')");
    i++;
}, 5000);
</script>
<style>
body
{
background-image: url('<?php echo $dir."/".$cbgimg; ?>');
background-repeat: no-repeat;
padding-top: 25px;
padding-bottom: 0px;
background-size:100% 110%;
}
.ceil {
background-color: #1c1c1c;
color: #ffffff;
-webkit-border-radius: 8px;
-moz-border-radius: 8px;
overflow:hidden;
border-radius: 10px; 
overflow: hidden;
}
</style>
</head>
<body>
<div class="container-fluid">
	<div class='row'>
		<div class='col-md-2 ceil'>
			<center>
				<?php echo $logo; ?>
			</center>
		</div>
		<div class='col-md-8'>
			<!-- Blank middle spot -->
		</div>
		<div class='col-md-2 ceil'>
			<B><?php echo "<font color='".$onoffcolour."'>Grid is ".$onoff."</font>"; ?></B><br>
			<?php
			if ($ShowStats) {
			?>
			Users in world: <?php echo $online; ?><br>
			Online Last 30 days: <?php echo $latestc; ?><br>
			Online Last 24 hours: <?php echo $latest24c; ?><br>
			Active Percentage: <?php echo $activepercent; ?>%<br>
			Total Users: <?php echo $totalc; ?><br>
			Total Regions: <?php echo $regionc; ?> <small>(<?php echo $totalsingleregions; ?>*)</small><br>
			<small>* Total count for all sims as if they were <?php echo $defsize." by ".$defsize; ?> meters</small>
			<?php
			}
			?>
		</div>
	</div>
</div>
<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
</body>
</html>
<?php
} // ends if ($_GET['stat'])

$mysqli->close();
?>
