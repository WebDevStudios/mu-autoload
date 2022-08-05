# Composer WordPress Must-Use Autoloader

Installs an mu-plugin to load libraries for all themes & plugins from a common vendor directory (usually in wp-content).

# Installation

Add this repository to your project's composer file by choosing one of these repository installation methods:

## Repository Option 1 - Public Access

Add the following entry to the 'repositories' section of your project's `composer.json`:

```
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/WebDevStudios/mu-autoload"
        }
    ]
```

## Repository Option 2 - WDS Internal (Private)
(if you haven't yet added the WDS Satis package server to your `composer.json`)

`composer config repositories.wds-satis composer https://packages.wdslab.com`

## Composer Config & Require

`composer config scripts.post-autoload-dump "WebDevStudios\MUAutoload\Installer::install"`

`composer require webdevstudios/mu-autoload:^1.0`
