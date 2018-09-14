<?php
include 'config.php';
global $db_host, $db_user, $db_name, $db_pass, $key, $hereKey;
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name) or die("Error " . mysqli_error($conn));
$conn->set_charset("latin1");
ini_set("allow_url_fopen", 1);
set_time_limit(500);
// error_reporting(E_ALL ^ E_WARNING);

$url = 'https://maps.googleapis.com/maps/api/geocode/json?address=';
$urlKey = '&key='.$key;

$hereUrl = 'https://geocoder.api.here.com/6.2/geocode.json?searchtext=';
$hereSuffix = '&app_id='.$hereID.'&app_code='.$hereCode;

$query = "SELECT id,address_1, address_2, city, state, zip_code FROM t_regist ORDER BY id ASC";
$result = mysqli_query($conn, $query);
$array = array();
while ($row = mysqli_fetch_array($result)) {
  if ($row['address_2'] == '') {
    $address = $row['address_1'] . "," . $row['city'] . "," . $row['state'] . "," . $row['zip_code'];
    $addr = 1;
  }
  else {
    $address = $row['address_2'] . "," . $row['city'] . "," . $row['state'] . "," . $row['zip_code'];
    $addr = 2;
  }

  $hereSearch = urlencode($row['address_1'] . ' ' . $row['address_2'] . ' ' . $row['city'] . ' ' . 'California');
  $hereAddress = $hereUrl . $hereSearch . $hereSuffix;

  $urlAddress = urlencode($address);
  $urlFull = $url . $urlAddress . $urlKey;

  $json = file_get_contents($urlFull);
  $obj = json_decode($json, true);

  $hereJson = file_get_contents($hereAddress);
  $hereObj = json_decode($hereJson, true);

  if (isset($hereObj['Response']['View'][0]['Result'][0]['Location']['DisplayPosition']['Longitude'])) {
    $hereLng = $hereObj['Response']['View'][0]['Result'][0]['Location']['DisplayPosition']['Longitude'];
    $hereLat = $hereObj['Response']['View'][0]['Result'][0]['Location']['DisplayPosition']['Latitude'];
  }
  else {
    $hereLng = 0;
    $hereLat = 0;
  }

  if ($obj['results'][0]['address_components'][0]['types'][0] == 'locality' || $obj['results'][0]['address_components'][0]['types'][0] == 'postal_code') {
    if ($addr == 1) {
      $address = $row['address_2'] . "," . $row['city'] . "," . $row['state'] . "," . $row['zip_code'];
    }
    else {
      $address = $row['address_1'] . "," . $row['city'] . "," . $row['state'] . "," . $row['zip_code'];
    }
    $urlAddress = urlencode($address);
    $urlFull = $url . $urlAddress . $urlKey;

    $json = file_get_contents($urlFull);
    $obj = json_decode($json, true);

    if ($obj['results'][0]['address_components'][0]['types'][0] == 'locality' || $obj['results'][0]['address_components'][0]['types'][0] == 'postal_code') {
      continue;
    }
  }
  $ver = 'No';
  $lat = $obj['results'][0]['geometry']['location']['lat'];
  $lng = $obj['results'][0]['geometry']['location']['lng'];
  $latDiff = abs($lat - $hereLat);
  $lngDiff = abs($lng - $hereLng);
  if (($latDiff <= 0.01) && ($lngDiff <= 0.01)) {
    $ver = 'Yes';
  }
  $link = 'https://www.google.com/maps/search/?api=1&query='.$lat.','.$lng;
  $hereLink = 'https://www.google.com/maps/search/?api=1&query='.$hereLat.','.$hereLng;

  $urlAddress = urlencode($address);
  $urlFull = $url . $urlAddress . $urlKey;

  $json = file_get_contents($urlFull);
  $obj = json_decode($json, true);

  if ($obj['status'] == "OK") {
    $miniArray['glat'] = $lat;
    $miniArray['glon'] = $lng;
    $miniArray['hlat'] = $hereLat;
    $miniArray['hlon'] = $hereLng;
    $miniArray['a1'] = $row['address_1'];
    $miniArray['a2'] = $row['address_2'];
    $miniArray['c'] = $row['city'];
    $array[] = $miniArray;
    // echo json_encode($miniArray);
  }
}
$finalJson = json_encode($array);
echo "<script>";
// echo "console.log($finalJson);";
echo "window.json = $finalJson;";
// echo "console.log(window.json);";
echo "</script>";
?>

<!DOCTYPE html>
<html>
  <head>
    <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBgFEGhTyZ7Qhd955EtDx-xrBZRyIPK7Rs&callback=initMap">
    </script>
    <style>
       /* Set the size of the div element that contains the map */
      #map {
        height: 600px;  /* The height is 400 pixels */
        width: 100%;  /* The width is the width of the web page */
       }
    </style>
    <script>
// Initialize and add the map
function initMap() {
  console.log(window.json);
  var map = new google.maps.Map(document.getElementById('map'), {
    zoom: 10,
    center: {
      lat: parseFloat('37.488877'),
      lng: parseFloat('-122.2523389')
    }
  });
  var labels = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  for (var i = 0; i < window.json.length; i++) {
    // console.log(i);
    marker = new google.maps.Marker({
      title: 'Google: '+window.json[i]['glat']+', '+window.json[i]['glon']+'\n'+window.json[i]['a1']+' '+window.json[i]['a2']+' '+window.json[i]['c'],
      map: map,
      animation: google.maps.Animation.DROP,
      position: {
        lat: parseFloat(window.json[i]['glat']),
        lng: parseFloat(window.json[i]['glon']),
      },
      label: labels[(i % 26)]
    });
    marker = new google.maps.Marker({
      title: 'Here: '+window.json[i]['hlat']+', '+window.json[i]['hlon']+'\n'+window.json[i]['a1']+' '+window.json[i]['a2']+' '+window.json[i]['c'],
      map: map,
      animation: google.maps.Animation.DROP,
      position: {
        lat: parseFloat(window.json[i]['hlat']),
        lng: parseFloat(window.json[i]['hlon']),
      },
      label: {
        text: labels[(i % 26)],
        color: 'white',
      }
    });
  }
  // marker = new google.maps.Marker({
  //   map: map,
  //   animation: google.maps.Animation.DROP,
  //   position: {
  //     lat: parseFloat('37.488877'),
  //     lng: parseFloat('-122.2523389')
  //   }
  // });
  // marker.addListener('click', toggleBounce);
}
    </script>
  </head>
  <body>
    <h3>Google vs Here</h3>
    <!--The div element for the map -->
    <div id="map"></div>
    <!--Load the API from the specified URL
    * The async attribute allows the browser to render the page while the API loads
    * The key parameter will contain your own API key (which is not needed for this tutorial)
    * The callback parameter executes the initMap() function
    -->
  </body>
</html>
