# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-09-28

### Added

- Ordered quantities are taken off the warehouses, fullest first, so the warehouse figures no longer
  drift from reality after a sale. PrestaShop's own stock is left to the core, which already reduces
  it, and to the back office save that sets it to the sum across warehouses.

### Fixed

- The block is rendered only once a product is assigned to at least one warehouse, so it never
  contradicts the quantity the shop is selling.

### Changed

- Released under the MIT license, with English and Polish catalogues and the standard documentation.
- The admin controller no longer restores the `l()` method PrestaShop 9 removed; every string goes
  through the translation system.

## [1.0.0] - 2026-09-01

### Added

- Warehouses with a delivery time, stock per product and combination, a back-office screen, the
  split shown on the product page and cleanup when a product is deleted.
