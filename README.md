# Ecomni Webp module

## Features
- Upload webp images everywhere in the Magento admin.
- Cronjob that automatically converts png/jpg product images into webp.

## How does the automatic conversion work?

This currently only works for product gallery images.

The module grabs a batch of products with images still in non-webp format, using the `catalog_product_entity_media_gallery` table.
The batch size per run is configurable, see the config section.
The converter loops through the gallery entries of each product and generates a .webp images using `cwebp`.
The original image is kept in place.
It creates a new gallery entry and removes the old one if the creation is successful.

From now on, the product has webp images and the database references the webp variant directly, so no conversion or redirects are needed at runtime.
If for any reason the product has a jpg/png image again in the future, the cron will simply process it again automatically.

## Configs

The module offers a few configs, in the `Ecomni/WebP` panel.
- `ecomni_webp/general/enabled` Enables the cron.
- `ecomni_webp/general/max_products_per_run` Cron batch size, default 50.
- `ecomni_webp/general/quality` Conversion quality used by cwebp, default 80. (Range: 1/100)

## Requirements

This module is developed for Magento 2.4.7-p1 or higher. Other versions could require small adjustments.
Your installation should have the `cwebp` package installed.

## Installation

- Install using composer `composer require ecomni/m2-webp`
- Enable module: `bin/magento module:enable Ecomni_Webp`