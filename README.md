# ZendViewJade
Use Jade inside a Zend 1 Framework project. Should not be considered as stable. Some features are missing. Your objects must me serializable in JSON. Please refer to the PHP documentation for more information.

## Installation

You need nodejs + npm + jade installed (`sudo npm install -g jade`).

```bash
cd library
git clone https://github.com/superboum/ZendViewJade
```

## Usage

```php
require_once("ZendViewJade/ZendViewJade.php");
$view = new Zend_View_Jade();
```
