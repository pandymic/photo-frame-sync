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

<link rel="manifest" href="/frame-sync.webmanifest">
<link rel="icon" sizes="192x192" type="image/png" href="./favicon.png">

<title>Synchronized Photo Frame</title>

<style>

@import url('https://fonts.googleapis.com/css2?family=Albert+Sans:wght@300&display=swap');

html, 
body {
  padding: 0;
  margin: 0;
  overflow: hidden;
}

body {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
}

.wrapper, 
.preload {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
}

.wrapper.active {
  transition: opacity 2.5s ease-out;
  z-index: 1;
}

.preload {
  z-index: -1;
}

.wrapper img, 
.preload img {
  display: block;
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.info {
  position: absolute;
  right: 3.5vw;
  bottom: 3.5vw;

  color: #fff;
  font-family: 'Albert Sans', sans-serif;
  font-weight: 300;
  line-height: 1;
  text-shadow: 1px 1px 2.5px rgba(0,0,0,0.5);

  opacity: 0.85;

  z-index: 2;

  pointer-events: none;
  transition: opacity 250ms ease-out;
}

.info.hidden {
  opacity: 0;
}

.info .time {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  font-size: 12.5vh;
}

.info .weather {
  display: flex;
  justify-content: flex-end;
  align-items: stretch;
  font-size: 5vh;
}

.info .weather img {
  height: 5vh;
  aspect-ratio: 1;
  filter: grayscale(1);
  order: -1;
}

.info .countdown {
  display: flex;
  justify-content: flex-end;
  align-items: stretch;
  font-size: 5vh;
}

</style>

<script>

var frameData = {
  photo: {
    last: <?php print json_encode( (string)$photos_first ); ?>,
    updated: false,
    update: function() {

      fetch( './index.php?action=checkPhotoUpdate&photoLast=' + encodeURIComponent( frameData.photo.last ) )
      .then( function( response ) {
        return response.json();
      } )
      .then( function( result ) {
        if ( result.filename ) {

          if ( result.filename === frameData.photo.last ) {

            setTimeout( function() {
              frameData.photo.update();
            }, 5000 );

          } else {

            frameData.photo.last = result.filename;
            frameData.photo.updated = result.src;

          }

        }
      } );
    }
  },
  time: {
    last: '',
    updated: false,
    update: function() {
      var date = new Date();
      var timeCurrent = date.getHours().toString() + ':' + date.getMinutes().toString().padStart( 2, '0' );

      if ( timeCurrent !== frameData.time.last ) {
        frameData.time.last = timeCurrent;
        frameData.time.updated = true;
      }
      setTimeout( frameData.time.update, 100 );
    }
  },
  weather: {
    last: '',
    updated: false,
    update: function() {
      fetch( './index.php?action=weatherUpdate' )
      .then( function( response ) {
        return response.json();
      } )
      .then( function( result ) {
        if ( result.html !== frameData.weather.last ) {
          frameData.weather.last = result.html;
          frameData.weather.updated = true;
        }
        setTimeout( frameData.weather.update, 60000 );
      } );
    }
  },
  render: function() {
    
    if ( false !== frameData.photo.updated ) {

      var nextPhoto = new Image(), last = document.querySelector( 'div.wrapper.last' ), active = document.querySelector( 'div.wrapper.active' );
      nextPhoto.addEventListener( 'load', function() { 
        setTimeout( function() {
          frameData.photo.update();
        }, 5000 );
      } );
      nextPhoto.src = frameData.photo.updated;
      frameData.photo.updated = false;
      
      frameData.preload.appendChild( nextPhoto );

      last.style.display = 'none';

      last.childNodes[0].remove();
      last.appendChild( frameData.preload.childNodes[0] );

      last.style.opacity = 0;
      last.classList.remove( 'last' );
      last.classList.add( 'active' );

      active.classList.add( 'last' );
      active.classList.remove( 'active' );

      setTimeout( function() {

        last.style.display = 'block';

        setTimeout( function() {
          last.style.opacity = 1;
        }, 125 );

      }, 125 );
    }
    
    if ( false !== frameData.time.updated ) {
      frameData.time.updated = false;
      frameData.time.element.innerHTML = frameData.time.last;
    }
    
    if ( false !== frameData.weather.updated ) {
      frameData.weather.updated = false;
      frameData.weather.element.innerHTML = frameData.weather.last;
    }
    
    window.requestAnimationFrame( frameData.render );
  },
  tap: {
    count: 0,
    timeout: undefined,
    handler: function() {

      frameData.tap.count++;

      if ( 'undefined' !== typeof frameData.tap.timeout ) {
        clearTimeout( frameData.tap.timeout );
        frameData.tap.timeout = undefined;
      }

      if ( 2 === frameData.tap.count ) {

        frameData.fullscreenToggle();
        frameData.tap.count = 0;

      } else {

        frameData.tap.timeout = setTimeout( function() {

          switch ( frameData.tap.count ) {
            case 1:
              frameData.infoToggle();
              break;
            case 2:
              frameData.fullscreenToggle();
              break;
          }
          frameData.tap.count = 0;
          frameData.tap.timeout = undefined;

        }, 225 );

      }

    }
  },
  infoToggle: function() {
    if ( frameData.info.classList.contains( 'hidden' ) ) frameData.info.classList.remove( 'hidden' );
    else frameData.info.classList.add( 'hidden' );
  },
  fullscreenToggle: function() {
    if ( document.fullscreenElement ) document.exitFullscreen();
    else document.body.requestFullscreen();
  }
};

document.addEventListener( 'DOMContentLoaded', function() {

  frameData.preload = document.querySelector( 'div.preload' );
  setTimeout( function() {
    frameData.photo.update();
  }, 5000 );

  frameData.info = document.querySelector( 'div.info' );

  frameData.time.element = document.querySelector( 'div.time' );
  frameData.time.update();

  frameData.weather.element = document.querySelector( 'div.weather' );
  frameData.weather.update();
  
  window.requestAnimationFrame( frameData.render );

  document.body.addEventListener( 'click', function( e ) {
    e.preventDefault();
    frameData.tap.handler();
  } );

  document.body.addEventListener( 'touchstart', function( e ) {
    e.preventDefault();
    frameData.tap.handler();
  }, { passive: false } );

} );

</script>

</head>

<body>

<div class="preload"></div>

<div class="wrapper last"><img src="<?php print (string)$photos_first_src; ?>"></div>
<div class="wrapper active"><img src="<?php print (string)$photos_first_src; ?>"></div>

<div class="info hidden">
  <div class="time"></div>
  <div class="countdown"></div> 
  <div class="weather"></div> 
</div>

</body>

</html>
