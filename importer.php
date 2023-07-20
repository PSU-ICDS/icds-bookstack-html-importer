<?php

/* Auto String Format and Import
Searches for and automatically removes consecutive numbers at the end of chapter title
strings if they are 3 chars or more...otherwise will prompt user if it should be kept
as is in case it is an intential naming scheme. Will then attempt to name empty strings 
to a placeholder OR an extracted variable.
*/

/* Import as Shelf
Remove $book_title and make $shelf_title...book title becomes shelf title...chapter title becomes book title....page title becomes chapter title?
Playing around with trying to customize output of the export...
*/

require_once('../bookstack_client.php');
require_once('./credentials.php');
require_once('./simplehtmldom/simple_html_dom.php');

if (!isset($credentials)) {
    die("Missing `credentials` array; exiting now.");
}

$europa = new BookStack_Client($credentials['url'], $credentials['id'], $credentials['secret'], true);

// Set the Shelf and HTML import path here
$shelf_title = "IT Shelf Import-Test1";
$read_path = "/home/kenny/IT";

// Create shelf
$shelf_id = $europa->create_shelf($shelf_title); // Replace with appropriate function

if (isset($shelf_id) && is_integer($shelf_id)) {
    // Get list of books from the directory structure
    $books = $europa->get_directory_structure($read_path);

    foreach ($books as $book_name => $chapters) {
        // Create book
        $book_id = $europa->create_book($book_name);

        if (isset($book_id) && is_integer($book_id)) {
            foreach ($chapters as $chapter_name => $pages) {
                // Create chapter
                $chapter_id = $europa->create_page(array(
                    "book_id" => $book_id,
                    "name" => $chapter_name,
                    "html" => "" // Set appropriate HTML content if needed
                ));

                if (isset($chapter_id) && is_integer($chapter_id)) {
                    foreach ($pages as $page_name) {
                        // Check if title contains consecutive numbers at the end
                        if (preg_match('/\d+$/', $page_name)) {
                            echo "Title contains numbers at the end: $page_name" . PHP_EOL;

                            // Remove numbers at the end of the title
                            $page_name = preg_replace('/\d+$/', '', $page_name);
                        }

                        if (empty($page_name)) {
                            // If string is empty, apply placeholder or extract variable
                            // Example: $page_name = "Page Placeholder";
                        }

                        var_dump(
                            array($page_name, $chapter_id)
                        );

                        // Build a payload to create a page
                        $payload = array(
                            "chapter_id" => $chapter_id,
                            "name" => $page_name,
                            "html" => "" // Set appropriate HTML content if needed
                        );

                        $page = $europa->create_page($payload);
                    }
                }
            }
        }
    }
}