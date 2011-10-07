<?php

function autoloadForTesting($classname) {
    $cwd = getcwd();
    $testdir = 'C:\php\ShrubRoots\tests';
    $homedir = 'C:\php\ShrubRoots';
    chdir($testdir);
    $classfile = $testdir.DIRECTORY_SEPARATOR.$classname.'.php';
    if (file_exists($classfile)) {
        include_once($classfile);
    }
    else {
        chdir($homedir);
        $classfile = $homedir.DIRECTORY_SEPARATOR.$classname.'.php';
        include_once($classfile);
    }
    chdir($cwd);   
}

?>
