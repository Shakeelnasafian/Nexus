<?php

namespace App\Http\Controllers\Accountability;

use App\Domain\Accountability\Models\SlaRecord;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class SlaRecordController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('SlaRecords/Index', [
            'records' => SlaRecord::with('vendor:id,name')
                ->latest('started_at')
                ->get(['id', 'vendor_id', 'title', 'status', 'started_at', 'breached_at', 'closed_at']),
        ]);
    }
}
