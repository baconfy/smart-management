<?php

declare(strict_types=1);

use App\Actions\BusinessRules\DeleteBusinessRule;
use App\Models\BusinessRule;
use App\Models\Project;

test('it deletes a business rule', function (): void {
    $project = Project::factory()->create(['name' => 'Test']);
    $rule = $project->businessRules()->create([
        'title' => 'To Delete',
        'description' => 'D',
        'category' => 'billing',
    ]);

    $result = (new DeleteBusinessRule)($rule);

    expect($result)->toBeTrue();
    expect(BusinessRule::count())->toBe(0);
});
