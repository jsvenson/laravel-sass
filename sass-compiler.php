<?php

/**
 * Class SassCompiler
 *
 * This simple tool compiles all .scss files in folder A to .css files (with exactly the same name) into folder B.
 * Everything happens right when you run your app, on-the-fly, in pure PHP. No Ruby needed, no configuration needed.
 *
 * SassWatcher is not a standalone compiler, it's just a little method that uses the excellent scssphp compiler written
 * by Leaf Corcoran (https://twitter.com/moonscript), which can be found here: http://leafo.net/scssphp/ and adds
 * automatic compiling to it.
 *
 * The currently supported version of SCSS syntax is 3.2.12, which is the latest one.
 * To avoid confusion: SASS is the name of the language itself, and also the "name" of the "first" version of the
 * syntax (which was quite different than CSS). Then SASS's syntax was changed to "SCSS", which is more like CSS, but
 * with awesome additional possibilities and features.
 *
 * The compiler uses the SCSS syntax, which is recommended and mostly used. The old SASS syntax is not supported.
 *
 * @see SASS Wikipedia: http://en.wikipedia.org/wiki/Sass_%28stylesheet_language%29
 * @see SASS Homepage: http://sass-lang.com/
 * @see scssphp, the used compiler (in PHP): http://leafo.net/scssphp/
 */
class SassCompiler
{
    /**
     * Compiles all .scss files in a given folder into .css files in a given folder
     *
     * @param string $scss_folder source folder where you have your .scss files
     * @param string $css_folder destination folder where you want your .css files
     * @param string $format_style CSS output format, see http://leafo.net/scssphp/docs/#output_formatting for more.
     */
    static public function run($scss_folder, $css_folder, $format_style = "scss_formatter")
    {
        // check $scss_folder and $css_folder for trailing '/'
        if (!self::endsWith($scss_folder, DIRECTORY_SEPARATOR)) $scss_folder .= DIRECTORY_SEPARATOR;
        if (!self::endsWith($css_folder, DIRECTORY_SEPARATOR)) $css_folder .= DIRECTORY_SEPARATOR;
        
        // scssc will be loaded automatically via Composer
        $scss_compiler = new scssc();
        // set the path where your _mixins are
        $scss_compiler->setImportPaths($scss_folder);
        // set css formatting (normal, nested or minimized), @see http://leafo.net/scssphp/docs/#output_formatting
        $scss_compiler->setFormatter($format_style);
        // get all non-partial .scss files from scss folder
        $filelist = array_values(array_filter(glob($scss_folder . '*.scss'), function($el) {
            return substr(basename($el), 0, 1) != '_';
        }));
        
        $extension = '.css';
        if ($format_style == 'scss_formatter_compressed') $extension = '.min.css';

        // check the modified date of each file against the modified date of the existing compiled file
        $scss_folder_iterator = new RecursiveDirectoryIterator($scss_folder);
        $iterator = new RecursiveIteratorIterator($scss_folder_iterator, RecursiveIteratorIterator::SELF_FIRST);
        $newest_date = 0;
        foreach ($iterator as $current) {
            if ($current->isFile() && $current->getMTime() > $newest_date) {
                $newest_date = $current->getMTime();
            }
        }
        
        $recompile_scss = false;
        foreach ($filelist as $file_path) {
            $file_path_elements = pathinfo($file_path);
            $file_name = $file_path_elements['filename'];
            $compiled_file = $css_folder . $file_name . $extension;
            if (!is_file($compiled_file) || filemtime($compiled_file) < $newest_date) {
                $recompile_scss = true;
            }
        }
        
        if ($recompile_scss) {
            // step through all .scss files in that folder
            foreach ($filelist as $file_path) {
                // get path elements from that file
                $file_path_elements = pathinfo($file_path);
                // get file's name without extension
                $file_name = $file_path_elements['filename'];
                // get .scss's content, put it into $string_sass
                $string_sass = file_get_contents($scss_folder . $file_name . ".scss");
                // compile this SASS code to CSS
                $string_css = $scss_compiler->compile($string_sass);
                // write CSS into file with the same filename, but .css extension
                file_put_contents($css_folder . $file_name . $extension, $string_css);
            }
        }
    }
    
    static public function runInEnvironment($targetEnvironment, $currentEnvironment, $scss_folder, $css_folder, $format_style = 'scss_formatter') 
    {
        if ($targetEnvironment == $currentEnvironment) self::run($scss_folder, $css_folder, $format_style);
    }
    
    static private function endsWith($string, $cap = '') 
    {
        return substr($string, -strlen($cap)) == $cap;
    }
}
