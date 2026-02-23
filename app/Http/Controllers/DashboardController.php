<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with project summaries and statistics.
     */
    public function __invoke(Request $request): Response
    {
        $projects = $request->user()->projects()
            ->withCount([
                'tasks',
                'tasks as tasks_open_count' => fn ($q) => $q->open(),
                'tasks as tasks_closed_count' => fn ($q) => $q->closed(),
                'decisions as decisions_count' => fn ($q) => $q->active(),
                'businessRules as business_rules_count' => fn ($q) => $q->active(),
                'conversations',
            ])
            ->with(['tasks' => fn ($q) => $q->with('status')->latest('updated_at')->limit(1)])
            ->latest('projects.created_at')
            ->get(['projects.id', 'projects.ulid', 'projects.name', 'projects.color']);

        $totals = [
            'projects' => $projects->count(),
            'tasks_open' => $projects->sum('tasks_open_count'),
            'tasks_closed' => $projects->sum('tasks_closed_count'),
            'decisions' => $projects->sum('decisions_count'),
        ];

        return Inertia::render('dashboard', [
            'totals' => $totals,
            'projects' => $projects,
        ]);
    }
}
