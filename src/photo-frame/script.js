( () => {
  const frameData = {
    scriptArguments: Object.fromEntries( new URL( document.currentScript.src ).searchParams ),
    photo: {
      value: null,
      updated: false,
      update: () => {

        fetch( './index.php?action=checkPhotoUpdate&photoLast=' + encodeURIComponent( frameData.photo.value ) )
        .then( function( response ) {
          return response.json();
        } )
        .then( function( result ) {
          if ( result.filename ) {

            if ( result.filename === frameData.photo.value ) {

              setTimeout( () => {
                frameData.photo.update();
              }, 5000 );

            } else {

              frameData.photo.value = result.filename;
              frameData.photo.updated = result.src;

            }

          }
        } );
      }
    },
    time: {
      value: '',
      updated: false,
      update: () => {
        const date = new Date();
        const timeCurrent = date.getHours().toString() + ':' + date.getMinutes().toString().padStart( 2, '0' );

        if ( timeCurrent !== frameData.time.value ) {
          frameData.time.value = timeCurrent;
          frameData.time.updated = true;
        }
        setTimeout( frameData.time.update, 100 );
      }
    },
    greeting: {
      value: '',
      updated: false,
      update: () => {
        fetch( './index.php?action=greetingUpdate' )
        .then( function( response ) {
          return response.json();
        } )
        .then( function( result ) {
          if ( result.html !== frameData.greeting.value ) {
            frameData.greeting.value = result.html;
            frameData.greeting.updated = true;
          }
          setTimeout( frameData.greeting.update, 60000 );
        } );
      }
    },
    weather: {
      value: '',
      updated: false,
      update: () => {
        fetch( './index.php?action=weatherUpdate' )
        .then( function( response ) {
          return response.json();
        } )
        .then( function( result ) {
          if ( result.html !== frameData.weather.value ) {
            frameData.weather.value = result.html;
            frameData.weather.updated = true;
          }
          setTimeout( frameData.weather.update, 900000 );
        } );
      }
    },
    render: () => {
      
      if ( false !== frameData.photo.updated ) {

        const nextPhoto = new Image(), prev = document.querySelector( 'div.wrapper.prev' ), active = document.querySelector( 'div.wrapper.active' );
        nextPhoto.addEventListener( 'load', () => { 
          setTimeout( () => {
            frameData.photo.update();
          }, 5000 );
        } );
        nextPhoto.src = frameData.photo.updated;
        frameData.photo.updated = false;
        
        frameData.preload.appendChild( nextPhoto );

        prev.style.display = 'none';

        prev.childNodes[0].remove();
        prev.appendChild( frameData.preload.childNodes[0] );

        prev.style.opacity = 0;
        prev.classList.remove( 'prev' );
        prev.classList.add( 'active' );

        active.classList.add( 'prev' );
        active.classList.remove( 'active' );

        setTimeout( () => {

          prev.style.display = 'block';

          setTimeout( () => {
            prev.style.opacity = 1;
          }, 125 );

        }, 125 );
      }
      
      if ( false !== frameData.time.updated ) {
        frameData.time.updated = false;
        frameData.time.element.innerHTML = frameData.time.value;
      }
      
      if ( false !== frameData.greeting.updated ) {
        frameData.greeting.updated = false;
        frameData.greeting.element.innerHTML = frameData.greeting.value;
      }
      
      if ( false !== frameData.weather.updated ) {
        frameData.weather.updated = false;
        frameData.weather.element.innerHTML = frameData.weather.value;
      }
      
      window.requestAnimationFrame( frameData.render );
    },
    tap: {
      count: 0,
      timeout: undefined,
      handler: () => {

        frameData.tap.count++;

        if ( 'undefined' !== typeof frameData.tap.timeout ) {
          clearTimeout( frameData.tap.timeout );
          frameData.tap.timeout = undefined;
        }

        if ( 2 === frameData.tap.count ) {

          frameData.fullscreenToggle();
          frameData.tap.count = 0;

        } else {

          frameData.tap.timeout = setTimeout( () => {

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
    infoToggle: () => {
      if ( frameData.info.classList.contains( 'hidden' ) ) frameData.info.classList.remove( 'hidden' );
      else frameData.info.classList.add( 'hidden' );
    },
    fullscreenToggle: () => {
      if ( document.fullscreenElement ) document.exitFullscreen();
      else document.body.requestFullscreen();
    }
  };

  document.addEventListener( 'DOMContentLoaded', () => {

    frameData.photo.value = frameData.scriptArguments.photo;
    console.dir( frameData.photo );

    frameData.preload = document.querySelector( 'div.preload' );
    setTimeout( () => {
      frameData.photo.update();
    }, 5000 );

    frameData.info = document.querySelector( 'div.info' );

    frameData.time.element = document.querySelector( 'div.time' );
    frameData.time.update();

    frameData.greeting.element = document.querySelector( 'div.greeting' );
    frameData.greeting.update();

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
} )();