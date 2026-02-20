<?php

declare(strict_types=1);

use App\Ai\Tools\ListBusinessRules;
use App\Models\Project;
use Laravel\Ai\Tools\Request;

test('list business rules tool has a description', function (): void {
    $project = Project::create(['name' => 'Test']);
    $tool = new ListBusinessRules($project);

    expect((string) $tool->description())->not->toBeEmpty();
});

test('list business rules tool returns project rules', function (): void {
    $project = Project::create(['name' => 'Test']);

    $project->businessRules()->create(['title' => 'Rule 1', 'description' => 'Desc 1', 'category' => 'payments']);
    $project->businessRules()->create(['title' => 'Rule 2', 'description' => 'Desc 2', 'category' => 'auth']);

    $result = (string) new ListBusinessRules($project)->handle(new Request([]));

    expect($result)->toContain('Rule 1')->toContain('Rule 2');
});

test('list business rules tool filters by status', function (): void {
    $project = Project::create(['name' => 'Test']);

    $project->businessRules()->create(['title' => 'Active Rule', 'description' => 'D', 'category' => 'c', 'status' => 'active']);
    $project->businessRules()->create(['title' => 'Deprecated Rule', 'description' => 'D', 'category' => 'c', 'status' => 'deprecated']);

    $result = (string) new ListBusinessRules($project)->handle(new Request(['status' => 'active']));

    expect($result)->toContain('Active Rule')->not->toContain('Deprecated Rule');
});

test('list business rules tool filters by category', function (): void {
    $project = Project::create(['name' => 'Test']);

    $project->businessRules()->create(['title' => 'Payment Rule', 'description' => 'D', 'category' => 'payments']);
    $project->businessRules()->create(['title' => 'Auth Rule', 'description' => 'D', 'category' => 'auth']);

    $result = (string) new ListBusinessRules($project)->handle(new Request(['category' => 'payments']));

    expect($result)->toContain('Payment Rule')->not->toContain('Auth Rule');
});

test('list business rules tool returns message when empty', function (): void {
    $project = Project::create(['name' => 'Test']);

    $result = (string) new ListBusinessRules($project)->handle(new Request([]));

    expect($result)->toContain('No business rules');
});

test('list business rules tool only returns rules from its project', function (): void {
    $projectA = Project::create(['name' => 'A']);
    $projectB = Project::create(['name' => 'B']);

    $projectA->businessRules()->create(['title' => 'Rule A', 'description' => 'D', 'category' => 'c']);
    $projectB->businessRules()->create(['title' => 'Rule B', 'description' => 'D', 'category' => 'c']);

    $result = (string) new ListBusinessRules($projectA)->handle(new Request([]));

    expect($result)->toContain('Rule A')->not->toContain('Rule B');
});
