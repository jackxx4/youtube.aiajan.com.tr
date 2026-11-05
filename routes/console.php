<?php
use Illuminate\Support\Facades\Schedule;
Schedule::command('supporters:expire')->dailyAt('02:15')->withoutOverlapping();
Schedule::command('youtube:sync-channels')->everySixHours()->withoutOverlapping();
