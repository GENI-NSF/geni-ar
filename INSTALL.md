# Install GENI Account Request System

# Install dependencies

* Install LDAP (see INSTALL-LDAP.md)
* Install Shibboleth (see INSTALL-shibboleth.md)
* Install PostgreSQL

# Load the database schema.

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

2. Edit /etc/geni-ar/settings.php to reflect the local configuration

    What needs to be edited?

# Add users for the administrative area

Create a password file for the protected area using `/usr/bin/htpasswd`.

To create the first entry, use the `-c` flag:

```
htpasswd -c /etc/geni-ar/passwords alice
```

To add another entry:

```
htpasswd /etc/geni-ar/passwords bob
```

See the htpasswd man page for more info.
