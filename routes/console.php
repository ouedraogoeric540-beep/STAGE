<?php

use Illuminate\Support\Facades\Schedule;

// Vérifier toutes les minutes
Schedule::command('eventsecure:terminer-evenements')->everyMinute();