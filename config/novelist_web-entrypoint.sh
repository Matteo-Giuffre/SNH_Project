#!/bin/bash
cp /run/secrets/https_cert /etc/apache2/ssl/server-cert.pem
cp /run/secrets/https_key /etc/apache2/ssl/server-key.pem

chown www-data:www-data /etc/apache2/ssl/*
chmod 644 /etc/apache2/ssl/*

exec apache2-foreground