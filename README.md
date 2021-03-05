# Basic Two Factor Authentication System 

Two factor authentication system using only symfony security component. As two-factor authentication method,
it uses authentication code via email.

## Setup

To get it working, follow these steps:

Make sure you have [Composer installed](https://getcomposer.org/download/)
and then run:

```
composer install
```

**Configure the the .env File**

Make sure you have an `.env` file (you should).
If you don't, copy `.env.dist` to create it.

Configuration is required for `MAILER_DSN` and  `DATABASE_URL`.

**Setup the Database**

Again, make sure `.env` is setup for your computer. Then, create
the database & tables!

```
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

If you get an error that the database exists, that should
be ok. But if you have problems, completely drop the
database (`doctrine:database:drop --force`) and try again.

**Start the built-in web server**

You can use Nginx or Apache, but the built-in web server works
great:

```
php bin/console server:run
```

Now check out the site at `http://localhost:8000`

**Optional: Webpack Encore Assets**

This app uses Webpack Encore for the CSS, JS and image files. 
Built assets are already inside the
project. So... you don't need to do anything to get thing set up!

If you *do* want to build the Webpack Encore assets manually
make sure you have [yarn](https://yarnpkg.com/lang/en/)
installed and then run:

```
yarn install
yarn encore dev --watch
```

