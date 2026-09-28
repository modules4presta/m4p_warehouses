# M4P Warehouses and Stock for PrestaShop 8 & 9

**Tell the customer which warehouse their goods sit in and how fast they ship — instead of one number that says nothing about delivery.**

> **Meta description (150 chars):** Multi-warehouse stock for PrestaShop with a delivery time per warehouse, shown on the product page. Stock per combination. Free MIT module for B2B shops.

---

## Why one stock number is not enough

A wholesale buyer asking "do you have forty of these" is really asking "when can I have them". With
goods spread across several warehouses, a single quantity hides the answer:

- **Delivery time is part of the stock** — 30 today from one warehouse beats 40 next week
- **The customer decides** — seeing the split, they can order what ships now and the rest later
- **Fewer questions to sales** — the information is on the product page, not in an e-mail thread
- **Per combination** — sizes and variants rarely sit in the same place

## What the module does

You add your warehouses in **Catalogue → Warehouses**, each with a delivery time in your own words.
On every product you then set how many pieces each warehouse holds, per combination. The product
page shows that split to the customer, and the sum becomes the quantity PrestaShop sells. When an
order is placed, the quantity comes off the warehouses, fullest first.

### Key features

- **Its own back-office screen** — a list of warehouses with names, delivery times and total stock
- **Stock per combination** — every variant has its own row in every warehouse
- **Delivery time in your words** — "24 h", "2-3 working days", whatever your customers understand
- **Deducted on order** — warehouse figures stay true after a sale
- **Works with reservations** — quantities already held in carts by `m4p_reservations` are subtracted
  from what the customer sees

### How stock is kept in step

Saving warehouse stock in the back office sets the product's available quantity to the sum across
warehouses — the warehouses are the source of truth. When an order is placed, PrestaShop reduces its
own quantity and the module takes the same amount off the warehouses, so the two never drift apart.

## Compatibility

| | |
|---|---|
| PrestaShop | 1.7.6 – 9.x |
| PHP | 7.4+ |
| Requirements | none; integrates with `m4p_reservations` when installed |
| Multistore | Warehouses and stock are shared across shops |
| Themes | The product page block needs `displayProductAdditionalInfo` (all standard themes have it) |

The module performs no core overrides. It creates two tables, `m4p_warehouse` and
`m4p_warehouse_stock`, and drops them on uninstall.

## Installation

1. Upload and install the module from **Modules → Module Manager**.
2. Open **Catalogue → Warehouses** and add your warehouses with their delivery times.
3. Open any product, find the **Warehouse stock** section and enter the quantities.
4. Open the product page in the front office — the split is shown with the product details.

## Configuration options

The module has no configuration screen. Everything is edited where it belongs:

| Where | What |
|---|---|
| **Catalogue → Warehouses** | Name, delivery time and whether the warehouse is in use |
| **Product page, Warehouse stock** | Quantity per warehouse, per combination |

## Frequently asked questions

**Which warehouse is an order taken from?**
The fullest one first, then the next, until the ordered quantity is covered. The module does not
choose by distance or by customer address.

**What happens when I uninstall the module?**
Both tables are dropped, so the split is lost. Products keep the quantity PrestaShop knows about.

**Does the customer see empty warehouses?**
They see the list with quantities, including zeros, so it is clear where the goods are not. A product
that has not been assigned to any warehouse shows no block at all, rather than claiming it is out of
stock everywhere.

**What happens when a product is deleted?**
Its warehouse rows go with it — the module cleans up after itself.

**Can I import quantities from an ERP?**
Not out of the box. The tables are plain and documented above, so an import writing to
`m4p_warehouse_stock` is straightforward; remember to update `stock_available` to the sum.

---

**Keywords:** PrestaShop multi warehouse, warehouse stock, delivery time, B2B inventory, stock per
combination, multiple locations.

## License

MIT — see [LICENSE](LICENSE). Free to use commercially, fork and modify; keep the copyright notice.

## Contributing

Bug reports and pull requests are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). For security
issues, follow [SECURITY.md](SECURITY.md) instead of opening a public issue.

---

Built by [Nice Code](https://nice-code.com/pl/produkty/sklep-b2b-prestashop) — we build B2B stores on PrestaShop.

© Nice Code sp. z o.o. (Modules4Presta) — released under the MIT license.
