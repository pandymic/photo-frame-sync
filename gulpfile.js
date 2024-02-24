const gulp = require( 'gulp' );

gulp.task( 'dist', () => {
  return gulp.src( [
    './src/**/*',
    '!./src/**/*.md',
    '!./src/**/.gitignore'
  ] )
    .pipe( gulp.dest( './dist' ) );
} );

gulp.task( 'watch', () => {
  gulp.watch( './src/**/*', gulp.series( 'default' ) );
} );

gulp.task( 'default', gulp.series( 'dist' ) );