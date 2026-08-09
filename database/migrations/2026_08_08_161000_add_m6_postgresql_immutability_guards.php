<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION m6_guard_published_scenario_version()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    IF OLD.publication_status = 'published' AND (
        NEW.environment IS DISTINCT FROM OLD.environment OR
        NEW.threat_level IS DISTINCT FROM OLD.threat_level OR
        NEW.mechanism IS DISTINCT FROM OLD.mechanism OR
        NEW.estimated_casualty_count IS DISTINCT FROM OLD.estimated_casualty_count OR
        NEW.resources::jsonb IS DISTINCT FROM OLD.resources::jsonb OR
        NEW.learning_objectives::jsonb IS DISTINCT FROM OLD.learning_objectives::jsonb OR
        NEW.expected_actions::jsonb IS DISTINCT FROM OLD.expected_actions::jsonb OR
        NEW.critical_errors::jsonb IS DISTINCT FROM OLD.critical_errors::jsonb
    ) THEN
        RAISE EXCEPTION 'Published scenario version definition is immutable.' USING ERRCODE = '23514';
    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER m6_published_scenario_version_immutable
BEFORE UPDATE ON scenario_versions
FOR EACH ROW
EXECUTE FUNCTION m6_guard_published_scenario_version();

CREATE OR REPLACE FUNCTION m6_guard_finalized_assessment()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    IF OLD.status = 'finalized' THEN
        RAISE EXCEPTION 'Finalized assessment is immutable.' USING ERRCODE = '23514';
    END IF;

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER m6_finalized_assessment_immutable
BEFORE UPDATE OR DELETE ON execution_assessments
FOR EACH ROW
EXECUTE FUNCTION m6_guard_finalized_assessment();

CREATE OR REPLACE FUNCTION m6_guard_direct_assessment_content()
RETURNS trigger
LANGUAGE plpgsql
AS $$
DECLARE
    old_assessment_id bigint;
    new_assessment_id bigint;
BEGIN
    IF TG_OP <> 'INSERT' THEN
        old_assessment_id := OLD.execution_assessment_id;
    END IF;

    IF TG_OP <> 'DELETE' THEN
        new_assessment_id := NEW.execution_assessment_id;
    END IF;

    IF EXISTS (
        SELECT 1
        FROM execution_assessments
        WHERE status = 'finalized'
          AND id IN (old_assessment_id, new_assessment_id)
    ) THEN
        RAISE EXCEPTION 'Finalized assessment content is immutable.' USING ERRCODE = '23514';
    END IF;

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER m6_assessment_criteria_immutable
BEFORE INSERT OR UPDATE OR DELETE ON assessment_criteria
FOR EACH ROW
EXECUTE FUNCTION m6_guard_direct_assessment_content();

CREATE TRIGGER m6_critical_error_occurrences_immutable
BEFORE INSERT OR UPDATE OR DELETE ON critical_error_occurrences
FOR EACH ROW
EXECUTE FUNCTION m6_guard_direct_assessment_content();

CREATE TRIGGER m6_key_time_records_immutable
BEFORE INSERT OR UPDATE OR DELETE ON key_time_records
FOR EACH ROW
EXECUTE FUNCTION m6_guard_direct_assessment_content();

CREATE OR REPLACE FUNCTION m6_guard_assessment_evidence()
RETURNS trigger
LANGUAGE plpgsql
AS $$
DECLARE
    old_assessment_id bigint;
    new_assessment_id bigint;
BEGIN
    IF TG_OP <> 'INSERT' THEN
        SELECT execution_assessment_id
          INTO old_assessment_id
          FROM assessment_criteria
         WHERE id = OLD.assessment_criterion_id;
    END IF;

    IF TG_OP <> 'DELETE' THEN
        SELECT execution_assessment_id
          INTO new_assessment_id
          FROM assessment_criteria
         WHERE id = NEW.assessment_criterion_id;
    END IF;

    IF EXISTS (
        SELECT 1
        FROM execution_assessments
        WHERE status = 'finalized'
          AND id IN (old_assessment_id, new_assessment_id)
    ) THEN
        RAISE EXCEPTION 'Finalized assessment evidence is immutable.' USING ERRCODE = '23514';
    END IF;

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER m6_assessment_evidence_immutable
BEFORE INSERT OR UPDATE OR DELETE ON assessment_evidence
FOR EACH ROW
EXECUTE FUNCTION m6_guard_assessment_evidence();

CREATE OR REPLACE FUNCTION m6_guard_execution_debrief()
RETURNS trigger
LANGUAGE plpgsql
AS $$
DECLARE
    old_assessment_id bigint;
    new_assessment_id bigint;
BEGIN
    IF TG_OP <> 'INSERT' THEN
        old_assessment_id := OLD.execution_assessment_id;
    END IF;

    IF TG_OP <> 'DELETE' THEN
        new_assessment_id := NEW.execution_assessment_id;
    END IF;

    IF EXISTS (
        SELECT 1
        FROM execution_assessments
        WHERE status = 'finalized'
          AND id IN (old_assessment_id, new_assessment_id)
    ) THEN
        RAISE EXCEPTION 'Finalized assessment debrief is immutable.' USING ERRCODE = '23514';
    END IF;

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER m6_execution_debrief_immutable
BEFORE INSERT OR UPDATE OR DELETE ON execution_debriefs
FOR EACH ROW
EXECUTE FUNCTION m6_guard_execution_debrief();

CREATE OR REPLACE FUNCTION m6_guard_debrief_entry()
RETURNS trigger
LANGUAGE plpgsql
AS $$
DECLARE
    old_assessment_id bigint;
    new_assessment_id bigint;
BEGIN
    IF TG_OP <> 'INSERT' THEN
        SELECT execution_assessment_id
          INTO old_assessment_id
          FROM execution_debriefs
         WHERE id = OLD.execution_debrief_id;
    END IF;

    IF TG_OP <> 'DELETE' THEN
        SELECT execution_assessment_id
          INTO new_assessment_id
          FROM execution_debriefs
         WHERE id = NEW.execution_debrief_id;
    END IF;

    IF EXISTS (
        SELECT 1
        FROM execution_assessments
        WHERE status = 'finalized'
          AND id IN (old_assessment_id, new_assessment_id)
    ) THEN
        RAISE EXCEPTION 'Finalized assessment debrief entry is immutable.' USING ERRCODE = '23514';
    END IF;

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER m6_debrief_entries_immutable
BEFORE INSERT OR UPDATE OR DELETE ON debrief_entries
FOR EACH ROW
EXECUTE FUNCTION m6_guard_debrief_entry();

CREATE OR REPLACE FUNCTION m6_guard_action_item()
RETURNS trigger
LANGUAGE plpgsql
AS $$
DECLARE
    old_assessment_id bigint;
    new_assessment_id bigint;
    touches_finalized boolean;
BEGIN
    IF TG_OP <> 'INSERT' THEN
        SELECT execution_assessment_id
          INTO old_assessment_id
          FROM execution_debriefs
         WHERE id = OLD.execution_debrief_id;
    END IF;

    IF TG_OP <> 'DELETE' THEN
        SELECT execution_assessment_id
          INTO new_assessment_id
          FROM execution_debriefs
         WHERE id = NEW.execution_debrief_id;
    END IF;

    SELECT EXISTS (
        SELECT 1
        FROM execution_assessments
        WHERE status = 'finalized'
          AND id IN (old_assessment_id, new_assessment_id)
    ) INTO touches_finalized;

    IF NOT touches_finalized THEN
        IF TG_OP = 'DELETE' THEN
            RETURN OLD;
        END IF;

        RETURN NEW;
    END IF;

    IF TG_OP = 'UPDATE' AND
        NEW.uuid IS NOT DISTINCT FROM OLD.uuid AND
        NEW.execution_debrief_id IS NOT DISTINCT FROM OLD.execution_debrief_id AND
        NEW.action IS NOT DISTINCT FROM OLD.action AND
        NEW.responsible_person_id IS NOT DISTINCT FROM OLD.responsible_person_id AND
        NEW.responsible_label IS NOT DISTINCT FROM OLD.responsible_label AND
        NEW.due_date IS NOT DISTINCT FROM OLD.due_date AND
        NEW.notes IS NOT DISTINCT FROM OLD.notes AND
        NEW.created_at IS NOT DISTINCT FROM OLD.created_at
    THEN
        RETURN NEW;
    END IF;

    RAISE EXCEPTION 'Finalized assessment action content is immutable.' USING ERRCODE = '23514';
END;
$$;

CREATE TRIGGER m6_action_items_immutable
BEFORE INSERT OR UPDATE OR DELETE ON action_items
FOR EACH ROW
EXECUTE FUNCTION m6_guard_action_item();

CREATE OR REPLACE FUNCTION m6_guard_execution_event_append_only()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    RAISE EXCEPTION 'Execution timeline is append-only.' USING ERRCODE = '23514';
END;
$$;

CREATE TRIGGER m6_execution_events_append_only
BEFORE UPDATE OR DELETE ON execution_events
FOR EACH ROW
EXECUTE FUNCTION m6_guard_execution_event_append_only();
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS m6_execution_events_append_only ON execution_events;
DROP TRIGGER IF EXISTS m6_action_items_immutable ON action_items;
DROP TRIGGER IF EXISTS m6_debrief_entries_immutable ON debrief_entries;
DROP TRIGGER IF EXISTS m6_execution_debrief_immutable ON execution_debriefs;
DROP TRIGGER IF EXISTS m6_assessment_evidence_immutable ON assessment_evidence;
DROP TRIGGER IF EXISTS m6_key_time_records_immutable ON key_time_records;
DROP TRIGGER IF EXISTS m6_critical_error_occurrences_immutable ON critical_error_occurrences;
DROP TRIGGER IF EXISTS m6_assessment_criteria_immutable ON assessment_criteria;
DROP TRIGGER IF EXISTS m6_finalized_assessment_immutable ON execution_assessments;
DROP TRIGGER IF EXISTS m6_published_scenario_version_immutable ON scenario_versions;

DROP FUNCTION IF EXISTS m6_guard_execution_event_append_only();
DROP FUNCTION IF EXISTS m6_guard_action_item();
DROP FUNCTION IF EXISTS m6_guard_debrief_entry();
DROP FUNCTION IF EXISTS m6_guard_execution_debrief();
DROP FUNCTION IF EXISTS m6_guard_assessment_evidence();
DROP FUNCTION IF EXISTS m6_guard_direct_assessment_content();
DROP FUNCTION IF EXISTS m6_guard_finalized_assessment();
DROP FUNCTION IF EXISTS m6_guard_published_scenario_version();
SQL);
    }
};
