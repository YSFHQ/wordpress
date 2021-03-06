'use strict';
module.exports = function(grunt) {

  grunt.initConfig({
    jshint: {
      options: {
        jshintrc: '.jshintrc'
      },
      all: [
        'Gruntfile.js',
        'assets/js/*.js',
        'assets/js/plugins/*.js',
        '!assets/js/scripts.min.js'
      ]
    },
    recess: {
      dist: {
        options: {
          compile: true,
          compress: true
        },
        files: {
          'assets/css/main.min.css': [
            'assets/less/bootstrap/bootstrap.less',
            'assets/less/style.css',
            'assets/less/header.css',
            'assets/less/bootstrap/responsive.less',
            'assets/less/style_responsive.css'
          ]
        }
      }
    },
    uglify: {
      dist: {
        files: {
          'assets/js/scripts.min.js': [
            'assets/js/plugins/bootstrap/bootstrap-transition.js',
            'assets/js/plugins/bootstrap/bootstrap-alert.js',
            'assets/js/plugins/bootstrap/bootstrap-button.js',
            'assets/js/plugins/bootstrap/bootstrap-carousel.js',
            'assets/js/plugins/bootstrap/bootstrap-collapse.js',
            'assets/js/plugins/bootstrap/bootstrap-dropdown.js',
            'assets/js/plugins/bootstrap/bootstrap-modal.js',
            'assets/js/plugins/bootstrap/bootstrap-tooltip.js',
            'assets/js/plugins/bootstrap/bootstrap-popover.js',
            'assets/js/plugins/bootstrap/bootstrap-scrollspy.js',
            'assets/js/plugins/bootstrap/bootstrap-affix.js',
            'assets/js/plugins/bootstrap/bootstrap-tab.js',
            'assets/js/plugins/bootstrap/bootstrap-typehead.js',
            'assets/js/plugins/*.js',
            'assets/js/_*.js'
          ]
        }
      }
    },
    watch: {
      less: {
        files: [
          'assets/less/*.less',
          'assets/less/bootstrap/*.less'
        ],
        tasks: ['recess', 'version']
      },
      js: {
        files: [
          '<%= jshint.all %>'
        ],
        tasks: ['jshint', 'uglify', 'version']
      }
    },
    clean: {
      dist: [
        'assets/css/main.min.css',
        'assets/js/scripts.min.js'
      ]
    }
  });

  // Load tasks
  grunt.loadTasks('tasks');
  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-recess');

  // Register tasks
  grunt.registerTask('default', [
    'clean',
    'recess',
    'uglify',
    'version'
  ]);
  grunt.registerTask('dev', [
    'watch'
  ]);

};
