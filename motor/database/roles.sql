-- Creates the engine's databases and the role that owns them.
-- Run once per machine as the PostgreSQL superuser, passing the password as a variable
-- so it is never written in the repository:
--   psql -U postgres -v owner_password='...' -f database/roles.sql

CREATE ROLE libreria_owner LOGIN PASSWORD :'owner_password';

CREATE DATABASE libreria_motor OWNER libreria_owner;
CREATE DATABASE libreria_motor_testing OWNER libreria_owner;

REVOKE ALL ON DATABASE libreria_motor, libreria_motor_testing FROM PUBLIC;
