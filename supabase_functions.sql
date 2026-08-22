-- Supabase Helper Functions for CollabIQ PHP PDO Bridge

CREATE OR REPLACE FUNCTION run_query(query_text text)
RETURNS json
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
    result json;
BEGIN
    EXECUTE 'SELECT json_agg(t) FROM (' || query_text || ') t' INTO result;
    RETURN COALESCE(result, '[]'::json);
EXCEPTION WHEN OTHERS THEN
    RETURN json_build_object('error', SQLERRM);
END;
$$;

CREATE OR REPLACE FUNCTION run_cmd(query_text text)
RETURNS json
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
BEGIN
    EXECUTE query_text;
    RETURN json_build_object('success', true);
EXCEPTION WHEN OTHERS THEN
    RETURN json_build_object('error', SQLERRM);
END;
$$;

CREATE OR REPLACE FUNCTION insert_returning_id(query_text text)
RETURNS json
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
    new_id int;
BEGIN
    EXECUTE query_text || ' RETURNING id' INTO new_id;
    RETURN json_build_object('id', new_id);
EXCEPTION WHEN OTHERS THEN
    RETURN json_build_object('error', SQLERRM);
END;
$$;

GRANT EXECUTE ON FUNCTION run_query(text) TO anon, authenticated, service_role;
GRANT EXECUTE ON FUNCTION run_cmd(text) TO anon, authenticated, service_role;
GRANT EXECUTE ON FUNCTION insert_returning_id(text) TO anon, authenticated, service_role;
