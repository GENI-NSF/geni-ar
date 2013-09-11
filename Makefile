# Programs
RSYNC = /usr/bin/rsync

.PHONY: default syncidp

default:
	@echo "Try: make syncidp"

syncshib:
	$(RSYNC) --delete --delete-excluded --exclude .git -aztv geni-ar macomb.gpolab.bbn.com: