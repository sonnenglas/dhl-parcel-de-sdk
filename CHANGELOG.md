# Changelog

## v2.4.0 — DHL Returns API support

- Added `ReturnsService`, `ReturnsClient`, `ReturnShipment`, `ReturnLabelType`, `ReturnLabelResponse`, `ReturnResponseParser` for the dedicated DHL Parcel DE Returns API (`/parcel/de/shipping/returns/v1/orders`).
- Generates return labels (Retoure) without producing an unused regular shipment label.
- `Dhl::getReturnsService()` exposes the new service alongside `getShipmentService()`.
- `Client` constructor now accepts an optional `baseUriOverride` (backward-compatible) and resolves URIs via late static binding so subclasses can override `URI_PRODUCTION` / `URI_SANDBOX`.
