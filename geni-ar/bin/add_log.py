import os
import sys
import subprocess
import time
import optparse

class ActionLogger:

    def add_entry(self,uid,action_performed,comment):
        psql_cmd = ['psql', '-U', 'accreq', '-h', 'localhost', 'accreq']
        psql = subprocess.Popen(psql_cmd, stdin=subprocess.PIPE,
                                stdout=subprocess.PIPE,
                                stderr=subprocess.PIPE)

        performer = os.getlogin()
        sql = ('INSERT into idp_account_actions ' 
               + "(uid,action_performed,comment,performer) values ('%s','%s','%s','%s');\n")

        psql.stdin.write(sql %(uid,action_performed,comment,performer))
            

if __name__ == "__main__":
    parser = optparse.OptionParser()
    parser.add_option("-u","--user", dest="uid")
    parser.add_option("-a","--action", dest="action_performed")
    parser.add_option("-c","--comment", dest="comment", default="")

    (opts,args) = parser.parse_args()
    if (not opts.uid or not opts.action_performed):
        print "username and action required. See 'python add_log.py -h'"
        exit()
    
    logger = ActionLogger()
    logger.add_entry(opts.uid,opts.action_performed,opts.comment)
                              
