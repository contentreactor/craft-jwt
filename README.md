# contentreactor-jwt

JWT Auth plugin for Craft CMS

## Usage

Make POST request to retrive your token to use all the API endpoints.
``` bash
POST /api/auth
# body params
{
    "loginName": "admin@contentreactor.com", # You can use username or email
    "password": "admin"
}
```
You need to have ContentReactor API user group and ContentReactor JWT (Use API with JWT auth) permission added to your profile. 

## Requirements

This plugin requires Craft CMS 4.7.0 or later, and PHP 8.0.2 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “contentreactor-jwt”. Then press “Install”.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require contentreactor/jwt

# tell Craft to install the plugin
./craft plugin/install contentreactor-jwt
```
