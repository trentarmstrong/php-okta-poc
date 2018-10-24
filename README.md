# Project Description

This project was put together as a POC to demonstrate a simple implementation of an Okta authentication integration within a PHP application. The application just consists of a single home page with a login/logout button that makes use of the Okta Hosted login page.

# File Descriptions

**.env.sample**
Example environment variable file. vlucas/phpdotenv is used in this project to inject environment variables into the code at runtime. You will need to add a **.env** file to the server manually as it is not checked into Git.

**.htaccess**
The POC is written to run on a simple Apache Web Server on an AWS EC2 Linux instance. Placing this .htaccess file in the root web directory allows us to overwrite some of the default Apache settings. The POC uses a PHP library called Fastroute to do some simple routing. As such, we want all requests to the application, regardless of the route, to go to index.php where all the routing is taken care of. This .htaccess file re-writes urls to point at index.php for everything.

**composer.json**
Composer is like the NPM for PHP. Manages dependencies.

**functions.php**
Contains the Okta written code to validate JWT tokens and check for authentication success.

**http.conf**
Slightly altered version of the default file located at /etc/httpd/conf/httpd.conf . This version has an altered <Directory /> configuration to allow for the above .htaccess file to override the default settings.

TODO: Use this file in the startup.sh script. Right now it needs to be manually updated/loaded to the server.

**index.php**
Main entry point for the application, contains the bulk of the code.

**test.php**
Simple PHP test page just to check that PHP was installed correctly. Can access this at http://{ec2-host}/test.php.

# Other Notes
Make sure to swap


