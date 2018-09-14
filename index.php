<?php
include 'config.php';
global $db_host, $db_user, $db_name, $db_pass, $key, $hereKey;
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name) or die("Error " . mysqli_error($conn));
$conn->set_charset("latin1");
ini_set("allow_url_fopen", 1);
set_time_limit(500);
error_reporting(E_ALL ^ E_WARNING);

$url = 'https://maps.googleapis.com/maps/api/geocode/json?address=';
$urlKey = '&key='.$key;

$hereUrl = 'https://geocoder.api.here.com/6.2/geocode.json?searchtext=';
$hereSuffix = '&app_id='.$hereID.'&app_code='.$hereCode;

$query = "SELECT id,address_1, address_2, city, state, zip_code FROM t_regist ORDER BY id ASC";
$result = mysqli_query($conn, $query);
?>
<html>
  <head>
    <title>Coordinates</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>

    <style>
    .table-hover tbody tr:hover td {
    background: #E8B4FE;
    }
    a {
      color: black;
    }
    a:hover {
      color: #8f61e5;
    }
    </style>
  </head>

  <body>
    <div class="wrapper">
      <div class="container-fluid">
        <div class="col-sm">
          <div class="row">
            <h2>Coordinates</h2>
          </div>
          <div class="row">
            <h6><b><i>Bold</i></b> rows means they might be incorrect (Google and Here results are very different)</h6>
          </div>
          <div class="row">
            <h6>Verified row is comparing Google Geocoder result with Here Geocoder with a small margin of error</h6>
          </div>
          <div class="row">
            <h6>Click on values in the Google Lat/Long or Here Lat/Lon to view them in Google Maps</h6>
          </div>
          <div class="page-hedaer clearfix">
            <table class="table-striped table-hover table">
              <thead>
                <tr class='bg-dark text-white'>
                  <th scope="col">ID</th>
                  <th scope="col">Address 1</th>
                  <th scope="col">Address 2</th>
                  <th scope="col">City</th>
                  <th scope="col">Google Lat</th>
                  <th scope="col">Google Lon</th>
                  <th scope="col">Verified</th>
                  <th scope="col">Here Lat</th>
                  <th scope="col">Here Lon</th>
                  <th scope="col">URL</th>
                </tr>
              </thead>
              <tbody>
              <?php

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
    if ($ver == 'No') {
      $lat = $obj['results'][0]['geometry']['location']['lat'];
      $lng = $obj['results'][0]['geometry']['location']['lng'];

      $latDiff = abs($lat - $hereLat);
      $lngDiff = abs($lng - $hereLng);
      $ver = 'No';
      if (($latDiff <= 0.01) && ($lngDiff <= 0.01)) {
        $ver = 'Yes';
      }
      echo "<tr>";

      echo "<td><b><i>";
      echo $row['id'];
      echo "</b></i></td>";

      echo "<td><b><i>";
      echo $row['address_1'];
      echo "</b></i></td>";

      echo "<td><b><i>";
      echo $row['address_2'];
      echo "</b></i></td>";

      echo "<td><b><i>";
      echo $row['city'];
      echo "</b></i></td>";

      echo "<td><b><i>";
      echo "<a href=$link>".$lat."</a>";
      echo "</b></i></td>";

      echo "<td><b><i>";
      echo "<a href=$link>".$lng."</a>";
      echo "</b></i></td>";

      echo "<td><b><i>";
      echo $ver;
      echo "</b></i></td>";

      echo "<td><b><i>";
      echo "<a href=$hereLink>".$hereLat."</a>";
      echo "</b></i></td>";

      echo "<td><b><i>";
      echo "<a href=$hereLink>".$hereLng."</a>";
      echo "</b></i></td>";
    }
    else {
      $lat = $obj['results'][0]['geometry']['location']['lat'];
      $lng = $obj['results'][0]['geometry']['location']['lng'];

      $latDiff = abs($lat - $hereLat);
      $lngDiff = abs($lng - $hereLng);

      $ver = 'No';
      if (($latDiff <= 0.01) && ($lngDiff <= 0.01)) {
        $ver = 'Yes';
      }
      echo "<tr>";

      echo "<td>";
      echo $row['id'];
      echo "</td>";

      echo "<td>";
      echo $row['address_1'];
      echo "</td>";

      echo "<td>";
      echo $row['address_2'];
      echo "</td>";

      echo "<td>";
      echo $row['city'];
      echo "</td>";

      echo "<td>";
      echo "<a href=$link>".$lat."</a>";
      echo "</td>";

      echo "<td>";
      echo "<a href=$link>".$lng."</a>";
      echo "</td>";

      echo "<td>";
      echo $ver;
      echo "</td>";

      echo "<td>";
      echo "<a href=$hereLink>".$hereLat."</a>";
      echo "</td>";

      echo "<td>";
      echo "<a href=$hereLink>".$hereLng."</a>";
      echo "</td>";
    }

  }
  else {
    echo "<td>----</td>";
    echo "<td>----</td>";
  }

  echo "<td>".$hereAddress."</td>";
  echo "</tr>";
}
$conn->close(); ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
