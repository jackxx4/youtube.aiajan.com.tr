<?php

use Illuminate\Support\Facades\Schedule;

// Supporter expiry is evaluated at read time; this command can normalize old statuses.
Schedule::command('supporters:expire')->dailyAt('02:15')->withoutOverlapping();
