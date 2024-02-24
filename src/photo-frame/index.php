<?php

ini_set('display_errors', 0 );
error_reporting( 0 );

if ( isset( $_GET['action'] ) ) {

  $response = false;
  switch( $_GET['action'] ) {
    case 'checkPhotoUpdate':

      $photos_sync = file_get_contents( __DIR__ . '/sync/photo.json' );
      if ( false !== $photos_sync ) {
        $photos_sync = json_decode( $photos_sync );

        $response = (object)[ 'filename' => $photos_sync->filename ];
        if ( !isset( $_GET['photoLast'] ) || $_GET['photoLast'] !== $photos_sync->filename ) {
          $response->src = $photos_sync->src;
        }

      }

      break;
    case 'weatherUpdate':

      $openweathermap_conf = include __DIR__ . '/../../etc/openweathermap.conf';

      $ch = curl_init( 'https://api.openweathermap.org/data/2.5/weather?lat=49.1&lon=-122.7&units=metric&appid=' . urlencode( $openweathermap_conf->appId ) );
      curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
      curl_setopt( $ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ] );
      $ch_response = curl_exec( $ch );
      curl_close( $ch );

      $ch_json = json_decode( $ch_response );
      if ( !is_null( $ch_json ) && isset( $ch_json->cod ) && 200 === $ch_json->cod ) {
        $html = round( $ch_json->main->temp ) . '&deg;C ' . '<img src="https://openweathermap.org/img/wn/' . $ch_json->weather[0]->icon . '@2x.png">';
      } else {
        $html = '&ndash;&deg;C';
      }

      $response = (object)[ 'html' => $html ];

      break;
    case 'greetingUpdate':

      $greeting_conf = include __DIR__ . '/../../etc/greeting.conf';

      if ( false !== $greeting_conf ) {
        $html = strval( $greeting_conf );
      } else {
        $html = '';
      }
      $response = (object)[ 'html' => $html ];

      break;
  }

  http_response_code( 200 );
  header( 'Content-Type: application/json' );
  print json_encode( $response );
  exit;

}

$photos_sync = json_decode( file_get_contents( __DIR__ . '/sync/photo.json' ) );

$photos_first = $photos_sync->filename;
$photos_first_src = $photos_sync->src;

?><html>

<head>

<link rel="manifest" href="../photo-frame.webmanifest">
<link rel="icon" sizes="192x192" type="image/png" href="./favicon.png">

<title>Synchronized Photo Frame</title>

<link rel="stylesheet" href="./style.css">
<script src="./script.js?photo=<?php print htmlentities( (string)$photos_first ); ?>"></script>

</head>

<body>

<div class="preload"></div>

<div class="wrapper prev"><img src="<?php print (string)$photos_first_src; ?>"></div>
<div class="wrapper active"><img src="<?php print (string)$photos_first_src; ?>"></div>

<div class="info hidden">
  <div class="time"></div>
  <div class="weather"></div>
  <div class="greeting"></div>
</div>

</body>

</html>