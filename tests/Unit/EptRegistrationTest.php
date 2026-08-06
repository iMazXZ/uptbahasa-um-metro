<?php

namespace Tests\Unit;

use App\Models\EptRegistration;
use Tests\TestCase;

class EptRegistrationTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_requires_group_assignments_to_be_distinct(): void
    {
        $this->assertTrue(EptRegistration::hasDistinctGroupAssignments([1, 2, 3]));
        $this->assertTrue(EptRegistration::hasDistinctGroupAssignments([1, 2, null, 4]));
        $this->assertFalse(EptRegistration::hasDistinctGroupAssignments([1, 2, 1]));
        $this->assertFalse(EptRegistration::hasDistinctGroupAssignments([5, 5, null]));
    }
}
