## Installation
- git clone https://gurpreetappyarn@bitbucket.org/gurpreetappyarn/laravel-holiday-api.git
- composer install
- cp .env.example .env and configure database connection
- php artisan migrate
- Run new command to import json holiday data into database
For example php artisan holiday:add-country-holiday [CODE_ROOT_PATH]/tests/data/GB.json
- tests/data/GB.json file has additional attribute bank_holiday which can be set in any other holiday json file also
- Updated api will be available at /api/v1/holidays?country=GB&year=2017
- Bank holiday filter can be used by passing bankHolidays=1 in QueryString. /api/v1/holidays?country=GB&year=2017&bankHolidays=1

## Changes made for API
- New command added. app/Console/Commands/AddCountryHolidays.php
- Service added for API. app/Services/ApiV1Service.php
- Two models added for country and holiday in app/Models/
- Repositories added for the same in app/Models/Repository
- routes/api.php updated for api routes
- Two basic PHPUnit test cases added. One for Functional & other one for Unit testing available in tests/
- Holiday data in JSON format available in tests/data/# holidays-api
