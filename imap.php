<?php

imap_open("{smtp.bbtecno.net.br:993/imap/ssl/novalidate-cert}INBOX", 'abordagem.bb@bbtecno.net.br', 'bbts@123') or die('Cannot connect: ' . print_r(imap_errors(), true));
