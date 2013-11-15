ckanphpimport
=============

A PHP script to import data from source json into inventory CKAN site.

To use, run
$ php import.php dev my-org

Above example command will load data defined in server.ini and write it to dev server. 
all data will go into my-org organization.

When define source url in server.ini, also provide appropriate source type. It can be 
either a json file, or a ckan package_search result.
