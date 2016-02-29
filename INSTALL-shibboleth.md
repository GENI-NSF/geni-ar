The definitive source of information about Shibboleth Identity Provider
installation is on the
[Shibboleth Wiki](https://wiki.shibboleth.net/confluence/display/IDP30).
The instructions there will guide
you through all the installation and deployment options. This guide follows
a single path through the installation. It should not be considered a
substitute for reading the pages on the Shibboleth Wiki.

The Shibboleth Identity Provider installation documentation starts at
https://wiki.shibboleth.net/confluence/display/IDP30/Installation

As of early 2016 the
[recommended system requirements](https://wiki.shibboleth.net/confluence/display/IDP30/SystemRequirements)
for Shibboleth Identity Provider installation were Oracle JDK and the Jetty
servlet container. These installation instructions guide the installation
of Shibboleth Identity Provider to use these dependencies.

This guide installs the following software:

* Shibboleth Identity Provider 3
* Oracle JDK 8
* Jetty servlet container 9.3

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
to upgrade to a newer version in the future. These instructions assume you are
using the RPM.

Once downloaded, the JDK RPM can be installed as follows (replace RPM file
name as appropriate):

```bash
sudo yum localinstall jdk-8u74-linux-x64.rpm
```

## Install the Java Cryptography Extension (JCE) Unlimited Strength Jurisdiction Policy Files

See https://wiki.shibboleth.net/confluence/display/IDP30/Installation for
more information about this *required* component.

Download from
http://www.oracle.com/technetwork/java/javase/downloads/index.html,
towards the bottom. Once unpacked, installation instructions can be found
in a file named `README.txt`.

```bash
# Adjust JDK_HOME as needed
unzip jce_policy-8.zip
cd UnlimitedJCEPolicyJDK8
sudo cp local_policy.jar /usr/java/default/jre/lib/security/
sudo cp US_export_policy.jar /usr/java/default/jre/lib/security/
```


# Install Jetty

Refer to the
[Jetty 9.3 installation instructions](https://wiki.shibboleth.net/confluence/display/IDP30/Jetty93)
on the Shibboleth Wiki.


Download Jetty from the [Jetty website](http://download.eclipse.org/jetty/).
Be sure to download the lates version of the 9.3 series. At this writing
that was version 9.3.7.v20160115.

Unpack Jetty:

```bash
cd /opt
sudo tar zxf /path/to/jetty-distribution-9.3.7.v20160115.tar.gz
```

Create `/opt/jetty-base` for Jetty configuration:

```bash
sudo mkdir /opt/jetty-base
```

Set up environment variables to aid in later commands:

```bash
export JETTY_HOME=/opt/jetty-distribution-9.3.7.v20160115
export JETTY_BASE=/opt/jetty-base
```

Follow the
"[Required Configuration](https://wiki.shibboleth.net/confluence/display/IDP30/Jetty93#Jetty93-RequiredConfiguration)"
instructions. You will be following the thread that leaves the default ports
in place and uses a port forwarding approach (Apache) documented at the end
of that page.

Follow the
"[Recommended Configuration](https://wiki.shibboleth.net/confluence/display/IDP30/Jetty93#Jetty93-RecommendedConfiguration)"
instructions. This includes downloading the
[logback](http://logback.qos.ch/download.html)
and
[slf4j](http://www.slf4j.org/download.html)
libraries. If you have any doubt about where files should go in the JETTY_BASE
directory hierarchy, refer to the list of files at the top of the wiki page.

_**TODO: I am deferring the "Offloading TLS" section for now.
We need to handle offloading TLS to Apache in order to get the IdP
service running on port 443. We have had issues with some users who
are unable to connect to non-standard ports on our current IdP.
Some of the configuration is covered below in references to the
Jetty web site. Revisit this section later.**_

For Offloading TLS, use the example code on the Shibboleth wiki (bottom
of the Jetty page) and the "X-Forward-for Configuration" in the
[linked Jetty instructions](http://www.eclipse.org/jetty/documentation/current/configuring-connectors.html#d0e4447).

# Install Shibboleth Identity Provider

Download the latest Shibboleth
[Identity Provider](https://shibboleth.net/downloads/identity-provider/latest/)
software.

Unpack the distribution in `/opt`. This way it is easy to find if you
have to rebuild it later. Then build according to the instructions.
Here is an example:

```
# Adjust version as appropriate
IDP_VERSION=3.2.1

cd /opt
sudo tar zxf /path/to/shibboleth-identity-provider-${IDP_VERSION}.tar.gz
cd shibboleth-identity-provider-${IDP_VERSION}
sudo JAVA_HOME=/usr/java/default bin/install.sh
```

_**Document how to answer the questions asked by `build.sh`. This
requires advance planning. You need a host name, scope, and
some passwords. Be prepared to answer the questions!**_

Here is a sample installation where the install script prompts for
answers to a number of questions:

```bash
$ sudo JAVA_HOME=/usr/java/default bin/install.sh
Source (Distribution) Directory: [/opt/shibboleth-identity-provider-3.2.1]

Installation Directory: [/opt/shibboleth-idp]

Hostname: [aptvm070-1.apt.emulab.net]
idp.geni.net
SAML EntityID: [https://idp.geni.net/idp/shibboleth]

Attribute Scope: [apt.emulab.net]
gpolab.bbn.com
Backchannel PKCS12 Password: 
Re-enter password: 
Cookie Encryption Key Password: 
Re-enter password: 
Warning: /opt/shibboleth-idp/bin does not exist.
Warning: /opt/shibboleth-idp/dist does not exist.
Warning: /opt/shibboleth-idp/doc does not exist.
Warning: /opt/shibboleth-idp/system does not exist.
Warning: /opt/shibboleth-idp/webapp does not exist.
Generating Signing Key, CN = idp.geni.net URI = https://idp.geni.net/idp/shibboleth ...
...done
Creating Encryption Key, CN = idp.geni.net URI = https://idp.geni.net/idp/shibboleth ...
...done
Creating Backchannel keystore, CN = idp.geni.net URI = https://idp.geni.net/idp/shibboleth ...
...done
Creating cookie encryption key files...
...done
Rebuilding /opt/shibboleth-idp/war/idp.war ...
...done

BUILD SUCCESSFUL
Total time: 1 minute 0 seconds
```

| Value | Description |
| ----- | ----------- |
| Source (Distribution) Directory | Accept Default |
| Installation Directory          | Accept Default |
| Hostname | Choose a CNAME (a DNS alias) so the host can be moved later |
| SAML EntityID | Accept default |
| Attribute Scope | ??? |
| Backchannel PKCS12 Password | Not used for GENI |
| Cooke Encryption Key Password | Record it for later |

# Test a bit

1. Start Jetty

    ```bash
    sudo java -jar ../jetty-distribution-9.3.7.v20160115/start.jar jetty.base=$JETTY_BASE
    ```

1. Check the Jetty log file for errors

    ```bash
    tail -f $JETTY_BASE/logs/jetty.log
    ```

1. _**I had to set idp.home in $JETTY_BASE/start.ini to /opt/shibboleth-idp
   despite that file saying it wasn't necessary if I chose that location. Why?**_

1. If everything is working so far, this command will generate output
   summarizing the environment and information about the IdP's state:

    ```bash
    /opt/shibboleth-idp/bin/status.sh --url http://localhost:8080/idp
    ```

# Install and configure Apache

See https://wiki.centos.org/HowTos/Https for more information

1. If it's not already installed, install Apache:

    ```
    sudo yum install -y httpd mod_ssl openssl
    ```

2. If this is a production installation you need a real SSL certificate.
If this is a staging or development installation, you can generate a
self-signed SSL certificate as follows:

    ```
    # Generate private key 
    openssl genrsa -out ca.key 2048 
    
    # Generate CSR
    # Note: leave all fields blank except CN. Set CN to the fully qualified
    # domain name of the host, or a DNS CNAME for the host
    openssl req -new -key ca.key -out ca.csr
    
    # Generate Self Signed Key
    openssl x509 -req -days 365 -in ca.csr -signkey ca.key -out ca.crt
    
    # Copy the files to the correct locations
    sudo cp ca.crt /etc/pki/tls/certs
    sudo cp ca.key /etc/pki/tls/private/ca.key
    sudo cp ca.csr /etc/pki/tls/private/ca.csr
    ```

3. Edit `/etc/httpd/conf.d/ssl.conf` as instructed at
https://wiki.centos.org/HowTos/Https

4. Restart httpd:

    ```
    sudo /bin/systemctl restart httpd.service
    ```

5. Test by accessing a web page via https:

    ```
    https://your.host.name
    ```

    You should see an Apache test page. If you see anything else, stop, debug,
    and fix the issue until you can navigate to a test page at that URL.

# Configure TLS offloading

Configure Apache to dispatch requests to Jetty via proxy.

See:

* https://wiki.shibboleth.net/confluence/display/IDP30/Jetty93#Jetty93-OffloadingTLS
* http://www.eclipse.org/jetty/documentation/current/configuring-connectors.html#d0e4413

1. Configure Jetty to accept forwarded (proxied) requests

 1. Read the Jetty documentation, referenced above, about Proxy / Load Balancer
    connection configuration

 1. Copy the `jetty.xml` file to `JETTY_BASE`:

     ```
     cp $JETTY_HOME/etc/jetty.xml $JETTY_BASE/etc
     ```

 1. Uncomment the block that starts with the comment:

     "Uncomment to enable handling of X-Forwarded- style headers"

 1. Save the file

 1. Restart Jetty (command is earlier in this document)

2. Configure Apache to forward to Jetty

 1. Read the "Offloading TLS" section of the 
    [Shibboleth Jetty 9.3 documentation](https://wiki.shibboleth.net/confluence/display/IDP30/Jetty93#Jetty93-OffloadingTLS)

 1. Insert the documented block (3 lines) inside the `VirtualHost` declaration
    in `/etc/httpd/conf.d/ssl.conf`

3. Restart Apache

    ```
    sudo /bin/systemctl restart httpd.service
    ```

4. Test by navigating to https://your.host.name/idp

You should now see a Shibboleth page that requires customization and says
something like "Our Identity Provider". If you see errors or anything else,
stop, debug, and fix the issue(s) until you see the right page.

# Configure Jetty as a service

The last step is to cause Jetty to launch at boot time. The definitive
instructions can be found in
[the Jetty documentation](http://www.eclipse.org/jetty/documentation/current/startup-unix-service.html)

1. Copy `jetty.sh` to `/etc/init.d`

    ```
    sudo cp "${JETTY_HOME}"/bin/jetty.sh /etc/init.d/jetty
    ```

1. Create and populate `/etc/default/jetty`

    ```
    echo "JETTY_HOME=${JETTY_HOME}" > /tmp/jetty
    echo "JETTY_BASE=${JETTY_BASE}" > /tmp/jetty
    sudo mv /tmp/jetty /etc/default/jetty
    ```

1. Test

    ```
    sudo service jetty status
    ```

1. Start Jetty

    ```
    sudo service jetty start
    ```

1. Test via web browser at https://your.host.name/idp
