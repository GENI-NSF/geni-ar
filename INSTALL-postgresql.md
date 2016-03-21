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

Configure PostgreSQL to start automatically when the host reboots by
executing these commands:

```
sudo /usr/bin/systemctl enable postgresql
sudo /usr/bin/systemctl start postgresql
```

# Set postgres user password

Set the database superuser password using the following command. Be sure
to replace `SOME_PASSWORD` with a password of your choice. Remember or
record this password, you may need it in the future.

```
sudo -u postgres /usr/bin/psql \
    -c "alter user postgres with password 'SOME_PASSWORD'"
```

# Create geni-ar user

_Remember the user name and password, you will need them
for `/etc/geni-ar/settings.php`._

Replace `$DB_USER` and `$DB_PASSWORD` below with values of your choosing.
A good value for `$DB_USER` is "accreq" (short for ACCount REQuest). Any
valid username is fine.

```
sudo -u postgres createuser -S -D -R $DB_USER
sudo -u postgres psql -c "alter user $DB_USER with password '$DB_PASSWORD'"
```

# Create geni-ar database

_Remember the database name, you will need it for `/etc/geni-ar/settings.php`._

Replace `$DB_DATABASE` below with a value of your choosing.
A good value for `$DB_DATABASE` is "accreq" (short for ACCount REQuest). Any
valid database name is fine.

```
sudo -u postgres createdb $DB_DATABASE
```

# Configure database access via password login

Edit the file `/var/lib/pgsql/data/pg_hba.conf` to replace "ident"
authentication with "md5" authentication.

Change the two lines ending with "ident":

```
host    all             all             127.0.0.1/32            ident
host    all             all             ::1/128                 ident
```

by replacing "ident" with "md5" so that they look like this:

```
host    all             all             127.0.0.1/32            md5
host    all             all             ::1/128                 md5
```

Then restart the database server

```
sudo /usr/bin/systemctl restart postgresql
```

# Test database connection

```
psql -U $DB_USER -h $DB_HOST $DB_DATABASE
```

# [Optional] Create a `.pgpass` file

_This step is optional._

To make it easier to log in to the database manually it is possible to
store the password(s) in a file so that they don't need to be typed
each time. The file is `$HOME/.pgpass`. Each line of `$HOME/.pgpass`
has the following general form:

```
Host:*:Database:User:Password
```

For instance, to access the database "example" on host "localhost" with
username "scott" and password "tiger" the entry would look like this:

```
localhost:*:example:scott:tiger
```

This file must have restrictive permissions to protect the entries.
To set appropriate permissions:

```
chmod 0600 $HOME/.pgpass
```
