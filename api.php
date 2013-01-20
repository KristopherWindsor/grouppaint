<?php

// using files as the data store and pretending we're too cool for Mongo

header('Content-type: application/json');

$input = json_decode(file_get_contents('php://input'));

$room = @$input->room . '';
if (!ctype_alnum($room) || !$room || strlen($room) > 32)
	$room =  'lobby';

$clientId = max(0, (int) @$input->clientId);

if (@$input->action == 'poll')
	poll();
else if (@$input->action == 'push')
	push();

function poll(){
	global $input, $room, $clientId;
	
	// get existing drawing
	$res = array();
	for ($i = 0; $i < 10; $i++){
		$drawing = array();
		if (file_exists('rooms/' . $room))
			$drawing = array_map('json_decode', array_filter(explode("\n", file_get_contents('rooms/' . $room))));
		
		foreach ($drawing as $j)
			if ($j->clientId != $clientId && $j->uid > @$input->lastUid)
				$res[] = $j;
		
		if ($res)
			break;
		sleep(1);
	}

	echo json_encode(array('drawing' => $res));
}

function push() {
	global $input, $room;
	
	// get existing drawing
	$drawing = array();
	$next_index = 0;
	$room_exists = file_exists('rooms/' . $room);
	if ($room_exists){
		$drawing = array_map('json_decode', array_filter(explode("\n", file_get_contents('rooms/' . $room))));
		$next_index = $drawing[count($drawing) - 1]->uid + 1;
		// truncate to keep drawing manageable
		if (count($drawing) > 1000){
			$drawing = array_slice($drawing, count($drawing) - 1000);
			file_put_contents('rooms/' . $room, implode("\n", array_map('json_encode', $drawing)));
		}
	}

	// validate input and add uids
	$new_drawing = (array) @$input->drawing;
	foreach ($new_drawing as & $new_drawing_item){
		foreach ($new_drawing_item as $i => $j){
			$j = str_replace('#', '', str_replace('.', '', $j));
			if (!ctype_alnum($i) || !ctype_alnum($j))
				return;
			$new_drawing_item->uid = $next_index++;
		}
	}

	// append input drawing to data store
	file_put_contents('rooms/' . $room, ($room_exists ? "\n" : '') . implode("\n", array_map('json_encode', $new_drawing)), FILE_APPEND);
}
