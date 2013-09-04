import os
import sys
import subprocess
import time
import optparse

class ArImporter:

    def import_csv(self, activity_log):
        psql_cmd = ['psql', '-U', 'accreq', '-h', 'localhost', 'accreq'];
        psql = subprocess.Popen(psql_cmd, stdin=subprocess.PIPE,
                                stdout=subprocess.PIPE,
                                stderr=subprocess.PIPE)

        infile =  open(activity_log, 'r')
        sql = ('INSERT into idp_account_actions' 
               + "(action_ts,action_performed,uid,performer) values ('%s','%s','%s','%s');\n")

        for line in infile:
            line = line.strip()
            if (line.startswith("Time")):
                continue;
            (time,action,uid,performer) = line.split(",");
            psql.stdin.write(sql %(time,action,uid,performer))


if __name__ == "__main__":
    parser = optparse.OptionParser()
    parser.add_option("-f","--file", dest="activity_log");

    (options, args) = parser.parse_args();

    converter = ArImporter()
    converter.import_csv(options.activity_log)
                              
