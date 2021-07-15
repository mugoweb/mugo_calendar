<?php
$Module = array(
    'name' => 'Mugo Calendar',
    'variable_params' => true,
);

$ViewList = array();

$ViewList[ 'view' ] = array(
    'functions' => array( 'public' ),
    'script' => 'view.php',
);

$ViewList[ 'list' ] = array(
    'functions' => array( 'public' ),
    'script' => 'list.php',
);

$ViewList[ 'fetch' ] = array(
    'functions' => array( 'public' ),
    'script' => 'fetch.php',
);

$ViewList[ 'resolve_recurrence' ] = array(
    'functions' => array( 'public' ),
    'script' => 'resolve_recurrence.php',
);

$FunctionList = array();
$FunctionList[ 'public' ] = array();
