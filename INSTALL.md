# Install GENI Account Request System

# Install dependencies

* Install LDAP (see INSTALL-LDAP.md)
* Install Shibboleth (see INSTALL-shibboleth.md)
* Install PostgreSQL (see INSTALL-postgresql.md)

# Install geni-ar system

If geni-ar is not available from a package, install manually:

```
cd /path/to/geni-ar
./autogen.sh
./configure
make
sudo make install
```

# Load the database schema

```
psql -U <USER> [-h <HOST>] <DBNAME> \
     -f /usr/share/geni-ar/db/postgresql/schema.sql
```

# Configure geni-ar

1. If `/etc/geni-ar/settings.php` does not exist copy it from
   `/usr/share/geni-ar/etc/settings.php`

    ```
    sudo cp /usr/share/geni-ar/etc/settings.php /etc/geni-ar/settings.php
    ```

2. Edit `/etc/geni-ar/settings.php` to reflect the local configuration

    What needs to be edited?

# Add account administrators

The administrative web pages are used to approve new accounts and perform other
account maintenance. Access to these web pages are restricted to users
in the password file. Each user who will have access to the administrative
pages must have an entry in the password file. The Apache program
`htpasswd` is used to manage the password file.

To create the first entry, use the `-c` flag:

```
sudo /usr/bin/htpasswd -c /etc/geni-ar/passwords alice
```

To add another entry:

```
sudo /usr/bin/htpasswd /etc/geni-ar/passwords bob
```

See the htpasswd man page for more info.

# Configure web server

_TBD_

# Test

_TBD_
