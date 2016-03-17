# Overview

The GENI Account Request system requires a PostgreSQL database. This
database can live on the same host as the GENI Account Request system
or on a different host. If an existing PostgreSQL installation is availble
you can simply create a new database in that installation and configure
the GENI Account Request system to use it.

# Install PostgreSQL software

```
sudo yum install -y postgresql-server
```

# Initialize the database server

This is a one time operation

```
sudo /usr/bin/postgresql-setup initdb
```

# Configure to start automatically

```
sudo /usr/bin/systemctl enable postgresql
sudo /usr/bin/systemctl start postgresql
```

# To Do

Here's more from the `geni-ch/templates/install_postgresql.sh` script:

```
POSTGRESQL_DIR=/var/lib/pgsql/data
DB_HOST=localhost
DB_USER=portal
DB_DATABASE=portal
DB_ADMIN_PASSWORD=postgres
DB_PASSWORD=portal

sudo -u postgres /usr/bin/psql \
    -c "alter user postgres with password '$DB_ADMIN_PASSWORD'"

sudo sed -i -e "\$alisten_addresses='*'" $POSTGRESQL_DIR/postgresql.conf
sudo sed -i -e "s/^host/#host/g" $POSTGRESQL_DIR/pg_hba.conf
sudo sed -i -e "\$ahost all all 0.0.0.0/0 md5" $POSTGRESQL_DIR/pg_hba.conf
sudo sed -i -e "\$ahost all all ::1/128 md5" $POSTGRESQL_DIR/pg_hba.conf

sudo systemctl restart postgresql.service

sudo -u postgres createuser -S -D -R $DB_USER
sudo -u postgres psql -c "alter user $DB_USER with password '$DB_PASSWORD'"
sudo -u postgres createdb $DB_DATABASE
echo "$DB_HOST:*:$DB_DATABASE:$DB_USER:$DB_PASSWORD"  > ~/.pgpass
chmod 0600 ~/.pgpass
touch ~/.psql_history
PSQL="psql -U $DB_USER -h $DB_HOST $DB_DATABASE"
```
