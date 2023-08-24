<?php

/*
This script takes a main folder which indicates a "Shelf" you can manually name and Add
a description for said shelf. The subfolders will then be automatically imported to 
Bookstack as "Books" with pages inside.

Searches for and automatically removes consecutive numbers at the end of page title
strings if they are 3 chars or more to remove the old Confluence IDs that are tagged 
at the end of the files after the export. Those Confluence IDs are then added to the 
individual pages as tags.

This script slows the API call speed to around 480 times a minute in order to stay 
under Bookstacks maximum allowed calls per minute of 500. In a new Instance of Bookstack
you will need to adjust the API call speed in Bookstack's .env file OR slow the speed of
uploads down to 333333 (180 calls a minute) from 111111 (480 calls a minute).

***Note: as of this time a book by the name of "." as well as a ".." and perhaps the shelf itself. This will 
be cleaned up after import. Unless I have time to fiddle with them script some more.

*/
require_once('../bookstack_client.php');
require_once('./credentials.php');
require_once('./simplehtmldom/simple_html_dom.php'); // Need to auto apply Title

if (!isset($credentials)) {
	die("Missing `credentials` array; exiting now.");
}

// API call speed in microseconds (default of 111111 is about 480 calls per minute)
$api_call_speed = 111111; // (333333 is about 180 calls per minute which is Bookstack Default)

//Array to store book id's to be added to Shelf after all imports take place
$book_ids = array(); 

$temp_title;
// Enter your credentials for API calls from the credentials.php file
$europa = new BookStack_Client($credentials['url'], $credentials['id'], $credentials['secret'], true); // needs to be in scope

// Specify the root directory you want to start iterating from
$root_directory = readline("Enter path to shelf imports: ");
//$root_directory = "/home/kenny/test";

// Create a shelf beforehand; finish after iterations
$shelf_name = readline("Enter a shelf name: ");
$shelf_description = readline("Enter a shelf description: ");

// Set iterate from Main directory(shelf) thru subdirectories(books)
//$iterator = new RecursiveDirectoryIterator($root_directory); 
$iterator = new DirectoryIterator($root_directory); 
// Iterate through directories from specified root directory;
//	creating books where applicable.
foreach ($iterator as $path) {

/* Uncomment to add Error Checking
	echo "Iterator : {$iterator}";

    $isExit56 = readline("Line 54 foreach (\$iterator as \$path) : {$path} Exit?");  // ERROR CHECKING OUTPUTS REMOVE LATER
        if ($isExit56 === "y") {
            exit("You have exited");
        }
        else {
            echo "Moving on...";
        }																			// ERROR CHECKING END
*/

	// create $temp to hold $path because you cannot use isDOT() on RecursiveDirectoryIterator
	// so it must temporarily become Directory Iterator to pass thru if statement
	echo "Var Dump: Path ---     ";
	var_dump($path);

/* Uncomment to add Error Checking	
	$nothing = readline("Line 66 \$path = new DirectoryIterator(\$path) : {$path}    temp: {\$temp} Exit?");
	
	
	$isExit60 = readline("Line 66 \$temp = new DirectoryIterator(\$path) : {$temp} Exit?");  // ERROR CHECKING OUTPUTS REMOVE LATER
        if ($isExit60 === "y") {
            exit("You have exited");
        }
        else {
            echo "Moving on...";
        }																// ERROR CHECKING END
*/
		// if is a directory and is one level in
		if ($path->isDir() && !$path->isDot() ) {
			
			echo "Processing directory: " . $path->getPathname() . PHP_EOL;
/* Uncomment to add Error Checking
			$isExit73 = readline("Line 77 if (\$temp->isDir() && \$temp->isDot() ) path: {$path}  {\$temp} Exit?");  // ERROR CHECKING OUTPUTS REMOVE LATER
        if ($isExit73 === "y") {
            exit("You have exited");
        }
        else {
            echo "Moving on...";
        }				
*/											// ERROR CHECKING END
			$read_path = $path->getPathname(); 

			echo "Line 97 read_path: {$read_path}          ";
			// remove the _ and name the book from the subdirectory path

			$book_title = str_replace("_", " ", $path);
			echo "Book title: {$book_title}" . PHP_EOL;
/* Uncomment to add Error Checking
			$isExit76 = readline("Line 95 Book Title : {$book_title} ... Read Path: {$read_path} Exit?");  // ERROR CHECKING OUTPUTS REMOVE LATER
			if ($isExit76 === "y") {
				exit("You have exited");
			}
			else {
				echo "Moving on...";
			}	
*/																								// ERROR CHECKING END
		   // Create book
			try {
				$book_id = $europa->create_book($book_title);
// Uncomment for error checking				$nothing1 = readline("book_id: {$book_id}      book_title: {$book_title}");
			} catch (Exception $e) {
				echo "Error: " . $e->getMessage() . PHP_EOL;
			}
			
			// Create page in book
			if (isset($book_id) && is_integer($book_id)) {

				// Add book ID to array to populate shelf at the end
				$book_ids[] = $book_id;
				
				// Load files from directory
// Uncomment for error checking				$nothing2 = readline(" line 125 read_path: {$read_path}        ");
				$iteratorBook = new DirectoryIterator($read_path); 

				// Iterate over files
				foreach ($iteratorBook as $fileinfo) {
/* Uncomment for error checking					
					$isExit123 = readline("Line 121 foreach (\$iteratorBook asn \$fileinfo) : {$fileinfo} ... Exit?");  // ERROR CHECKING OUTPUTS REMOVE LATER
					if ($isExit123 === "y") {
						exit("You have exited");
					}
					else {
						echo "Moving on...";
					}																						// ERROR CHECKING END
*/
					//if file is NOT a . or .. 
					if (!$fileinfo->isDot() && !$fileinfo->isDir()) {
						$file_name = $fileinfo->getFilename();
                        echo "line 134 \$file_name = \$fileinfo->getFilename(); : file_name = {$file_name}";
						// Only handle files that end with .html extension
						if ($europa->ends_with($file_name, ".html")) {
							$title = str_replace(array("-", "_"), " ", $file_name);
							$title = str_replace(array(".html"), "", $title);

							// Save the striped off Conflunce ID to add as a tag to the page
							preg_match('/\d{5,}/', $title, $matches); 
							$confluence_page_id = $matches[0];

							// Check if title contains consecutive numbers at the end
							if (preg_match('/\d+$/', $title)) {
								//echo "Title contains numbers at the end: $title" . PHP_EOL;

									// Remove numbers at the end of the title
									$title = preg_replace('/\d+$/', '', $title);
                                    
								}
							} 
							
							// If string is empty or has empty space(s); Apply appropriate page/chapter name
							if ($title === '' || $title === ' ' || $title === '  ') {
								//echo "Title is blank after consecutive number removal...";
								// if title is blank after number removal access DOM and read in <title> from HTML <head>
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
											//echo "Auto-Title applied: $title";
							}
						}

// Uncomment to provide more output (too much)						    //var_dump(
						    //   array($title, $book_id)
						    //);

							// Build a payload to create a page
							// Can apply multiple tags by adding to 2d Array
							
							$payload = array(
								"book_id" => $book_id,
								"name" => $title,
								"html" => file_get_contents("{$read_path}/{$file_name}"),
								"tags" => array(
									array(
									"name" => "ConfluenceID",
									"value" => (string)$confluence_page_id
									)
								)
							); 
/* Example of API call to affix tags at book creation. Only 'name' is neccessary to add
just nest additional array's within the top level 
                        "tags": [
                            {"name": "Category", "value": "API Examples"},
                            {"name": "Rating", "value": "Alright"}
                        ]
*/

						// Create API call to create page and place in book.
                        $page = $europa->create_page($payload); 

						// Adjust sleep time up top of script...this slows script to Bookstack's API
						// calls per minute to ensure everything uploaded. Check for error Code 429 
						// in output to see if calls per minute exceeded during Book/Page creation
                        usleep($api_call_speed);
                }
            }
        }            
    }
/* Uncomment for error checking
		// Pause after each iteration through loop
		echo " line 210 path: {$path}            ";
        $isExit183 = readline("Line 184 Do you want to exit? (y): ");			// ERROR CHECKING OUTPUTS REMOVE LATER
        if ($isExit183 === "y") {
            exit("You have exited");
        }
        else {
            echo "Next Iteration...";
        }		*/													// ERROR CHECKING END
}


// Finish shelf creation
$shelf_id = $europa->create_shelf($shelf_name);

if ($shelf_id) {
    // Add created books to the shelf
    $payload = array(
        "books" => $book_ids  
    );
	// Get appropriate error codes
    try {
        $europa->put("{$europa->url}shelves/{$shelf_id}", array("books" => $book_ids));
		echo "Books added to the shelf successfully!";
    } catch (Exception $e) {
		echo "---Failed to create the shelf---";
        echo "Error: " . $e->getMessage() . PHP_EOL;
		
    }
}   
    
    

