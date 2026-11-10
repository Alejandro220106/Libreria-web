-- Creates the engine's roles and databases.
-- Run once per machine as the PostgreSQL superuser, passing the passwords as variables
-- so they are never written in the repository:
--   psql -U postgres -v owner_password='...' -v app_password='...' -f database/roles.sql

-- Owns the schema and runs the migrations.
CREATE ROLE libreria_owner LOGIN PASSWORD :'owner_password';
-- Serves the requests: reads and writes data, cannot change the schema.
CREATE ROLE libreria_app LOGIN PASSWORD :'app_password';

CREATE DATABASE libreria_motor OWNER libreria_owner;
CREATE DATABASE libreria_motor_testing OWNER libreria_owner;

REVOKE ALL ON DATABASE libreria_motor, libreria_motor_testing FROM PUBLIC;
GRANT CONNECT ON DATABASE libreria_motor, libreria_motor_testing TO libreria_app;
