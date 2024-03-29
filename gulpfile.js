const gulp = require('gulp');
const concat = require('gulp-concat');
const uglify = require('gulp-uglify-es').default;

//script paths
const jsDest = 'assets/js';

gulp.task('magic', () => {
  return gulp
    .src(['assets/js/src/bento-wordpress-sdk.js'])
    .pipe(concat('bento-wordpress-sdk.min.js'))
    .pipe(gulp.dest(jsDest))
    .pipe(uglify())
    .pipe(gulp.dest(jsDest));
});

gulp.task('watch', function () {
  gulp.watch('assets/js/src/bento-wordpress-sdk.js', gulp.series('magic'));
});
