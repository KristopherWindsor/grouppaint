<?php

// using files as the data store and pretending we're too cool for Mongo

$input = json_decode(file_get_contents('php://input'));

$room = @$input->room . '';
if (!ctype_alnum($room) || !$room || strlen($room) > 32)
	$room =  'lobby';

// get existing drawing
$drawing = array();
$room_exists = file_exists('rooms/' . $room);
if ($room_exists)
	$drawing = array_map('json_decode', array_filter(explode("\n", file_get_contents('rooms/' . $room))));

// get input drawing, add to memory
$new_drawing = (array) @$input->drawing;
$next_index = $drawing ? $drawing[0]->uid + 1: 0;
foreach ($new_drawing as & $new_drawing_item){
	//todo: validate / bail here
	$new_drawing_item->uid = $next_index++;
	$drawing[] = $new_drawing_item;
}

// append input drawing to data store
file_put_contents('rooms/' . $room, ($room_exists ? "\n" : '') . implode("\n", $new_drawing), FILE_APPEND);

// output
header('Content-type: application/json');
echo json_encode($drawing);
