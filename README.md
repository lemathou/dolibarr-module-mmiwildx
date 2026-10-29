# MMIWildx — WildX : connecteur et intégration.

> Module Dolibarr **MMIWildx** (version 1.0), branche `20.0`.

## Fonctionnalités

### Fonctionnalités principales

- Connecteur et intégration WildX.
- Configuration associée.

### Écrans et menus

- **Connecteur Wildx** — `/mmiwildx/mmiwildxindex.php` (menu mmiwildx, droit `1`)

### Pages

- `admin/about.php`
- `admin/setup.php`
- `mmiwildxindex.php`

### Configuration

| Constante | Type | Rôle |
|---|---|---|
| `MMI_WILDX_HOST` | string | Serveur (hostname) |
| `MMI_WILDX_APP_ID` | string | App ID |
| `MMI_WILDX_APP_NAME` | string | App Name |
| `MMI_WILDX_SECRET_KEY` | securekey | Secret Key |

### Classes et composants

- `class/mmi_wildx_sync.class.php`
- `lib/mmiwildx.lib.php`

## Prérequis

- Dolibarr >= 11.0 (branche `20.0` : Dolibarr 20.0).
- Modules MMI : `mmicommon`. **`mmicommon` doit être activé en premier.**

## Installation

1. Copier le module dans `htdocs/custom/mmiwildx` :

   ```sh
   cd htdocs/custom
   git clone <dépôt> mmiwildx
   ```

2. Dans Dolibarr : **Configuration → Modules**, activer d'abord `mmicommon`, puis ce module.
3. Vérifier les permissions dans la fiche des utilisateurs concernés.

## Licence

GPLv3 ou ultérieure — voir `COPYING`. Documentation sous GFDL.

## Changelog

Voir [CHANGELOG.md](CHANGELOG.md) (évolutions datées).
