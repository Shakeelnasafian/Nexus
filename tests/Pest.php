<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(Tests\TestCase::class)
    ->in('Feature');

uses(RefreshDatabase::class)->in('Feature/Billing');
uses(RefreshDatabase::class)->in('Feature/Vendor');
uses(RefreshDatabase::class)->in('Feature/Workflow');
uses(RefreshDatabase::class)->in('Feature/Accountability');
uses(RefreshDatabase::class)->in('Feature/Integration');
uses(RefreshDatabase::class)->in('Feature/Http');