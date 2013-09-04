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

apache2.conf: Makefile
	rm -f $@ $@.tmp
	srcdir=''; \
	  test -f ./$@.in || srcdir=$(srcdir)/; \
	  $(edit) $${srcdir}$@.in >$@.tmp
	chmod a-w $@.tmp
	mv $@.tmp $@

apache2.conf: $(srcdir)/apache2.conf.in

EXTRA_DIST = apache2.conf.in
CLEANFILES = apache2.conf

nobase_dist_pkgdata_DATA = \
	apache2.conf \
	db/postgresql/schema.sql \
	etc/leads-email.txt \
	etc/notification-email.txt \
	etc/settings.php \
	etc/user-email.txt \
	lib/php/ar_constants.php \
	lib/php/db_utils.php \
	lib/php/geni_syslog.php \
	lib/php/ldap_utils.php \
	lib/php/response_format.php \
	protected/acct_actions.php \
	protected/action_log.php \
	protected/display_accounts.php \
	protected/display_requests.php \
	protected/index.html \
	protected/ldap.php \
	protected/request_actions.php \
	protected/send_email.php \
	www/handlerequest.php \
	www/geni.png \
	www/kmtool.css \
	www/request.html

dist_bin_SCRIPTS = \
	bin/geni-add-log \
	bin/geni-convert-logs

pkgsysconfdir = $(sysconfdir)/$(PACKAGE_NAME)

install-data-local:
	$(MKDIR_P) $(DESTDIR)$(pkgsysconfdir)
