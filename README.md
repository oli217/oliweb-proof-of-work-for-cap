# oliweb-proof-of-work-for-cap

WordPress plugin to integrate [Cap](https://github.com/tiagozip/cap) — a self-hosted proof-of-work CAPTCHA — into WordPress forms.

No external dependencies: built entirely on native WordPress APIs. The WebAssembly module is bundled locally within the plugin.

---

## Requirements

- PHP 8.2+
- WordPress 6.4+
- A self-hosted Cap instance

---

## Installation

1. Copy the `tilivier-pow-for-cap/` folder into `wp-content/plugins/`
2. Activate the plugin from **Plugins** in the WordPress admin
3. Go to **Settings > PoW for Cap** and enter your endpoint and secret key

The plugin ships with all required assets (JS, CSS, WASM). No build step or additional download required.

---

## Configuration

Go to **Settings > PoW for Cap** in the WordPress admin.

| Field | Description | Default |
|-------|-------------|---------|
| Endpoint URL | Full URL of your Cap instance, including the site key (e.g. `https://cap.example.com/your-site-key/`) | — |
| Secret Key | Secret key generated in the Cap dashboard (never expose client-side) | — |
| Token Field Name | Name of the hidden field injected by the widget | `cap-token` |
| Timeout (seconds) | Request timeout for `/siteverify` calls | `5` |
| Fail Open | If checked, allows requests through on Cap communication errors | unchecked |
| Hide Attribution Link | If checked, hides the "Cap" attribution link at the bottom right of the widget | unchecked |

### Fail-open mode

By default, any communication error with the Cap instance (network failure, timeout, 5xx error) blocks the request. Enabling **Fail Open** reverses this behaviour: infrastructure errors silently pass the request through.

Errors covered by Fail Open:
- Network error or timeout (`WP_Error` returned by the WordPress HTTP API)
- Non-2xx HTTP response (5xx, 4xx returned by the Cap server)
- Response that cannot be decoded as valid JSON

**Never covered by Fail Open:**
- **Missing or empty token** — unconditionally rejected. A missing token is not an infrastructure error; it means no verification was attempted.
- **Explicitly invalid token** (`success: false` in the JSON response) — always rejected.

---

## Usage

### Native integrations

The plugin automatically integrates with the following WordPress forms upon activation:

| Form | Widget insertion | Validation |
|------|-----------------|------------|
| Comments | `comment_form_after_fields` | `preprocess_comment` |
| Login | `login_form` | `wp_authenticate_user` |
| Registration | `register_form` | `registration_errors` |
| WooCommerce checkout | `woocommerce_after_checkout_billing_form` | `woocommerce_checkout_process` |
| Gravity Forms | `gform_submit_button` | `gform_validation` |

WooCommerce and Gravity Forms integrations are only active if the corresponding plugins are installed and activated.

### Shortcode `[tpow_widget]`

Insert the Cap widget into any page, post, or form builder:

```
[tpow_widget]
```

With CSP nonce:

```
[tpow_widget nonce="your-nonce"]
```

The shortcode automatically enqueues the widget JS, CSS, and WASM, as well as `window.TPOW_CONFIG`.

### Programmatic mode — `[tpow_programmatic]`

For cases where you want to trigger Cap verification without displaying a visible widget (SPA, multi-step forms, custom integration):

```
[tpow_programmatic field="cap-token" id="tpow-token"]
```

| Attribute | Description | Default |
|-----------|-------------|---------|
| `field` | Name of the `<input type="hidden">` | `cap-token` |
| `id` | HTML ID of the field | `tpow-token` |

The shortcode enqueues assets and inserts a hidden field. The endpoint and field name are exposed via `window.TPOW_CONFIG`, available as soon as the script loads.

**Example:**

```html
[tpow_programmatic field="cap-token" id="my-cap-token"]

<script type="module">
document.getElementById('submit-btn').addEventListener('click', async (e) => {
    e.preventDefault();

    const cap = new Cap({ apiEndpoint: window.TPOW_CONFIG.apiEndpoint });

    cap.addEventListener('progress', (event) => {
        console.log(`Solving… ${event.detail.progress}%`);
    });

    const { token } = await cap.solve();
    document.getElementById('my-cap-token').value = token;
    e.target.closest('form').submit();
});
</script>
```

`window.TPOW_CONFIG` is injected automatically by `wp_add_inline_script` when assets are enqueued (via `[tpow_widget]`, `[tpow_programmatic]`, or any native integration):

```javascript
window.TPOW_CONFIG = {
    apiEndpoint: "https://cap.example.com/your-site-key/",
    tokenField:  "cap-token"
};
```

---

## CSP

The widget relies on Web Workers and WebAssembly. A strict CSP must include:

```
Content-Security-Policy:
  script-src 'nonce-{nonce}' 'strict-dynamic';
  worker-src blob:;
  wasm-unsafe-eval;
  connect-src 'self';
```

`worker-src blob:` — required because the widget spawns workers via Blob URLs.
`wasm-unsafe-eval` — required for WebAssembly hash computation.
`connect-src 'self'` — sufficient for WASM, bundled locally within the plugin (no requests to any external CDN).

---

## Uninstallation

Uninstalling via the WordPress interface automatically removes all registered database options:

- `tpow_endpoint`
- `tpow_secret`
- `tpow_token_field`
- `tpow_timeout`
- `tpow_fail_open`

---

## License

MIT — see [LICENSE](LICENSE)
