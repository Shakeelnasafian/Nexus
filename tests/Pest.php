<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(Tests\TestCase::class)
    ->in('Feature');

uses(RefreshDatabase::class)->in('Feature/Billing');