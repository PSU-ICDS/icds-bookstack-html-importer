<?php

/*
Modified to allow choice to remove consecutive numbers from titles -- Kenny
Further modifications may include...
	-Changes to which level is import going to too; IE, instead of creating a single book
	perhaps shelves are created, perhaps this will preserve original structure better
*/
require_once('../bookstack_client.php');
require_once('./credentials.php');

if (!isset($credentials)) {
	die("Missing `credentials` array; exiting now.");
}

$europa = new BookStack_Client($credentials['url'], $credentials['id'], $credentials['secret'], true);

// Set the Book and HTML import path here
$book_title = "ConfluenceTest-TitleStringFormatter";
$read_path = "/home/kenny/IT";

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
			/*
				These are chapter titles FYI...
			*/
			if ($europa->ends_with($file_name, ".html")) {
				$title = str_replace(array("-", "_"), " ", $file_name);
				$title = str_replace(array(".html"), "", $title);
				/*
				New section here...prompts to remove numbers...may alter to simply be an
				automatic selection...we shall see....
				*/
				// Check if title contains any numbers
				if (preg_match('/\d/', $title)) {
					echo "Title contains numbers: $title" . PHP_EOL;
					$continue = readline("Do you want to keep the title as is? (y/n): ");

					// Replace consecutive numbers if no...also why error on if statement?
					if (strtolower($continue) === 'n') {
						$manual = readline("Do you want to write in a custom title? (y/n): ");
						if (strtolower($manual) === 'n') {
						$title = preg_replace('/\d+/', '', $title);
						}
						if (strtolower($manual) === 'y') {
						/*
						Or, if you want to type in a custom title, uncomment the following;
						I may add another choice, however that will take even longer to
						import at that point
						*/
						$custom_title = readline("Enter a custom title: ");
						$title = $custom_title;
						}	
					}
				}

				var_dump(
					array($title, $book_id)
				);

				// Build a payload to createa a page
				$payload = array(
					"book_id" => $book_id,
					"name" => $title,
					"html" => file_get_contents("{$read_path}/{$file_name}"),
					//"tags" => array()
				);

				$page = $europa->create_page($payload);
			}
		}
	}
}
