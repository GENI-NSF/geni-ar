The definitive source of information about Shibboleth Identity Provider
installation is on the Shibboleth Wiki. The instructions there will guide
you through all the installatino and deployment options. This guide follows
a single path through the installation. It should not be considered a
substitute for reading the pages on the Shibboleth Wiki.

The Shibboleth Identity Provider installation documentation starts at
https://wiki.shibboleth.net/confluence/display/IDP30/Installation

This guide installs the following software:

* Shibboleth Identity Provider 3
* Oracle JDK 8
* Jetty servlet container 9.3

As of early 2016 the
[recommended system requirements](https://wiki.shibboleth.net/confluence/display/IDP30/SystemRequirements)
for Shibboleth Identity Provider installation were Oracle JDK and the Jetty
servlet container. These installation instructions guide the installation
of Shibboleth Identity Provider to use these dependencies.


# Reading

* Base instructions
 - https://wiki.shibboleth.net/confluence/display/IDP30/Installation
 - https://wiki.shibboleth.net/confluence/display/IDP30/Jetty93
 - https://wiki.shibboleth.net/confluence/display/IDP30/SecurityAndNetworking
 - http://www.eclipse.org/jetty/documentation/current/configuring-connectors.html#d0e4404
 - http://www.eclipse.org/jetty/documentation/current/startup-base-and-home.html


# Before installing

1. Read the
[Before You Begin](https://wiki.shibboleth.net/confluence/display/IDP30/Installation#Installation-BeforeYouBegin)
section of the Shibboleth Installation instructions.
2. Update your operating system. These instructions assume CentOS, so the update
command is:

    ```bash
    sudo yum update -y
    ```

# Install Oracle JDK

Start at http://www.oracle.com/technetwork/java/javase/downloads/index.html

Install the latest version of the Java 8 JDK (not the JRE). Also search the
page for "Java Cryptography Extension (JCE) Unlimited Strength Jurisdiction
Policy Files", which is also required. The Java version changes often enough
that it is impractical to list a specific version here.

For installation on CentOS consider using the RPM. That should make it easier
to upgrade to a newer version in the future.

Version current as of 15-Feb-2016
JDK_URL=http://download.oracle.com/otn-pub/java/jdk/8u74-b02/jdk-8u74-linux-x64.rpm
wget --no-cookies --no-check-certificate \
     --header "Cookie:oraclelicense=accept-securebackup-cookie" ${JDK_URL}

## Install JDK
sudo yum localinstall jdk-8u74-linux-x64.rpm

## Install the Java Cryptography Extension (JCE) Unlimited Strength Jurisdiction Policy Files
See https://wiki.shibboleth.net/confluence/display/IDP30/Installation

Download from http://www.oracle.com/technetwork/java/javase/downloads/index.html, towards the bottom

```
unzip jce_policy-8.zip
cd UnlimitedJCEPolicyJDK8
sudo cp local_policy.jar /usr/java/jdk1.8.0_74/jre/lib/security/
sudo cp US_export_policy.jar /usr/java/jdk1.8.0_74/jre/lib/security/
```


# Install Jetty

Refer to the
[Jetty 9.3 installation instructions](https://wiki.shibboleth.net/confluence/display/IDP30/Jetty93)
on the Shibboleth Wiki.


Download Jetty from the [Jetty website](http://download.eclipse.org/jetty/).
Be sure to download the lates version of the 9.3 series. At this writing
that was version 9.3.7.v20160115.


# Install Shibboleth Identity Provider


# Download Shibboleth IdP
# Version current as of 15-Feb-2016
SHIB_URL=http://shibboleth.net/downloads/identity-provider/latest/shibboleth-identity-provider-3.2.1.tar.gz
wget ${SHIB_URL}

# Download Logback (needed for logging)
# Version current as of 15-Feb-2016
LOGBACK_URL=http://logback.qos.ch/dist/logback-1.1.5.tar.gz
wget ${LOGBACK_URL}

# Download SLF4J (needed for logging)
# Version current as of 15-Feb-2016
SLF4J_URL=http://www.slf4j.org/dist/slf4j-1.7.16.tar.gz
wget ${SLF4J_URL}




# Unpack Jetty
cd /opt
sudo tar zxf /path/to/jetty-distribution-9.3.7.v20160115.tar.gz

# Create jetty-base for Jetty configuration
sudo mkdir /opt/jetty-base

export JETTY_HOME=/opt/jetty-distribution-9.3.7.v20160115
export JETTY_BASE=/opt/jetty-base

# Configure jetty
sudo mkdir "${JETTY_BASE}"/start.d
sudo cp "${JETTY_HOME}"/demo-base/start.d/http.ini "${JETTY_BASE}"/start.d
sudo cp "${JETTY_HOME}"/demo-base/start.d/https.ini "${JETTY_BASE}"/start.d
sudo cp "${JETTY_HOME}"/demo-base/start.d/ssl.ini "${JETTY_BASE}"/start.d


# Unpack Shibboleth
cd /opt
sudo tar zxf /path/to/shibboleth-identity-provider-3.2.1.tar.gz
cd shibboleth-identity-provider-3.2.1

# Edit conf/idp.properties
Set idp.entityID to your entityID (for staging, try shib-idp1.geni.net)
Set idp.scope to geni.net

# Build the IdP
sudo JAVA_HOME=/usr bin/build.sh

# Configure Jetty
See https://wiki.shibboleth.net/confluence/display/IDP30/Jetty93


# Configuring mod_proxy for Jetty from Apache
http://wiki.eclipse.org/Jetty/Tutorial/Apache

# Configure Jetty as a service
http://www.itzgeek.com/how-tos/linux/centos-how-tos/install-jetty-web-server-on-centos-7-rhel-7.html



# Sample /etc/httpd/conf.d/idp.conf
```
# Base configuration from http://wiki.eclipse.org/Jetty/Tutorial/Apache


# Turn off support for true Proxy behaviour as we are acting as 
# a transparent proxy
ProxyRequests Off
 
# Turn off VIA header as we know where the requests are proxied
ProxyVia Off
 
# Turn on Host header preservation so that the servlet container
# can write links with the correct host and rewriting can be avoided.
ProxyPreserveHost On
 
 
# Set the permissions for the proxy
<Proxy *>
  AddDefaultCharset off
  Order deny,allow
  Allow from all
</Proxy>
 
# Turn on Proxy status reporting at /status
# This should be better protected than: Allow from all
ProxyStatus On
<Location /status>
  SetHandler server-status
  Order Deny,Allow
  Allow from all
</Location>

ProxyPass /idp http://localhost:8080/idp
```

# Install mod_ssl
sudo yum install -y mod_ssl

An SSL certificate is in /etc/pki/tls/certs/localhost.crt
The key is in /etc/pki/tls/localhost.key



Ansible
https://github.com/AAROC/DevOps/wiki/idp-ldap-playbook
https://www.digitalocean.com/community/tutorials/how-to-configure-apache-using-ansible-on-ubuntu-14-04
