# Table of contents

* [What is it?](#what-is-it)
* [What does the app stack look like?](#what-does-the-app-stack-look-like)
* [How do you run it locally?](#how-do-you-run-it-locally)
    * [How do you stop it?](#how-do-you-stop-it)
    * [What ports are the containers mapped to?](#what-ports-are-the-containers-mapped-to)
* [Using the app](#using-the-app)
    * [How do I configure the app?](#how-do-i-configure-the-app)
    * [Authentication and Authorization](#authentication-and-authorization)
    * [How do I add content?](#how-do-i-add-content)
    * [What are the Post fields?](#what-are-the-post-fields)
    * [How do I publish?](#how-do-i-publish)
      * [Local publishing](#local-publishing)
      * [AWS S3 Publishing](#aws-s3-publishing)

# What is it?

A developer-oriented CMS meant to rapidly publish content to multiple sites.

# What does the app stack look like?

* Laravel 8 Backend
    * Site management
      * Determines which sites posts will be published to
    * Posts
      * Option between plain text, JSON, and HTML
* PostgreSQL database
    * Stores posts and sites

# How do you run it locally?

Running it requires docker compose.

From the repository root, run:

```
docker-compose build
docker-compose up -d
```

### How do you stop it?

```
docker-compose down --volume
```

### What ports are the containers mapped to?

* `8001` - Laravel backend
* `54321` - PostgreSQL

# Using the app

The front end will be available at: http://localhost:8001.

## How do I configure the app?
There is a file at the root of the project with environment variables. Running the project via docker-compose will create this file automatically.
It will automatically override any matching system environment variables.

The file is: `.env`.

## Authentication and Authorization

Users are not required for this demo, but you may restrict access by IP.
In order to restrict access by IP, set the environment variable `TRUSTED_IPS` with a CSV of IPs to allow.

Example:
```
export TRUSTED_IPS=192.168.0.1,192.168.0.2
```

## How do I add content?

In order to create a Post, you must first create a Site. Go to the site section and create a site with a domain name and identifying title.

After creating a Site, you can then create a Post for that Site.

## What are the Post fields?

```
Site - The site that the post belongs to
Title - The title of the post
Content - The body of the post
Human Readable URL - The URL that the post will be available at on the Site
```

## How do I publish?

By default, the application will publish content to the local disk

### Local publishing

Simply click the `Publish` button on the site or run the command:

`php /var/www/html/pay_less_cms/artisan posts:publish`

The files will be available at:

`/var/www/html/pay_less_cms/storage/app/published/`

### AWS S3 Publishing
You can alternatively publish to AWS S3. First, indicate that S3 will be the publish target by setting the environment variable:
```
FILESYSTEM_PUBLISHER_DRIVER=s3
```

In order to authenticate to S3, you must set the following environment variables.

```
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
```
