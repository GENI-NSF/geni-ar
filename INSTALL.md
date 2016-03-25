# Install GENI Account Request System

# Install dependencies

* Install LDAP (see INSTALL-LDAP.md)
* Install Shibboleth (see INSTALL-shibboleth.md)
* Install PostgreSQL (see INSTALL-postgresql.md)

# Install packages

_These should be handled by a package install_

_Note: run these two commands separately, the second depends on the
first._

```
sudo yum install epel-release
sudo yum install php php-pear-MDB2-Driver-pgsql
```

# Install geni-ar system

If geni-ar is not available from a package, install manually:

```
cd /path/to/geni-ar
./autogen.sh
./configure --prefix=/usr --sysconfdir=/etc --bindir=/usr/local/bin --sbindir=/usr/local/sbin
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

    The following settings _must_ be changed to appropriate values:

    | Setting | Description |
    | ------- | ----------- |
    | $db_dsn | Database connection string, see documentation in file for format |
    | $idp_approval_email | Destination email address for new request notification |
    | $idp_leads_email | Destination email address for policy board |
    | $idp_audit_email | Destination email address for audit/log messages |
    | $acct_manager_url | URL of the management home page, based on apache configuration |
    | $base_dn | Base LDAP distinguished name for searches |
    | $user_dn | Base user DN to append to new user IDs |
    | $ldaprdn | Administrative LDAP user for adding/modifying LDAP entries |
    | $ldappass | Password for Administrative LDAP user |


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

Make geni-ar available via the web server. Edit `/etc/httpd/conf.d/ssl.conf`
to add the following line at the end of and inside the VirtualHost block.
This statement should just after the lines that were added for the
Shibboleth installation described in INSTALL-shibboleth.md.

```
Include /usr/share/geni-ar/apache-2.4.conf
```

# Test

_TBD_
