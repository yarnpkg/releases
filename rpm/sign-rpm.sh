#!/usr/bin/expect -f
# Hacks around the fact that rpmsign always asks for passphrase even when using gpg-agent
# http://aaronhawley.livejournal.com/10615.html
spawn rpmsign --addsign {*}$argv
expect -exact "Enter pass phrase: "
send -- "blank\r"
expect eof
