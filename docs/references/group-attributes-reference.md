# Official References — WooCommerce Group Attributes

## Primary Sources

| Source | Location |
|--------|----------|
| **Official plugin documentation (PDF)** | `docs/references/WooCommerce Group Product Attributes Plugin.pdf` (900K — verified readable 2026-11-23 via `pdftotext`) |
| **WooCommerce.com documentation** | https://woocommerce.com/document/group-attributes/ |
| **Admin screenshots** | `docs/references/screenshots/` (11 images) |

> The official "WooCommerce Group Attributes" plugin is the **target experience to match** — the reference for required behavior, **NOT a runtime dependency**.

## Feature Summary (extracted from the PDF, 2026-11-23)

1. **Products → Group Attributes** — add/manage group attributes:
   - Name the attribute group; select existing attribute values to display under it.
   - Create or select the group category for this group.
   - Drag-and-drop attribute values to sort them.
   - List all created group attributes.
2. **Products → Group Attributes Categories** — create group categories; attach them to groups in each group's settings.
3. **Product edit (Product Data → Attributes):**
   - "Create group for this product" toggle.
   - "Manage Attribute Groups" select box + **Load** button — load groups one by one (loads all attributes associated with the group).
   - "Load Group Categories" field — load multiple group attributes at once.
   - After loading, select the attribute values to display on the product page.
   - Drag-and-drop attribute groups to sort them per product.
4. **WooCommerce → Settings → Group Attributes (general settings):**
   - Table style, location, table colors.
   - Enable the "View Attribute" button on the shop page.

## Screenshot Index

| File | Shows |
|------|-------|
| `add-group.webp` | Adding a new group attribute |
| `group-settings.webp` | Group settings (name, values, category) |
| `sort-values.webp` | Drag-and-drop value sorting |
| `manage-groups.webp` | All created group attributes list |
| `categories.webp` | Group Attributes Categories screen |
| `load-categories.webp` | "Load Group Categories" field on product edit |
| `load-group.webp` | Loading a group's attributes onto a product |
| `check-group.webp` | "Create group for this product" toggle |
| `select-values.webp` | Selecting attribute values to display |
| `sort-group.webp` | Drag-and-drop group sorting per product |
| `general-settings-1.webp` | WooCommerce → Settings → Group Attributes |
