# wordpress-cap

Plugin WordPress pour intégrer [Cap](https://github.com/tiagozip/cap) — un CAPTCHA proof-of-work auto-hébergé — dans les formulaires WordPress.

Aucune dépendance externe : tout repose sur les API natives WordPress. Le module WebAssembly est bundlé localement dans le plugin.

---

## Prérequis

- PHP 8.2+
- WordPress 6.4+
- Une instance Cap auto-hébergée

---

## Installation

1. Copier le dossier `tilivier-pow-for-cap/` dans `wp-content/plugins/`
2. Activer le plugin depuis **Extensions** dans l'administration WordPress
3. Aller dans **Réglages > PoW for Cap** et renseigner l'endpoint et la clé secrète

Le plugin embarque tous les assets nécessaires (JS, CSS, WASM). Aucune étape de build ni de téléchargement supplémentaire n'est requise.

---

## Configuration

Accéder à **Réglages > PoW for Cap** dans l'administration WordPress.

| Champ | Description | Défaut |
|-------|-------------|--------|
| Endpoint URL | URL complète de votre instance Cap, incluant le site-key (ex : `https://cap.example.com/votre-site-key/`) | — |
| Secret Key | Clé secrète générée dans le tableau de bord Cap (ne jamais exposer côté client) | — |
| Token Field Name | Nom du champ hidden injecté par le widget | `cap-token` |
| Timeout (seconds) | Délai avant abandon de la requête vers `/siteverify` | `5` |
| Fail Open | Si coché, laisse passer la requête en cas d'erreur de communication avec Cap | décoché |
| Hide Attribution Link | Si coché, masque le lien « Cap » en bas à droite du widget | décoché |

### Mode fail-open

Par défaut, toute erreur de communication avec l'instance Cap (réseau, timeout, erreur 5xx) bloque la requête. Activer **Fail Open** inverse ce comportement : les erreurs d'infrastructure laissent passer la requête.

Les erreurs couvertes par Fail Open sont exclusivement :
- Erreur réseau ou timeout (`WP_Error` retourné par l'API HTTP WordPress)
- Code HTTP hors 2xx (5xx, 4xx renvoyé par le serveur Cap)
- Réponse non décodable en JSON valide

**Ce qui n'est jamais couvert par Fail Open :**
- **Token absent ou vide** — rejeté inconditionnellement (`false`). L'absence de token n'est pas une erreur d'infrastructure, c'est une absence de tentative de vérification.
- **Token explicitement invalide** (`success: false` dans la réponse JSON) — toujours rejeté.

---

## Utilisation

### Intégrations natives

Le plugin s'intègre automatiquement aux formulaires WordPress suivants dès l'activation :

| Formulaire | Ajout du widget | Validation |
|------------|-----------------|------------|
| Commentaires | `comment_form_after_fields` | `preprocess_comment` |
| Connexion | `login_form` | `wp_authenticate_user` |
| Inscription | `register_form` | `registration_errors` |
| WooCommerce checkout | `woocommerce_after_checkout_billing_form` | `woocommerce_checkout_process` |
| Gravity Forms | `gform_submit_button` | `gform_validation` |

Les intégrations WooCommerce et Gravity Forms ne sont actives que si les plugins correspondants sont installés et activés.

### Shortcode `[tpow_widget]`

Insérer le widget Cap dans n'importe quelle page, article ou constructeur de formulaire :

```
[tpow_widget]
```

Avec nonce CSP :

```
[tpow_widget nonce="votre-nonce"]
```

Le shortcode enqueue automatiquement le JS, le CSS et le WASM du widget, ainsi que `window.TPOW_CONFIG`.

### Mode programmatic — Shortcode `[tpow_programmatic]`

Pour les cas où vous souhaitez déclencher la vérification Cap sans afficher de widget visible (SPA, formulaire multi-étapes, intégration custom), utilisez `[tpow_programmatic]` :

```
[tpow_programmatic field="cap-token" id="tpow-token"]
```

| Attribut | Description | Défaut |
|----------|-------------|--------|
| `field` | Nom du `<input type="hidden">` | `cap-token` |
| `id` | ID HTML du champ | `tpow-token` |

Le shortcode enqueue les assets et insère un champ hidden. L'endpoint et le nom du champ sont exposés dans `window.TPOW_CONFIG`, disponible dès le chargement du script.

**Exemple :**

```html
[tpow_programmatic field="cap-token" id="my-cap-token"]

<script type="module">
document.getElementById('submit-btn').addEventListener('click', async (e) => {
    e.preventDefault();

    const cap = new Cap({ apiEndpoint: window.TPOW_CONFIG.apiEndpoint });

    cap.addEventListener('progress', (event) => {
        console.log(`Résolution… ${event.detail.progress}%`);
    });

    const { token } = await cap.solve();
    document.getElementById('my-cap-token').value = token;
    e.target.closest('form').submit();
});
</script>
```

`window.TPOW_CONFIG` est injecté automatiquement par `wp_add_inline_script` lors de l'enqueue des assets (via `[tpow_widget]`, `[tpow_programmatic]`, ou l'une des intégrations natives) :

```javascript
window.TPOW_CONFIG = {
    apiEndpoint: "https://cap.example.com/votre-site-key/",
    tokenField:  "cap-token"
};
```

---

## CSP

Le widget utilise des Web Workers et WebAssembly. Une CSP stricte doit inclure :

```
Content-Security-Policy:
  script-src 'nonce-{nonce}' 'strict-dynamic';
  worker-src blob:;
  wasm-unsafe-eval;
  connect-src 'self';
```

`worker-src blob:` — requis car le widget crée des workers via des URLs `Blob`.
`wasm-unsafe-eval` — requis pour le calcul WebAssembly.
`connect-src 'self'` — suffisant pour le WASM, bundlé localement dans le plugin (aucune requête vers un CDN externe).

---

## Désinstallation

La désinstallation via l'interface WordPress supprime automatiquement toutes les options enregistrées en base de données :

- `tpow_endpoint`
- `tpow_secret`
- `tpow_token_field`
- `tpow_timeout`
- `tpow_fail_open`

---

## Licence

MIT
