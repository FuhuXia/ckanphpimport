ckanphpimport
=============

A PHP script to import data from source json into inventory CKAN site.

To use, run

$ php import.php dev my-org

Above example command will load data defined in server.ini and write it to dev server. 
All data will go into my-org organization, unless ckan_use_src_org is set to true.

When define source url in server.ini, also provide appropriate source type. It can be 
either a json file, or a ckan package_search result.

Things to do:

1. Fix the limitation of the number of fields that can go into extras. Currently new entry to push out existing entries.

2. Do a deletion purge via cron job or something. Otherwise deleted datasets and organization will make adding new datasets with same names difficult.

3. Compare the source dataset with existing dataset and identify same ones. Do a update instead of create new duplicated ones.

4. Log instead of die().