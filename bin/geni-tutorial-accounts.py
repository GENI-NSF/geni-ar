#! /usr/bin/env python

import os
import sys
import subprocess
import time
import optparse
import hashlib
import base64
import uuid

class TutorialAcctCreator:

    def add_request(self,num,uprefix,phone,pwprefix,desc):
        psql_cmd = ['psql', '-U', 'accreq', '-h', 'localhost', 'accreq']
        psql = subprocess.Popen(psql_cmd, stdin=subprocess.PIPE,
                                stdout=subprocess.PIPE,
                                stderr=subprocess.PIPE)

        performer = os.getlogin()
        
        firstname = "User"
        lastname = "Account%s" %num
        uname = "%s%s" %(uprefix,num)
        org = "Tutorial"
        title = "User"
        email = "%s@gpolab.bbn.com" %(uname)

        password = "%s%s" %(pwprefix,num)
        salt     = base64.urlsafe_b64encode(uuid.uuid4().bytes)
        t_sha = hashlib.sha512()
        t_sha.update(password+salt)
        pw_hash =  base64.urlsafe_b64encode(t_sha.digest())
        sql = ('INSERT into idp_account_request ' 
               + "(first_name,last_name,email,username_requested,phone,password_hash,organization,title,reason)"
               + " values ('%s','%s','%s','%s', '%s','%s','%s','%s', '%s');\n")

        fullsql = sql %(firstname,lastname,email,uname,phone,pw_hash,org,title,desc)
        print "%s" %fullsql

        psql.stdin.write(fullsql)
            

if __name__ == "__main__":
    parser = optparse.OptionParser()
    parser.add_option("-u","--userprefix", dest="user_prefix")
    parser.add_option("-t","--telephone", dest="phone")
    parser.add_option("-p","--pwprefix", dest="pw_prefix")
    parser.add_option("-n","--num", dest="numaccts")
    parser.add_option("-d","--description", dest="desc")

    (opts,args) = parser.parse_args()
#    if len(opts) != 6:
#        print "Incorrect usage. For usage enter: geni-tutorial-accounts -h"
#        exit()

    numaccts = int(opts.numaccts)
    tutorial = TutorialAcctCreator()
    for num in range(1,numaccts+1):
        unum = str(num)
        if len(unum) == 1:
            unum = "0%s" %unum
        tutorial.add_request(unum, opts.user_prefix,opts.phone,opts.pw_prefix,opts.desc)
                              
