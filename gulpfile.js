const gulp = require( 'gulp' ),
  log = require( 'fancy-log' ),
  shell = require( 'child_process' ).exec;

gulp.task( 'dist', () => {
  return gulp.src( [
    './src/**/*',
    '!./src/photo-frame/sync',
    '!./src/**/*.md',
    '!./src/**/.gitignore'
  ] )
    .pipe( gulp.dest( './dist' ) );
} );

gulp.task( 'permissions', ( callback ) => {
  shell( 'cd ./dist && find . -user $USER -not -group apache -exec chgrp apache {} \\;', ( err, stdout, stderr ) => {
    log.info( 'File group updated...' );
    if ( stdout.length ) log( stdout.trim() );
    if ( stderr.length ) log.error( stderr.trim() );
    callback( err );
  } );
  shell( 'cd ./dist/bin && chmod u+x,g+x photo-frame-sync', ( err, stdout, stderr ) => {
    log.info( 'Binary file executable bit updated...' );
    if ( stdout.length ) log( stdout.trim() );
    if ( stderr.length ) log.error( stderr.trim() );
    callback( err );
  } );
} );

gulp.task( 'watch', () => {
  gulp.watch( './src/**/*', gulp.series( 'default' ) );
} );

gulp.task( 'default', gulp.series( 'dist', 'permissions' ) );