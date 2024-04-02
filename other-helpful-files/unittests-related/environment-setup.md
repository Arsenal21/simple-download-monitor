# About Plugin Integration Test
___
This will guide you to set up the proper environment to the integration tests of you WordPress plugin locally.

### Pre-requisites:
The following tools needs to be installed on your system and their path should be added to the environment variable.
- PHP >= 8.2
- Composer
- MySQL
- A Fresh copy of WordPress


### Environment Setup
Step 1 - Setup LAMP server
- Install a tool like Xampp/WAMP/Laragon etc. Or if you wish you can set up webservers like Apache/Nginx manually.
- Make sure to install PHP >= 8.2 amd MySQL >= 8.0. (this gets installed with Xampp/WAMP/Laragon etc.)
- Add the path of executable of PHP and MySQL (if not set automatically)
- After that, you can run these command in you command line application to verify the executables path were added successfully.
  - `php --version`
  - `mysql --version`

Step 2 - Install WordPress
- With the help of your LAMP server application, install a fresh copy of WordPress using proper database credentials.
- Grab the absolute path of the directory of your WordPress installation.

Step 3 - Install Composer (PHP Dependency Manager)
- Download Composer from <a href="https://getcomposer.org/download/">here</a>.
- During installation, composer will require path to your installed PHP executables. This should grab the path automatically. If not, provide the porper path.
- After that, verify that the executable path of composer were added successfully. (Composer automatically added its exe path to environment variable).
    - `composer --version`

Step 4 - Setup Test Database
- Create a new database in your MySQL server, this will separate different from your locally installed WordPress's database.
- Grab the credentials of that database like database name, host, user and password.

Setup - 5 Add Configurations and Install Packages:
- Navigate to you plugin repository directory.
- In the root directory, find 'tests' folder and open 'wp-tests-config.php' file inside that.
- At the very top where the "ABSPATH" constant is defined, update the constant value with the absolute path of your WordPress directory (grabbed in step-2). NOTE: The directory path must end with a forward slash ('/').
- Update the other constant "DB_NAME","DB_USER","DB_PASSWORD", "DB_HOST" with the value grabbed in step-4.


### Runs The Tests
- Now in the repository root directory, open your command line application and run the command: `composer install`. Wait a few seconds and this will install all the required packages for testing.
- Not to run the tests, run the command `vendor/bin/phpunit`. You can also run `vendor/bin/phpunit --testdox` to see a detailed info of unit tests that runs.


That's it.

