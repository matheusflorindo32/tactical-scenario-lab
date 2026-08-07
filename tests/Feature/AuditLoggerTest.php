<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Person;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_payload_fields_are_redacted_recursively(): void
    {
        $organization = Organization::create(['name' => 'Tactical Medicine Academy']);
        $person = Person::create(['display_name' => 'Pessoa Auditada']);
        $request = Request::create('/audit-test', 'POST', server: [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'Audit Test Agent',
        ]);

        $log = app(AuditLogger::class)->record(
            'person_identifier.created',
            $person,
            $organization->id,
            [
                'type' => 'cpf',
                'value' => '123.456.789-09',
                'masked_value' => '***.***.***-09',
                'nested' => [
                    'email' => 'sensitive@example.com',
                    'safe_status' => 'confirmed',
                ],
            ],
            $request,
        );

        $payload = $log->fresh()->payload;

        $this->assertSame('[REDACTED]', $payload['value']);
        $this->assertSame('***.***.***-09', $payload['masked_value']);
        $this->assertSame('[REDACTED]', $payload['nested']['email']);
        $this->assertSame('confirmed', $payload['nested']['safe_status']);
        $this->assertSame('127.0.0.1', $log->ip_address);
        $this->assertSame('Audit Test Agent', $log->user_agent);
    }

    public function test_audit_log_references_subject_and_organization_without_pii(): void
    {
        $organization = Organization::create(['name' => 'Organização Auditada']);
        $person = Person::create(['display_name' => 'Pessoa Auditada']);

        app(AuditLogger::class)->record('person.created', $person, $organization->id, [
            'status' => 'incomplete',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'actor_type' => 'system',
            'action' => 'person.created',
            'subject_type' => Person::class,
            'subject_id' => $person->id,
        ]);

        $this->assertSame(['status' => 'incomplete'], AuditLog::firstOrFail()->payload);
    }
}
