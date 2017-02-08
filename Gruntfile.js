module.exports = function(grunt) {

    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        /*less: {
            development: {
                options: {
                    paths: ['vendor/braincrafted/bootstrap-bundle/Braincrafted/Bundle/BootstrapBundle/Resources/less']
                },
                files: {
                    "app/Resources/css/braincrafted.css" : "vendor/braincrafted/bootstrap-bundle/Braincrafted/Bundle/BootstrapBundle/Resources/less/form.less"
                }
            }
        },*/
        bowercopy: {
            options: {
                srcPrefix: 'bower_components'
            },
            stylesheets: {
                options: {
                    destPrefix: 'src/AppBundle/Resources/css'
                },
                files: {
                    'leaflet.css' : 'leaflet/dist/leaflet.css'
                }
            },
            scripts: {
                options: {
                    destPrefix: 'src/AppBundle/Resources/js'
                },
                files: {
                    'leaflet.min.js': 'leaflet/dist/leaflet.js',
                    'jquery.min.js': 'jquery/dist/jquery.min.js',
                    'spin.min.js': 'spin.js/spin.min.js',
                    'leaflet.spin.js': 'leaflet-spin/leaflet.spin.js',
                    'floatThead.js': 'jquery.floatThead/dist/jquery.floatThead.min.js'
                }
            }
        },
        rewrite: {
          dist: {
              src: "src/AppBundle/Resources/css/leaflet.css",
              editor: function(contents, filePath) {
                  return contents.replace(/images/g, "../img");
              }
          }
        },
        cssmin: {
            target: {
                files: [{
                    expand: true,
                    cwd: 'src/AppBundle/Resources/css',
                    src: ['*.css', '!*min.css'],
                    dest: 'web/css',
                    ext: '.min.css'
                }]
            }
        },
        uglify: {
            leaflet: {
                files: {
                    'web/js/leaflet.min.js': ['src/AppBundle/Resources/js/leaflet.min.js'],
                    'web/js/leaflet.ajax.min.js': ['src/AppBundle/Resources/js/leaflet.ajax.min.js'],
                    'web/js/weather.leaflet.js' : ['src/AppBundle/Resources/js/weather.leaflet.js'],
                    'web/js/jquery.min.js' : ['src/AppBundle/Resources/js/jquery.min.js'],
                    'web/js/leaflet.spin.min.js': ['src/AppBundle/Resources/js/leaflet.spin.js'],
                    'web/js/spin.min.js': ['src/AppBundle/Resources/js/spin.min.js'],
                    'web/js/floatThead.js': ['src/AppBundle/Resources/js/floatThead.js']
                }
            }
        },
        copy: {
            dist: {
                expand: true,
                cwd: 'bower_components/leaflet/dist/images',
                src: ['layers*.png'],
                dest: 'src/AppBundle/Resources/img'
            },
            prod: {
                expand: true,
                cwd: 'src/AppBundle/Resources/img',
                src: ['*'],
                dest: 'web/img'
            }
        }
    });

    // Load the plugin that provides the "uglify" task.
    grunt.loadNpmTasks('grunt-bowercopy');
    grunt.loadNpmTasks('grunt-rewrite');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-copy');

    // Default task(s).
    grunt.registerTask('default', ['bowercopy', 'rewrite', 'cssmin', 'uglify', 'copy']);

};