# Inventory

[![Travis CI](https://img.shields.io/travis/stevebauman/inventory/v1.7.5.svg?style=flat-square)](https://travis-ci.org/stevebauman/inventory)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/stevebauman/inventory.svg?style=flat-square)](https://scrutinizer-ci.com/g/stevebauman/inventory/?branch=master)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/69e0abf0-cd74-4d4d-b40c-e943c4a7eea9.svg?style=flat-square)](https://insight.sensiolabs.com/projects/69e0abf0-cd74-4d4d-b40c-e943c4a7eea9)
[![Latest Stable Version](https://img.shields.io/packagist/v/stevebauman/inventory.svg?style=flat-square)](https://packagist.org/packages/stevebauman/inventory)
[![Total Downloads](https://img.shields.io/packagist/dt/stevebauman/inventory.svg?style=flat-square)](https://packagist.org/packages/stevebauman/inventory)
[![License](https://img.shields.io/packagist/l/stevebauman/inventory.svg?style=flat-square)](https://packagist.org/packages/stevebauman/inventory)

## 🚨 Abandoned 🚨

This package was created years ago to supplement an internal application that was in development. The project was cancelled, and I have no time and use to maintain this repository. If anyone would like to take over and maintain this package, please contact me at steven_bauman@outlook.com, or create an issue.

Thank you!

## Index

[![Join the chat at https://gitter.im/stevebauman/inventory](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/stevebauman/inventory?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

<ul>
    <li>
        <a href="#description">Description</a>
        <ul>
            <li><a href="#requirements">Requirements</a></li>
            <li><a href="#benefits">Benefits</a></li>
        </ul>
    </li>
    <li>
        <a href="docs/INSTALLATION.md">Installation</a>
        <ul>
            <li><a href="docs/INSTALLATION.md#installation-laravel-4">Laravel 4</a></li>
            <li><a href="docs/INSTALLATION.md#installation-laravel-5">Laravel 5</a></li>
            <li>
                <a href="docs/INSTALLATION.md#customize-installation">Customize Installation</a>
                <ul>
                    <li><a href="docs/INSTALLATION.md#i-dont-need-to-customize-my-models">I don't need to customize my models</a></li>
                    <li><a href="docs/INSTALLATION.md#i-want-to-customize-my-models">I need to customize my models</a></li>
                </ul>
            </li>
        </ul>
    </li>
    <li>
        <a href="docs/UPDATES.md">Updates</a>
        <ul>
            <li><a href="docs/UPDATES.md#updating-from-10-to-11">Updating from 1.0.* to 1.1.*</a></li>
            <li><a href="docs/UPDATES.md#updating-from-11-to-12">Updating from 1.1.* to 1.2.*</a></li>
            <li><a href="docs/UPDATES.md#updating-from-12-to-13">Updating from 1.2.* to 1.3.*</a></li>
            <li><a href="docs/UPDATES.md#updating-from-13-to-14">Updating from 1.3.* to 1.4.*</a></li>
            <li><a href="docs/UPDATES.md#updating-from-14-to-15">Updating from 1.4.* to 1.5.*</a></li>
            <li><a href="docs/UPDATES.md#updating-from-15-to-16">Updating from 1.5.* to 1.6.*</a></li>
            <li><a href="docs/UPDATES.md#updating-from-16-to-17">Updating from 1.6.* to 1.7.*</a></li>
            <li><a href="docs/UPDATES.md#upcoming-updates">Upcoming Updates</a></li>
        </ul>
    </li>
    <li>
        <a href="docs/USAGE.md">Usage</a>
        <ul>
            <li><a href="docs/USAGE.md#asking-questions">Asking Questions</a></li>
            <li><a href="docs/USAGE.md#sku-generation">SKU Generation</a></li>
            <li><a href="docs/USAGE.md#suppliers">Suppliers</a></li>
            <li><a href="docs/TRANSACTIONS.md">Transactions</a></li>
            <li><a href="docs/VARIANTS.md">Variants</a></li>
            <li><a href="docs/ASSEMBLIES.md">Assemblies (BoM)</a></li>
            <li><a href="docs/KITS.md">Kits / Bundles (Coming Soon)</a></li>
            <li><a href="docs/SEPARATING-INVENTORY.md">Separating Inventory</a></li>
            <li><a href="docs/EVENTS.md">Events</a></li>
            <li><a href="docs/USAGE.md#exceptions">Exceptions</a></li>
            <li><a href="docs/USAGE.md#auth-integration">Auth Integration</a></li>
            <li><a href="docs/USAGE.md#misc-functions-and-uses">Misc Functions and Uses</a></li>
        </ul>
    </li>
</ul>

## Description

Inventory is a fully tested, PSR compliant Laravel inventory solution. It provides the basics of inventory management using Eloquent such as:

- Inventory Item management
- Inventory Item Variant management
- Inventory Stock management (per location)
- Inventory Stock movement tracking
- Inventory SKU generation
- Inventory Assembly management (Bill of Materials)
- Inventory Supplier management
- Inventory Transaction management

All movements, stocks and inventory items are automatically given the current logged in user's ID. All inventory actions
such as puts/removes/creations are covered by Laravel's built in database transactions. If any exception occurs
during an inventory change, it will be rolled back automatically.

Depending on your needs, you may use the built in traits for customizing and creating your own models, or
you can simply use the built in models.

### Requirements

- Laravel 4.* | 5.*
- Laravel's Auth, Sentry or Sentinel if you need automatic accountability
- A `users` database table

Recommended:

- [Stevebauman/Revision](http://www.github.com/stevebauman/revision) (For tracking Category and Location changes to stocks)

If you need another Auth driver supported, please send a pull request or create an issue.

### Benefits

If you're using the traits from this package to customize your install, that means you have complete flexibility over your own
models, methods (excluding relationship names/type), database tables, property names, and attributes. You can set your
own base model, and if you don't like the way a method is performed just override it.

Sit back and relax, it's nice to have control.
