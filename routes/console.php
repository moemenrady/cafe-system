<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('shifts:close-midnight')->dailyAt('00:00');
