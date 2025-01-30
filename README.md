# Ecomni Webp module

This module contains a package of composer patches for the Magento admin, to allow uploading and working with webp images.

It can also automatically update jpg/png images, by converting them and updating their references in the database.
Anytime you use a jpg/png again, the cron will simply process it again. No worries or hassles for your content team!

This module is inspired on the [WebP2](https://github.com/yireo/Yireo_Webp2) module from [Yireo](https://www.yireo.nl/).

## Features
- Upload webp images everywhere in the Magento admin.
- Cronjob that automatically converts and updates png/jpg images into webp.

By default, the cron starts every night at 01:00 am, kickstarting every content type at 5 minute intervals.
The cron currently works for:
- Product gallery images
- Category images
- Pagebuilder background images in CMS Blocks & Pages

## How does the automatic conversion work?

### Products

The module grabs a batch of products with images still in non-webp format, using the `catalog_product_entity_media_gallery` table.
The batch size per run is configurable, see the config section.
The converter loops through the gallery entries of each product and generates a .webp images using `cwebp`.
The original image is kept in place.
It creates a new gallery entry and removes the old one if the creation is successful.

From now on, the product has webp images and the database references the webp variant directly, so no conversion or redirects are needed at runtime.
If for any reason the product has a jpg/png image again in the future, the cron will simply process it again automatically.

### Categories

Comparable to the product conversion, only this time we use the `catalog_category_entity_varchar` table to look for image attributes that can be converted.
The category attribute is updated afterwards.

### CMS Pages & Blocks

First we fetch blocks/pages that have 'jpg, jpeg or png' in their content.
The HTML content is loaded into a DOMDocument object and queried for nodes with the `data-background-images` attribute.
If these nodes contain a jpg/png image, the corresponding file is found & converted. The node attribute gets updated with the new image path, and the block/page HTML is saved.
This supports both the `desktop_background` and `mobile_background` attributes.

## Configs

The module offers a few configs, in the `Ecomni/WebP` panel.
- `ecomni_webp/general/enabled` Enables the cron.
- `ecomni_webp/general/max_products_per_run` Cron batch size, default 50.
- `ecomni_webp/general/quality` Conversion quality used by cwebp, default 80. (Range: 1/100)

Note that the patches that allow you to save webp images in the admin, are always active when you have this module installed. The config only controls the cronjobs.

## Requirements

This module is developed for Magento 2.4.7-p1 or higher. Other versions could require small adjustments.
Your installation should have the `cwebp` package installed.

## Installation

- Install using composer `composer require ecomni/m2-webp`
- Enable module: `bin/magento module:enable Ecomni_Webp`