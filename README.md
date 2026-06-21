# webpopulous-saas-listing
TSW custom Listing makes a post type file available for using in WebPopulous theme as a custom post type for posting listings to the theme.

## htaccess

```
RewriteEngine On
RewriteBase /

# 1. HARD REDIRECT: Catch external requests to query strings and point them to clean URLs
RewriteCond %{THE_REQUEST} \s/\?listing=([a-zA-Z0-9_-]+)\s [NC]
RewriteRule ^ /listings/%1/? [R=301,L]

# 2. INTERNAL PASS: Silently route incoming clean URLs to the backend engine
RewriteRule ^listings/([a-zA-Z0-9_-]+)/?$ index.php?listing=$1 [QSA,L]

<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{HTTP_USER_AGENT} ^WordPress [NC,OR]
RewriteCond %{HTTP_USER_AGENT} ^Jetpack [NC]
RewriteRule .* - [F,L]
</IfModule>

# Protect xmlrpc.php
<IfModule mod_alias.c>
RedirectMatch 403 (?i)/xmlrpc\.php
</IfModule>
```

## Other notes
