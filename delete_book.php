<?php

/*
This script will list books and give option to delete a book. This will hopefully be
used as a last resort in instances where an error does not allow typical book deletion
from within bookstack
*/

require_once('../bookstack_client.php');
require_once('./credentials.php');

if (!isset($credentials)) {
	die("Missing `credentials` array; exiting now.");
}

// Enter your credentials for API calls from the credentials.php file
$europa = new BookStack_Client($credentials['url'], $credentials['id'], $credentials['secret'], true); // needs to be in scope

$isDone = false;

$payload = array();

while (!$isDone) {
    
    $books = $europa->get_books($payload);

    $delete_id = readline("Enter the ID of the book you wish to delete: ");

    $result = $europa->delete_book($delete_id);
    
    if ($result === true) {
        echo "Book deleted successfully!";
    } else {
        echo "---Failed to delete the book---";
        echo "Error: " . $result . PHP_EOL;
    }

    $choice = readline("(n) to exit. Any key to continue: ");
    if ($choice === "n") {$isDone = true;}
} 