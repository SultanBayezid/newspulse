<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| Scheduled articles are promoted to "published" automatically once their
| publish time arrives. Run "php artisan schedule:work" locally or register
| the system cron entry in production.
|
*/

Schedule::command('articles:publish')->everyMinute();
