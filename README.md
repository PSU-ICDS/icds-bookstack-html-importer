# icds_html_bookstack_importer
Some scripts and an additional readme to help migrate content from Atlassian Confluence over to Bookstack. These are modifiied from Oddity Engineers Bookstack-HTML-Importer to better format titles and to add titles for cases in which the HTML export doesn't seem to provide them. This is a work in progress and not representative of what I will leave.

Included .zip's include simple-html-dom and the Oddity Engineers HTML Bookstack Importer (for reference if needed). The simple-html-dom will need to be extracted as is in the icds-bookstack-html-importer folder for my modified script(s) to run and extract titles from the HTML itself if needed.

bookstack_client.php will need to be moved (or copied) into the root of your bookstack instance
the /icds_html_bookstack_importer folder should be in the root of the bookstack instance as well...with the simple-html-dom unzipped within it.

Export Atlassean Confluence as HTML

SSH to the VM running Bookstack

Edit importer.php to reference the Book title you wish to create and to reference the location of the HTML file

Edit credentials.php to reference your Oauth token -- You will need admin access to bookstack instance, go to settings and create an Oauth token.

Run importer.php to import Book.

As of now...this will need to be repeated for every 'Space' from Confluence.


