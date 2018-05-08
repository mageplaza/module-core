# Mageplaza Core for Magento 2



[![Latest Stable Version](https://poser.pugx.org/mageplaza/module-core/v/stable)](https://packagist.org/packages/mageplaza/module-core)
[![Total Downloads](https://poser.pugx.org/mageplaza/module-core/downloads)](https://packagist.org/packages/mageplaza/module-core)


## How to install & upgrade Mageplaza_Core


### 1. Install via composer (recommend)

We recommend you to install Mageplaza_Core module via composer. It is easy to install, update and maintaince.

Run the following command in Magento 2 root folder.

#### 1.1 Install

```
composer require mageplaza/module-core
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy
```

#### 1.2 Upgrade

```
composer update mageplaza/module-core
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy
```

Run compile if your store in Product mode:

```
php bin/magento setup:di:compile
```

### 2. Copy and paste

If you don't want to install via composer, you can use this way. 

- Download [the latest version here](https://github.com/mageplaza/module-core/archive/master.zip) 
- Extract `master.zip` file to `app/code/Mageplaza/Core` ; You should create a folder path `app/code/Mageplaza/Core` if not exist.
- Go to Magento root folder and run upgrade command line to install `Mageplaza_Core`:

```
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy
```
