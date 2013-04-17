# Directories
WEBROOT=$(DESTDIR)/var/www/account

# Programs
INSTALL ?= /usr/bin/install

.PHONY: build install

build:
	echo nothing to do for build

# Install target for idp-tools-ldap-ui
install:
	$(INSTALL) -d $(WEBROOT)
	$(INSTALL) -m 0644 www/request.html $(WEBROOT)
	$(INSTALL) -m 0644 www/handlerequest.php $(WEBROOT)

