<?php

function map_icon_msc_to_wi( $code = false ) {

  $wi_code = null;

  if ( filter_var( $code, FILTER_VALIDATE_INT ) ) {

    $code = intval( $code );

    switch( $code ) {
      case 0:
      case 1:
        $wi_code = 'wi-day-sunny';
        break;
      case 2:
      case 3:
      case 4:
      case 5:
        $wi_code = 'wi-day-cloudy';
        break;
      case 6:
        $wi_code = 'wi-day-showers';
        break;
      case 7:
        $wi_code = 'wi-day-rain-mix';
        break;
      case 8:
        $wi_code = 'wi-day-snow';
        break;
      case 9:
        $wi_code = 'wi-day-storm-showers';
        break;
      case 10:
        $wi_code = 'wi-cloud';
        break;
      case 11:
      case 12:
        $wi_code = 'wi-showers';
        break;
      case 13:
        $wi_code = 'wi-rain';
        break;
      case 14:
        $wi_code = 'wi-hail';
        break;
      case 15:
        $wi_code = 'wi-rain-mix';
        break;
      case 16:
      case 17:
      case 18:
        $wi_code = 'wi-snow';
        break;
      case 19:
        $wi_code = 'wi-thunderstorm';
        break;
      case 20:
      case 21:
      case 22:
      case 23:
      case 24:
        $wi_code = 'wi-cloudy';
        break;
      case 25:
        $wi_code = 'wi-sandstorm';
        break;
      case 26:
        $wi_code = 'wi-snow';
        break;
      case 27:
        $wi_code = 'wi-snow';
        break;
      case 28:
        $wi_code = 'wi-showers';
        break;
      case 30:
        $wi_code = 'wi-night-clear';
        break;
      case 31:
      case 32:
      case 33:
      case 34:
      case 35:
        $wi_code = 'wi-night-alt-cloudy';
        break;
      case 36:
        $wi_code = 'wi-night-alt-showers';
        break;
      case 37:
        $wi_code = 'wi-night-alt-rain-mix';
        break;
      case 38:
        $wi_code = 'wi-night-alt-snow';
        break;
      case 39:
        $wi_code = 'wi-night-alt-thunderstorm';
        break;
      case 40:
        $wi_code = 'wi-sandstorm';
        break;
      case 41:
      case 42:
        $wi_code = 'wi-tornado';
        break;
      case 43:
        $wi_code = 'wi-strong-wind';
        break;
      case 44:
        $wi_code = 'wi-fire';
        break;
      case 45:
        $wi_code = 'wi-sandstorm';
        break;
      case 46:
      case 47:
        $wi_code = 'wi-thunderstorm';
        break;
      case 48:
        $wi_code = 'wi-tornado';
        break;
    }

  }

  return $wi_code;
}

$now = time();

if ( isset( $_GET['action'] ) ) {

  ini_set('display_errors', 0 );
  error_reporting( 0 );

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

      $html = '&ndash;&deg;C';

      $weather_cached = json_decode( file_get_contents( __DIR__ . '/sync/weather.json' ), true );
      if ( false !== $weather_cached && is_array( $weather_cached ) && isset( $weather_cached['expires'] ) && $weather_cached['expires'] > $now ) {
        $weather = $weather_cached['data'];
      } else {
        $msc_weather_data = simplexml_load_string( file_get_contents( 'https://dd.weather.gc.ca/citypage_weather/xml/BC/s0000866_e.xml' ) );
        if ( false !== $msc_weather_data ) {
          if ( $msc_weather_data instanceof SimpleXMLElement ) {
            if ( isset( $msc_weather_data->currentConditions ) && $msc_weather_data->currentConditions instanceof SimpleXMLElement ) {
    
              $weather = json_decode( json_encode( $msc_weather_data->currentConditions ), true );

              if ( empty( $weather['condition'] ) && !empty( $weather_cached['data']['condition'] ) ) $weather['condition'] = $weather_cached['data']['condition'];
              if ( empty( $weather['iconCode'] ) && !empty( $weather_cached['data']['iconCode'] ) ) $weather['iconCode'] = $weather_cached['data']['iconCode'];

              $weather_cached = (object)[
                'expires' => $now + 900,
                'data' => $weather,
              ];
              file_put_contents( __DIR__ . '/sync/weather.json', json_encode( $weather_cached ) );
    
            }
          }
        }
      }

      if ( isset( $weather ) ) {
  
        $html = (int)$weather['temperature'] . '&deg;C';
        if ( is_string( $weather['iconCode'] ) ) $html .= ' ' . str_replace( '<svg', '<svg title="' . ( !empty( $weather['condition'] ) ? htmlentities( $weather['condition'] ) : '' ) . '"', preg_replace( '/^.*(<svg.+<\/svg>).*/s', '\1', file_get_contents( __DIR__ . '/weather-icons/svg/' . map_icon_msc_to_wi( $weather['iconCode'] ) . '.svg' ) ) );

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