# Install LDAP

## Links

* [OpenLDAP web site](http://www.openldap.org)
* [CentOS 7 installation instructions at Server World]
  (http://www.server-world.info/en/note?os=CentOS_7&p=openldap)

```
yum -y install openldap-servers openldap-clients
```

```
cp /usr/share/openldap-servers/DB_CONFIG.example /var/lib/ldap/DB_CONFIG 
chown ldap. /var/lib/ldap/DB_CONFIG 
systemctl start slapd 
systemctl enable slapd 
```

Populate LDAP with schemas:

**Note: Update location of geni-ar/ldap files as necessary**

```
sudo /usr/bin/ldapadd -Y EXTERNAL -H ldapi:/// -f /etc/openldap/schema/cosine.ldif
sudo /usr/bin/ldapadd -Y EXTERNAL -H ldapi:/// -f /etc/openldap/schema/nis.ldif
sudo /usr/bin/ldapadd -Y EXTERNAL -H ldapi:/// -f /etc/openldap/schema/inetorgperson.ldif
sudo /usr/bin/ldapadd -Y EXTERNAL -H ldapi:/// -f /usr/share/geni-ar/ldap/eduperson.ldif
sudo /usr/bin/ldapadd -Y EXTERNAL -H ldapi:/// -f /usr/share/geni-ar/ldap/backend.gpolab.bbn.com.ldif

```

Populate the gpolab.bbn.com tree

**Note: Update location of geni-ar/ldap files as necessary**

```
sudo /usr/bin/ldapadd -x -D cn=admin,dc=gpolab,dc=bbn,dc=com -w shibidp \
    -f /usr/share/geni-ar/ldap/frontend.gpolab.bbn.com.ldif
# Create LDAP users
sudo /usr/bin/ldapadd -x -D cn=admin,dc=gpolab,dc=bbn,dc=com -w shibidp \
    -f /usr/share/geni-ar/ldap/users.gpolab.bbn.com.ldif

```
