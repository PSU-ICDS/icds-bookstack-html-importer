<?php

require_once('../bookstack_client.php');
require_once('./credentials.php');

if (!isset($credentials)) {
	die("Missing `credentials` array; exiting now.");
}

$europa = new BookStack_Client($credentials['url'], $credentials['id'], $credentials['secret'], true);

// Set the Book and HTML import path here
$book_title = "KennySchoolScrap";
$read_path = "/kenny/ConfluenceMigration";

$payload = array();
// Get Shelves not utilised but could be
$shelves = $europa->get_shelves($payload);

// Get books
$payload = array();
$books = $europa->get_books($payload);

// Create book
$book_id = $europa->create_book($book_title);

// Create page in book
if (isset($book_id) && is_integer($book_id)) {
	// Load files from directory
	$iterator = new DirectoryIterator($read_path);

	// Iterate over files
	foreach ($iterator as $fileinfo) {
		if (!$fileinfo->isDot()) {
			$file_name = $fileinfo->getFilename();

			// Only handle files that end with .html extension
			if ($europa->ends_with($file_name, ".html")) {
				$title = str_replace(array("-", "_"), " ", $file_name);
				$title = str_replace(array(".html"), "", $title);
				var_dump(
					array($title, $book_id)
				);

				// Build a payload to createa a page
				$payload = array(
					"book_id" => $book_id,
					"name" => $title,
					"html" => file_get_contents("{$read_path}\\{$file_name}"),
					//"tags" => array()
				);

				$page = $europa->create_page($payload);
			}
		}
	}
}
