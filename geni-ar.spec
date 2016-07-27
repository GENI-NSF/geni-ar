Name:           geni-ar
Version:        1.9
Release:        1%{?dist}
Summary:        GENI account requests
BuildArch:      noarch
License:        GENI Public License
URL:            https://github.com/GENI-NSF/geni-ar
Source:         %{name}-%{version}.tar.gz
Group:          Applications/Internet
BuildRequires:  texinfo
Requires:       httpd, postgresql

%description

A web-based UI for requesting and approving accounts on a Shibboleth
identity provider that uses LDAP for authentication and attributes.

%prep
%setup -q

%build
%configure
make %{?_smp_mflags}

%install
rm -rf $RPM_BUILD_ROOT
%make_install
rm -f $RPM_BUILD_ROOT%{_infodir}/dir

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root)
%doc %{_infodir}/geni-ar.info.gz
%doc %{_mandir}/man1/geni-ar-expired-accounts.1.gz
%{_bindir}/geni-add-log
%{_bindir}/geni-convert-logs
%{_bindir}/geni-ar-expired-accounts
%{_datadir}/%{name}/apache2.conf
%{_datadir}/%{name}/apache-2.4.conf
%{_datadir}/%{name}/db/postgresql/schema.sql
%{_datadir}/%{name}/db/postgresql/update-1.sql
%{_datadir}/%{name}/db/postgresql/update-2.sql
%{_datadir}/%{name}/db/postgresql/update-3.sql
%{_datadir}/%{name}/etc/confirm-email.txt
%{_datadir}/%{name}/etc/leads-email.txt
%{_datadir}/%{name}/etc/notification-email.txt
%{_datadir}/%{name}/etc/settings.php
%{_datadir}/%{name}/etc/tutorial-email.txt
%{_datadir}/%{name}/etc/user-email.txt
%{_datadir}/%{name}/ldap/backend.gpolab.bbn.com.ldif
%{_datadir}/%{name}/ldap/eduperson.ldif
%{_datadir}/%{name}/ldap/frontend.gpolab.bbn.com.ldif
%{_datadir}/%{name}/ldap/users.gpolab.bbn.com.ldif
%{_datadir}/%{name}/lib/php/ar_constants.php
%{_datadir}/%{name}/lib/php/db_utils.php
%{_datadir}/%{name}/lib/php/email_utils.php
%{_datadir}/%{name}/lib/php/header.php
%{_datadir}/%{name}/lib/php/ldap_utils.php
%{_datadir}/%{name}/lib/php/log_actions.php
%{_datadir}/%{name}/lib/php/response_format.php
%{_datadir}/%{name}/lib/php/ssha.php
%{_datadir}/%{name}/protected/acct_actions.php
%{_datadir}/%{name}/protected/action_log.php
%{_datadir}/%{name}/protected/add_note.php
%{_datadir}/%{name}/protected/approve.php
%{_datadir}/%{name}/protected/cards.js
%{_datadir}/%{name}/protected/display_accounts.php
%{_datadir}/%{name}/protected/display_requests.php
%{_datadir}/%{name}/protected/fix_accounts.html
%{_datadir}/%{name}/protected/fix_actions.php
%{_datadir}/%{name}/protected/geni-ar.css
%{_datadir}/%{name}/protected/index.html
%{_datadir}/%{name}/protected/ldap.php
%{_datadir}/%{name}/protected/request_actions.php
%{_datadir}/%{name}/protected/send_email.php
%{_datadir}/%{name}/protected/tutorial_actions.php
%{_datadir}/%{name}/protected/tutorial_confirmation.php
%{_datadir}/%{name}/protected/tutorial_requests.php
%{_datadir}/%{name}/protected/whitelist.php
%{_datadir}/%{name}/shibboleth/attribute-filter-geni.xml
%{_datadir}/%{name}/shibboleth/attribute-resolver-geni.xml
%{_datadir}/%{name}/www/confirmemail.php
%{_datadir}/%{name}/www/favicon.ico
%{_datadir}/%{name}/www/geni.png
%{_datadir}/%{name}/www/handlerequest.php
%{_datadir}/%{name}/www/kmtool.css
%{_datadir}/%{name}/www/newpasswd.php
%{_datadir}/%{name}/www/pwchange.php
%{_datadir}/%{name}/www/request.html
%{_datadir}/%{name}/www/reset.html
%{_datadir}/%{name}/www/usernamerecover.html
%{_datadir}/%{name}/www/usernamerecover.php

%changelog
* Mon Mar 21 2016 Tom Mitchell <tmitchell@bbn.com> - 1.7-1%{?dist}
- Initial RPM packaging
