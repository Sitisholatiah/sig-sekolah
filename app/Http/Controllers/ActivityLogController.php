<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->when($request->aksi, fn ($q, $a) => $q->where('aksi', $a))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.activity-logs.index', compact('logs'));
    }
}