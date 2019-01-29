/* jshint node: true */
"use strict";

const gulp = require("gulp");
const concat = require("gulp-concat");
const uglify = require("gulp-uglify");

const jsBuildDir = "Resources/public/dist";
const toolsDir = "Resources/public/apps/tools";

const compileToolsJs = () => {
    gulp.src([
        `${toolsDir}/slides-in-slide-config-editor.js`,
    ])
        .pipe(concat('slide-tools.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest(jsBuildDir));

    return new Promise(function (resolve) {
        console.log("Tools JS built");
        resolve();
    });
};

const js = gulp.parallel(compileToolsJs);
js.description = 'Compile JS';

exports.js = js;
exports.default = js;
