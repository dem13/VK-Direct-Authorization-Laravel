# VK-Direct-Authorization-Laravel
Vkontakte Direct Authorization

## Description
This is [VK(Vkontakte)](https://vk.com/feed) Direct Authorization implemented with [Laravel](https://laravel.com).

## Set Up

To set up this project you need to:
 * Create `.env` file and set up your database
 * Execute following commands:
  ```
  composer install
  php artisan key:generate
  php artisan migrate
  php artisan serve
  ```
 ## Usage
  
 Go to http://localhost:8000/login
 
 Enter your username and password from VK (**It's completely safe. You can make sure by yourself!**) and if your credentials are correct you will see your VK conversations.