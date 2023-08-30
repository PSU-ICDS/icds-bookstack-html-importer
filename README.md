# icds_html_bookstack_importer

Some scripts and an additional readme to help migrate content from Atlassian Confluence over to Bookstack. These are modifiied from Oddity Engineers Bookstack-HTML-Importer to better format titles and to add titles for cases in which the HTML export doesn't seem to provide them. You will zip of parts of spaces as "books" carefully naming to omit any symbols and non-letter characters (you will have to add them back in later) and unzip and rename them into a main directory which will become your "shelf". You can rename the shelf when creating the script. 

Included .zip's include simple-html-dom and the Oddity Engineers HTML Bookstack Importer (for reference if needed). The simple-html-dom will need to be extracted as is in the icds-bookstack-html-importer folder for my modified script(s) to run and extract titles from the HTML itself if needed.

bookstack_client.php will need to be moved (or copied) into the root of your bookstack instance
the /icds_html_bookstack_importer folder should be in the root of the bookstack instance. Simple-html-dom unzipped within it. You must use MY version of the bookstack_client as I created several new methods that are now used by the importer script.

Export Atlassean Confluence as HTML. Click gear to bottom left. Every export will become a "book", unzip and rename these into a main directory which will become your 'shelf'.

After having created an entire shelf. Move the attachments folders of ALL the books contained within the shelf...to a single attachments folder. You will add ALL attachments from all shelves into here and place it into /{BookstackRoot}/public/attachments when complete with all shelves. You will have to adjust permissions in order to move files into that folder.

Zip your "Shelves" up

SSH to the VM running Bookstack see ConfluenceToBookstackMigration.docx for greater in using PuTTY for this

Create OAuth token within Bookstack; have credentials.php open and edit the contents as you will only see the secret one time. Don't lose it.

Edit credentials.php to reference your Oauth token -- You will need admin access to bookstack instance, go to settings and create an Oauth token. --

Run importer.php to import Books and place them onto a "Shelf".

Each Shelf you have created will have to be entered separately.


