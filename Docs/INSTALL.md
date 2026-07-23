# Install Linkuup Backend

## Preamble

This document was written for High-Office to aid in the installation of LinkUUp. If something is unclear feel free to contact me under +49 176 59 59 88 44 - christian@busch-peine.de


## Download

First we need to download the gdo6 core and clone the dependant modules to install them later. Please note that there is more documentation found in the gdo6 core docs.

    cd /wwwroot # goto www root
    git clone --recursive https://github.com/gizmore/gdo6 # clone core
    
    cd gdo6/GDO # goto modules folder
    # clone dependant modules
    git clone --recursive https://github.com/gizmore/gdo6-session-db Session
    git clone --recursive https://github.com/gizmore/gdo6-jquery JQuery
    git clone --recursive https://github.com/gizmore/gdo6-address Address
    git clone --recursive https://github.com/gizmore/gdo6-bootstrap Bootstrap
    git clone --recursive https://github.com/gizmore/gdo6-bootstrap-theme BootstrapTheme
    git clone --recursive https://github.com/gizmore/gdo6-opentimes OpenTimes
    git clone --recursive https://github.com/gizmore/gdo6-vote Vote
    git clone --recursive https://github.com/gizmore/gdo6-comment Comment
    git clone --recursive https://github.com/gizmore/gdo6-friends Friends
    git clone --recursive https://github.com/gizmore/gdo6-register Register
    git clone --recursive https://github.com/gizmore/gdo6-websocket Websocket
    git clone --recursive https://github.com/gizmore/gdo6-maps Maps
    git clone --recursive https://github.com/gizmore/gdo6-facebook Facebook
    git clone --recursive https://github.com/gizmore/gdo6-instagram Instagram
    git clone --recursive https://github.com/gizmore/gdo6-captcha Captcha
    git clone --recursive https://github.com/gizmore/gdo6-qrcode QRCode
    git clone --recursive https://github.com/gizmore/gdo6-jpgraph JPGraph
    git clone --recursive https://github.com/gizmore/gdo6-contact Contact
    git clone --recursive https://github.com/gizmore/gdo6-admin Admin
    git clone --recursive https://github.com/gizmore/gdo6-gallery Gallery
    git clone --recursive https://github.com/gizmore/gdo6-avatar Avatar
    git clone --recursive https://github.com/gizmore/gdo6-recovery Recovery
    git clone --recursive https://github.com/gizmore/gdo6-account Account
    git clone --recursive https://github.com/gizmore/gdo6-login Login
    git clone --recursive https://github.com/gizmore/gdo6-profile Profile
    ### LUP is private. we need your ssh key.
    git clone --recursive ssh://git@service.busch-peine.de:19198/gdo6-linkuup LinkUUp

Now we configure the main protected/config.php file. This is either done via the webserver using install/wizard.php or via the gdoadm.sh cli utility.

    cd /wwwroot/gdo6
    ./gdoadm.sh configure
    nano protected/config.php # configure manually

Make sure you have this config set:

    define('GDO_THEMES', env('GDO_THEMES', 'lup,bootstrap,default')); # theme chain
    
    define('GDO_IPC', env('GDO_IPC', 'db')); # using db for ipc. the value 1 is experimental and uses real ipc.
    
    
There are also more fields you have to configure accordingly, like the database, session cookies, etc. One pitfal is to forget to configure the sess domain or server domain.

Lets test the config

    ./gdoadm.sh test

### Installation of the modules

    ./gdoadm.sh install LinkUUp
    
This should install all the required modules into the database.

### Javascript assets.

The gdo6 post install process:

    ./gdo_post_install.sh # Run post install scripts
    ./gdo_yarn.sh # install yarn dependencies
    ./gdo_bower.sh # install bower dependencies
    

### An admin is required

LinkUUp gdo6 backend requires a user to login to proceed.

    ./gdoadm.sh admin username password email
    
### Configuration of the modules

You should have an almost working site now, but some modules need configuration. For example Facebook App tokens etc.

Login as your admin user and goto the admin panel.

Here is a brief list of what is important to configure

 - Module_Websocket: The websocket address and handler. The handler is /gdo6/GDO/LinkUUp/LUP_Websocket.php. If you want the secure wss protocol, see docs/nginx
 
# Launch the websocket server

    cd gdo6
    GDO/Websocket/bin/start_websocket_server.sh
 
   
# Install LUP app

    cd www
    git clone ssh://git@service.busch-peine.de:19198/linkuup-app
    npm install # load bower dependencies
    

## Configure LUP app
    
    cd config
    cp lup-app-config.example.js lup-app-config.js
    nano lup-app-config.js
    cp lup-php-config.example.php lup-php-config.php
    nano lup-php-config.php
    

## Build LUP app

A minified production version of the app can be built with
    
    php build.php
    
During development, simply use the index_debug.php app
