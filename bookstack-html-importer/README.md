# BookStack HTML Importer - PHP

This project was put together to aid in migrating content from Atlassian Confluence to BookStack. 

It requires that the Confluence Space to be imported is first exported to HTML. Then that HTML can be imported to BookStack using this API client helper library.

The requirements were simplistic for the import task; the importer in its default state will create a Book using the name you provide and the import HTML files from the path provided. Each HTML document imported will create a new page within the book using the file name as the page name.

Feel free to adapt this to your own requirements.

**Notice**: this doesn't handle attachments.

## Usage Instructions

See `examples/importer.php` for a working example.

Essentially you want to do the following:
- Exported a space to HTML.
- Extract the space and copy the path to the location.
- Define a Book name and include the book name and imported path for the HTML in the file: `importer.php` lines: 13-14.
- Run the import task; which will create a Book and imported each HTML page as a page with the Book.

To test create a credentials file: credentials.php and include your Rest API credentials from BookStack.

```php
$credentials = array(
	'url' => "<bookstack-path>",
	'id' => "<id>",
	'secret' => "<api-token>"
);
```

Current functions:
- `get_shelves` - List all shelves
- `get_books` - List all books
- `create_book` - Create a book; supports checks for existing books to prevent duplicates.
- `create_page` - Create a page within a book.