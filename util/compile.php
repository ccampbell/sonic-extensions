#!/usr/bin/env php
<?php
/**
 * compiles php files into a single file
 *
 * @author Craig Campbell
 */

/**
 * errors message
 *
 * @return void
 */
function usage_and_exit()
{
    output('need to specify path to extension directory');
    exit;
}

/**
 * outputs message to command line
 *
 * @param string $message
 * @param bool $verbose_only
 * @return void
 */
function output($message, $verbose_only = false)
{
    if ($verbose_only && !in_array('--verbose', $_SERVER['argv'])) {
        return;
    }
    echo $message,"\n";
}

/**
 * processes a directory of php files to find out which ones we should
 * include when we compile
 *
 * @param string $dir path to directory
 * @param bool $first is the top directory?
 * @return array
 */
function processDir($dir, $first = false)
{
    $files = new \RecursiveDirectoryIterator($dir);
    $files_to_include = array();
    foreach ($files as $file) {
        $name = $file->getFilename();

        // ignore any files beginning with . or _
        if (in_array($name[0], array('.', '_'))) {
            continue;
        }

        // skip core file
        if ($first && $name == 'Core.php') {
            continue;
        }

        // delegate file is not included
        if ($name == 'Delegate.php') {
            continue;
        }

        // do not include anything in libs directory since chances are it will
        // be a ton of files
        if ($first && $file->isDir() && in_array($file->getFilename(), array('libs', 'util'))) {
            output('skipping ' .  $file->getFilename() . ' directory', true);
            continue;
        }

        if ($file->isDir()) {
            $files_to_include = array_merge($files_to_include, processDir($file->getRealPath()));
            continue;
        }

        $files_to_include[] = $file->getRealPath();
    }

    return $files_to_include;
}

/**
 * goes through the files and finds out what files belong in what namespace
 *
 * also stores what "use" statements are present
 *
 * returns a multidimensional array of namespace to files
 *
 * @param array $files
 * @param array $use
 * @return array
 */
function namespacesFromFiles($files, &$use = array())
{
    $build_plan = array();
    foreach ($files as $file) {
        $contents = file_get_contents($file);
        preg_match('/namespace\s(.*);/', $contents, $matches);

        $namespace = isset($matches[1]) ? $matches[1] : 'none';
        preg_match_all('/use ([a-zA-Z,\s_\\\]+?);/', $contents, $matches);

        if (!isset($use[$namespace])) {
            $use[$namespace] = array();
        }
        if (isset($matches[1])) {
            foreach ($matches[1] as $class_list) {
                $to_use = explode(',', $class_list);
                foreach ($to_use as $class) {
                    $class = trim($class);
                    $use[$namespace][$class] = $class;
                }
            }
        }

        if (!isset($build_plan[$namespace])) {
            $build_plan[$namespace] = array();
        }
        $build_plan[$namespace][] = $file;
    }
    return $build_plan;
}

/**
 * gets comment for the top of the file
 *
 * @param string $dir
 * @param array $files
 * @return string
 */
function getComment($dir, $files)
{
    $path_bits = explode(DIRECTORY_SEPARATOR, $dir);
    $extension_name = array_pop($path_bits);
    $comment = "/**\n * combined core files for {$extension_name} extension (with comments stripped)\n *\n * includes ";
    $names = array();
    foreach ($files as $file) {
        $names[] = str_replace($dir . DIRECTORY_SEPARATOR, '', $file);
    }

    $comment .= implode(', ', $names) . "\n *\n * @category Sonic\n * @package {$extension_name}";

    $contents = file_get_contents($dir . '/_manifest.php');

    preg_match('/@author (.*)/', $contents, $matches);
    $author = isset($matches[1]) ? $matches[1] : null;
    if ($author) {
        $comment .="\n * @author {$author}";
    }

    preg_match('/const VERSION\s?=\s?"(.*)";/', $contents, $matches);
    $version = isset($matches[1]) ? $matches[1] : null;
    if ($version)  {
        $comment .= "\n * @version {$version}";
    }

    date_default_timezone_set('America/New_York');
    $comment .= "\n *\n * generated: " . date('Y-m-d H:i:s') . ' EST';

    $comment .= "\n */\n";
    return $comment;
}

/**
 * modifies a PHP file to strip comments and other stuff
 *
 * @param string $contents
 * @param bool $minimize
 * @return string
 */
function updateFile($contents, $minimize = true)
{
    $contents = str_replace('<?php' . "\n", '', $contents);
    $contents = preg_replace('/namespace(.*?);/', '', $contents);
    $contents = preg_replace('/use ([a-zA-Z,\s_\\\]+?);/', '', $contents);

    $contents = str_replace(' =', '=', $contents);
    $contents = str_replace('= ', '=', $contents);
    $contents = str_replace(', ', ',', $contents);
    $contents = str_replace(' && ', '&&', $contents);
    $contents = str_replace(' || ', '||', $contents);
    $contents = str_replace(' !=', '!=', $contents);
    $contents = str_replace(' . ', '.', $contents);
    $contents = str_replace('=> ', '=>', $contents);
    $contents = preg_replace('!/\*.*?\*/!s', '', $contents);
    $contents = preg_replace('/\/\/(.*)/', '', $contents);
    $contents = preg_replace('/\n\s*\n/', "\n", $contents);

    if ($minimize) {
        $contents = preg_replace('/\s+/', ' ', $contents);
        $contents = str_replace(' } ', '}', $contents);
        $contents = str_replace(' { ', '{', $contents);
        $contents = str_replace('; ', ';', $contents);
        $contents = str_replace(' final class', 'final class', $contents);
        $contents = str_replace(' class', 'class', $contents);
        $contents = str_replace('abstractclass', 'abstract class', $contents);
        $contents = str_replace('finalclass', 'final class', $contents);
        $contents = str_replace('divclass', 'div class', $contents);
        $contents = str_replace('if (', 'if(', $contents);
    }

    return $contents;
}

/**
 * takes the build plan and generates a Core file combining the other files
 *
 * @param array $build_plan
 * @param array $use
 * @return string
 */
function compileFromBuildPlan($build_plan, $use, $comment = null)
{
    $content = '<?php' . "\n";

    $content .= $comment;

    foreach ($build_plan as $namespace => $files) {
        if ($namespace != 'none') {
            $content .= 'namespace ' . $namespace . ' {' . "\n    ";
            if (isset($use[$namespace]) && count($use[$namespace])) {
                $content .= 'use ' . implode(', ', $use[$namespace]) . ';' . "\n    ";
            }
        }

        foreach ($files as $file) {
            output('adding file: ' . $file, true);
            $content .= updateFile(file_get_contents($file));
        }

        if ($namespace != 'none') {
            $content .= "\n" . '}' . "\n";
        }
    }
    return $content;
}

$args = $_SERVER['argv'];
if (!isset($args[1])) {
    usage_and_exit();
}

$dir = $args[1];

output('building file list from ' . $dir);
$files = processDir($dir, true);

output('generating build plan', true);
$build_plan = namespacesFromFiles($files, $use);

$comment = getComment($dir, $files);

output('compiling files together');
$content = compileFromBuildPlan($build_plan, $use, $comment);
$file = $dir . '/Core.php';

if (file_exists($file)) {
    output('removing old file at ' . $file, true);
    unlink($file);
}

output('saving core file to ' . $file);
file_put_contents($file, $content);

output('done');
