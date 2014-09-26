'use strict';

module.exports = function(grunt) {

  // Load grunt tasks automatically
  require('load-grunt-tasks')(grunt);

  // Time how long tasks take. Can help when optimizing build times
  require('time-grunt')(grunt);

  // Define the configuration for all the tasks
  grunt.initConfig({

    // mautic assets dir path
    mautic: {
      // configurable paths
      coreBundleAssets: 'app/bundles/coreBundle/Assets',
      rootAssets: 'assets'
    },

    // Watches files for changes and runs tasks based on the changed files
    watch: {
      less: {
        files: ['<%= mautic.coreBundleAssets %>/css/**/*.less'],
        tasks: ['less', /*'autoprefixer',*/ 'copy', 'cssmin']
      }
    },

    // Compile LESS files to CSS
    less: {
      coreBundleAssets: {
        files: {
          // mautic app
          '<%= mautic.coreBundleAssets %>/css/1.bootstrap.css': '<%= mautic.coreBundleAssets %>/css/1.bootstrap.less',
          '<%= mautic.coreBundleAssets %>/css/2.app.css': '<%= mautic.coreBundleAssets %>/css/2.app.less',

          // font awesome
          '<%= mautic.rootAssets %>/css/font-awesome.css': '<%= mautic.coreBundleAssets %>/css/font-awesome/less/font-awesome.less',

          // libraries
          '<%= mautic.coreBundleAssets %>/css/libraries/1.typeahead.css': '<%= mautic.coreBundleAssets %>/css/libraries/1.typeahead.less'
        }
      }
    },

    // Add vendor prefixed styles
    autoprefixer: {
      options: {
        browsers: ['last 2 version', 'ie 9']
      },
      coreBundleAssets: {
        files: [{
          expand: true,
          cwd: '<%= mautic.coreBundleAssets %>/css/',
          src: '{,*/}*.css',
          dest: '<%= mautic.coreBundleAssets %>/css/'
        }]
      }
    },

    // Copies files
    copy: {
      fontAwesome: {
        files: [{
          expand: true,
          cwd: '<%= mautic.coreBundleAssets %>/css/font-awesome',
          dest: '<%= mautic.rootAssets %>',
          src: ['fonts/**/*']
        }]
      }
    },

    // minify css
    cssmin: {
      fontAwesome: {
        files: {
          '<%= mautic.rootAssets %>/css/font-awesome.min.css': '<%= mautic.rootAssets %>/css/font-awesome.css'
        }
      }
    }
  });

  grunt.registerTask('compile-less', [
    'less',
    //'autoprefixer',
    'copy',
    'cssmin',
    'watch'
  ]);
};