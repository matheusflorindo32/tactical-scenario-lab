<?php

namespace Tests\Feature;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionalAuditFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_creation_is_audited_without_notes(): void
    {
        $this->post(route('organizations.store'), [
            'name' => 'Organização Auditada',
            'kind' => 'company',
            'status' => 'active',
            'notes' => 'Informação interna que não deve ser copiada para o log.',
        ])->assertRedirect();

        $organization = Organization::firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'action' => 'organization.created',
            'subject_type' => Organization::class,
            'subject_id' => $organization->id,
            'actor_type' => 'system',
        ]);

        $payload = $organization->auditLogs ?? null;
        $stored = json_decode((string) \DB::table('audit_logs')->value('payload'), true);

        $this->assertSame('company', $stored['kind']);
        $this->assertSame('active', $stored['status']);
        $this->assertArrayNotHasKey('notes', $stored);
    }

    public function test_unit_creation_is_audited_with_safe_metadata(): void
    {
        $organization = Organization::create(['name' => 'Organização Auditada']);

        $this->post(route('units.store'), [
            'organization_id' => $organization->id,
            'name' => 'Unidade Segura',
            'kind' => 'department',
            'status' => 'active',
            'notes' => 'Não registrar este texto no log.',
        ])->assertRedirect(route('organizations.show', $organization));

        $unit = $organization->units()->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'action' => 'unit.created',
            'subject_type' => $unit::class,
            'subject_id' => $unit->id,
        ]);

        $stored = json_decode((string) \DB::table('audit_logs')->value('payload'), true);

        $this->assertSame('department', $stored['kind']);
        $this->assertSame('active', $stored['status']);
        $this->assertNull($stored['parent_unit_id']);
        $this->assertArrayNotHasKey('notes', $stored);
    }
}
