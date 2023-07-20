<?php
/*
 * Copyright (c) 2022. Oddineers Ltd. All Rights Reserved
 * License: MIT License
 * Written by Steven Brown <mortanius@oddineers.co.uk>, 2022
 */

class BookStack_Client {
	public $domain_url = false;
	public $url = false;
	public $last_url = false;
	public $last_status_code = false;
	public $last_status_message = false;
	private $headers = array();
	private $debug = false;
	private $cookie = false;
	private $data_string = false;
	private $file_as_json = false;
	protected $c_types = array(
		"gif" => "image/gif",
		"jpg" => "image/jpeg",
		"png" => "image/png",
		"pdf" => "application/pdf",
		"txt" => "text/plain",
	);

	function __construct( $domain_url, $id, $secret, $debug = false ) {
		if ( empty( $domain_url ) ) {
			$this->last_status_message = 'The hostname/url cannot be empty.';

			return false;
		}
		if ( empty( $id ) ) {
			$this->last_status_message = 'The authenticating username cannot be empty.';

			return false;
		}
		if ( empty( $secret ) ) {
			$this->last_status_message = 'The users application password cannot be empty.';

			return false;
		}

		$this->domain_url = $domain_url;
		$prefix           = "/api/";

		if ( is_string( $domain_url ) && $this->ends_with( $domain_url, "/" ) ) {
			$prefix = "api/";
		}

		$this->url              = "{$domain_url}{$prefix}";
		$this->last_url         = null;
		$this->last_status_code = null;

		# Setup debug flag for printing results
		if ( $debug ) {
			$this->debug = true;
		} else {
			$this->debug = false;
		}

		$this->data_string = "{$id}:{$secret}";
	}

	/**
	 * Basic authentication
	 * @return void
	 */
	function basic_auth() {
		//$encoded_token = base64_encode( $this->data_string );
		$encoded_token = $this->data_string;
		$this->headers = array_merge( array(
			'Authorization: Token ' . $encoded_token, // utf8_decode( $encoded_token ),
		), $this->headers );
	}

	/**
	 * HTTP based authetnication with Cookie Jar.
	 *
	 * @param $ch
	 *
	 * @return void
	 */
	function user_auth( $ch ) {
		//$encoded_token = base64_encode( $this->data_string );
		$encoded_token = $this->data_string;
		curl_setopt( $ch, CURLOPT_USERPWD, $encoded_token );
		curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
		//curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt( $ch, CURLOPT_COOKIEJAR, $this->cookie );
		curl_setopt( $ch, CURLOPT_COOKIEFILE, $this->cookie );
	}

	/**
	 * Dumps values if debugging is enabled.
	 *
	 * @param $string
	 *
	 * @return bool
	 */
	function log( $string ) {
		if ( $this->debug ) {
			var_dump( $string );

			return true;
		} else {
			return false;
		}
	}

	/**
	 * Create a POST request; supports a variety of scenarios.
	 *
	 * @param $url
	 * @param $payload
	 * @param $type
	 * @param $filename
	 *
	 * @return bool|string
	 */
	function post( $url, $payload, $type = "POST", $filename = false ) {
		// Reset header
		$this->headers = array();
		// Set the last url attribute to match this request
		$this->last_url = $url;
		// Get cURL resource
		$curl = curl_init();
		// Merge the payload with channel_payload
		// $payload = json_encode($payload);

		// Custom POST/GET requests
		if ( $type === "POST" ) {
			curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'POST' );
		}

		if ( $type === "GET" ) {
			curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'GET' );
		}

		if ( $type === "PUT" ) {
			curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'PUT' );
		}

		// If file is empty set default header to JSON
		if ( $filename === false ) {
			$payload       = json_encode( $payload );
			$this->headers = array_merge( $this->headers, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen( $payload )
			) );
		} // No file assume JSON based request
		else if ( is_string( $filename ) && file_exists( $filename ) ) {
			$base_filename = basename( $filename );
			$path_parts    = pathinfo( $filename );
			$ext           = $path_parts['extension'];

			// If the content type is valid
			if ( isset( $this->c_types[ $ext ] ) ) {
				// File sent as JSON parameter
				if ( $this->file_as_json ) {
					$this->headers = array_merge( array(
						'Content-Type: application/json;',
					), $this->headers );

					$file_payload = new CURLFile( $filename, $this->c_types[ $ext ], $base_filename );
					$file_payload = array( 'file' => $file_payload ); //new CURLFILE($filename)); //$payload);

					$payload             = array_merge( $payload, $file_payload );
					$payload['contents'] = utf8_encode( file_get_contents( $filename ) );
					$payload             = json_encode( $payload );

					$this->log( $payload );
				} // Otherwise sent as attachment file content type
				else {
					// Attachment header
					$this->headers = array_merge( array(
						"Content-Disposition: attachment; filename='{$base_filename}'; charset=utf-8"
					), $this->headers );
					// Content type derived from extension
					$this->headers = array_merge( array(
						"Content-Type: {$this->c_types[$ext]}; charset=utf-8"
					), $this->headers );
				}
			}
		}

		// Auth method
		$this->basic_auth();
		//$this->user_auth($curl);

		// Set some options - we are passing in a useragent too here
		curl_setopt( $curl, CURLOPT_URL, $url );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, $this->headers );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $curl, CURLOPT_USERAGENT, 'europa_bookstack' );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $payload );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );

		// Send the request & save response to $resp
		$resp      = curl_exec( $curl );
		$http_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
		$errors    = curl_error( $curl );
		curl_close( $curl );

		// Check for response errors
		$friendly_errors = $resp;

		if ( ! isset( $resp ) ) {
			if ( isset( $errors ) ) {
				$friendly_errors = $errors;
			} else {
				$friendly_errors = $resp;
			}
		}

		//$this->log($payload);
		$this->log( $friendly_errors );
		$this->last_status_message = $errors;
		$this->last_status_code    = $http_code;

		return ( $http_code >= 200 && $http_code < 300 ) ? $resp : $friendly_errors;
	}

	/**
	 * Utilises `post` to make a GET request using similar options.
	 * @param $url
	 * @param $payload
	 *
	 * @return bool|string
	 */
	function get( $url, $payload ) {
		return $this->post( $url, $payload, "GET" );
	}

	/**
	 * Utilises `post` to make a PUT request using similar options.
	 * @param $url
	 * @param $payload
	 *
	 * @return bool|string
	 */
	function put( $url, $payload ) {
		return $this->post( $url, $payload, "PUT" );
	}

	/**
	 * Retrieves a list of shelves.
	 *
	 * @param $payload
	 *
	 * @return false
	 */
	function get_shelves( $payload ) {
		if ( is_array( $payload ) ) {
			$shelves = $this->get( "{$this->url}shelves", $payload );
			if ( isset( $shelves ) ) {
				$shelves = json_decode( $shelves );
				if ( isset( $shelves->data ) ) {
					$shelves = $shelves->data;
					$this->log( $shelves );

					return $shelves;
				}
			}
		}

		return false;
	}

	/**
	 * Retrieves a list of books.
	 *
	 * @param $payload array
	 *
	 * @return false
	 */
	function get_books( $payload ) {
		if ( is_array( $payload ) ) {
			$books = $this->get( "{$this->url}books", $payload );

			if ( isset( $books ) ) {
				$books = json_decode( $books );
				if ( isset( $books->data ) ) {
					$books = $books->data;
					$this->log( $books );

					return $books;
				}
			}
		}

		return false;
	}

	/** Get Directory Structure should return books, chapters, pages based upon directory structure......
	 * 
	 */
function get_directory_structure($directory)
{
    $structure = array();

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $path) {
        if ($path->isDir()) {
            $structure[$path->getBasename()] = array();
        } else {
            $parts = explode(DIRECTORY_SEPARATOR, $path->getPath());
            $parent = &$structure;
            foreach ($parts as $part) {
                if (!isset($parent[$part])) {
                    $parent[$part] = array();
                }
                $parent = &$parent[$part];
            }
            $parent[] = $path->getBasename();
        }
    }

    return $structure;
}

	/** Kenny's Addition "Create Shelf"
	 * Creates a new book if it does not exists.
	 * $check_existing Default: true. When true checks if there is an existing book with the same name and returns
	 * that books ID, when false creates a new book regardless
	 *
	 * @param $shelf_title
	 * @param $check_existing
	 *
	 * @return false|mixed
	 */

	function create_shelf( $shelf_title, $check_existing = true ) {
		$shelf_id = false;

		# Check books if required (default)
		if ( is_bool( $check_existing ) && $check_existing ) {
			$shelves = $this->get_shelves( array() );

			if ( $shelves ) {
				foreach ( $shelves as $shelf ) {
					$this->log( $shelf->name );
					//continue;
					if ( $shelf->name === $shelf_title ) {
						$book_id = $shelf->id;
						$this->log( "Shelf exists with ID: {$shelf_id}" );

						return $shelf_id;
					}
				}
			}
		}

		# No shelf was found create one
		if ( $shelf_id === false ) {
			# Shelf doesn't exists so create it
			$payload  = array(
				"name"        => "{$shelf_title}",
				"description" => "" // Perhaps add auto description from DOM later
			);
			$new_shelf = $this->post( "{$this->url}shelves", $payload );
			if ( isset( $new_shelf ) ) {
				$new_shelf = json_decode( $new_shelf );
				if ( isset( $new_shelf->id ) ) {
					$shelf_id = $new_shelf->id;
					$this->log( "Created Shelf with ID: {$shelf_id}" );
				}
			}
		}

		return $shelf_id;
	}

	/**
	 * Creates a new book if it does not exists.
	 * $check_existing Default: true. When true checks if there is an existing book with the same name and returns
	 * that books ID, when false creates a new book regardless
	 *
	 * @param $book_title
	 * @param $check_existing
	 *
	 * @return false|mixed
	 */
	function create_book( $book_title, $check_existing = true ) {
		$book_id = false;

		# Check books if required (default)
		if ( is_bool( $check_existing ) && $check_existing ) {
			$books = $this->get_books( array() );

			if ( $books ) {
				foreach ( $books as $book ) {
					$this->log( $book->name );
					//continue;
					if ( $book->name === $book_title ) {
						$book_id = $book->id;
						$this->log( "Book exists with ID: {$book_id}" );

						return $book_id;
					}
				}
			}
		}

		# No book was found create one
		if ( $book_id === false ) {
			# Book doesn't exists so create it
			$payload  = array(
				"name"        => "{$book_title}",
				"description" => ""
			);
			$new_book = $this->post( "{$this->url}books", $payload );
			if ( isset( $new_book ) ) {
				$new_book = json_decode( $new_book );
				if ( isset( $new_book->id ) ) {
					$book_id = $new_book->id;
					$this->log( "Created Book with ID: {$book_id}" );
				}
			}
		}

		return $book_id;
	}

	/**
	 * Creates a page within a book.
	 *
	 * @param $payload array Requires keys: book_id, name, html
	 *
	 * @return false
	 */
	function create_page( $payload ) {
		// Must be an array and have the 3 keys
		if (
			is_array( $payload )
			&& isset( $payload['book_id'] )
			&& isset( $payload['name'] )
			&& isset( $payload['html'] )
		) {
			$page = $this->post( "{$this->url}pages", $payload );

			if ( isset( $page ) ) {
				$page = json_decode( $page );
				if ( isset( $page->data ) ) {
					$page = $page->data;
					$this->log( $page );

					return $page;
				}
			}
		}

		return false;
	}

	function starts_with( $haystack, $needle ) {
		$length = strlen( $needle );

		return substr( $haystack, 0, $length ) === $needle;
	}

	function ends_with( $haystack, $needle ) {
		$length = strlen( $needle );
		if ( ! $length ) {
			return true;
		}

		return substr( $haystack, - $length ) === $needle;
	}
}
