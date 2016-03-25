## Process this file with automake to produce Makefile.in

#----------------------------------------------------------------------
# Handle file replacements. Files can contain configure variables, and
# will be processed by this sed script. Process files via the
# Makefile, rather than configure, so that the user can make
# adjustments to paths at Make time. This is the preferred method, see
# GNU docs for more info.
# ----------------------------------------------------------------------
edit = sed \
	-e 's|@bindir[@]|$(bindir)|g' \
	-e 's|@pkgdatadir[@]|$(pkgdatadir)|g' \
	-e 's|@pkgsysconfdir[@]|$(pkgsysconfdir)|g' \
	-e 's|@prefix[@]|$(prefix)|g'

apache2.conf apache-2.4.conf lib/php/ar_constants.php lib/php/email_utils.php: Makefile
	rm -f $@ $@.tmp
	srcdir=''; \
	  test -f ./$@.in || srcdir=$(srcdir)/; \
	  $(edit) $${srcdir}$@.in >$@.tmp
	chmod a-w $@.tmp
	mv $@.tmp $@

apache2.conf: $(srcdir)/apache2.conf.in
apache-2.4.conf: $(srcdir)/apache-2.4.conf.in
lib/php/ar_constants.php: $(srcdir)/lib/php/ar_constants.php.in
lib/php/email_utils.php: $(srcdir)/lib/php/email_utils.php.in

EXTRA_DIST = \
	apache2.conf.in \
	apache-2.4.conf.in \
	lib/php/ar_constants.php.in \
	lib/php/email_utils.php.in

CLEANFILES = \
	apache2.conf \
	apache-2.4.conf \
	lib/php/ar_constants.php \
	lib/php/email_utils.php

nobase_dist_pkgdata_DATA = \
	apache2.conf \
	apache-2.4.conf \
	db/postgresql/schema.sql \
	db/postgresql/update-1.sql \
	etc/confirm-email.txt \
	etc/leads-email.txt \
	etc/notification-email.txt \
	etc/settings.php \
	etc/user-email.txt \
	etc/tutorial-email.txt \
	ldap/backend.gpolab.bbn.com.ldif \
	ldap/eduperson.ldif \
	ldap/frontend.gpolab.bbn.com.ldif \
	ldap/users.gpolab.bbn.com.ldif \
	lib/php/ar_constants.php \
	lib/php/db_utils.php \
	lib/php/email_utils.php \
	lib/php/header.php \
	lib/php/ldap_utils.php \
	lib/php/log_actions.php \
	lib/php/response_format.php \
	lib/php/ssha.php \
	protected/acct_actions.php \
	protected/action_log.php \
	protected/add_note.php \
	protected/approve.php \
	protected/cards.js \
	protected/display_accounts.php \
	protected/display_requests.php \
	protected/fix_accounts.html \
	protected/fix_actions.php \
	protected/geni-ar.css \
	protected/index.html \
	protected/ldap.php \
	protected/request_actions.php \
	protected/send_email.php \
	protected/tutorial_requests.php \
	protected/tutorial_actions.php \
	protected/tutorial_confirmation.php \
	protected/whitelist.php \
	shibboleth/attribute-filter-geni.xml \
	shibboleth/attribute-resolver-geni.xml \
	www/confirmemail.php \
	www/handlerequest.php \
	www/geni.png \
	www/favicon.ico \
	www/kmtool.css \
	www/newpasswd.php \
	www/pwchange.php \
	www/request.html \
	www/reset.html \
	www/usernamerecover.html \
	www/usernamerecover.php

dist_bin_SCRIPTS = \
	bin/geni-add-log \
	bin/geni-convert-logs

pkgsysconfdir = $(sysconfdir)/$(PACKAGE_NAME)

install-data-local:
	$(MKDIR_P) $(DESTDIR)$(pkgsysconfdir)
