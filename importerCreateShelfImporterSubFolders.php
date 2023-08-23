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

// Attempting to debug; for test folder example should iterate and create 3 books...does iterate through 3 folders...for some reason does not
// properly create 3 books....
//static $dir_count = 1; // no longer needed...doesn't increment properly because I am a dummy at php

$europa = new BookStack_Client($credentials['url'], $credentials['id'], $credentials['secret'], true); // needs to be in scope

// Set the Book and HTML import path here
//$book_title = "IT_8-2-Meeting_Test";

// Specify the root directory you want to start iterating from
$root_directory = readline("Enter path to shelf imports: ");

// Create a shelf beforehand; finish after iterations
$shelf_name = readline("Enter a shelf name: ");
$shelf_description = readline("Enter a shelf description: ");

// Scratch iterator conditions
    // RecursiveDirectoryIterator::SKIP_DOTS
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root_directory)
);

// Scratch conditions; redundant? Needed? 
    // && !$path->isDot() && $path->getFilename() !== '.' && $path->getFilename() !== '..'

$book_ids = array(); //Array to store book id's

foreach ($iterator as $path) {
    if ($path instanceof DirectoryIterator && $path->isDir() && !$path->isDot()) {
        // Run your code for each subdirectory here
       // echo "<br>";
        echo "Processing directory: " . $path->getPathname() . PHP_EOL;
      //  echo "<br>";
      //  echo "directory iterator loop if(path isDir():";
      //  echo "<br>";
      //  echo $dir_count;
     //   $dir_count++;
        
        //$read_path = $path; // this will be auto implementated as we iterate through directories // redundant; changing $read_path simply to $
        
        $book_title = str_replace("/", "",strrchr($path, "/"));
        echo "Book title: {$book_title}" . PHP_EOL;

        // GETTING RID OF THESE TO REDUCE EXTRANEOUS PRINTING TO CONSOLE

        //$payload = array();
        // Get Shelves not utilised but could be
        //$shelves = $europa->get_shelves($payload);

        // Get books
        //$payload = array();
        //$books = $europa->get_books($payload);

        // Create book; make sure it was created
       
        try {
            $book_id = $europa->create_book($book_title);
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . PHP_EOL;
        }
        //echo "Book ID: {$book_id}" . PHP_EOL;

        if ($book_id) {
            echo "Book created with ID: {$book_id}" . PHP_EOL;
        } else {
            echo "Failed to create the book." . PHP_EOL;
        }

        // Create page in book
        if (isset($book_id) && is_integer($book_id)) {

            // Add book ID to array to populate shelf at the end
            $book_ids[] = $book_id;
            // Load files from directory
            $iteratorBook = new DirectoryIterator($path); //changed from read_path

            // Iterate over files
            foreach ($iteratorBook as $fileinfo) {
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
                            if (is_file("{$path}/{$file_name}")) {
                                $html = file_get_contents("{$path}/{$file_name}");
                                $dom = new \DOMDocument();
                                @$dom->loadHTML($html);
                                $title_element = $dom->getElementsByTagName('title')->item(0);
                            } else {
                                echo "File does not exist: {$path}/{$file_name}";
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
                            "html" => file_get_contents("{$path}/{$file_name}"),
                            //"tags" => array()
                        );

                        $page = $europa->create_page($payload);
                    }
                }
            }            
        }
    }

// Finish shelf creation
$shelf_id = $europa->create_shelf($shelf_name);

if ($shelf_id) {
    // Add created books to the shelf
    $payload = array(
        "books" => $book_ids  
    );

    $europa->put("{$europa->url}shelves/{$shelf_id}", array("books" => $book_ids));
    echo "Books added to the shelf successfully!";
} else {
    echo "Failed to create the shelf.";
}
        

