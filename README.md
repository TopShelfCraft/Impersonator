# Impersonator

_a plugin for Craft CMS_

**A [Top Shelf Craft](https://topshelfcraft.com) creation**  
[Michael Rog](https://michaelrog.com), Proprietor  

Who do you want to be?

* * *

### TL;DR.

_Impersonator_ provides convenient controller actions for authorized users to impersonate, and _un_-impersonate, other users.

## Installation

Visit the _Plugin Store_ in your Craft control panel, search for **Impersonator**, and click to _Install_ the plugin.

Alternatively, install via Composer:
```
composer require topshelfcraft/impersonator
```

## Configuration

You can configure the form parameter name and the impersonation session duration, via an `impersonator.php` file in your `config` directory:

```php
use craft\helpers\App;
use TopShelfCraft\Impersonator\Settings;

return Settings::create()
    ->accountParamName('accountToImpersonate')
    ->impersonatorSessionDuration('P1H');
```

## Usage

### Impersonation

From an authorized user session, submit a POST request to the `impersonator/impersonator/impersonate` action with an identifier (Username, Email, or ID) of the account to impersonate:

```twig
<form method="post">
  {{ csrfInput() }}
  {{ actionInput('impersonator/impersonator/impersonate') }}
  {{ redirectInput('my/start/page/path') }}
  <input name="impersonate" placeholder="ID, username, or email">
  <button type="submit">Impersonate!</button>
</form>
```

(You can customize the form input `name` by setting the `accountParamName` config item.)

### Template Tags

When an impersonation session is active, the plugin provides some useful info for you to use in your templates:

- The ID of the user performing the impersonation:

  ```twig
  {{ impersonator.getImpersonatorId() }}
  ```

- The user performing the impersonation:

  ```twig
  {{ impersonator.getImpersonatorIdentity().fullName }} is impersonating {{ currentUser.fullName }}
  ```

### _Un_-impersonation

The plugin keeps track of the session that initiated the impersonation, so you can provide your user a convenient way to end the impersonation and assume their original identity, without needing to log in again (as long as their original session is still valid):

```twig
{% if impersonator.getImpersonatorId() %}
  <form method="post">
    {{ csrfInput() }}
    {{ actionInput('impersonator/impersonator/unimpersonate') }}
    {{ redirectInput('my/return/page/path') }}
    <button type="submit">Stop Impersonating</button>
  </form>
{% endif %}
```

## Support

Version `4.x` is compatible with Craft 4.0+.

If you've found a bug, or have a question, please open a [GitHub Issue](https://github.com/topshelfcraft/Impersonator/issues), and if you're feeling ambitious, submit a PR.

* * *

### Contributors:

  - Plugin development: [Michael Rog](https://michaelrog.com) / @michaelrog
