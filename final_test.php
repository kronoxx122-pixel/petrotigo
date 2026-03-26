<?php
$_POST = [];
$input = ['value' => '3016690342'];
file_put_contents('test_input.json', json_encode($input));

function mock_file_get_contents($url) {
    if ($url === 'php://input') return file_get_contents('test_input.json');
    return file_get_contents($url);
}

// Emulate get_balance.php
include 'get_balance.php';
// The script echoes JSON and exits.
unlink('test_input.json');
