FROM postgres:16
COPY pgsql_initial.sql /docker-entrypoint-initdb.d/
