# Changelog

## v2.5.2 — Author metadata

- Package author metadata, README credits, and LICENSE copyright now list
  Przemek Peron (`przemek@sonnenglas.net`).

## v2.5.1 — Warenpost national → DHL Kleinpaket (V62KP)

- `ShipmentProduct::Warenpost` now sends `V62KP` instead of the retired `V62WP`. DHL renamed "Warenpost national" to "DHL Kleinpaket" and switched off the automatic V62WP→V62KP conversion after 2026-05-31, so the API began rejecting `V62WP` with "The product entered is unknown."

## v2.5.0 — Separate `addressHouse` field on `Address`

- `Address` now accepts an optional `addressHouse` constructor argument and emits it as a dedicated field in the DHL ContactAddress payload.
- Required by the **Parcel DE Returns API**, which rejects requests where the house number is concatenated into `addressStreet` with HTTP 400 *"Please add your data in the field 'Number'."*
- Backward compatible: when `addressHouse` is omitted (default `''`), the payload is identical to v2.4.x. Callers using positional arguments are unaffected — the new parameter is appended at the end of the constructor signature.
- `addressHouse` is ignored for Packstation/Locker addresses.

## v2.4.0 — DHL Returns API support

- Added `ReturnsService`, `ReturnsClient`, `ReturnShipment`, `ReturnLabelType`, `ReturnLabelResponse`, `ReturnResponseParser` for the dedicated DHL Parcel DE Returns API (`/parcel/de/shipping/returns/v1/orders`).
- Generates return labels (Retoure) without producing an unused regular shipment label.
- `Dhl::getReturnsService()` exposes the new service alongside `getShipmentService()`.
- `Client` constructor now accepts an optional `baseUriOverride` (backward-compatible) and resolves URIs via late static binding so subclasses can override `URI_PRODUCTION` / `URI_SANDBOX`.
