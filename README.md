# AutoGit: Symfony Example

This repository shows you step by step how you can deploy a Symfony app to Combell
hosting using the AutoGit feature.

* [Introduction](#introduction)
* [Create project](#create-project)
* [Git setup](#git-setup)
* [.autogit.yml](#autogityml)
* [Document root](#document-root)
* [Deployment hooks](#deployment-hooks)
    * [Composer](#composer)
    * [Shared files and folders](#shared-files-and-folders)
* [Deployment](#deployment)

## Introduction

All software projects benefit from a system like Git. Git is used to
[track changes of code over time](https://git-scm.com/book/en/v2/Getting-Started-About-Version-Control).
You keep track of changes by [performing commits](https://git-scm.com/book/en/v2/Git-Basics-Recording-Changes-to-the-Repository)
and some commits contain new features or bugfixes.

Since your Git repository now contains new versions of your project after commits, you can use it to
[create releases](https://git-scm.com/book/en/v2/Git-Basics-Tagging).

With the Autogit feature of Combell, deploying is as simple as [pushing your commits](https://git-scm.com/book/en/v2/Git-Basics-Working-with-Remotes)
to a repository hosted on the [Combell servers](https://www.combell.com/en/hosting/web-hosting).

## Create project

Create a new Symfony project using composer and add Apache support.

    composer create-project symfony/skeleton:"6.4.*@dev" myproject
    cd myproject
    composer require symfony/apache-pack

## Git setup

Initialize a Git repository in the new folder and register the Combell Git remote.

The exact location of the repository is shown in the **control panel**, in this case it is
"domainbe@ssh.domain.be:auto.git".

    git init .
    git add .
    git commit -m "composer create-project symfony/skeleton"
    git remote add combell domainbe@ssh.domain.be:auto.git

## .autogit.yml

Copy over the .autogit.yml file and add it to the repository.

    scp domainbe@ssh.domain.be:autogit.yml.example .autogit.yml
    git add .autogit.yml
    git commit -m "Add the default .autogit.yml template"

## Document root

Symfony uses `public` as the document root, but Combell looks for the `www` folder.
Luckily for us, Git supports symlinks in the repository.

    ln -s public www
    git add www
    git commit -m "Symlink the www folder to the public folder (combell www -> symfony public)"

## Deployment hooks

When we push to the remote, our `.gitignore` file excludes the `vendor/` directory we need to run the application
in production. We would also like the log files to survive new releases.


### Composer

After the shared symlinks are created in the new release folder, we want to run `composer install`

    sharedsymlink_after: |
      APP_ENV=prod composer install --no-dev --optimize-autoloader
      exit 0

And add it to the repository

    git commit -am "Run 'composer install' after the shared symlinks are created"

### Shared files and folders

Environment specific settings, like database credentials are stored in `.env.local`. This file should not be committed,
but altered on the server: `/checkout/main/shared` (main branch).

The `var/log` directory contains log files, `public/uploads` might be used for files uploaded by your web app.

We can register those in `.autogit.yml`:

    shared_files:
      - .env.local

    shared_folders:
      - var/log
      - public/uploads

And add it to the repository

    git commit -am "Add settings file and shared directories"

When we symlink the `var/*` directories, we need to delete it from the git repository (or the symlinks will error out). 
We could remove it from the repository, but we can also remove it from the release folder during deployment:

    install_after: |
        rm -Rf var/ # Get rid of the git var folder
        exit 0

For a first release, we check if there's a config file and log directory and create one if needed. You might need to add
extra config to the `.env.local` file.
Other releases only need a fresh writable cache and sessions directory:

    sharedsymlink_after: |
      test -f ../shared/.env.local || echo "APP_ENV=prod" >> ../shared/.env.local # Create default config if it's not there yet
      mkdir -p -m777 ../shared/var/log # Create shared log folder if it's not there yet
      mkdir -p -m777 var/cache
      mkdir -p -m777 var/sessions
      APP_ENV=prod composer install --no-dev --optimize-autoloader
      exit 0

And add it to the repository

    git commit -am "Create cache and sessions directory, create config file and log directory if needed"

## Deployment

    git push combell main