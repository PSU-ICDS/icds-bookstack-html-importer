<?php

/*
Searches for and automatically removes consecutive numbers at the end of chapter title
strings if they are 3 chars or more...otherwise will prompt user if it should be kept
as is in case it is an intential naming scheme. Will then attempt to name empty strings 
to a placeholder OR an extracted variable.
*/
require_once('../bookstack_client.php');
require_once('./credentials.php');
require_once('./simplehtmldom/simple_html_dom.php'); // Need to auto apply Title

if (!isset($credentials)) {
	die("Missing `credentials` array; exiting now.");
}

$europa = new BookStack_Client($credentials['url'], $credentials['id'], $credentials['secret'], true);

// Set the Book and HTML import path here
$book_title = "IT-Formatted-Titles-Test-2";
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
            if ($europa->ends_with($file_name, ".html")) {
                $title = str_replace(array("-", "_"), " ", $file_name);
                $title = str_replace(array(".html"), "", $title);

                // Check if title contains consecutive numbers at the end
                if (preg_match('/\d+$/', $title)) {
                    echo "Title contains numbers at the end: $title" . PHP_EOL;
                    
                        // Remove numbers at the end of the title
                        $title = preg_replace('/\d+$/', '', $title);
                    }
                } 
				if ($title === '' || $title === ' ' || $title === '  ') {
					// If string is empty or has empty space(s); Apply appropriate page/chapter name
                    echo "Title is blank after consecutive number removal...";
                    if (is_file("{$read_path}/{$file_name}")) {
                        $html = file_get_contents("{$read_path}/{$file_name}");
                        $dom = new \DOMDocument();
                        @$dom->loadHTML($html);
                        $title_element = $dom->getElementsByTagName('title')->item(0);
                    } else {
                        echo "File does not exist: {$read_path}/{$file_name}";
                    }
                        if ($title_element) {
                                $title = $title_element->nodeValue;
                                echo "Auto-Title applied: $title";
                }
            }

                var_dump(
                    array($title, $book_id)
                );

                // Build a payload to create a page
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
